/**
 * World Generation - Procedural overworld generation
 */

class World {
  /**
   * Create a new overworld
   * @param {number} width - World width in tiles
   * @param {number} height - World height in tiles
   * @param {number} tileSize - Size of each tile in pixels
   * @param {Object} universe - Universe theme data
   * @param {number} seed - Random seed for generation
   */
  constructor(width, height, tileSize, universe, seed = 0) {
    this.width = width;
    this.height = height;
    this.tileSize = tileSize;
    this.universe = universe;
    this.seed = seed;

    // Tile map
    this.tiles = [];
    this.obstacles = [];
    this.enemySpawns = [];
    this.dungeonEntrance = null;

    // Generate the world
    this.generateTerrain();
    this.generateFeatures();
  }

  /**
   * Generate base terrain using noise
   */
  generateTerrain() {
    this.tiles = [];

    for (let y = 0; y < this.height; y++) {
      const row = [];
      for (let x = 0; x < this.width; x++) {
        const noise1 = Utils.smoothNoise(x * 20, y * 20, 50);
        const noise2 = Utils.smoothNoise(x * 50, y * 50, 150);
        const combined = noise1 * 0.6 + noise2 * 0.4;

        let tileType;
        if (combined < 0.3) {
          tileType = this.universe.tileTypes[0]; // grass
        } else if (combined < 0.6) {
          tileType = this.universe.tileTypes[1]; // forest/secondary
        } else if (combined < 0.8) {
          tileType = this.universe.tileTypes[2]; // water/special
        } else {
          tileType = this.universe.tileTypes[3]; // stone
        }

        row.push({
          type: tileType,
          x: x * this.tileSize,
          y: y * this.tileSize,
          walkable: tileType !== 'water'
        });
      }
      this.tiles.push(row);
    }
  }

  /**
   * Generate world features - obstacles, spawns, dungeon entance
   */
  generateFeatures() {
    // Place dungeon entrance (bottom right area)
    const dungeonX = Math.floor(this.width * 0.8) + Utils.randInt(-5, 5);
    const dungeonY = Math.floor(this.height * 0.8) + Utils.randInt(-5, 5);
    this.dungeonEntrance = {
      x: dungeonX * this.tileSize + this.tileSize / 2,
      y: dungeonY * this.tileSize + this.tileSize / 2,
      size: 20
    };

    // Add obstacles (trees, rocks)
    for (let y = 0; y < this.height; y++) {
      for (let x = 0; x < this.width; x++) {
        const tile = this.tiles[y][x];
        
        // Create obstacles based on tile type
        if (tile.type === 'water') {
          this.obstacles.push({
            x: tile.x + this.tileSize / 2,
            y: tile.y + this.tileSize / 2,
            size: this.tileSize / 2,
            type: 'water'
          });
        } else if ((tile.type === 'forest' || tile.type === 'dark_water') && Math.random() < 0.3) {
          this.obstacles.push({
            x: tile.x + this.tileSize / 2,
            y: tile.y + this.tileSize / 2,
            size: this.tileSize / 2,
            type: 'obstacle'
          });
        }
      }
    }

    // Place enemy spawns
    const spawnCount = Math.floor(this.width * this.height / 50);
    for (let i = 0; i < spawnCount; i++) {
      const x = Utils.randInt(0, this.width - 1);
      const y = Utils.randInt(0, this.height - 1);
      const tile = this.tiles[y][x];

      if (tile.walkable) {
        // Don't spawn near dungeon entrance
        const distToDungeon = Utils.distance(
          tile.x + this.tileSize / 2,
          tile.y + this.tileSize / 2,
          this.dungeonEntrance.x,
          this.dungeonEntrance.y
        );

        if (distToDungeon > 300) {
          this.enemySpawns.push({
            x: tile.x + this.tileSize / 2,
            y: tile.y + this.tileSize / 2,
            enemyType: Utils.choice(this.universe.enemyTypes)
          });
        }
      }
    }
  }

  /**
   * Get tile at position
   * @param {number} px - Pixel x coordinate
   * @param {number} py - Pixel y coordinate
   * @returns {Object|null} Tile object or null
   */
  getTileAtPixel(px, py) {
    const tx = Math.floor(px / this.tileSize);
    const ty = Math.floor(py / this.tileSize);

    if (tx < 0 || tx >= this.width || ty < 0 || ty >= this.height) {
      return null;
    }

    return this.tiles[ty][tx];
  }

