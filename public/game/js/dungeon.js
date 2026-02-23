/**
 * Dungeon Generation - Procedural room-based dungeon layout
 */

class Dungeon {
  /**
   * Create a new procedural dungeon
   * @param {number} depth - Dungeon depth/difficulty level
   * @param {Object} universe - Universe theme for styling
   * @param {Object} player - Player for stat scaling
   * @param {number} seed - Random seed
   */
  constructor(depth = 1, universe = UNIVERSES[0], player = null, seed = 0) {
    this.depth = depth;
    this.universe = universe;
    this.player = player;
    this.seed = seed;

    // Room system
    this.rooms = [];
    this.currentRoomIndex = 0;
    this.roomSize = 600;
    this.roomConnections = [];

    // Difficulty scaling
    this.enemyCount = 3 + depth * 2;
    this.bossIndex = -1;

    // Generate dungeon
    this.generateRooms();
    this.populateRooms();
  }

  /**
   * Generate room layout using graph connectivity
   */
  generateRooms() {
    const roomCount = 5 + this.depth;

    // Create rooms
    for (let i = 0; i < roomCount; i++) {
      this.rooms.push({
        index: i,
        x: (i % 3) * this.roomSize,
        y: Math.floor(i / 3) * this.roomSize,
        width: this.roomSize,
        height: this.roomSize,
        type: this.getRoomType(i, roomCount),
        enemies: [],
        boss: null,
        treasure: null,
        visited: false,
        cleared: false,
        connections: []
      });
    }

    // Connect rooms to form graph
    // First, create a chain
    for (let i = 0; i < roomCount - 1; i++) {
      this.rooms[i].connections.push(i + 1);
      this.rooms[i + 1].connections.push(i);
    }

    // Add random connections for alternate paths
    for (let i = 0; i < roomCount - 2; i++) {
      if (Math.random() < 0.4) {
        const targetRoom = Utils.randInt(i + 2, Math.min(i + 4, roomCount - 1));
        if (!this.rooms[i].connections.includes(targetRoom)) {
          this.rooms[i].connections.push(targetRoom);
          this.rooms[targetRoom].connections.push(i);
        }
      }
    }

    // Last room is always the boss room
    this.bossIndex = roomCount - 1;
    this.rooms[this.bossIndex].type = 'boss';
  }

  /**
   * Determine room type based on position
   * @param {number} index - Room index
   * @param {number} total - Total rooms
   * @returns {string} Room type
   */
  getRoomType(index, total) {
    if (index === 0) return 'entrance';
    if (index === total - 1) return 'boss';

    const types = ['combat', 'trap', 'treasure', 'rest'];
    return Utils.choice(types);
  }

  /**
   * Populate rooms with enemies and items
   */
  populateRooms() {
    for (let i = 0; i < this.rooms.length; i++) {
      const room = this.rooms[i];

      if (room.type === 'entrance') {
        // Safe room
        continue;
      } else if (room.type === 'boss') {
        // Boss room
        const bossTemplate = Utils.choice(Object.values(BOSS_TEMPLATES));
        const boss = new Boss(bossTemplate, room.x + room.width / 2, room.y + room.height / 2);
        
        // Scale boss stats based on depth
        const scaling = 1 + (this.depth * 0.2);
        boss.stats.maxHp *= scaling;
        boss.hp = boss.stats.maxHp;
        boss.stats.attack *= scaling;
        boss.stats.defense *= scaling;
        
        room.boss = boss;
        room.treasure = { type: 'boss_reward', value: 500 * this.depth };
      } else if (room.type === 'rest') {
        // Healing station
        room.treasure = { type: 'healing_station', value: 50 };
      } else if (room.type === 'treasure') {
        // Treasure room
        room.treasure = { type: 'gold', value: 200 * this.depth };
        // Fewer enemies
        const enemyCount = Math.max(1, Math.floor(this.enemyCount / 2));
        this.spawnRoomEnemies(room, enemyCount);
      } else if (room.type === 'trap') {
        // Trap room with fewer but stronger enemies
        const enemyCount = Math.max(2, Math.floor(this.enemyCount * 0.7));
        this.spawnRoomEnemies(room, enemyCount);
      } else {
        // Regular combat room
        this.spawnRoomEnemies(room, this.enemyCount);
      }
    }
  }

