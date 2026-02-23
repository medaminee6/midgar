/**
 * Enemy AI System - State machine-based intelligent behavior
 */

// AI States
const AIState = {
  IDLE: 'idle',
  PATROL: 'patrol',
  CHASE: 'chase',
  ATTACK: 'attack',
  RETREAT: 'retreat',
  CALL_ALLIES: 'call_allies'
};

class EnemyAI {
  /**
   * Create enemy AI controller
   * @param {Enemy} enemy - The enemy to control
   * @param {string} behaviorType - Behavior template ('melee_aggressive', 'ranged', etc)
   */
  constructor(enemy, behaviorType) {
    this.enemy = enemy;
    this.behaviorType = behaviorType;
    this.state = AIState.IDLE;
    this.stateTimer = 0;
    this.patrolTarget = null;
    this.lastPlayerSeen = null;
    this.lastPlayerSeenTimer = 0;

    // Behavior parameters
    this.visionRange = 300;
    this.attackRange = this.enemy.range;
    this.retreatThreshold = this.enemy.stats.maxHp * 0.25;

    // Aggressiveness varies by behavior
    this.aggressiveness = this.getAggressiveness(behaviorType);
    this.ranged = this.isRanged(behaviorType);
    this.packable = this.isPackable(behaviorType);
  }

  /**
   * Get aggressiveness based on behavior type
   * @param {string} type - Behavior type
   * @returns {number} Aggressiveness (0-1)
   */
  getAggressiveness(type) {
    const map = {
      'melee_aggressive': 0.9,
      'melee_pack': 0.75,
      'melee_tank': 0.6,
      'ranged_aggressive': 0.85,
      'ranged': 0.7
    };
    return map[type] || 0.6;
  }

  /**
   * Check if behavior is ranged
   * @param {string} type - Behavior type
   * @returns {boolean}
   */
  isRanged(type) {
    return type.includes('ranged');
  }

  /**
   * Check if behavior includes pack behavior
   * @param {string} type - Behavior type
   * @returns {boolean}
   */
  isPackable(type) {
    return type.includes('pack');
  }

  /**
   * Update AI each frame
   * @param {Player} player - Player reference
   * @param {Array} allEnemies - All enemies for pack behavior
   * @param {number} deltaTime - Time delta in ms
   */
  update(player, allEnemies = [], deltaTime = 16) {
    this.stateTimer -= deltaTime;
    this.lastPlayerSeenTimer -= deltaTime;

    // Check if player is visible
    const distToPlayer = Utils.distance(this.enemy.x, this.enemy.y, player.x, player.y);
    const canSeePlayer = distToPlayer < this.visionRange;

    if (canSeePlayer) {
      this.lastPlayerSeen = { x: player.x, y: player.y };
      this.lastPlayerSeenTimer = 5000; // Remember for 5 seconds
    }

    // State machine
    switch (this.state) {
      case AIState.IDLE:
        this.updateIdle(player, distToPlayer);
        break;
      case AIState.PATROL:
        this.updatePatrol(player, distToPlayer);
        break;
      case AIState.CHASE:
        this.updateChase(player, distToPlayer, allEnemies);
        break;
      case AIState.ATTACK:
        this.updateAttack(player, distToPlayer);
        break;
      case AIState.RETREAT:
        this.updateRetreat(player);
        break;
      case AIState.CALL_ALLIES:
        this.updateCallAllies(allEnemies);
        break;
    }

    // Check universal retreat condition
    if (this.enemy.hp < this.retreatThreshold && this.state !== AIState.RETREAT) {
      this.transitionTo(AIState.RETREAT);
    }
  }

  /**
   * Transition to a new state
   * @param {string} newState - New AI state
   */
  transitionTo(newState) {
    this.state = newState;
    this.stateTimer = 0;
  }

  /**
   * IDLE STATE - Resting but vigilant
   */
  updateIdle(player, distToPlayer) {
    if (distToPlayer < this.visionRange) {
      this.transitionTo(AIState.CHASE);
      return;
    }

    // Random patrol
    if (this.stateTimer <= 0) {
      this.transitionTo(AIState.PATROL);
    }

    // Stop moving
    this.enemy.vx *= 0.9;
    this.enemy.vy *= 0.9;
  }

