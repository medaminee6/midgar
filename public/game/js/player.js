/**
 * Player Class - Represents the player character
 */

class Player {
  /**
   * Create a new player
   * @param {Object} characterData - Character template from CHARACTERS
   * @param {number} x - Starting x position
   * @param {number} y - Starting y position
   */
  constructor(characterData, x, y) {
    // Character info
    this.id = characterData.id;
    this.name = characterData.name;
    this.universe = characterData.universe;
    this.portrait = characterData.portrait;

    // Position and movement
    this.x = x;
    this.y = y;
    this.vx = 0;
    this.vy = 0;
    this.speed = 4;
    this.angle = 0;

    // Stats
    this.stats = { ...characterData.stats };
    this.hp = this.stats.maxHp;
    this.mana = 100;
    this.maxMana = 100;

    // Abilities
    this.passiveAbility = characterData.passiveAbility;
    this.activeAbility = characterData.activeAbility;
    this.abilityLastUsed = 0;

    // Combat
    this.invulnerable = false;
    this.invulnerableTimer = 0;
    this.invulnerableDuration = 500;
    this.dodgeChance = this.calculateDodgeChance();

    // Animation
    this.size = 20;
    this.animationCounter = 0;
    this.color = '#4da6ff';

    // Particles for effects
    this.particles = [];

    // Stats tracking
    this.enemiesDefeated = 0;
    this.damageTaken = 0;
    this.dungeonDepth = 1;
  }

  /**
   * Calculate dodge chance based on agility
   * @returns {number} Dodge chance percentage (0-1)
   */
  calculateDodgeChance() {
    const baseChance = 0.05; // 5% base
    const agilityBonus = this.stats.agility / 100;
    return Math.min(0.4, baseChance + agilityBonus); // Max 40%
  }

  /**
   * Update player state
   * @param {number} deltaTime - Time since last update in ms
   * @param {Array} obstacles - Array of obstacles to collide with
   */
  update(deltaTime, obstacles = []) {
    // Apply movement
    this.x += this.vx;
    this.y += this.vy;

    // Stop velocity (grid-based feel)
    this.vx *= 0.85;
    this.vy *= 0.85;

    // Collision detection
    this.handleCollisions(obstacles);

    // Update invulnerability
    if (this.invulnerable) {
      this.invulnerableTimer -= deltaTime;
      if (this.invulnerableTimer <= 0) {
        this.invulnerable = false;
      }
    }

    // Update mana regeneration
    if (this.mana < this.maxMana) {
      this.mana = Math.min(this.maxMana, this.mana + 0.02);
    }

    // Update animation
    this.animationCounter = (this.animationCounter + 1) % 60;

    // Update particles
    this.particles = this.particles.filter(p => {
      p.x += p.vx;
      p.y += p.vy;
      p.life -= deltaTime;
      p.vx *= 0.98; // Friction
      return p.life > 0;
    });
  }

  /**
   * Handle collisions with obstacles
   * @param {Array} obstacles - Array of obstacles
   */
  handleCollisions(obstacles) {
    for (const obstacle of obstacles) {
      const dist = Utils.distance(this.x, this.y, obstacle.x, obstacle.y);
      if (dist < this.size + obstacle.size) {
        // Push player back
        const angle = Utils.getAngle(obstacle.x, obstacle.y, this.x, this.y);
        const pushDist = this.size + obstacle.size - dist + 1;
        this.x += Math.cos(angle) * pushDist;
        this.y += Math.sin(angle) * pushDist;
      }
    }
  }

  /**
   * Move player in a direction
   * @param {string} direction - 'up', 'down', 'left', 'right'
   */
  move(direction) {
    const moveSpeed = this.speed * (1 + this.stats.agility / 100);
    switch (direction) {
      case 'up':
        this.vy = -moveSpeed;
        this.angle = -Math.PI / 2;
        break;
      case 'down':
        this.vy = moveSpeed;
        this.angle = Math.PI / 2;
        break;
      case 'left':
        this.vx = -moveSpeed;
        this.angle = Math.PI;
        break;
      case 'right':
        this.vx = moveSpeed;
        this.angle = 0;
        break;
    }
  }

  /**
   * Perform dodge roll with invincibility
   */
  dodge() {
    if (this.invulnerable) return;

    // Check if dodge succeeds based on agility
    const dodgeSuccess = Math.random() < (this.stats.agility / 50);
    if (!dodgeSuccess) return;

    // Dash in current direction
    const vel = Utils.getVelocity(this.angle, 8);
    this.x += vel.vx * 20;
    this.y += vel.vy * 20;

    // Grant invulnerability
    this.invulnerable = true;
    this.invulnerableTimer = this.invulnerableDuration;

    this.createParticles(10);
  }

