/**
 * Game API Module
 * Handles all communication with backend API endpoints
 */

class GameAPI {
  static BASE_URL = '/api/game';

  /**
   * Fetch personnage data
   */
  static async getPersonnage(id) {
    try {
      const response = await fetch(`${this.BASE_URL}/personnage/${id}`);
      if (!response.ok) throw new Error('Failed to fetch personnage');
      return await response.json();
    } catch (error) {
      console.error('Error fetching personnage:', error);
      throw error;
    }
  }

  /**
   * Fetch universe data
   */
  static async getUniverse(id) {
    try {
      const response = await fetch(`${this.BASE_URL}/universe/${id}`);
      if (!response.ok) throw new Error('Failed to fetch universe');
      return await response.json();
    } catch (error) {
      console.error('Error fetching universe:', error);
      throw error;
    }
  }

  /**
   * Fetch all enemies for a universe
   */
  static async getUniverseEnemies(universeId) {
    try {
      const response = await fetch(`${this.BASE_URL}/universe/${universeId}/enemies`);
      if (!response.ok) throw new Error('Failed to fetch enemies');
      return await response.json();
    } catch (error) {
      console.error('Error fetching enemies:', error);
      throw error;
    }
  }

  /**
   * Get a random enemy for battle
   */
  static async getRandomEnemyForBattle(universeId) {
    try {
      const response = await fetch(`${this.BASE_URL}/universe/${universeId}/enemy-for-battle`);
      if (!response.ok) throw new Error('Failed to fetch random enemy');
      return await response.json();
    } catch (error) {
      console.error('Error fetching random enemy:', error);
      throw error;
    }
  }

  /**
   * Save battle result to profile
   */
  static async saveBattleResult(profileId, result) {
    try {
      const response = await fetch(`/api/profile/${profileId}/battle-result`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': this.getCsrfToken(),
        },
        body: JSON.stringify(result),
      });
      if (!response.ok) throw new Error('Failed to save battle result');
      return await response.json();
    } catch (error) {
      console.error('Error saving battle result:', error);
      throw error;
    }
  }

  /**
   * Get CSRF token from meta tag
   */
  static getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = GameAPI;
}