  /**
   * PATROL STATE - Walk around aimlessly
   */
  updatePatrol(player, distToPlayer) {
    if (distToPlayer < this.visionRange) {
      this.transitionTo(AIState.CHASE);
      return;
    }

    // Pick patrol target if needed
    if (!this.patrolTarget || this.stateTimer <= 0) {
      const angle = Math.random() * Math.PI * 2;
      const distance = 150;
      this.patrolTarget = {
        x: this.enemy.x + Math.cos(angle) * distance,
        y: this.enemy.y + Math.sin(angle) * distance
      };
      this.stateTimer = 3000; // Patrol for 3 seconds
    }

    // Move toward patrol target
    this.moveToward(this.patrolTarget.x, this.patrolTarget.y, this.enemy.speed * 0.6);
  }

  /**
   * CHASE STATE - Pursuit the player
   */
  updateChase(player, distToPlayer, allEnemies) {
    // Attack if close enough
    if (this.ranged && distToPlayer < this.attackRange) {
      this.transitionTo(AIState.ATTACK);
      return;
    } else if (!this.ranged && distToPlayer < this.enemy.size + player.size + 20) {
      this.transitionTo(AIState.ATTACK);
      return;
    }

    // Use last seen position if player out of sight
    const targetPos = this.lastPlayerSeenTimer > 0 ? this.lastPlayerSeen : { x: player.x, y: player.y };

    if (targetPos) {
      this.moveToward(targetPos.x, targetPos.y, this.enemy.speed);
    }

    // Flanking AI for pack enemies
    if (this.packable && allEnemies.length > 1) {
      this.attemptFlanking(player, allEnemies);
    }

    // Call for backup if outnumbered
    if (distToPlayer < 150 && this.packable && Math.random() < 0.05) {
      this.transitionTo(AIState.CALL_ALLIES);
      return;
    }
  }

  /**
   * ATTACK STATE - Engage the player
   */
  updateAttack(player, distToPlayer) {
    // Check if still in range
    if (this.ranged && distToPlayer > this.attackRange * 1.2) {
      this.transitionTo(AIState.CHASE);
      return;
    } else if (!this.ranged && distToPlayer > this.enemy.size + player.size + 40) {
      this.transitionTo(AIState.CHASE);
      return;
    }

    // Maintain optimal distance for ranged enemies
    if (this.ranged) {
      const optimalDistance = this.attackRange * 0.8;
      if (distToPlayer < optimalDistance) {
        // Back away
        const angle = Utils.getAngle(player.x, player.y, this.enemy.x, this.enemy.y);
        const vel = Utils.getVelocity(angle, this.enemy.speed * 0.5);
        this.enemy.vx = vel.vx;
        this.enemy.vy = vel.vy;
      } else if (distToPlayer > optimalDistance) {
        // Move closer
        this.moveToward(player.x, player.y, this.enemy.speed * 0.7);
      }
    } else {
      // Melee - stay close and move toward player
      this.moveToward(player.x, player.y, this.enemy.speed * 0.8);
    }

    // Attack periodically
    if (this.stateTimer <= 0) {
      this.enemy.attackPlayer(player);
      this.stateTimer = 1000 + Math.random() * 500; // 1-1.5 second cooldown
    }

    // Return to idle if player escapes
    if (this.lastPlayerSeenTimer <= 0) {
      this.transitionTo(AIState.IDLE);
    }
  }

  /**
   * RETREAT STATE - Run away
   */
  updateRetreat(player) {
    // Flee from player
    const angle = Utils.getAngle(player.x, player.y, this.enemy.x, this.enemy.y);
    const vel = Utils.getVelocity(angle, this.enemy.speed);
    this.enemy.vx = vel.vx;
    this.enemy.vy = vel.vy;

    // Return to idle if far enough
    const dist = Utils.distance(this.enemy.x, this.enemy.y, player.x, player.y);
    if (dist > this.visionRange * 1.5) {
      this.transitionTo(AIState.IDLE);
    }
  }

  /**
   * CALL_ALLIES STATE - Attempt to group with other enemies
   */
  updateCallAllies(allEnemies) {
    // Find closest ally
    let closestAlly = null;
    let closestDist = Infinity;

    for (const ally of allEnemies) {
      if (ally === this.enemy) continue;
      const dist = Utils.distance(this.enemy.x, this.enemy.y, ally.x, ally.y);
      if (dist < closestDist) {
        closestDist = dist;
        closestAlly = ally;
      }
    }

    // Move toward ally or back to chase
    if (closestAlly && closestDist < 500) {
      this.moveToward(closestAlly.x, closestAlly.y, this.enemy.speed * 0.6);
    }

    // Exit state after delay
    if (this.stateTimer <= 0) {
      this.transitionTo(AIState.CHASE);
    }
  }