  /**
   * Check if position is walkable
   * @param {number} x - X coordinate
   * @param {number} y - Y coordinate
   * @param {number} radius - Collision radius
   * @returns {boolean} Whether position is walkable
   */
  isWalkable(x, y, radius = 0) {
    const tile = this.getTileAtPixel(x, y);
    if (!tile || !tile.walkable) return false;

    // Check corners if radius provided
    if (radius > 0) {
      const checks = [
        [x - radius, y - radius],
        [x + radius, y - radius],
        [x - radius, y + radius],
        [x + radius, y + radius]
      ];

      for (const [cx, cy] of checks) {
        const checkTile = this.getTileAtPixel(cx, cy);
        if (!checkTile || !checkTile.walkable) return false;
      }
    }

    return true;
  }

  /**
   * Get tile color for rendering
   * @param {string} tileType - Type of tile
   * @returns {string} Color hex code
   */
  getTileColor(tileType) {
    const colorMap = {
      'grass': '#3a7939',
      'forest': '#2d5016',
      'water': '#1e3a8a',
      'stone': '#6b7280',
      'snow': '#f0f0f0',
      'crystal': '#87ceeb',
      'ice': '#b0e0f6',
      'swamp': '#5a4a3a',
      'mud': '#4a3a2a',
      'dark_water': '#2d1b4e',
      'bones': '#a0a090',
      'sand': '#f4d03f',
      'temple': '#d4af37',
      'holy_ground': '#ffd700',
      'light': '#ffff99'
    };
    return colorMap[tileType] || '#666';
  }

  /**
   * Draw the world
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} cameraX - Camera x offset
   * @param {number} cameraY - Camera y offset
   * @param {number} viewWidth - Viewport width
   * @param {number} viewHeight - Viewport height
   */
  draw(ctx, cameraX, cameraY, viewWidth, viewHeight) {
    // Calculate visible range
    const startX = Math.max(0, Math.floor(cameraX / this.tileSize) - 1);
    const startY = Math.max(0, Math.floor(cameraY / this.tileSize) - 1);
    const endX = Math.min(this.width, Math.ceil((cameraX + viewWidth) / this.tileSize) + 1);
    const endY = Math.min(this.height, Math.ceil((cameraY + viewHeight) / this.tileSize) + 1);

    // Draw tiles
    for (let y = startY; y < endY; y++) {
      for (let x = startX; x < endX; x++) {
        const tile = this.tiles[y][x];
        const screenX = tile.x - cameraX;
        const screenY = tile.y - cameraY;

        // Draw tile
        ctx.fillStyle = this.getTileColor(tile.type);
        ctx.fillRect(screenX, screenY, this.tileSize, this.tileSize);

        // Draw tile border
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
        ctx.lineWidth = 0.5;
        ctx.strokeRect(screenX, screenY, this.tileSize, this.tileSize);
      }
    }

    // Draw obstacles
    for (const obstacle of this.obstacles) {
      const screenX = obstacle.x - cameraX;
      const screenY = obstacle.y - cameraY;

      if (obstacle.type === 'water') {
        ctx.fillStyle = 'rgba(30, 58, 138, 0.6)';
      } else {
        ctx.fillStyle = 'rgba(45, 80, 22, 0.5)';
      }

      Utils.drawCircle(ctx, screenX, screenY, obstacle.size, ctx.fillStyle);
    }

    // Draw dungeon entrance
    const dungeonScreenX = this.dungeonEntrance.x - cameraX;
    const dungeonScreenY = this.dungeonEntrance.y - cameraY;

    // Portal effect
    ctx.fillStyle = 'rgba(139, 0, 139, 0.3)';
    Utils.drawCircle(ctx, dungeonScreenX, dungeonScreenY, this.dungeonEntrance.size + 5, ctx.fillStyle);

    ctx.fillStyle = '#8b008b';
    Utils.drawCircle(ctx, dungeonScreenX, dungeonScreenY, this.dungeonEntrance.size, '#8b008b');

    // Portal animation
    ctx.strokeStyle = '#ff00ff';
    ctx.lineWidth = 2;
    const pulseSize = this.dungeonEntrance.size * 0.7;
    ctx.beginPath();
    ctx.arc(dungeonScreenX, dungeonScreenY, pulseSize, 0, Math.PI * 2);
    ctx.stroke();
  }
}
