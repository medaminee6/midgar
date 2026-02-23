/**
 * MIDGAR DUNGEON CRAWLER - Main Game Engine
 * A fully-featured 2D dungeon crawler inspired by Legend of Zelda
 * with procedural generation, smart enemy AI, and RPG systems
 */

class Game {
  /**
   * Initialize the game
   * @param {HTMLCanvasElement} canvas - Game canvas element
   */
  constructor(canvas) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.width = canvas.width;
    this.height = canvas.height;

    // Game state
    this.gameState = 'menu'; // menu, characterSelect, universeSelect, world, dungeon, gameOver
    this.selectedCharacterIndex = 0;
    this.selectedUniverseIndex = 0;

    // Game objects
    this.player = null;
    this.world = null;
    this.dungeon = null;
    this.enemies = [];
    this.ui = new UI(canvas);

    // Input handling
    this.keys = {};
    this.initInputs();

    // Timing
    this.lastFrameTime = Date.now();
    this.runStartTime = 0;
    this.statsTracking = {
      enemiesDefeated: 0,
      damageTaken: 0,
      timeTaken: 0,
      abilitiesUsed: 0
    };

    // Initialize progression
    Progression.init();

    // Start game loop
    this.gameLoop();
  }

  /**
   * Initialize input handling
   */
  initInputs() {
    document.addEventListener('keydown', (e) => {
      this.keys[e.key.toLowerCase()] = true;

      // Handle special keys
      if (this.gameState === 'characterSelect') {
        if (e.key === 'ArrowLeft') {
          this.selectedCharacterIndex = (this.selectedCharacterIndex - 1 + CHARACTERS.length) % CHARACTERS.length;
        } else if (e.key === 'ArrowRight') {
          this.selectedCharacterIndex = (this.selectedCharacterIndex + 1) % CHARACTERS.length;
        } else if (e.key === 'Enter') {
          this.startUniverseSelect();
        }
      } else if (this.gameState === 'universeSelect') {
        if (e.key === 'ArrowLeft') {
          this.selectedUniverseIndex = (this.selectedUniverseIndex - 1 + UNIVERSES.length) % UNIVERSES.length;
        } else if (e.key === 'ArrowRight') {
          this.selectedUniverseIndex = (this.selectedUniverseIndex + 1) % UNIVERSES.length;
        } else if (e.key === 'Enter') {
          this.startGame();
        }
      } else if (this.gameState === 'gameOver') {
        if (e.key === ' ' || e.key === 'Enter') {
          this.goToMenu();
        }
      }
    });

    document.addEventListener('keyup', (e) => {
      this.keys[e.key.toLowerCase()] = false;
    });
  }

  /**
   * Start character selection
   */
  startCharacterSelect() {
    this.gameState = 'characterSelect';
    this.selectedCharacterIndex = 0;
  }

  /**
   * Start universe selection
   */
  startUniverseSelect() {
    this.gameState = 'universeSelect';
    this.selectedUniverseIndex = 0;
  }

  /**
   * Start the actual game
   */
  startGame() {
    const character = CHARACTERS[this.selectedCharacterIndex];
    const universe = UNIVERSES[this.selectedUniverseIndex];

    // Create world
    this.world = new World(20, 20, 64, universe);

    // Create player at world center
    const worldCenterX = (this.world.width * this.world.tileSize) / 2;
    const worldCenterY = (this.world.height * this.world.tileSize) / 2;
    this.player = new Player(character, worldCenterX, worldCenterY);

    // Initialize enemies in world
    this.enemies = [];
    for (const spawn of this.world.enemySpawns) {
      const enemyType = spawn.enemyType;
      const template = ENEMY_TEMPLATES[enemyType];
      if (template) {
        const enemy = new Enemy(template, spawn.x, spawn.y, template.behavior || 'standard');
        this.enemies.push(enemy);
      }
    }

    // Reset stats tracking
    this.statsTracking = {
      enemiesDefeated: 0,
      damageTaken: 0,
      timeTaken: 0,
      abilitiesUsed: 0
    };

    this.runStartTime = Date.now();
    this.gameState = 'world';
  }

  /**
   * Transition to dungeon
   */
  enterDungeon() {
    const universe = UNIVERSES[this.selectedUniverseIndex];
    this.dungeon = new Dungeon(1, universe, this.player);

    // Spawn player in dungeon
    const spawnPos = this.dungeon.getPlayerSpawnPosition();
    this.player.x = spawnPos.x;
    this.player.y = spawnPos.y;

    // Spawn enemies from current room
    this.enemies = [...this.dungeon.getCurrentRoom().enemies];
    if (this.dungeon.getCurrentRoom().boss) {
      this.enemies.push(this.dungeon.getCurrentRoom().boss);
    }

    this.gameState = 'dungeon';
  }

  /**
   * Transition to next dungeon room
   */
  nextDungeonRoom() {
    const currentRoom = this.dungeon.getCurrentRoom();

    // Mark room as cleared
    this.dungeon.clearRoom();

    // Move to next room
    if (!this.dungeon.nextRoom()) {
      // Dungeon complete
      this.endRun(true);
      return;
    }

    // Reset enemies
    const newRoom = this.dungeon.getCurrentRoom();
    this.enemies = [...newRoom.enemies];
    if (newRoom.boss) {
      this.enemies.push(newRoom.boss);
    }

    // Reset player position
    const spawnPos = this.dungeon.getPlayerSpawnPosition();
    this.player.x = spawnPos.x;
    this.player.y = spawnPos.y;
  }

  /**
   * End the current run
   * @param {boolean} victory - Whether the run was a victory
   */
  endRun(victory) {
    this.statsTracking.timeTaken = Date.now() - this.runStartTime;
    this.statsTracking.damageTaken = Math.floor(
      (this.player.stats.maxHp - this.player.hp)
    );

    if (this.dungeon) {
      this.statsTracking.dungeonDepth = this.dungeon.depth * (this.dungeon.currentRoomIndex + 1);
    }

    Progression.recordRun(this.player, this.statsTracking, victory);

    this.gameOverStats = {
      victory: victory,
      character: this.player.name,
      ...this.statsTracking
    };

    this.gameState = 'gameOver';
  }

  /**
   * Return to main menu
   */
  goToMenu() {
    this.gameState = 'menu';
  }

  /**
   * Main game loop update
   */
  update() {
    const currentTime = Date.now();
    const deltaTime = Math.min(currentTime - this.lastFrameTime, 33); // Cap at 30 FPS
    this.lastFrameTime = currentTime;

    switch (this.gameState) {
      case 'world':
        this.updateWorld(deltaTime);
        break;
      case 'dungeon':
        this.updateDungeon(deltaTime);
        break;
      case 'menu':
      case 'characterSelect':
      case 'universeSelect':
      case 'gameOver':
        this.ui.update(deltaTime);
        break;
    }
  }

  /**
   * Update world state
   * @param {number} deltaTime - Time since last update
   */
  updateWorld(deltaTime) {
    // Handle player movement
    this.player.vx = 0;
    this.player.vy = 0;

    if (this.keys['arrowup'] || this.keys['w']) {
      this.player.move('up');
    }
    if (this.keys['arrowdown'] || this.keys['s']) {
      this.player.move('down');
    }
    if (this.keys['arrowleft'] || this.keys['a']) {
      this.player.move('left');
    }
    if (this.keys['arrowright'] || this.keys['d']) {
      this.player.move('right');
    }

    // Handle dodge
    if (this.keys[' ']) {
      this.player.dodge();
      this.keys[' '] = false;
    }

    // Handle attack/ability
    if (this.keys['e'] || this.keys['q']) {
      const result = this.player.useAbility(
        this.player.x, this.player.y, this.enemies
      );
      
      if (result) {
        this.statsTracking.abilitiesUsed++;
        
        // Apply damage to nearby enemies
        const abilityRange = result.range || 80;
        for (const enemy of this.enemies) {
          const dist = Utils.distance(
            enemy.x, enemy.y,
            this.player.x, this.player.y
          );
          if (dist < abilityRange) {
            const damage = Utils.calculateDamage(
              result.damage * this.player.stats.attack,
              this.player.stats.magic,
              enemy.stats.defense
            );
            enemy.takeDamage(damage, this.player);
          }
        }
      }
      this.keys['e'] = false;
      this.keys['q'] = false;
    }
    if (Utils.circleCollide(
      this.player.x, this.player.y, this.player.size,
      this.world.dungeonEntrance.x, this.world.dungeonEntrance.y, this.world.dungeonEntrance.size
    )) {
      this.ui.addMessage('Enter Dungeon? (SPACE)', 'info', 1000);
      if (this.keys['enter']) {
        this.enterDungeon();
        this.keys['enter'] = false;
        return;
      }
    }

    // Update player
    this.player.update(deltaTime, this.world.obstacles);

    // Update enemies
    for (const enemy of this.enemies) {
      enemy.update(this.player, this.enemies, this.world.obstacles, deltaTime);

      // Enemy attacks player
      if (Utils.circleCollide(
        this.player.x, this.player.y, this.player.size,
        enemy.x, enemy.y, enemy.size + 20
      )) {
        enemy.attackPlayer(this.player);
      }
    }

    // Remove dead enemies
    this.enemies = this.enemies.filter(e => {
      if (!e.isAlive()) {
        this.statsTracking.enemiesDefeated++;
      }
      return e.isAlive();
    });

    // Check if player died
    if (this.player.hp <= 0) {
      this.endRun(false);
    }

    this.ui.update(deltaTime);
  }

  /**
   * Update dungeon state
   * @param {number} deltaTime - Time since last update
   */
  updateDungeon(deltaTime) {
    const currentRoom = this.dungeon.getCurrentRoom();

    // Handle player movement
    this.player.vx = 0;
    this.player.vy = 0;

    if (this.keys['arrowup'] || this.keys['w']) this.player.move('up');
    if (this.keys['arrowdown'] || this.keys['s']) this.player.move('down');
    if (this.keys['arrowleft'] || this.keys['a']) this.player.move('left');
    if (this.keys['arrowright'] || this.keys['d']) this.player.move('right');

    // Handle dodge
    if (this.keys[' ']) {
      this.player.dodge();
      this.keys[' '] = false;
    }

    // Handle ability
    if (this.keys['e'] || this.keys['q']) {
      const result = this.player.useAbility(
        this.player.x, this.player.y, this.enemies
      );
      
      if (result) {
        this.statsTracking.abilitiesUsed++;
        
        // Apply damage to nearby enemies
        const abilityRange = result.range || 80;
        for (const enemy of this.enemies) {
          const dist = Utils.distance(
            enemy.x, enemy.y,
            this.player.x, this.player.y
          );
          if (dist < abilityRange) {
            const damage = Utils.calculateDamage(
              result.damage * this.player.stats.attack,
              this.player.stats.magic,
              enemy.stats.defense
            );
            enemy.takeDamage(damage, this.player);
          }
        }
      }
      this.keys['e'] = false;
      this.keys['q'] = false;
    }

    // Update player with room boundaries
    const roomCollisionBox = {
      x: currentRoom.x,
      y: currentRoom.y,
      width: currentRoom.width,
      height: currentRoom.height,
      size: 0
    };

    this.player.update(deltaTime, [roomCollisionBox]);

    // Update enemies
    for (const enemy of this.enemies) {
      enemy.update(this.player, this.enemies, [], deltaTime);

      // Enemy attacks player
      if (Utils.circleCollide(
        this.player.x, this.player.y, this.player.size,
        enemy.x, enemy.y, enemy.size + 20
      )) {
        enemy.attackPlayer(this.player);
      }
    }

    // Remove dead enemies
    const livingEnemies = this.enemies.filter(e => {
      if (!e.isAlive()) {
        this.statsTracking.enemiesDefeated++;
      }
      return e.isAlive();
    });
    this.enemies = livingEnemies;

    // Check if room is cleared
    if (currentRoom.enemies.length === 0 && currentRoom.boss === null) {
      // Can exit
      if (this.keys['e']) {
        this.nextDungeonRoom();
        this.keys['e'] = false;
        return;
      }
    } else if (currentRoom.enemies.every(e => !e.isAlive()) &&
               (!currentRoom.boss || !currentRoom.boss.isAlive())) {
      // Room cleared
      if (this.dungeon.isComplete()) {
        this.endRun(true);
        return;
      }

      if (this.keys['e']) {
        this.nextDungeonRoom();
        this.keys['e'] = false;
        return;
      }
    }

    // Check if player died
    if (this.player.hp <= 0) {
      this.endRun(false);
    }

    this.ui.update(deltaTime);
  }

  /**
   * Render game
   */
  render() {
    // Clear canvas
    this.ctx.clearRect(0, 0, this.width, this.height);

    switch (this.gameState) {
      case 'menu':
        this.renderMenu();
        break;
      case 'characterSelect':
        this.ui.drawCharacterSelect(this.ctx, this.selectedCharacterIndex);
        break;
      case 'universeSelect':
        this.ui.drawUniverseSelect(this.ctx, this.selectedUniverseIndex);
        break;
      case 'world':
        this.renderWorld();
        break;
      case 'dungeon':
        this.renderDungeon();
        break;
      case 'gameOver':
        this.ui.drawGameOverScreen(this.ctx, this.gameOverStats);
        break;
    }
  }

  /**
   * Render main menu
   */
  renderMenu() {
    // Background
    this.ctx.fillStyle = 'rgba(10, 10, 20, 0.95)';
    this.ctx.fillRect(0, 0, this.width, this.height);

    // Title
    this.ctx.fillStyle = '#4da6ff';
    this.ctx.font = 'bold 80px Arial';
    this.ctx.fillText('MIDGAR', this.width / 2 - 200, 120);

    this.ctx.font = '24px Arial';
    this.ctx.fillText('DUNGEON CRAWLER', this.width / 2 - 150, 160);

    // Subtitle
    this.ctx.font = '18px Arial';
    this.ctx.fillStyle = '#aaa';
    this.ctx.fillText('A legendary adventure awaits...', this.width / 2 - 180, 220);

    // Menu options
    const menuX = this.width / 2;
    const menuY = 320;
    this.ctx.font = 'bold 28px Arial';
    this.ctx.fillStyle = '#4da6ff';

    this.ctx.fillText('PRESS ENTER TO BEGIN', menuX - 230, menuY);

    // Statistics
    const stats = Progression.getStatisticsSummary();
    this.ctx.font = '14px Arial';
    this.ctx.fillStyle = '#888';
    let infoY = this.height - 180;

    this.ctx.fillText(`Total Runs: ${stats.totalRuns}`, 30, infoY);
    infoY += 25;
    this.ctx.fillText(`Victories: ${stats.victoryCount}`, 30, infoY);
    infoY += 25;
    this.ctx.fillText(`Win Rate: ${stats.winRate}%`, 30, infoY);
    infoY += 25;
    this.ctx.fillText(`Enemies Defeated: ${stats.totalEnemiesDefeated}`, 30, infoY);
    infoY += 25;
    this.ctx.fillText(`Max Depth: ${stats.maxDepthReached}`, 30, infoY);
    infoY += 25;
    this.ctx.fillText(`Titles Earned: ${stats.titlesEarned}`, 30, infoY);

    // Handle input
    if (this.keys['enter']) {
      this.startCharacterSelect();
      this.keys['enter'] = false;
    }
  }

  /**
   * Render world
   */
  renderWorld() {
    // Camera follow player
    const cameraX = this.player.x - this.width / 2;
    const cameraY = this.player.y - this.height / 2;

    // Draw world
    this.world.draw(this.ctx, cameraX, cameraY, this.width, this.height);

    // Draw enemies
    for (const enemy of this.enemies) {
      enemy.draw(this.ctx, cameraX, cameraY);
    }

    // Draw player
    this.player.draw(this.ctx, cameraX, cameraY);

    // Draw HUD
    this.ui.drawHUD(this.ctx, this.player);
    this.ui.drawMessages(this.ctx);
  }

  /**
   * Render dungeon
   */
  renderDungeon() {
    this.dungeon.draw(this.ctx, this.width, this.height);

    // Draw enemies
    for (const enemy of this.enemies) {
      enemy.draw(this.ctx, 0, 0);
    }

    // Draw player
    this.player.draw(this.ctx, 0, 0);

    // Draw HUD
    this.ui.drawHUD(this.ctx, this.player);
    this.ui.drawMessages(this.ctx);

    // Draw room info
    const room = this.dungeon.getCurrentRoom();
    if (this.dungeon.isRoomCleared()) {
      this.ctx.fillStyle = 'rgba(0, 255, 0, 0.5)';
      this.ctx.font = 'bold 20px Arial';
      this.ctx.fillText('ROOM CLEARED - Press E to proceed', 150, 60);
    }
  }

  /**
   * Main game loop
   */
  gameLoop = () => {
    this.update();
    this.render();
    requestAnimationFrame(this.gameLoop);
  }
}

// Initialize game on window load
window.addEventListener('DOMContentLoaded', () => {
  const canvas = document.getElementById('gameCanvas');
  if (canvas) {
    const game = new Game(canvas);
  }
});