  /**
   * Use active ability
   * @param {number} targetX - Target x position
   * @param {number} targetY - Target y position
   * @param {Array} enemies - Array of enemies to potentially damage
   * @returns {Object} Ability effect data
   */
  useAbility(targetX, targetY, enemies = []) {
    const now = Date.now();
    if (now - this.abilityLastUsed < this.activeAbility.cooldown) {
      return null; // Still on cooldown
    }

    // Check mana
    if (this.mana < this.activeAbility.cost) {
      return null; // Not enough mana
    }

    this.mana -= this.activeAbility.cost;
    this.abilityLastUsed = now;

    // Create effect indicator
    this.createParticles(20);

    return {
      name: this.activeAbility.name,
      x: targetX,
      y: targetY,
      range: this.activeAbility.range,
      damage: this.activeAbility.damage,
      cost: this.activeAbility.cost
    };
  }

  /**
   * Take damage
   * @param {number} damage - Damage amount
   * @param {Object} source - Source of damage (for knockback direction)
   */
  takeDamage(damage, source = null) {
    if (this.invulnerable) return;

    // Apply passive ability reduction
    let actualDamage = damage;
    if (this.passiveAbility.effect) {
      actualDamage = this.passiveAbility.effect(actualDamage);
    }

    // Apply defense reduction
    const defenseReduction = this.stats.defense / (this.stats.defense + 50);
    actualDamage = Math.floor(actualDamage * (1 - defenseReduction * 0.3));

    // Dodge check
    if (Math.random() < this.dodgeChance) {
      actualDamage = 0;
    }

    this.hp -= actualDamage;
    this.damageTaken += actualDamage;

    // Knockback
    if (source) {
      const angle = Utils.getAngle(source.x, source.y, this.x, this.y);
      const vel = Utils.getVelocity(angle, 3);
      this.vx += vel.vx;
      this.vy += vel.vy;
    }

    // Grant temporary invulnerability
    if (actualDamage > 0) {
      this.invulnerable = true;
      this.invulnerableTimer = 300;
      this.createDamageParticles(actualDamage, 15);
    }
  }

  /**
   * Heal the player
   * @param {number} amount - Healing amount
   */
  heal(amount) {
    const oldHp = this.hp;
    this.hp = Math.min(this.stats.maxHp, this.hp + amount);
    const healed = this.hp - oldHp;
    if (healed > 0) {
      this.createParticles(8, '#4da6ff');
    }
  }

  /**
   * Create particle effect at player position
   * @param {number} count - Number of particles
   * @param {string} color - Particle color
   */
  createParticles(count, color = '#4da6ff') {
    for (let i = 0; i < count; i++) {
      const angle = (Math.PI * 2 * i) / count;
      const speed = Utils.randFloat(2, 5);
      const vel = Utils.getVelocity(angle, speed);
      this.particles.push({
        x: this.x,
        y: this.y,
        vx: vel.vx,
        vy: vel.vy,
        life: 500,
        color: color,
        size: Utils.randFloat(2, 4)
      });
    }
  }

  /**
   * Create damage particle effect
   * @param {number} damage - Damage amount
   * @param {number} count - Particle count
   */
  createDamageParticles(damage, count) {
    for (let i = 0; i < count; i++) {
      const angle = Utils.randFloat(0, Math.PI * 2);
      const speed = Utils.randFloat(2, 6);
      const vel = Utils.getVelocity(angle, speed);
      this.particles.push({
        x: this.x + Utils.randFloat(-10, 10),
        y: this.y + Utils.randFloat(-10, 10),
        vx: vel.vx,
        vy: vel.vy,
        life: 800,
        color: '#ff4444',
        size: Utils.randFloat(3, 5),
        damage: damage
      });
    }
  }

  /**
   * Draw the player
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} cameraX - Camera x offset
   * @param {number} cameraY - Camera y offset
   */
  draw(ctx, cameraX, cameraY) {
    const screenX = this.x - cameraX;
    const screenY = this.y - cameraY;

    // Invulnerability flicker
    if (this.invulnerable && Math.floor(this.invulnerableTimer / 50) % 2 === 0) {
      return; // Skip drawing for flicker effect
    }

    // Body glow
    ctx.fillStyle = 'rgba(77, 166, 255, 0.2)';
    ctx.beginPath();
    ctx.arc(screenX, screenY, this.size + 8, 0, Math.PI * 2);
    ctx.fill();

    // Main body
    Utils.drawCircle(ctx, screenX, screenY, this.size, this.color);

    // Eyes (animation)
    const eyeOffset = Math.sin(this.animationCounter * 0.1) * 2;
    ctx.fillStyle = '#000';
    Utils.drawCircle(ctx, screenX - 6, screenY - 5 + eyeOffset, 3, '#000');
    Utils.drawCircle(ctx, screenX + 6, screenY - 5 + eyeOffset, 3, '#000');

    // Direction indicator
    const dirVel = Utils.getVelocity(this.angle, 12);
    ctx.strokeStyle = '#4da6ff';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(screenX, screenY);
    ctx.lineTo(screenX + dirVel.vx, screenY + dirVel.vy);
    ctx.stroke();

    // Draw particles
    for (const p of this.particles) {
      ctx.fillStyle = p.color;
      ctx.globalAlpha = Math.max(0, p.life / 500);
      Utils.drawCircle(ctx, screenX + (p.x - this.x), screenY + (p.y - this.y), p.size, p.color);
      ctx.globalAlpha = 1;
    }
  }
}
