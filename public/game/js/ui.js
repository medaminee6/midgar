/**
 * UI System - All game UI rendering and management
 */

class UI {
  /**
   * Create UI manager
   * @param {Canvas} canvas - Game canvas
   */
  constructor(canvas) {
    this.canvas = canvas;
    this.width = canvas.width;
    this.height = canvas.height;
    this.messages = [];
    this.messageTimer = 0;
  }

  /**
   * Add a message to display
   * @param {string} text - Message text
   * @param {string} type - Message type ('info', 'warning', 'damage', 'heal')
   * @param {number} duration - Display duration in ms
   */
  addMessage(text, type = 'info', duration = 2000) {
    this.messages.push({
      text: text,
      type: type,
      duration: duration,
      life: duration
    });
  }

  /**
   * Update UI messages
   * @param {number} deltaTime - Time since last update
   */
  update(deltaTime) {
    this.messages = this.messages.filter(msg => {
      msg.life -= deltaTime;
      return msg.life > 0;
    });
  }

  /**
   * Draw game HUD (player stats, ability cooldown, etc)
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {Player} player - Player reference
   */
  drawHUD(ctx, player) {
    const padding = 20;

    // Character name
    ctx.fillStyle = '#fff';
    ctx.font = 'bold 16px Arial';
    ctx.fillText(player.name, padding, padding + 20);

    // Health bar
    this.drawHealthBar(
      ctx,
      padding,
      padding + 35,
      200,
      20,
      player.hp,
      player.stats.maxHp,
      '#ff4444'
    );

    // HP text
    ctx.fillStyle = '#aaa';
    ctx.font = '12px Arial';
    ctx.fillText(`${Math.ceil(player.hp)} / ${player.stats.maxHp}`, padding + 210, padding + 50);

    // Mana bar
    this.drawHealthBar(
      ctx,
      padding,
      padding + 65,
      200,
      15,
      player.mana,
      player.maxMana,
      '#4488ff'
    );

    // Mana text
    ctx.fillText(`Mana: ${Math.ceil(player.mana)} / ${player.maxMana}`, padding + 210, padding + 75);

    // Ability cooldown
    const abilitySection = this.width - 250;
    ctx.font = 'bold 14px Arial';
    ctx.fillStyle = '#fff';
    ctx.fillText('Ability:', abilitySection, padding + 20);

    const now = Date.now();
    const cooldownRemaining = player.abilityLastUsed + player.activeAbility.cooldown - now;
    const onCooldown = cooldownRemaining > 0;

    if (onCooldown) {
      const cooldownPercent = Math.max(0, cooldownRemaining / player.activeAbility.cooldown);
      ctx.fillStyle = `rgba(255, 100, 100, 0.5)`;
      ctx.fillRect(abilitySection, padding + 30, 150, 30);
      ctx.fillStyle = '#ff4444';
      ctx.fillRect(abilitySection, padding + 30, 150 * cooldownPercent, 30);
      ctx.fillText(
        Math.ceil(cooldownRemaining / 1000) + 's',
        abilitySection + 50,
        padding + 50
      );
    } else {
      ctx.fillStyle = '#4da6ff';
      ctx.fillRect(abilitySection, padding + 30, 150, 30);
      ctx.fillStyle = '#fff';
      ctx.font = '12px Arial';
      ctx.fillText(player.activeAbility.name, abilitySection + 5, padding + 50);
    }

    // Stats
    const statsX = this.width - 250;
    const statsY = padding + 80;
    ctx.font = '11px Arial';
    ctx.fillStyle = '#aaa';

    ctx.fillText(`ATK: ${Math.floor(player.stats.attack)}`, statsX, statsY);
    ctx.fillText(`DEF: ${Math.floor(player.stats.defense)}`, statsX + 80, statsY);
    ctx.fillText(`MAG: ${Math.floor(player.stats.magic)}`, statsX, statsY + 20);
    ctx.fillText(`AGI: ${Math.floor(player.stats.agility)}`, statsX + 80, statsY + 20);
  }

  /**
   * Draw a health/stat bar
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} x - X position
   * @param {number} y - Y position
   * @param {number} width - Bar width
   * @param {number} height - Bar height
   * @param {number} current - Current value
   * @param {number} max - Maximum value
   * @param {string} color - Bar color
   */
  drawHealthBar(ctx, x, y, width, height, current, max, color) {
    const ratio = Math.max(0, Math.min(1, current / max));

    // Background
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
    ctx.fillRect(x, y, width, height);

    // Border
    ctx.strokeStyle = color;
    ctx.lineWidth = 1;
    ctx.strokeRect(x, y, width, height);

    // Fill
    ctx.fillStyle = color;
    ctx.fillRect(x + 1, y + 1, (width - 2) * ratio, height - 2);

    // Highlight
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
    ctx.lineWidth = 1;
    ctx.strokeRect(x + 1, y + 1, (width - 2) * ratio, height - 2);
  }

