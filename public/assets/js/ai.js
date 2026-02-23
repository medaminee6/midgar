/**
 * Enemy AI Module
 * Handles intelligent enemy decision-making in battle
 */

class EnemyAI {
  /**
   * Determine best action based on battle state
   * @param {Object} enemy - Enemy object with stats
   * @param {Object} player - Player object with stats and HP
   * @param {number} enemyHp - Current HP of enemy
   * @param {number} enemyMaxHp - Max HP of enemy
   * @returns {Object} Action object {type, targetStat} where type is 'attack', 'defend', 'skill', 'dodge'
   */
  static decideBestAction(enemy, player, enemyHp, enemyMaxHp) {
    const healthRatio = enemyHp / enemyMaxHp;
    const playerHealthRatio = player.stats.maxHp ? (player.currentHp || player.stats.maxHp) / player.stats.maxHp : 1;

    // If critically low on health, try to dodge or defend
    if (healthRatio < 0.2) {
      return { type: 'dodge', priority: 10 };
    }

    // If low on health, defend
    if (healthRatio < 0.4) {
      if (Math.random() < 0.6) {
        return { type: 'defend', priority: 8 };
      }
    }

    // Analyze enemy strengths and player weaknesses
    const playerDefense = player.stats.defense || 5;
    const playerMagic = player.stats.magic || 0;
    const enemyMagic = enemy.stats.magic || 0;
    const enemyAttack = enemy.stats.attack || 10;

    // If player has high magic resistance, use attack
    if (playerMagic > 20 && enemyMagic < playerMagic) {
      // Player is magic-resistant, use physical attack
      return { type: 'attack', priority: 9 };
    }

    // If enemy has high magic, consider spell attack
    if (enemyMagic > player.stats.defense && Math.random() < 0.5) {
      return { type: 'skill', priority: 8 };
    }

    // Default to attack, most common action
    return { type: 'attack', priority: 5 };
  }

  /**
   * Calculate strategic damage value for skill attack
   */
  static calculateSkillDamage(enemy, player) {
    const baseDamage = (enemy.stats.magic || 0) * 1.5;
    const playerDefense = player.stats.defense || 0;
    const defenseReduction = Math.max(0, Math.min(0.8, playerDefense / (playerDefense + 50)));
    return Math.max(1, Math.round(baseDamage * (1 - defenseReduction * 0.5)));
  }

  /**
   * Get personality-based flavor text for actions
   */
  static getActionFlavor(enemy, actionType) {
    const flavors = {
      attack: [
        `${enemy.name} charges forward!`,
        `${enemy.name} lunges at you!`,
        `${enemy.name} prepares a devastating blow!`,
        `${enemy.name} attacks with ferocity!`,
      ],
      defend: [
        `${enemy.name} takes a defensive stance!`,
        `${enemy.name} braces for impact!`,
        `${enemy.name} guards carefully!`,
      ],
      skill: [
        `${enemy.name} channels magical energy!`,
        `${enemy.name} casts a spell!`,
        `${enemy.name} summons dark energy!`,
      ],
      dodge: [
        `${enemy.name} prepares to evade!`,
        `${enemy.name} gets ready to dodge!`,
      ],
    };

    const actionFlavors = flavors[actionType] || flavors.attack;
    return actionFlavors[Math.floor(Math.random() * actionFlavors.length)];
  }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = EnemyAI;
}
