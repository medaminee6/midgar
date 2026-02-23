/**
 * Midgar Quiz Battle System
 * Main battle controller - Turn-based RPG with bullet-hell dodge phase
 * 
 * Architecture:
 * - battle.js: Main controller & state management
 * - battle-ui.js: UI rendering & animations  
 * - battle-ai.js: Strategic enemy AI
 * - battle-bullets.js: Bullet pattern system
 */

(function() {
    'use strict';
    
    // ============================================
    // BATTLE CONFIGURATION
    // ============================================
    const BATTLE_CONFIG = {
        // Timing windows for attack (milliseconds from perfect)
        // Zone-based system:
        // - PERFECT: 0-20% of bar (1.5x-2x damage)
        // - GOOD: 20-45% of bar (1.25x-1.5x damage)
        // - OK: 45-100% of bar (0.5x-1x damage)
        // These values are for backwards compatibility
        ATTACK_WINDOW: {
            PERFECT: 50,    // 2x damage - tight but fair
            GOOD: 125,      // 1.5x damage - generous
            OK: 250,        // 1x damage - very generous
            MISS: 350       // 0.25x damage
        },
        
        // Bullet phase settings
        BULLET_PHASE: {
            NORMAL_DURATION: 8000,  // 8 seconds of dodging
            BOSS_DURATION: 10000,   // 10 seconds for bosses
            ARENA_SIZE: 300,        // pixels
            PLAYER_SIZE: 24,        // pixels
            BULLET_BASE_SPEED: 200, // pixels per second
            MAX_BULLETS: 50
        },
        
        // Visual settings
        INTERNAL_WIDTH: 480,
        INTERNAL_HEIGHT: 320,
        PIXEL_SCALE: 2
    };
    
    // ============================================
    // BATTLE STATE
    // ============================================
    class BattleState {
        constructor() {
            this.phase = 'INIT'; // INIT, DECISION, ATTACK, BULLET, ENEMY_TURN, VICTORY, DEFEAT
            this.turn = 1;
            this.player = null;
            this.enemy = null;
            this.universe = null;
            this.playerAction = null;
            this.enemyAction = null;
            this.battleHistory = []; // Track player actions for AI
            this.isPaused = false;
            this.playerHealth = 100;
            this.playerMaxHealth = 100;
            this.playerMp = 50;
            this.playerMaxMp = 50;
        }
        
        reset() {
            this.phase = 'INIT';
            this.turn = 1;
            this.playerAction = null;
            this.enemyAction = null;
            this.battleHistory = [];
        }
    }
    
    // ============================================
    // MAIN BATTLE CONTROLLER
    // ============================================
    
    /**
     * Convert overworld enemy format to battle enemy format
     */
    function convertOverworldEnemyToBattle(overworldEnemy, playerData) {
        console.log('Converting overworld enemy:', overworldEnemy);
        const enemyTypes = {
            enemy1: { name: 'Evil Frog', color: '#32CD32', difficulty: 'normal', magicOnly: false },
            enemy2: { name: 'Spooky Ghost', color: '#9370DB', difficulty: 'normal', magicOnly: true },
            enemy3: { name: 'Haunted Carrot', color: '#FF8C00', difficulty: 'normal', magicOnly: false },
            boss: { name: 'Dragon Lord', color: '#DC143C', difficulty: 'boss', magicOnly: false }
        };
        
        const type = enemyTypes[overworldEnemy.sprite] || enemyTypes.enemy1;
        const playerAttack = playerData.stats?.attack || 15;
        const playerMagic = playerData.stats?.magic || 8;
        const playerMaxHp = playerData.stats?.maxHp || 120;

        let enemyLevel = overworldEnemy.level || 1;
        if (overworldEnemy.id) {
            const levelMatch = String(overworldEnemy.id).match(/^L(\d+)_/);
            if (levelMatch) {
                enemyLevel = parseInt(levelMatch[1], 10);
            }
        }
        
        const isBoss = overworldEnemy.isBoss || overworldEnemy.sprite === 'boss';
        const baseStats = isBoss ? {
            attack: Math.max(12, Math.floor(playerAttack * 0.72 + enemyLevel * 3.6)),
            defense: Math.max(4, Math.floor(playerAttack * 0.2 + enemyLevel * 1.8)),
            magic: Math.max(6, Math.floor(playerMagic * 0.52 + enemyLevel * 2)),
            agility: 10 + enemyLevel,
            maxHp: Math.min(1200, Math.max(480, Math.floor(340 + (enemyLevel * 65) + (playerMaxHp * 0.42))))
        } : {
            attack: Math.max(8, Math.floor(playerAttack * 0.4 + enemyLevel * 3.2)),
            defense: Math.max(2, Math.floor(playerAttack * 0.14 + enemyLevel * 1.4)),
            magic: Math.max(4, Math.floor(playerMagic * 0.3 + enemyLevel * 1.6)),
            agility: 9 + enemyLevel * 2,
            maxHp: Math.min(700, Math.max(70, Math.floor(60 + (enemyLevel * 24) + (enemyLevel * enemyLevel * 5) + (playerMaxHp * 0.12))))
        };

        const behaviorBySprite = {
            enemy1: 'aggressive',
            enemy2: 'tricky',
            enemy3: 'defensive',
            boss: 'boss'
        };
        
        return {
            name: type.name,
            type: 'monster',
            difficulty: type.difficulty,
            color: type.color,
            stats: baseStats,
            behavior: behaviorBySprite[overworldEnemy.sprite] || (isBoss ? 'boss' : 'normal'),
            loot: isBoss ? { xp: 100, gold: 50 } : { xp: 25, gold: 10 },
            id: overworldEnemy.id,
            sprite: overworldEnemy.sprite,
            level: enemyLevel,
            magicOnly: type.magicOnly || false // Ghosts can only be hit with magic
        };
    }
    
    class BattleController {
        constructor() {
            this.state = new BattleState();
            this.ui = null;
            this.ai = null;
            this.bullets = null;
            this.animationFrame = null;
            this.lastTime = 0;
        }

        getAvailableMagicSpells() {
            const magicStat = Math.max(0, Number(this.state?.player?.magic) || 0);
            const spells = [
                { id: 'fireball', name: 'Fire Ball', type: 'damage', power: 20, mpCost: 10, unlock: 0 }
            ];

            if (magicStat >= 20) {
                spells.push({ id: 'heal', name: 'Healing Light', type: 'heal', power: 25, mpCost: 12, unlock: 20 });
            }
            if (magicStat >= 50) {
                spells.push({ id: 'thunder', name: 'Thunder', type: 'damage', power: 50, mpCost: 16, unlock: 50 });
            }
            if (magicStat >= 100) {
                spells.push({ id: 'cross-slash', name: 'Cross Slash', type: 'damage', power: 75, mpCost: 20, unlock: 100 });
            }

            return spells;
        }

        getQualityMultiplier(quality, precision) {
            if (quality === 'PERFECT') return 1.35 + (precision * 0.35);
            if (quality === 'GOOD') return 1.1 + (precision * 0.3);
            if (quality === 'OK') return 0.9 + (precision * 0.15);
            return 0.25;
        }

        getStatRewardAmount() {
            const enemyLevel = Math.max(1, Number(this.state?.enemy?.level) || 1);
            const bossBonus = this.state?.enemy?.isBoss ? 20 : 0;
            const amount = 10 + (enemyLevel * 6) + bossBonus;
            return Math.min(35, amount);
        }

        getRewardPreview() {
            return {
                restorePercent: 25,
                statGain: this.getStatRewardAmount()
            };
        }

        clampStat(value) {
            return Math.max(0, Math.min(999, value));
        }

        getMaxHpFromDefense(defense) {
            const safeDefense = Math.max(0, Number(defense) || 0);
            return Math.min(999, 120 + (safeDefense * 8));
        }

        getMaxMpFromMagic(magic) {
            const safeMagic = Math.max(0, Number(magic) || 0);
            return Math.min(999, 80 + (safeMagic * 8));
        }
        
        /**
         * Initialize battle with character and universe data
         */
        async init(personnageId, universeId, initialHealth = 100, initialMp = 50, overworldEnemyData = null) {
            let playerData, universeData, enemyData;
            this.personnageId = personnageId;
            
            try {
                // Fetch character data
                const playerResponse = await fetch(`/api/game/personnage/${personnageId}`);
                if (playerResponse.ok) {
                    playerData = await playerResponse.json();
                }
            } catch(e) { console.warn('Character API failed:', e); }
            
            try {
                // Fetch universe data
                const universeResponse = await fetch(`/api/game/universe/${universeId}`);
                if (universeResponse.ok) {
                    universeData = await universeResponse.json();
                }
            } catch(e) { console.warn('Universe API failed:', e); }
            
            // Use overworld enemy data if provided, otherwise fetch or use fallback
            console.log('Overworld enemy data:', overworldEnemyData);
            if (overworldEnemyData) {
                // Convert overworld enemy format to battle format
                console.log('Calling convertOverworldEnemyToBattle with:', overworldEnemyData);
                enemyData = convertOverworldEnemyToBattle(overworldEnemyData, playerData);
                console.log('After conversion, enemyData:', enemyData);
            } else {
                try {
                    // Fetch random enemy
                    const enemyResponse = await fetch(`/api/game/universe/${universeId}/enemy-for-battle`);
                    if (enemyResponse.ok) {
                        enemyData = await enemyResponse.json();
                    }
                } catch(e) { console.warn('Enemy API failed:', e); }
            }
            
            // Use fallback data if API fails
            if (!playerData) {
                playerData = {
                    name: 'Hero',
                    classRole: 'Warrior',
                    stats: { attack: 15, defense: 10, magic: 5, agility: 12, maxHp: 100 }
                };
            }
            
            if (!universeData) {
                universeData = {
                    name: 'Fantasy World',
                    genre: 'fantasy',
                    bannerImage: null
                };
            }
            
            // Create scaled fallback enemy based on player stats
            // Normal enemies should be beatable, bosses should be hard
            const playerAttack = playerData.stats?.attack || 15;
            const playerMaxHp = playerData.stats?.maxHp || 120;
            
            if (!enemyData) {
                enemyData = {
                    name: 'Dark Slime',
                    type: 'monster',
                    difficulty: 'normal',
                    color: '#8844aa',
                    // Normal enemies: roughly equal to player
                    stats: { 
                        attack: Math.max(5, Math.floor(playerAttack * 0.6)),  
                        defense: Math.max(2, Math.floor(playerAttack * 0.2)),
                        magic: 2, 
                        agility: 8, 
                        maxHp: Math.max(30, Math.floor(playerMaxHp * 0.5))  // 50% of player HP
                    },
                    behavior: 'normal',
                    loot: { xp: 25, gold: 10 }
                };
            }
            
            // Scale enemy based on difficulty tier if provided
            if (enemyData.difficulty) {
                const diff = enemyData.difficulty;
                const scale = diff === 'boss' ? 1.35 : diff === 'strong' ? 1.15 : diff === 'normal' ? 1.0 : 0.85;
                enemyData.stats.attack = Math.floor(enemyData.stats.attack * scale);
                enemyData.stats.defense = Math.floor(enemyData.stats.defense * scale);
                if (diff === 'boss') {
                    enemyData.stats.maxHp = 1000;
                } else {
                    enemyData.stats.maxHp = Math.floor(enemyData.stats.maxHp * scale);
                }
            }
            
            console.log('Using data:', { playerData, universeData, enemyData });
            
            // Clamp player stats to 100 max
            if (playerData.stats) {
                playerData.stats.attack = this.clampStat(playerData.stats.attack || 0);
                playerData.stats.defense = this.clampStat(playerData.stats.defense || 0);
                playerData.stats.magic = this.clampStat(playerData.stats.magic || 0);
                playerData.stats.agility = this.clampStat(playerData.stats.agility || 0);
            }

            const derivedMaxHp = this.getMaxHpFromDefense(playerData.stats?.defense || 0);
            const derivedMaxMp = this.getMaxMpFromMagic(playerData.stats?.magic || 0);

            // Use the health and MP passed from overworld, or fall back to derived maxima
            const finalInitialHp = (initialHealth !== undefined && initialHealth !== null)
                ? initialHealth
                : derivedMaxHp;
            const finalInitialMp = (initialMp !== undefined && initialMp !== null)
                ? initialMp
                : derivedMaxMp;
            
            // Initialize state
            this.state.player = {
                ...playerData.stats,
                currentHp: Math.min(derivedMaxHp, finalInitialHp),
                maxHp: derivedMaxHp,
                currentMp: Math.min(derivedMaxMp, finalInitialMp),
                maxMp: derivedMaxMp,
                name: playerData.name,
                classRole: playerData.classRole,
                portrait: playerData.portrait
            };
            
            this.state.universe = universeData;
            this.state.enemy = {
                ...enemyData.stats,
                id: enemyData.id,
                sprite: enemyData.sprite,
                currentHp: enemyData.stats.maxHp,
                maxHp: enemyData.stats.maxHp,
                name: enemyData.name,
                type: enemyData.type,
                color: enemyData.color,
                portrait: enemyData.portrait,
                behavior: enemyData.behavior,
                loot: enemyData.loot,
                isBoss: enemyData.difficulty === 'boss',
                phase: enemyData.difficulty === 'boss' ? 1 : null,
                level: enemyData.level || 1, // Pass level for background
                magicOnly: enemyData.magicOnly || false // Ghost enemies can only be hit with magic
            };
            
            // Set initial health and MP from overworld state
            this.state.playerHealth = this.state.player.currentHp;
            this.state.playerMaxHealth = derivedMaxHp;
            this.state.playerMp = this.state.player.currentMp;
            this.state.playerMaxMp = derivedMaxMp;
            
            // Initialize modules
            this.ui = new BattleUI(this);
            this.ai = new BattleAI(this);
            this.bullets = new BulletSystem(this);
            
            // Initialize UI
            this.ui.init(this.state.player, this.state.enemy, this.state.universe);
            
            // Start battle
            this.startDecisionPhase();
            
            return true;
        }
        
        /**
         * Start the decision phase - player chooses action
         */
        startDecisionPhase() {
            this.state.phase = 'DECISION';
            this.ui.showDecisionPhase();
            this.ui.updateStats();
            
            // Log player's turn
            const turnNum = this.state.turn || 1;
            this.ui.addLogEntry(`=== Turn ${turnNum} - Your turn! ===`, 'turn');
        }
        
        /**
         * Player selects an action
         */
        async selectAction(action) {
            if (this.state.phase !== 'DECISION') return;
            
            this.state.playerAction = action;
            this.state.battleHistory.push({ turn: this.state.turn, action: action });
            
            switch(action) {
                case 'ATTACK':
                    this.state.phase = 'ATTACK';
                    this.ui.showAttackPhase();
                    break;
                case 'MAGIC':
                    const availableSpells = this.getAvailableMagicSpells();
                    const selectedSpell = await this.ui.showMagicMenu(availableSpells, this.state.player.currentMp);

                    if (!selectedSpell) {
                        this.state.playerAction = null;
                        this.ui.showDecisionPhase();
                        return;
                    }

                    if (this.state.player.currentMp < selectedSpell.mpCost) {
                        this.ui.showMessage('Not enough MP!');
                        this.state.playerAction = null;
                        this.ui.showDecisionPhase();
                        return;
                    }

                    this.state.player.currentMp -= selectedSpell.mpCost;
                    this.state.selectedSpell = selectedSpell;
                    this.ui.updateStats();

                    if (selectedSpell.type === 'heal') {
                        const healAmount = selectedSpell.power;
                        this.state.player.currentHp = Math.min(this.state.player.maxHp, this.state.player.currentHp + healAmount);
                        this.ui.playSound('heal');
                        this.ui.addLogEntry(`${selectedSpell.name} restored ${healAmount} HP!`, 'heal');
                        this.ui.showMessage(`${selectedSpell.name}! +${healAmount} HP`);
                        this.ui.updateStats();
                        this.state.selectedSpell = null;
                        this.delay(900).then(() => this.executeEnemyTurn());
                        return;
                    }

                    this.state.phase = 'ATTACK';
                    this.ui.showMessage(`${selectedSpell.name} ready! Press SPACE!`);
                    this.ui.showAttackPhase();
                    break;
                case 'ITEM':
                    this.ui.showItemMenu();
                    break;
                case 'FLEE':
                    this.handleFlee();
                    break;
            }
        }
        
        /**
         * Handle flee attempt
         */
        handleFlee() {
            this.state.phase = 'FLED';
            this.state.playerHealth = this.state.player.currentHp;
            this.state.playerMp = this.state.player.currentMp;

            this.ui.cleanupBulletControls();
            this.ui.showMessage('You fled the battle!');
            this.ui.addLogEntry('You fled successfully.', 'system');

            const fleeResult = {
                type: 'battle-complete',
                victory: false,
                fled: true,
                xp: 0,
                coins: 0,
                health: this.state.playerHealth,
                mp: this.state.playerMp,
                enemyId: this.state.enemy?.id,
                enemyName: this.state.enemy?.name,
                enemyDefeated: false
            };

            if (window.parent && window.parent !== window) {
                window.parent.postMessage(fleeResult, '*');
            } else {
                if (typeof $ !== 'undefined') {
                    $(document).trigger('battleComplete', [fleeResult]);
                } else {
                    window.dispatchEvent(new CustomEvent('battleComplete', { detail: fleeResult }));
                }
            }

        }
        
        /**
         * Use an item
         */
        useItem(itemId) {
            if (itemId === 'mana') {
                // Mana potion - restore 20 MP
                const mpRestore = 20;
                this.state.player.currentMp = Math.min(this.state.player.maxMp, this.state.player.currentMp + mpRestore);
                this.ui.showMessage(`Used Mana Potion! +${mpRestore} MP`);
                this.ui.addLogEntry(`Used Mana Potion! Restored +${mpRestore} MP`, 'heal');
                this.ui.playSound('heal');
                this.ui.updateStats();
                
                // After using item, enemy attacks
                this.delay(1500).then(() => this.executeEnemyTurn());
            } else {
                // Health potion - heal 30 HP (default)
                const healAmount = 30;
                this.state.player.currentHp = Math.min(this.state.player.maxHp, this.state.player.currentHp + healAmount);
                this.ui.showMessage(`Used Potion! +${healAmount} HP`);
                this.ui.addLogEntry(`Used Potion! Healed +${healAmount} HP`, 'heal');
                this.ui.playSound('heal');
                this.ui.updateStats();
                
                // After using item, enemy attacks
                this.delay(1500).then(() => this.executeEnemyTurn());
            }
        }
        
        /**
         * Handle attack timing hit
         */
        handleAttackHit(timing) {
            if (this.state.phase !== 'ATTACK') return;
            
            const playerAtk = this.state.player.attack || 10;
            const playerMagic = this.state.player.magic || 15;
            const enemyDef = this.state.enemy.defense || 5;
            
            // Check if enemy can only be hit with magic
            const isMagicOnly = this.state.enemy.magicOnly === true;
            const isPhysicalAttack = this.state.playerAction === 'ATTACK';
            
            console.log('[BATTLE] Attack check - Enemy:', this.state.enemy.name, 'Sprite:', this.state.enemy.sprite, 'magicOnly:', isMagicOnly, 'Action:', this.state.playerAction);
            
            // Show slice animation and play attack sound
            this.ui.showSliceAnimation();
            this.ui.playSound('attack');
            
            // If enemy requires magic but player used physical attack, show immune message
            if (isMagicOnly && isPhysicalAttack) {
                this.ui.showMissOverlay();
                this.ui.addLogEntry('Your attack passes through the ghost! Use MAGIC!', 'damage');
                
                // Skip to enemy turn
                this.delay(1500).then(() => this.executeEnemyTurn());
                return;
            }
            
            // Get attack quality from UI (zone-based)
            let quality = this.ui.getAttackQuality();
            console.log('[BATTLE] Attack quality:', quality, 'Timing (ms):', timing, 'Action:', this.state.playerAction);
            
            let damage;
            let baseDamage;
            
            const activeSpell = this.state.playerAction === 'MAGIC' ? this.state.selectedSpell : null;

            // Use selected spell power when casting magic, otherwise use attack stat
            if (this.state.playerAction === 'MAGIC') {
                baseDamage = activeSpell?.power || Math.max(20, playerMagic);
            } else {
                baseDamage = playerAtk;
            }
            
            const attackWindows = BATTLE_CONFIG.ATTACK_WINDOW;
            const qualityWindow = quality === 'PERFECT'
                ? attackWindows.PERFECT
                : quality === 'GOOD'
                    ? attackWindows.GOOD
                    : quality === 'OK'
                        ? attackWindows.OK
                        : attackWindows.MISS;
            const safeTiming = Math.max(0, Number(timing) || 0);
            const precision = Math.max(0, Math.min(1, 1 - (safeTiming / Math.max(1, qualityWindow))));

            const qualityMultiplier = this.getQualityMultiplier(quality, precision);

            if (quality === 'PERFECT') {
                this.ui.showCriticalHit();
            }

            damage = Math.floor(baseDamage * qualityMultiplier);
            
            // Apply enemy defense (reduces damage but not to zero)
            const defenseReduction = this.state.playerAction === 'MAGIC'
                ? Math.floor(enemyDef * 0.2)
                : Math.floor(enemyDef * 0.35);
            const variance = 0.95 + Math.random() * 0.1;
            let finalDamage = Math.max(1, Math.floor((damage - defenseReduction) * variance));

            const playerStats = this.state.player || {};
            const playtestOneShotMode = (playerStats.attack || 0) >= 100
                && (playerStats.defense || 0) >= 100
                && (playerStats.magic || 0) >= 100
                && (playerStats.agility || 0) >= 100;

            if (playtestOneShotMode) {
                finalDamage = this.state.enemy.currentHp;
            }

            // Boss guardrail: OK hits should never spike too high
            if (!playtestOneShotMode && this.state.enemy.isBoss && quality === 'OK') {
                finalDamage = Math.min(finalDamage, 50);
            }
            
            // Apply damage to enemy
            this.state.enemy.currentHp = Math.max(0, this.state.enemy.currentHp - finalDamage);
            
            const attackLabel = activeSpell?.name || (this.state.playerAction === 'MAGIC' ? 'Magic' : 'Attack');
            this.ui.addLogEntry(`${attackLabel} dealt ${finalDamage} damage! (${quality})`, 'turn');
            
            // Show damage numbers
            this.ui.showDamageNumber(finalDamage, quality);
            
            // Show attack multiplier on attack bar
            this.ui.showAttackMultiplier(quality);
            
            this.ui.updateStats();
            
            // Check for victory
            if (this.state.enemy.currentHp <= 0) {
                this.handleVictory();
                return;
            }

            // Boss phase transitions by HP thresholds
            if (this.state.enemy.isBoss) {
                const newPhase = this.getBossPhaseByHp(this.state.enemy.currentHp);
                if (newPhase !== this.state.enemy.phase) {
                    this.state.enemy.phase = newPhase;
                    this.ui.addLogEntry(`Boss enters PHASE ${newPhase}!`, 'damage');
                    this.ui.showMessage(`⚠️ PHASE ${newPhase}!`);
                }
            }
            
            // Transition to enemy turn
            this.state.selectedSpell = null;
            this.delay(1500).then(() => this.executeEnemyTurn());
        }

        getBossPhaseByHp(currentHp) {
            if (currentHp <= 400) return 3;
            if (currentHp <= 650) return 2;
            return 1;
        }
        
        /**
         * Execute player defend action
         */
        executePlayerDefend() {
            // Defend grants 50% damage reduction and restores some MP
            this.state.player.currentMp = Math.min(this.state.player.maxMp, this.state.player.currentMp + 5);
            this.ui.updateStats();
            
            // Check for victory (unlikely on defend but check anyway)
            if (this.state.enemy.currentHp <= 0) {
                this.handleVictory();
                return;
            }
            
            this.executeEnemyTurn();
        }
        
        /**
         * Execute enemy turn - simplified to always use pellet attack
         */
        async executeEnemyTurn() {
            this.state.phase = 'ENEMY_TURN';
            
            // Log enemy turn
            this.ui.addLogEntry(`${this.state.enemy.name}'s turn!`, 'damage');
            
            // Enemy always uses pellet attack - simplified
            const enemyAction = { type: 'ATTACK', name: 'Pellet Barrage', damage: 15 };
            this.state.enemyAction = enemyAction;
            
            // Log what enemy is doing
            this.ui.addLogEntry(`${this.state.enemy.name} launches a pellet barrage!`, 'turn');
            
            // Show enemy attack animation
            await this.ui.showEnemyAttack(enemyAction.damage);
            this.ui.showMessage('Dodge the pellets!');
            
            // Skip directly to dodge phase - no damage taken yet!
            // Player must dodge to avoid damage
            this.ui.updateStats();
            
            // Trigger dodge phase
            this.ui.showMessage('Dodge!', 500);
            await this.delay(500);
            this.startBulletPhase();
            return; // Bullet phase will handle returning to decision phase
        }
        
        /**
         * Start bullet phase (Undertale-inspired dodge)
         */
        startBulletPhase() {
            this.state.phase = 'BULLET';
            const duration = this.state.enemy.isBoss ? 
                BATTLE_CONFIG.BULLET_PHASE.BOSS_DURATION : 
                BATTLE_CONFIG.BULLET_PHASE.NORMAL_DURATION;
            
            this.ui.showBulletPhase(duration);
            this.bullets.start(duration);
        }
        
        /**
         * Handle bullet phase end - after dodging, return to player turn
         */
        handleBulletPhaseEnd(damageTaken) {
            // Clean up bullet controls
            this.ui.cleanupBulletControls();
            
            // HP has already been updated in real-time during bullet phase
            // Just log the total damage taken
            
            // Log damage taken
            if (damageTaken > 0) {
                this.ui.addLogEntry(`Took ${damageTaken} damage!`, 'damage');
            } else {
                this.ui.addLogEntry('Perfect dodge! No damage taken!', 'heal');
            }
            
            this.ui.updateStats();
            
            // Check for defeat (already handled in bullet phase, but check again)
            if (this.state.player.currentHp <= 0) {
                this.handleDefeat();
                return;
            }
            
            // Show result of dodge attempt - damage number shows in game area
            // Don't show text message, damage number is enough
            
            // Return to player turn
            this.state.turn++;
            this.state.enemyAction = null;
            this.delay(500).then(() => this.startDecisionPhase());
        }
        
        /**
         * Handle battle victory
         */
        async handleVictory() {
            this.state.phase = 'VICTORY';
            
            // Clean up bullet controls
            this.ui.cleanupBulletControls();
            
            // HP stays in memory during continuous play
            const loot = this.state.enemy.loot;
            const rewardPreview = this.getRewardPreview();
            
            // Show victory screen and let the player pick a stat upgrade
            const choice = await this.ui.showVictory(loot, rewardPreview);
            if (choice) {
                await this.applyVictoryChoice(choice);
            }

            // Sync overworld carry-over values
            this.state.playerHealth = this.state.player.currentHp;
            this.state.playerMaxHealth = this.state.player.maxHp;
            this.state.playerMp = this.state.player.currentMp;
            this.state.playerMaxMp = this.state.player.maxMp;
            
            // Save to database
            try {
                fetch('/overworld/battle-result', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        personnageId: this.personnageId,
                        enemyDefeated: true
                    })
                }).catch(() => {});
            } catch (e) {
                console.warn('Failed to save battle result');
            }
            
            // Notify parent window (for overworld integration)
            if (window.parent && window.parent !== window) {
                // Inside iframe - use postMessage
                window.parent.postMessage({
                    type: 'battle-complete',
                    victory: true,
                    xp: 0,
                    coins: 0,
                    health: this.state.playerHealth,
                    mp: this.state.playerMp,
                    statUpgrade: choice || null,
                    enemyId: this.state.enemy.id,
                    enemyName: this.state.enemy.name,
                    enemyDefeated: true
                }, '*');
            } else {
                // Same window (overworld integration) - use jQuery event
                if (typeof $ !== 'undefined') {
                    $(document).trigger('battleComplete', [{
                        victory: true,
                        xp: 0,
                        coins: 0,
                        health: this.state.playerHealth,
                        mp: this.state.playerMp,
                        statUpgrade: choice || null,
                        enemyId: this.state.enemy.id,
                        enemyName: this.state.enemy.name,
                        enemyDefeated: true
                    }]);
                } else {
                    // Dispatch native custom event
                    window.dispatchEvent(new CustomEvent('battleComplete', {
                        detail: {
                            victory: true,
                            xp: 0,
                            coins: 0,
                            health: this.state.playerHealth,
                            mp: this.state.playerMp,
                            statUpgrade: choice || null,
                            enemyId: this.state.enemy.id,
                            enemyName: this.state.enemy.name,
                            enemyDefeated: true
                        }
                    }));
                }
            }

        }

        async applyVictoryChoice(choice) {
            const amount = this.getStatRewardAmount();
            const player = this.state.player;

            if (choice === 'restore-hp') {
                const hpRestore = Math.max(1, Math.floor(player.maxHp * 0.25));
                player.currentHp = Math.min(player.maxHp, player.currentHp + hpRestore);
            } else if (choice === 'restore-mp') {
                const mpRestore = Math.max(1, Math.floor(player.maxMp * 0.25));
                player.currentMp = Math.min(player.maxMp, player.currentMp + mpRestore);
            } else {
                const statKey = choice;
                switch (statKey) {
                    case 'attack':
                        player.attack = this.clampStat((player.attack || 0) + amount);
                        player.currentHp = Math.min(player.currentHp, player.maxHp);
                        break;
                    case 'defense':
                        player.defense = this.clampStat((player.defense || 0) + amount);
                        player.maxHp = this.getMaxHpFromDefense(player.defense);
                        player.currentHp = Math.min(player.currentHp, player.maxHp);
                        break;
                    case 'magic':
                        player.magic = this.clampStat((player.magic || 0) + amount);
                        player.maxMp = this.getMaxMpFromMagic(player.magic);
                        player.currentMp = Math.min(player.currentMp, player.maxMp);
                        break;
                    case 'agility':
                        player.agility = this.clampStat((player.agility || 0) + amount);
                        break;
                    default:
                        return;
                }

                try {
                    fetch(`/api/game/personnage/${this.personnageId}/stat-upgrade`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            stat: statKey,
                            amount: amount
                        })
                    }).catch(() => {});
                } catch (e) {
                    console.warn('Failed to save stat upgrade');
                }
            }

            if (this.ui && this.ui.updatePlayerStats) {
                this.ui.updatePlayerStats(player);
            }
            this.ui.updateStats();
        }
        
        /**
         * Handle battle defeat
         */
        async handleDefeat() {
            this.state.phase = 'DEFEAT';
            // HP stays in memory for retry

            this.state.playerHealth = this.state.player.currentHp;
            this.state.playerMp = this.state.player.currentMp;
            
            // Clean up bullet controls
            this.ui.cleanupBulletControls();
            
            const choice = await this.ui.showDefeat();
            const retryHp = Math.max(1, Math.floor(this.state.player.maxHp * 0.5));
            const retryMp = Math.max(0, Math.floor(this.state.player.maxMp * 0.5));

            const resultPayload = {
                type: 'battle-complete',
                victory: false,
                xp: 0,
                coins: 0,
                health: choice === 'retry-half' ? retryHp : this.state.playerHealth,
                mp: choice === 'retry-half' ? retryMp : this.state.playerMp,
                enemyId: this.state.enemy?.id,
                enemyName: this.state.enemy?.name,
                enemyDefeated: false,
                retry: choice === 'retry-half',
                giveUp: choice === 'give-up',
                badEnding: choice === 'give-up'
            };

            if (window.parent && window.parent !== window) {
                window.parent.postMessage(resultPayload, '*');
            } else {
                if (typeof $ !== 'undefined') {
                    $(document).trigger('battleComplete', [resultPayload]);
                } else {
                    window.dispatchEvent(new CustomEvent('battleComplete', { detail: resultPayload }));
                }
            }

        }
        
        /**
         * Utility: delay
         */
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
        
        /**
         * Cleanup
         */
        destroy() {
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
            }
            if (this.bullets) {
                this.bullets.stop();
            }
            if (this.ui && typeof this.ui.destroy === 'function') {
                this.ui.destroy();
            }
            this.ui = null;
            this.bullets = null;
        }
    }
    
    // ============================================
    // EXPORT TO GLOBAL
    // ============================================
    window.BattleController = BattleController;
    window.BATTLE_CONFIG = BATTLE_CONFIG;
    
})();
