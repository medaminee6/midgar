/**
 * Main Game Module
 * Manages game state, battle flow, and integration
 */

class MidgarGame {
  constructor(options = {}) {
    this.options = {
      personnageId: options.personnageId,
      battleContainer: options.battleContainer || '#battleContainer',
      onGameEnd: options.onGameEnd || (() => {}),
      ...options,
    };

    this.player = null;
    this.enemy = null;
    this.universe = null;
    this.battle = null;
    this.battleUI = null;
    this.state = 'loading'; // loading, ready, battling, over
    this.log = [];

    this.init();
  }

  /**
   * Initialize game
   */
  async init() {
    try {
      // Fetch player data
      this.player = await GameAPI.getPersonnage(this.options.personnageId);

      // Fetch universe data
      this.universe = await GameAPI.getUniverse(this.player.universe_id);

      // Initialize battle UI
      this.battleUI = new BattleUI(
        document.querySelector(this.options.battleContainer),
        this
      );

      this.state = 'ready';
      this.log.push('Game initialized successfully');
    } catch (error) {
      console.error('Failed to initialize game:', error);
      this.state = 'error';
      throw error;
    }
  }

  /**
   * Start a new battle with random enemy from universe
   */
  async startBattle() {
    try {
      this.state = 'battling';
      
      // Fetch random enemy for this universe
      this.enemy = await GameAPI.getRandomEnemyForBattle(this.player.universe_id);

      // Create battle instance
      this.battle = new Battle(this.player, this.enemy);

      // Initialize UI
      this.battleUI.initialize(this.battle);

      this.log.push(`Battle started against ${this.enemy.name}!`);
    } catch (error) {
      console.error('Failed to start battle:', error);
      this.state = 'error';
      throw error;
    }
  }

  /**
   * Handle player action
   */
  handlePlayerAction(actionType) {
    if (this.state !== 'battling' || !this.battle) return;

    // Execute player action
    const playerResult = this.battle.executePlayerAction(actionType);
    
    if (playerResult) {
      this.log.push(`Player: ${actionType}`);

      // Check if battle is over after player action
      if (this.battle.isOver) {
        this.state = 'over';
        this.battleUI.update();
        this.endBattle();
        return;
      }

      // Process turn - let UI show player action first
      setTimeout(() => this.processTurn(), 500);
    }
  }

  /**
   * Process enemy turn
   */
  processTurn() {
    if (this.state !== 'battling' || !this.battle) return;

    // Execute enemy action
    const enemyResult = this.battle.executeEnemyAction();
    
    if (enemyResult) {
      this.log.push(`Enemy: ${enemyResult.action}`);

      // Check if battle is over after enemy action
      if (this.battle.isOver) {
        this.state = 'over';
        this.battleUI.update();
        this.endBattle();
        return;
      }

      this.battle.turn++;
      this.battleUI.update();
    }
  }

  /**
   * End battle and save results
   */
  async endBattle() {
    if (!this.battle) return;

    const result = this.battle.getResult();
    
    try {
      // Save result to server if profile ID provided
      if (this.options.profileId) {
        await GameAPI.saveBattleResult(this.options.profileId, {
          victory: result.victory,
          enemyName: result.enemyName,
          damageDealt: result.damageDealt,
          damageTaken: result.damageTaken,
          xpGained: result.xpGained,
          goldGained: result.goldGained,
          turnsUsed: result.turnsUsed,
        });
      }

      // Notify parent
      this.state = 'over';
      this.options.onGameEnd(result);

      this.log.push('Battle ended and saved');
    } catch (error) {
      console.error('Failed to end battle:', error);
    }
  }

  /**
   * Reset game state
   */
  reset() {
    this.battle = null;
    this.enemy = null;
    this.state = 'ready';
    this.log = [];
  }

  /**
   * Get current game state
   */
  getState() {
    return {
      state: this.state,
      player: this.player,
      enemy: this.enemy,
      universe: this.universe,
      battle: this.battle ? this.battle.getState() : null,
      log: this.log,
    };
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = MidgarGame;
}