  /**
   * Draw floating damage numbers from enemies
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {Array} entities - Entities to draw numbers from
   * @param {number} cameraX - Camera x offset
   * @param {number} cameraY - Camera y offset
   */
  drawFloatingNumbers(ctx, entities, cameraX, cameraY) {
    for (const entity of entities) {
      for (const particle of entity.particles) {
        if (particle.damage !== undefined) {
          const screenX = (entity.x - cameraX) + (particle.x - entity.x);
          const screenY = (entity.y - cameraY) + (particle.y - entity.y);

          ctx.fillStyle = '#ff6666';
          ctx.font = 'bold 14px Arial';
          ctx.globalAlpha = particle.life / 600;
          ctx.fillText(Math.ceil(particle.damage), screenX, screenY);
          ctx.globalAlpha = 1;
        }
      }
    }
  }

  /**
   * Draw messages
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   */
  drawMessages(ctx) {
    let yOffset = this.height - 100;

    for (const msg of this.messages) {
      const alpha = msg.life / msg.duration;
      ctx.globalAlpha = alpha;

      let color = '#fff';
      if (msg.type === 'damage') color = '#ff4444';
      if (msg.type === 'heal') color = '#44ff44';
      if (msg.type === 'warning') color = '#ffaa00';

      ctx.fillStyle = color;
      ctx.font = '14px Arial';
      ctx.fillText(msg.text, this.width / 2 - ctx.measureText(msg.text).width / 2, yOffset);

      yOffset -= 25;
    }

    ctx.globalAlpha = 1;
  }

  /**
   * Draw character selection screen
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} selectedIndex - Currently selected character
   */
  drawCharacterSelect(ctx, selectedIndex = 0) {
    // Background
    ctx.fillStyle = 'rgba(10, 10, 20, 0.9)';
    ctx.fillRect(0, 0, this.width, this.height);

    // Title
    ctx.fillStyle = '#4da6ff';
    ctx.font = 'bold 48px Arial';
    ctx.fillText('SELECT YOUR HERO', this.width / 2 - 300, 60);

    // Characters
    const charWidth = (this.width - 100) / CHARACTERS.length;
    const charStartX = 50;
    const charStartY = 150;

    for (let i = 0; i < CHARACTERS.length; i++) {
      const char = CHARACTERS[i];
      const x = charStartX + i * charWidth;
      const y = charStartY;

      // Selection highlight
      if (i === selectedIndex) {
        ctx.fillStyle = '#4da6ff';
        ctx.fillRect(x - 5, y - 5, charWidth - 10, 350);
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#4da6ff';
        ctx.strokeRect(x - 5, y - 5, charWidth - 10, 350);
      }

      // Character box
      ctx.fillStyle = 'rgba(30, 30, 50, 0.8)';
      ctx.fillRect(x, y, charWidth - 20, 340);

      // Character name
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 16px Arial';
      ctx.fillText(char.name, x + 10, y + 30);

      // Stats
      ctx.font = '12px Arial';
      ctx.fillStyle = '#aaa';
      const statY = y + 60;
      ctx.fillText(`ATK: ${char.stats.attack}`, x + 10, statY);
      ctx.fillText(`DEF: ${char.stats.defense}`, x + 10, statY + 20);
      ctx.fillText(`MAG: ${char.stats.magic}`, x + 10, statY + 40);
      ctx.fillText(`AGI: ${char.stats.agility}`, x + 10, statY + 60);

      // Ability
      ctx.font = '11px Arial';
      ctx.fillStyle = '#88ccff';
      ctx.fillText(`Active: ${char.activeAbility.name}`, x + 10, statY + 90);
      ctx.fillText(`Passive: ${char.passiveAbility.name}`, x + 10, statY + 110);
    }

    // Instructions
    ctx.font = '14px Arial';
    ctx.fillStyle = '#888';
    ctx.fillText('← → Use arrow keys to select | ENTER to confirm', this.width / 2 - 250, this.height - 30);
  }