  /**
   * Spawn enemies in a room
   * @param {Object} room - Room object
   * @param {number} count - Enemy count
   */
  spawnRoomEnemies(room, count) {
    for (let i = 0; i < count; i++) {
      const enemyType = Utils.choice(this.universe.enemyTypes);
      const template = ENEMY_TEMPLATES[enemyType];

      if (template) {
        // Random position in room
        const x = room.x + Utils.randInt(50, room.width - 50);
        const y = room.y + Utils.randInt(50, room.height - 50);

        const enemy = new Enemy(template, x, y, template.behavior);

        // Scale enemy stats based on depth
        const scaling = 1 + (this.depth * 0.15);
        enemy.stats.maxHp *= scaling;
        enemy.hp = enemy.stats.maxHp;
        enemy.stats.attack *= scaling;
        enemy.stats.defense *= scaling;

        room.enemies.push(enemy);
      }
    }
  }

  /**
   * Get current room
   * @returns {Object} Current room object
   */
  getCurrentRoom() {
    return this.rooms[this.currentRoomIndex];
  }

  /**
   * Check if current room is cleared
   * @returns {boolean}
   */
  isRoomCleared() {
    const room = this.getCurrentRoom();
    if (room.type === 'entrance') return true;
    if (room.type === 'rest') return true;

    // Check if all enemies dead
    return room.boss === null || room.boss.hp <= 0;
  }

  /**
   * Mark room as cleared
   */
  clearRoom() {
    const room = this.getCurrentRoom();
    room.cleared = true;
  }

  /**
   * Move to next room
   * @returns {boolean} Whether movement was successful
   */
  nextRoom() {
    if (!this.isRoomCleared()) return false;

    const room = this.getCurrentRoom();
    if (room.connections.length === 0) return false;

    // For now, move to first available connection
    const nextIndex = room.connections[0];
    this.currentRoomIndex = nextIndex;
    this.rooms[this.currentRoomIndex].visited = true;
    return true;
  }

  /**
   * Check if dungeon is complete
   * @returns {boolean}
   */
  isComplete() {
    return this.currentRoomIndex === this.bossIndex && this.rooms[this.bossIndex].boss.hp <= 0;
  }

  /**
   * Get spawn position for player in room
   * @returns {Object} {x, y} position
   */
  getPlayerSpawnPosition() {
    const room = this.getCurrentRoom();
    return {
      x: room.x + 50,
      y: room.y + room.height / 2
    };
  }

  /**
   * Get all room connections and compute accessible rooms
   * @returns {Array} Indices of accessible rooms
   */
  getAccessibleRooms() {
    const accessible = new Set();
    const queue = [this.currentRoomIndex];
    accessible.add(this.currentRoomIndex);

    while (queue.length > 0) {
      const index = queue.shift();
      const room = this.rooms[index];

      for (const nextIndex of room.connections) {
        if (!accessible.has(nextIndex)) {
          accessible.add(nextIndex);
          queue.push(nextIndex);
        }
      }
    }

    return Array.from(accessible);
  }

  /**
   * Draw the current room
   * @param {CanvasRenderingContext2D} ctx - Canvas context
   * @param {number} viewWidth - Viewport width
   * @param {number} viewHeight - Viewport height
   */
  draw(ctx, viewWidth, viewHeight) {
    const room = this.getCurrentRoom();

    // Background
    ctx.fillStyle = 'rgba(20, 20, 40, 0.8)';
    ctx.fillRect(0, 0, viewWidth, viewHeight);

    // Room floor
    let floorColor = '#2a2a4a';
    if (room.type === 'boss') {
      floorColor = '#4a1a1a';
    } else if (room.type === 'rest') {
      floorColor = '#2a4a2a';
    } else if (room.type === 'treasure') {
      floorColor = '#4a4a2a';
    }

    ctx.fillStyle = floorColor;
    ctx.fillRect(10, 10, viewWidth - 20, viewHeight - 20);

    // Room borders (walls)
    ctx.strokeStyle = '#666';
    ctx.lineWidth = 3;
    ctx.strokeRect(10, 10, viewWidth - 20, viewHeight - 20);

    // Room type indicator
    ctx.fillStyle = '#aaa';
    ctx.font = '14px Arial';
    ctx.fillText(`${room.type.toUpperCase()} - Level ${this.depth}`, 20, 30);

    // Connections indicator
    if (room.connections.length > 0) {
      ctx.fillStyle = '#888';
      ctx.fillText(`Exits: ${room.connections.length}`, viewWidth - 150, 30);
    }
  }
}
