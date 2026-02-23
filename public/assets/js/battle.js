/**
 * Turn-Based Battle System
 * Handles all battle logic and turn management
 */

class Battle {
  constructor(player, enemy) {
    this.player = {
      ...player,
      currentHp: player.stats.maxHp || 100,
    };

    this.enemy = {
      ...enemy,
      currentHp: enemy.stats.maxHp || 50,
    };

    this.turn = 0;
    this.currentActor = this.getFirstActor();
    this.log = [];
    this.isOver = false;
    this.winner = null;
  }

  /**
   * Determine who goes first based on agility
   */
  getFirstActor() {
    const playerAgility = this.player.stats.agility || 8;
    const enemyAgility = this.enemy.stats.agility || 8;
    return playerAgility >= enemyAgility ? 'player' : 'enemy';
  }

  /**
   * Execute player action
   */
  executePlayerAction(actionType) {
    if (this.currentActor !== 'player') return null;

    let damage = 0;
    let message = '';

    switch (actionType) {
      case 'attack':
        damage = this.calculateDamage(this.player, this.enemy);
        message = `${this.player.name} attacks and deals ${damage} damage!`;
        this.enemy.currentHp -= damage;
        break;

      case 'skill':
        damage = Math.round((this.player.stats.magic || 0) * 1.5);
        damage = Math.max(0, damage - Math.round((this.enemy.stats.defense || 0) * 0.3));
        message = `${this.player.name} casts a skill and deals ${damage} damage!`;
        this.enemy.currentHp -= damage;
        break;

      case 'defend':
        message = `${this.player.name} takes a defensive stance!`;
        this.player.defendMultiplier = 0.6; // Reduce next damage by 40%
        break;

      case 'dodge':
        message = `${this.player.name} attempts to dodge!`;
        this.player.dodging = true;
        break;

      default:
        message = `${this.player.name} does nothing.`;
    }

    this.addLog(message);
    this.currentActor = 'enemy'; // Pass turn to enemy

    // Check if enemy is defeated
    if (this.enemy.currentHp <= 0) {
      this.isOver = true;
      this.winner = 'player';
      this.addLog(`🎉 Victory! ${this.enemy.name} has been defeated!`);
    }

    return { actionType, damage, message };
  }

  /**
   * Execute enemy action (AI-based)
   */
  executeEnemyAction() {
    if (this.currentActor !== 'enemy') return null;

    const action = EnemyAI.decideBestAction(
      this.enemy,
      this.player,
      this.enemy.currentHp,
      this.enemy.stats.maxHp
    );

    let damage = 0;
    let message = EnemyAI.getActionFlavor(this.enemy, action.type);

    switch (action.type) {
      case 'attack':
        damage = this.calculateDamage(this.enemy, this.player);
        
        // Apply player dodge effect
        if (this.player.dodging && Math.random() < 0.4) {
          damage = 0;
          message += ` ${this.player.name} successfully dodges!`;
          this.addLog(message);
          this.player.dodging = false;
          this.currentActor = 'player';
          return { actionType: 'dodge_success', damage: 0 };
        }

        // Apply player defense multiplier
        if (this.player.defendMultiplier) {
          damage = Math.round(damage * this.player.defendMultiplier);
          message += ` ${this.player.name} blocks some damage!`;
          this.player.defendMultiplier = null;
        }

        this.player.currentHp -= damage;
        message += ` Deals ${damage} damage!`;
        break;

      case 'skill':
        damage = EnemyAI.calculateSkillDamage(this.enemy, this.player);
        
        if (this.player.dodging && Math.random() < 0.6) {
          damage = 0;
          message += ` ${this.player.name} evades the spell!`;
          this.player.dodging = false;
        } else {
          this.player.currentHp -= damage;
          message += ` Deals ${damage} magical damage!`;
        }
        break;

      case 'defend':
        message += ` Reduces next damage by 40%!`;
        this.enemy.defendMultiplier = 0.6;
        break;

      case 'dodge':
        message += ` Prepares to evade!`;
        this.enemy.dodging = true;
        break;
    }

    this.addLog(message);
    this.currentActor = 'player'; // Pass turn to player

    // Check if player is defeated
    if (this.player.currentHp <= 0) {
      this.isOver = true;
      this.winner = 'enemy';
      this.addLog(`💀 Defeat! ${this.player.name} has been defeated!`);
    }

    return { action: action.type, damage, message };
  }

  /**
   * Calculate damage with stat consideration
   */
  calculateDamage(attacker, defender) {
    const baseDamage = attacker.stats.attack || 10;
    const defenseValue = defender.stats.defense || 5;
    const defenseReduction = Math.max(0, Math.min(0.7, defenseValue / (defenseValue + 50)));
    const variance = 1 + (Math.random() - 0.5) * 0.2; // ±10% variance
    
    return Math.max(1, Math.round(baseDamage * (1 - defenseReduction) * variance));
  }

  /**
   * Add message to battle log
   */
  addLog(message) {
    this.log.push({
      message,
      timestamp: Date.now(),
    });
  }

  /**
   * Get battle state
   */
  getState() {
    return {
      player: {
        name: this.player.name,
        hp: Math.max(0, this.player.currentHp),
        maxHp: this.player.stats.maxHp,
        stats: this.player.stats,
      },
      enemy: {
        name: this.enemy.name,
        hp: Math.max(0, this.enemy.currentHp),
        maxHp: this.enemy.stats.maxHp,
        stats: this.enemy.stats,
      },
      currentActor: this.currentActor,
      isOver: this.isOver,
      winner: this.winner,
      turn: this.turn,
      log: this.log,
    };
  }

  /**
   * Get battle result
   */
  getResult() {
    if (!this.isOver) return null;

    const victory = this.winner === 'player';
    return {
      victory,
      playerName: this.player.name,
      enemyName: this.enemy.name,
      damageDealt: this.enemy.stats.maxHp - this.enemy.currentHp,
      damageTaken: this.player.stats.maxHp - this.player.currentHp,
      xpGained: victory ? this.enemy.loot.xp : 0,
      goldGained: victory ? this.enemy.loot.gold : 0,
      turnsUsed: this.turn,
      log: this.log,
    };
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = Battle;
}
