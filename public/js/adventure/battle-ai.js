/**
 * Battle AI Module
 * Strategic enemy AI that adapts based on player behavior
 */

(function() {
    'use strict';
    
    class BattleAI {
        constructor(battle) {
            this.battle = battle;
            this.turnCount = 0;
            this.lastPlayerAction = null;
            this.playerMagicCount = 0;
            this.playerDefendCount = 0;
        }
        
        /**
         * Decide enemy action based on game state
         */
        decideAction() {
            const state = this.battle.state;
            const player = state.player;
            const enemy = state.enemy;
            
            // Track player behavior
            this.analyzePlayerBehavior();
            
            // Calculate player HP percentage
            const playerHpPercent = (player.currentHp / player.maxHp) * 100;
            
            // Calculate enemy HP percentage
            const enemyHpPercent = (enemy.currentHp / enemy.maxHp) * 100;
            
            // Determine action based on strategy
            
            // 1. Boss AI - More aggressive and varied
            if (enemy.isBoss) {
                return this.bossStrategy(playerHpPercent, enemyHpPercent);
            }
            
            // 2. Low HP enemy - Desperate attacks or surrender
            if (enemyHpPercent < 20) {
                return this.desperateStrategy(playerHpPercent);
            }
            
            // 3. Counter player strategies
            if (this.shouldCounterMagic()) {
                return this.createAction('SPECIAL', {
                    name: 'Silence',
                    damage: 0,
                    effect: 'silence',
                    message: 'The enemy disrupts your magic!'
                });
            }
            
            if (this.shouldCounterDefend() && playerHpPercent < 50) {
                return this.createAction('ATTACK', {
                    damage: Math.floor(enemy.attack * 1.5),
                    pierceDefense: true
                });
            }
            
            // 4. Adapt based on player HP
            if (playerHpPercent < 20) {
                // Player is weak - finish them off
                return this.createAction('ATTACK', {
                    damage: Math.floor(enemy.attack * 1.3)
                });
            }
            
            // 5. Random but weighted actions
            const roll = Math.random();
            const hasMagic = enemy.magic > 0;
            
            if (roll < 0.4) {
                // 40% chance - Basic attack
                return this.createAction('ATTACK', {
                    damage: Math.floor(enemy.attack * (0.8 + Math.random() * 0.4))
                });
            } else if (roll < 0.6 && hasMagic) {
                // 20% chance - Magic attack (only if enemy has magic)
                return this.createAction('MAGIC', {
                    damage: Math.floor(enemy.magic * (0.8 + Math.random() * 0.4))
                });
            } else if (roll < 0.8) {
                // 20% chance - Bullet phase (Undertale-style)
                return this.createAction('BULLET_ATTACK', {});
            } else {
                // 20% chance - Observe/wait
                return this.createAction('WAIT', {});
            }
        }
        
        /**
         * Boss-specific AI strategy
         */
        bossStrategy(playerHpPercent, enemyHpPercent) {
            const state = this.battle.state;
            const enemy = state.enemy;
            
            // Boss phases based on HP
            if (enemyHpPercent < 30) {
                // Phase 3 - Desperate mode
                const roll = Math.random();
                
                if (roll < 0.4) {
                    // Aggressive attacks
                    return this.createAction('SPECIAL', {
                        name: 'Ultimate Attack',
                        damage: Math.floor(enemy.attack * 2),
                        message: `${enemy.name} uses Ultimate Attack!`
                    });
                } else if (roll < 0.7) {
                    return this.createAction('BULLET_ATTACK', {
                        isEnhanced: true
                    });
                } else {
                    return this.createAction('ATTACK', {
                        damage: Math.floor(enemy.attack * 1.5)
                    });
                }
            }
            
            if (enemyHpPercent < 60) {
                // Phase 2 - Mixed attacks
                const roll = Math.random();
                
                if (roll < 0.3) {
                    return this.createAction('SPECIAL', {
                        name: 'Power Strike',
                        damage: Math.floor(enemy.attack * 1.5),
                        message: `${enemy.name} charges up!`
                    });
                } else if (roll < 0.6) {
                    return this.createAction('BULLET_ATTACK', {});
                } else {
                    return this.createAction('ATTACK', {
                        damage: Math.floor(enemy.attack * 1.2)
                    });
                }
            }
            
            // Phase 1 - Learning mode
            // Boss observes player and adapts
            const roll = Math.random();
            const hasMagic = enemy.magic > 0;
            
            if (roll < 0.35) {
                return this.createAction('ATTACK', {
                    damage: Math.floor(enemy.attack)
                });
            } else if (roll < 0.5 && hasMagic) {
                return this.createAction('MAGIC', {
                    damage: Math.floor(enemy.magic)
                });
            } else if (roll < 0.7) {
                return this.createAction('BULLET_ATTACK', {});
            } else {
                return this.createAction('WAIT', {
                    message: `${enemy.name} is studying your movements...`
                });
            }
        }
        
        /**
         * Desperate strategy when enemy is low HP
         */
        desperateStrategy(playerHpPercent) {
            const enemy = this.battle.state.enemy;
            
            // High risk - go all out
            const roll = Math.random();
            
            if (roll < 0.5) {
                return this.createAction('ATTACK', {
                    damage: Math.floor(enemy.attack * 1.5),
                    message: `${enemy.name} makes a desperate attack!`
                });
            } else if (roll < 0.8) {
                return this.createAction('SPECIAL', {
                    name: 'Final Stand',
                    damage: Math.floor(enemy.attack * 2),
                    message: `${enemy.name} uses Final Stand!`
                });
            } else {
                // Try to run or something
                return this.createAction('WAIT', {
                    message: `${enemy.name} looks for an escape...`
                });
            }
        }
        
        /**
         * Analyze player behavior for counter strategies
         */
        analyzePlayerBehavior() {
            const history = this.battle.state.battleHistory;
            
            if (!history || history.length === 0) return;
            
            // Get last action
            const lastEntry = history[history.length - 1];
            this.lastPlayerAction = lastEntry.action;
            
            // Count magic usage
            this.playerMagicCount = history.filter(h => h.action === 'MAGIC').length;
            
            // Count defend usage
            this.playerDefendCount = history.filter(h => h.action === 'DEFEND').length;
            
            // Track consecutive dodge attempts
            this.playerDodgeCount = history.filter(h => h.action === 'DODGE').length;
        }
        
        /**
         * Should counter magic user?
         */
        shouldCounterMagic() {
            // If player uses magic more than 40% of the time
            const history = this.battle.state.battleHistory;
            if (history.length < 3) return false;
            
            return (this.playerMagicCount / history.length) > 0.4;
        }
        
        /**
         * Should counter defend user?
         */
        shouldCounterDefend() {
            const history = this.battle.state.battleHistory;
            if (history.length < 3) return false;
            
            return (this.playerDefendCount / history.length) > 0.5;
        }
        
        /**
         * Create an action object
         */
        createAction(type, data = {}) {
            return {
                type: type,
                damage: data.damage || 0,
                name: data.name,
                message: data.message,
                effect: data.effect,
                pierceDefense: data.pierceDefense || false,
                isEnhanced: data.isEnhanced || false
            };
        }
        
        /**
         * Reset AI state for new battle
         */
        reset() {
            this.turnCount = 0;
            this.lastPlayerAction = null;
            this.playerMagicCount = 0;
            this.playerDefendCount = 0;
            this.playerDodgeCount = 0;
        }
    }
    
    // Export to global
    window.BattleAI = BattleAI;
    
})();
