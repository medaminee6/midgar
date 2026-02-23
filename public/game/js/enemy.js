/**
 * Enemy Class - Represents an enemy entity
 */

class Enemy {
  /**
   * Create a new enemy
   * @param {Object} template - Enemy template from ENEMY_TEMPLATES or BOSS_TEMPLATES
   * @param {number} x - Starting x position
   * @param {number} y - Starting y position
   * @param {string} behavior - Behavior type
   * @param {boolean} isBoss - Whether this is a boss enemy
   */
  constructor(template, x, y, behavior = 'melee_aggressive', isBoss = false) {
    // Basic info
    this.template = template;
    this.name = template.name;
    this.x = x;
    this.y = y;
    this.isBoss = isBoss;

    // Movement
    this.vx = 0;
    this.vy = 0;
    this.speed = template.speed || 1;
    this.angle = 0;

    // Stats
    this.stats = {
      maxHp: template.hp,
      attack: template.attack,
      defense: template.defense,
      magic: template.magic,
      agility: template.agility
    };
    this.hp = this.stats.maxHp;

    // Combat
    this.size = template.size || 20;
    this.range = template.range || 100;
    this.color = template.color || '#ff6347';
    this.attackCooldown = 0;
    this.attackDamage = this.stats.attack;
    this.defenseBoost = 1;
    this.defenseBoostTimer = 0;

    // AI
    this.ai = isBoss ? new BossAI(this, template) : new EnemyAI(this, behavior);

    // Animation
    this.animationCounter = 0;
    this.particles = [];

    // Boss specific
    if (isBoss) {
      this.shouldSummon = false;
      this.minionCount = 0;
    }
  }

  /**
   * Update enemy state
   * @param {Player} player - Player reference
   * @param {Array} allEnemies - All enemies for AI
   * @param {Array} obstacles - Collision obstacles
   * @param {number} deltaTime - Time since last update in ms
   */
  update(player, allEnemies = [], obstacles = [], deltaTime = 16) {
    // Update AI behavior
    this.ai.update(player, allEnemies, deltaTime);

    // Apply movement
    this.x += this.vx;
    this.y += this.vy;

    // Friction
    this.vx *= 0.9;
    this.vy *= 0.9;

    // Collision with obstacles
    this.handleCollisions(obstacles);

    // Update cooldowns
    this.attackCooldown = Math.max(0, this.attackCooldown - deltaTime);
    this.defenseBoostTimer = Math.max(0, this.defenseBoostTimer - deltaTime);

    if (this.defenseBoostTimer <= 0) {
      this.defenseBoost = 1;
    }

    // Animation update
    this.animationCounter = (this.animationCounter + 1) % 60;

    // Update particles
    this.particles = this.particles.filter(p => {
      p.x += p.vx;
      p.y += p.vy;
      p.life -= deltaTime;
      p.vx *= 0.98;
      return p.life > 0;
    });
  }

  /**
   * Handle collisions with obstacles
   * @param {Array} obstacles - Obstacle array
   */
  handleCollisions(obstacles) {
    for (const obstacle of obstacles) {
      if (Utils.circleCollide(this.x, this.y, this.size, obstacle.x, obstacle.y, obstacle.size)) {
        // Push back
        const angle = Utils.getAngle(obstacle.x, obstacle.y, this.x, this.y);
        const pushDist = this.size + obstacle.size - Utils.distance(this.x, this.y, obstacle.x, obstacle.y) + 1;
        this.x += Math.cos(angle) * pushDist;
        this.y += Math.sin(angle) * pushDist;
      }
    }
  }

  /**
   * Attack the player
   * @param {Player} player - Player to attack
   * @returns {number|null} Damage dealt or null if on cooldown
   */
  attackPlayer(player) {
    if (this.attackCooldown > 0) return null;

    // Calculate damage
    const baseDamage = this.attackDamage;
    let damage = Utils.calculateDamage(baseDamage, this.stats.attack, player.stats.defense);

    // Apply defense boost if active
    if (this.defenseBoost > 1) {
      damage = Math.floor(damage * 1.2);
    }

    // Deal damage
    player.takeDamage(damage, this);

    // Set cooldown
    this.attackCooldown = 1000 + Math.random() * 500;

    // Create hit effect
    this.createParticles(5, '#ff4444');

    return damage;
  }

  /**
   * Take damage from an attack
   * @param {number} damage - Damage amount
   * @param {*} source - Source of damage (Player or another enemy)
   */
  takeDamage(damage, source = null) {
    // Apply defense
    const defenseReduction = (this.stats.defense * this.defenseBoost) / (this.stats.defense * this.defenseBoost + 50);
    const actualDamage = Math.max(1, Math.floor(damage * (1 - defenseReduction * 0.4)));

    this.hp -= actualDamage;

    // Knockback
    if (source) {
      const angle = Utils.getAngle(source.x, source.y, this.x, this.y);
      const vel = Utils.getVelocity(angle, 2);
      this.vx += vel.vx;
      this.vy += vel.vy;
    }

    // Damage particles
    this.createDamageParticles(actualDamage);
  }

