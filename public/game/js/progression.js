/**
 * Progression System - Handles titles, highscores, and profile management
 */

class Progression {
  /**
   * Initialize progression system
   */
  static init() {
    this.loadProfile();
  }

  /**
   * Load player profile from localStorage
   */
  static loadProfile() {
    const saved = localStorage.getItem('gameProfile');
    if (saved) {
      this.profile = JSON.parse(saved);
    } else {
      this.profile = {
        totalRuns: 0,
        highscores: [],
        titles: [],
        characterStats: {},
        universesExplored: new Set()
      };
    }
  }

  /**
   * Save profile to localStorage
   */
  static saveProfile() {
    localStorage.setItem('gameProfile', JSON.stringify(this.profile));
  }

  /**
   * Generate a title based on player performance
   * @param {Object} stats - Player stats from the run
   * @returns {string} Generated title
   */
  static generateTitle(stats) {
    let title = '';

    // No damage taken
    if (stats.damageTaken === 0) {
      return 'Untouched';
    }

    // Speed-based
    if (stats.timeTaken < 120000) { // 2 minutes
      return 'Speedrunner';
    }

    // Damage based
    if (stats.damageTaken > stats.maxHp * 3) {
      return 'Ghost'; // Took massive damage but survived
    }

    // Depth-based
    if (stats.dungeonDepth >= 10) {
      return 'Void Conqueror';
    }

    // Enemy count based
    if (stats.enemiesDefeated > 100) {
      return 'Monster Slayer';
    }

    // Magic usage based
    if (stats.abilitiesUsed >= 20) {
      return 'Arcane Master';
    }

    // Default pool
    return Utils.choice(TITLE_POOL);
  }

  /**
   * Record a completed run
   * @param {Player} player - The player
   * @param {Object} stats - Run statistics
   * @param {boolean} victory - Whether the run was a victory
   */
  static recordRun(player, stats, victory) {
    this.profile.totalRuns++;

    // Generate title if victory
    const title = victory ? this.generateTitle(stats) : null;
    if (title) {
      if (!this.profile.titles.includes(title)) {
        this.profile.titles.push(title);
      }
    }

    // Record highscore if victory
    if (victory) {
      const score = {
        character: player.name,
        universe: player.universe,
        depth: stats.dungeonDepth,
        enemiesDefeated: stats.enemiesDefeated,
        damageTaken: stats.damageTaken,
        timeTaken: stats.timeTaken,
        title: title,
        timestamp: new Date().toISOString()
      };

      this.profile.highscores.push(score);
      this.profile.highscores.sort((a, b) => b.depth - a.depth);
      this.profile.highscores = this.profile.highscores.slice(0, 10); // Top 10
    }

    // Track character usage
    if (!this.profile.characterStats[player.id]) {
      this.profile.characterStats[player.id] = {
        timesPlayed: 0,
        victories: 0,
        totalEnemiesDefeated: 0
      };
    }

    this.profile.characterStats[player.id].timesPlayed++;
    if (victory) {
      this.profile.characterStats[player.id].victories++;
      this.profile.characterStats[player.id].totalEnemiesDefeated += stats.enemiesDefeated;
    }

    // Track universes
    if (!this.profile.universesExplored.has(player.universe)) {
      this.profile.universesExplored.add(player.universe);
    }

    this.saveProfile();
  }

  /**
   * Get highscore table
   * @returns {Array} Array of highscore objects, sorted by depth
   */
  static getHighscores() {
    return this.profile.highscores || [];
  }

  /**
   * Get player titles
   * @returns {Array} Array of earned titles
   */
  static getTitles() {
    return this.profile.titles || [];
  }

  /**
   * Get character statistics
   * @param {string} characterId - Character ID
   * @returns {Object} Character stats
   */
  static getCharacterStats(characterId) {
    return this.profile.characterStats[characterId] || {
      timesPlayed: 0,
      victories: 0,
      totalEnemiesDefeated: 0
    };
  }

  /**
   * Get win rate for a character
   * @param {string} characterId - Character ID
   * @returns {number} Win rate as percentage (0-100)
   */
  static getCharacterWinRate(characterId) {
    const stats = this.getCharacterStats(characterId);
    if (stats.timesPlayed === 0) return 0;
    return Math.round((stats.victories / stats.timesPlayed) * 100);
  }

  /**
   * Get best run
   * @returns {Object|null} Best highscore entry
   */
  static getBestRun() {
    const scores = this.getHighscores();
    return scores.length > 0 ? scores[0] : null;
  }

  /**
   * Get statistics summary
   * @returns {Object} Summary statistics
   */
  static getStatisticsSummary() {
    const scores = this.getHighscores();
    const totalVictories = scores.length;
    const totalRuns = this.profile.totalRuns;
    const winRate = totalRuns === 0 ? 0 : Math.round((totalVictories / totalRuns) * 100);

    let totalEnemiesDefeated = 0;
    let maxDepthReached = 0;

    for (const score of scores) {
      totalEnemiesDefeated += score.enemiesDefeated;
      maxDepthReached = Math.max(maxDepthReached, score.depth);
    }

    return {
      totalRuns: totalRuns,
      victoryCount: totalVictories,
      winRate: winRate,
      totalEnemiesDefeated: totalEnemiesDefeated,
      maxDepthReached: maxDepthReached,
      titlesEarned: this.profile.titles.length,
      universesExplored: this.profile.universesExplored.size
    };
  }

  /**
   * Reset all profile data
   */
  static resetProfile() {
    this.profile = {
      totalRuns: 0,
      highscores: [],
      titles: [],
      characterStats: {},
      universesExplored: new Set()
    };
    localStorage.removeItem('gameProfile');
  }
}