  /**
   * Move enemy toward a target
   * @param {number} targetX - Target x
   * @param {number} targetY - Target y
   * @param {number} speed - Movement speed
   */
  moveToward(targetX, targetY, speed) {
    const angle = Utils.getAngle(this.enemy.x, this.enemy.y, targetX, targetY);
    const vel = Utils.getVelocity(angle, speed);
    this.enemy.vx = vel.vx;
    this.enemy.vy = vel.vy;
  }

  /**
   * Flanking AI - Try to flank the player with allies
   * @param {Player} player - Player reference
   * @param {Array} allEnemies - All enemies
   */
  attemptFlanking(player, allEnemies) {
    const allies = allEnemies.filter(e => e !== this.enemy && 
                                           Utils.distance(e.x, e.y, this.enemy.x, this.enemy.y) < 300);
    
    if (allies.length === 0) return;

    // Pick a flanking angle
    const playerAngle = Utils.getAngle(this.enemy.x, this.enemy.y, player.x, player.y);
    const alliedCount = Math.floor(Math.random() * 2); // 0 or 1
    const flankAngle = playerAngle + Math.PI / 2 + alliedCount * Math.PI;

    // Move to flanking position
    const flankDist = 100;
    const flankX = player.x + Math.cos(flankAngle) * flankDist;
    const flankY = player.y + Math.sin(flankAngle) * flankDist;

    this.moveToward(flankX, flankY, this.enemy.speed * 0.7);
  }
}

/**
 * Boss AI - More complex adaptive behavior
 */
class BossAI extends EnemyAI {
  /**
   * Create boss AI
   * @param {Enemy} boss - Boss enemy
   * @param {Object} bossTemplate - Boss template data
   */
  constructor(boss, bossTemplate) {
    super(boss, 'boss_adaptive');
    this.boss = boss;
    this.template = bossTemplate;
    this.abilities = bossTemplate.abilities || [];
    this.abilityIndex = 0;
    this.adaptiveStrategy = 'balanced';
    this.phaseTransition = false;

    this.visionRange = 500; // Bosses see farther
    this.aggressiveness = 0.95;
  }

  /**
   * Update boss with adaptive behavior
   * @param {Player} player - Player reference
   * @param {Array} allEnemies - All enemies
   * @param {number} deltaTime - Time delta
   */
  update(player, allEnemies = [], deltaTime = 16) {
    // Analyze player and adapt
    this.analyzePlayer(player);

    // Basic state machine
    super.update(player, allEnemies, deltaTime);

    // Boss phase transitions
    if (this.boss.hp < this.boss.stats.maxHp * 0.5 && !this.phaseTransition) {
      this.transitionTo(AIState.CALL_ALLIES); // Summon minions
      this.phaseTransition = true;
    }
  }

  /**
   * Analyze player stats and adjust strategy
   * @param {Player} player - Player reference
   */
  analyzePlayer(player) {
    const defenseRatio = player.stats.defense / player.stats.attack;
    const agilityRatio = player.stats.agility / player.stats.attack;
    const magicRatio = player.stats.magic / player.stats.attack;

    if (defenseRatio > 1.5) {
      // High defense - use magic
      this.adaptiveStrategy = 'magic_focus';
    } else if (agilityRatio > 1.2) {
      // High agility - use area attacks
      this.adaptiveStrategy = 'aoe_focus';
    } else if (magicRatio > 1) {
      // Resist magic - close in
      this.adaptiveStrategy = 'melee_aggressive';
    } else {
      // Balanced approach
      this.adaptiveStrategy = 'balanced';
    }
  }

  /**
   * Boss attack with adaptive abilities
   * @param {Player} player - Player reference
   */
  bossAttack(player) {
    const ability = this.abilities[this.abilityIndex];
    
    switch (ability) {
      case 'shield_bash':
        // Temporary defense boost
        this.boss.defenseBoost = 1.5;
        this.boss.defenseBoostTimer = 2000;
        break;
      case 'power_attack':
        // High damage attack
        this.boss.attackDamage = this.boss.stats.attack * 2;
        break;
      case 'summon_minions':
        // Mark for minion spawn in world
        this.boss.shouldSummon = true;
        break;
      case 'arcane_blast':
        // Magic damage attack
        this.boss.attackDamage = this.boss.stats.magic * 1.8;
        break;
      case 'heal':
        // Restore health
        this.boss.hp = Math.min(this.boss.stats.maxHp, this.boss.hp + 50);
        break;
    }

    // Cycle through abilities
    this.abilityIndex = (this.abilityIndex + 1) % this.abilities.length;
  }
}