  /**
   * Create particle effect
   * @param {number} count - Particle count
   * @param {string} color - Particle color
   */
  createParticles(count, color = '#ffaa00') {
    for (let i = 0; i < count; i++) {
      const angle = (Math.PI * 2 * i) / count;
      const speed = Utils.randFloat(2, 4);
      const vel = Utils.getVelocity(angle, speed);
      this.particles.push({
        x: this.x,
        y: this.y,
        vx: vel.vx,
        vy: vel.vy,
        life: 400,
        color: color,
        size: Utils.randFloat(2, 3)
      });
    }
  }

  /**
   * Create damage particles
   * @param {number} damage - Damage amount
   */
  createDamageParticles(damage) {
    const particleCount = Math.min(damage / 3, 8);
    for (let i = 0; i < particleCount; i++) {
      const angle = Utils.randFloat(0, Math.PI * 2);
      const speed = Utils.randFloat(2, 5);
      const vel = Utils.getVelocity(angle, speed);
      this.particles.push({
        x: this.x + Utils.randFloat(-5, 5),
        y: this.y + Utils.randFloat(-5, 5),
        vx: vel.vx,
        vy: vel.vy,
        life: 600,
        color: '#ff6666',
        size: Utils.randFloat(2, 4),
        damage: damage
      });
    }
  }

  /**
   * Check if enemy is still alive
   * @returns {boolean}
   */
  isAlive() {
    return this.hp > 0;
  }

  /**
   * Draw the enemy
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} cameraX - Camera x offset
   * @param {number} cameraY - Camera y offset
   */
  draw(ctx, cameraX, cameraY) {
    const screenX = this.x - cameraX;
    const screenY = this.y - cameraY;

    // Glow effect for boss
    if (this.isBoss) {
      ctx.fillStyle = 'rgba(255, 100, 71, 0.15)';
      ctx.beginPath();
      ctx.arc(screenX, screenY, this.size + 15, 0, Math.PI * 2);
      ctx.fill();
    }

    // Main body with health-based color
    const healthRatio = this.hp / this.stats.maxHp;
    if (healthRatio > 0.5) {
      ctx.fillStyle = this.color;
    } else if (healthRatio > 0.25) {
      ctx.fillStyle = '#ff8c42';
    } else {
      ctx.fillStyle = '#ff4444';
    }

    ctx.beginPath();
    ctx.arc(screenX, screenY, this.size, 0, Math.PI * 2);
    ctx.fill();

    // Border
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
    ctx.lineWidth = 1;
    ctx.stroke();

    // Boss indicator
    if (this.isBoss) {
      ctx.strokeStyle = '#ff6347';
      ctx.lineWidth = 3;
      ctx.beginPath();
      ctx.arc(screenX, screenY, this.size + 3, 0, Math.PI * 2);
      ctx.stroke();
    }

    // Eyes if not too small
    if (this.size > 15) {
      const eyeOffset = Math.sin(this.animationCounter * 0.1) * 2;
      ctx.fillStyle = '#000';
      Utils.drawCircle(ctx, screenX - 4, screenY - 3 + eyeOffset, 2, '#000');
      Utils.drawCircle(ctx, screenX + 4, screenY - 3 + eyeOffset, 2, '#000');
    }

    // Draw particles
    for (const p of this.particles) {
      ctx.fillStyle = p.color;
      ctx.globalAlpha = Math.max(0, p.life / 600);
      Utils.drawCircle(ctx, screenX + (p.x - this.x), screenY + (p.y - this.y), p.size, p.color);
      ctx.globalAlpha = 1;
    }

    // Damage boost indicator
    if (this.defenseBoost > 1) {
      ctx.strokeStyle = '#ffff00';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.arc(screenX, screenY, this.size + 5, 0, Math.PI * 2);
      ctx.stroke();
    }
  }
}

/**
 * Boss Enemy - Stronger variant with special abilities
 */
class Boss extends Enemy {
  /**
   * Create a boss enemy
   * @param {Object} bossTemplate - Boss template from BOSS_TEMPLATES
   * @param {number} x - Starting x
   * @param {number} y - Starting y
   */
  constructor(bossTemplate, x, y) {
    super(bossTemplate, x, y, 'boss', true);
    
    // Increase stats for difficulty
    const scaling = 1.5;
    this.stats.maxHp *= scaling;
    this.hp = this.stats.maxHp;
    this.stats.attack *= scaling;
    this.stats.defense *= scaling;
    this.stats.magic *= scaling;
    
    this.size = 35;
    this.ai = new BossAI(this, bossTemplate);
  }
}