  /**
   * Draw universe selection screen
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} selectedIndex - Currently selected universe
   */
  drawUniverseSelect(ctx, selectedIndex = 0) {
    // Background
    ctx.fillStyle = 'rgba(10, 10, 20, 0.9)';
    ctx.fillRect(0, 0, this.width, this.height);

    // Title
    ctx.fillStyle = '#4da6ff';
    ctx.font = 'bold 48px Arial';
    ctx.fillText('CHOOSE YOUR REALM', this.width / 2 - 300, 60);

    // Universes
    const universeWidth = (this.width - 100) / UNIVERSES.length;
    const universeStartX = 50;
    const universeStartY = 150;

    for (let i = 0; i < UNIVERSES.length; i++) {
      const uni = UNIVERSES[i];
      const x = universeStartX + i * universeWidth;
      const y = universeStartY;

      // Selection highlight
      if (i === selectedIndex) {
        ctx.fillStyle = uni.colors.primary;
        ctx.fillRect(x - 5, y - 5, universeWidth - 10, 340);
        ctx.lineWidth = 3;
        ctx.strokeStyle = uni.colors.primary;
        ctx.strokeRect(x - 5, y - 5, universeWidth - 10, 340);
      }

      // Universe box
      ctx.fillStyle = `rgba(${this.hexToRgb(uni.colors.dark).join(', ')}, 0.8)`;
      ctx.fillRect(x, y, universeWidth - 20, 340);

      // Border
      ctx.strokeStyle = uni.colors.primary;
      ctx.lineWidth = 2;
      ctx.strokeRect(x, y, universeWidth - 20, 340);

      // Universe name
      ctx.fillStyle = uni.colors.primary;
      ctx.font = 'bold 18px Arial';
      ctx.fillText(uni.name, x + 10, y + 30);

      // Description
      ctx.font = '11px Arial';
      ctx.fillStyle = '#aaa';
      const desc = uni.description.substring(0, 35) + '...';
      ctx.fillText(desc, x + 10, y + 60);

      // Enemy types
      ctx.fillStyle = '#88ccff';
      ctx.fillText('Enemies:', x + 10, y + 90);
      ctx.fillStyle = '#aaa';
      ctx.fillText(uni.enemyTypes.join(', '), x + 10, y + 110);
    }

    // Instructions
    ctx.font = '14px Arial';
    ctx.fillStyle = '#888';
    ctx.fillText('← → Use arrow keys to select | ENTER to confirm', this.width / 2 - 250, this.height - 30);
  }

  /**
   * Draw game over / victory screen
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {Object} stats - Final game statistics
   */
  drawGameOverScreen(ctx, stats) {
    // Semi-transparent overlay
    ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
    ctx.fillRect(0, 0, this.width, this.height);

    // Win/Lose
    ctx.font = 'bold 60px Arial';
    if (stats.victory) {
      ctx.fillStyle = '#44ff44';
      ctx.fillText('VICTORY!', this.width / 2 - 150, 100);
    } else {
      ctx.fillStyle = '#ff4444';
      ctx.fillText('DEFEATED', this.width / 2 - 180, 100);
    }

    // Stats
    const statsX = this.width / 2 - 150;
    let statsY = 200;
    ctx.font = 'bold 24px Arial';
    ctx.fillStyle = '#fff';
    ctx.fillText('FINAL STATS', statsX, statsY);

    ctx.font = '18px Arial';
    statsY += 50;

    if (stats.title) {
      ctx.fillStyle = '#ffd700';
      ctx.fillText(`Title: ${stats.title}`, statsX, statsY);
      statsY += 40;
    }

    ctx.fillStyle = '#aaa';
    ctx.fillText(`Enemies Defeated: ${stats.enemiesDefeated}`, statsX, statsY);
    statsY += 30;
    ctx.fillText(`Damage Taken: ${stats.damageTaken}`, statsX, statsY);
    statsY += 30;
    ctx.fillText(`Dungeon Depth: ${stats.dungeonDepth}`, statsX, statsY);
    statsY += 30;
    ctx.fillText(`Time Elapsed: ${Math.floor(stats.timeTaken / 1000)}s`, statsX, statsY);

    // Instructions
    ctx.font = '16px Arial';
    ctx.fillStyle = '#888';
    ctx.fillText('Press SPACE or ENTER to continue', this.width / 2 - 180, this.height - 50);
  }

  /**
   * Convert hex to RGB
   * @param {string} hex - Hex color code
   * @returns {Array} RGB array
   */
  hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? [
      parseInt(result[1], 16),
      parseInt(result[2], 16),
      parseInt(result[3], 16)
    ] : [0, 0, 0];
  }
}
