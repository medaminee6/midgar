/**
 * Game Configuration
 * Easily customize game settings here
 * All values are optional - defaults will be used if not specified
 */

const GAME_CONFIG = {
  // Canvas settings
  canvas: {
    width: 800,
    height: 600,
    backgroundColor: '#1a1a2e'
  },

  // Player settings
  player: {
    baseSpeed: 4,
    invulnerableDuration: 300, // ms
    dodgeCooldown: 1000, // ms
    manaRegenRate: 0.02  // per frame
  },

  // Enemy settings
  enemies: {
    visionRange: 300,        // pixels where enemy detects player
    visionMemory: 5000,      // ms to remember player position
    baseSpeed: 1.5,
    attackCooldown: 1500,    // ms between attacks
    retreatThreshold: 0.3    // retreat when HP < 30%
  },

  // World settings
  world: {
    width: 20,              // tiles
    height: 20,             // tiles
    tileSize: 64,           // pixels
    enemySpawnChance: 0.02, // per tile
    dungeonEntryDistance: 300 // pixels to enter dungeon
  },

  // Dungeon settings
  dungeon: {
    baseRoomCount: 5,
    roomSize: 600,          // pixels
    roomConnectChance: 0.4, // chance for alternate paths
    enemyScaling: 1.15,     // stat multiplier per level
    bossScaling: 1.5        // boss stat multiplier
  },

  // Difficulty settings
  difficulty: {
    enabled: true,
    baseEnemyCount: 3,          // enemies per room initially
    depthEnemyGrowth: 2,        // additional enemies per depth
    playerStartHP: 100,
    playerStartMana: 100
  },

  // UI settings
  ui: {
    fontSize: '16px',
    hudOpacity: 0.8,
    messageDisplayTime: 2000,   // ms
    colorScheme: 'dark'        // 'dark' or 'light'
  },

  // Progression settings
  progression: {
    storageKey: 'midgar_game_profile',
    maxHighscores: 10,
    autoSave: true,
    enableTitles: true
  },

  // Performance settings
  performance: {
    targetFPS: 60,
    maxFrameTime: 33, // ms
    particleLimit: 500,
    enemyUpdateDistance: 1000 // only update enemies within this range
  },

  // Debug settings (set to true to enable)
  debug: {
    showColliders: false,
    showFPS: false,
    showAIStates: false,
    unlimitedMana: false,
    godMode: false,
    instantWin: false
  }
};

/**
 * Quality Presets - Uncomment to use
 */

// PERFORMANCE MODE - Reduced graphical fidelity for older devices
/*
GAME_CONFIG.particle_system = {
  enabled: true,
  maxParticles: 100
};
GAME_CONFIG.performance.targetFPS = 30;
*/

// ULTRA MODE - Maximum visual quality
/*
GAME_CONFIG.particle_system = {
  enabled: true,
  maxParticles: 1000
};
GAME_CONFIG.performance.targetFPS = 120;
*/

// MOBILE MODE - Optimized for mobile browsers
/*
GAME_CONFIG.canvas.width = 600;
GAME_CONFIG.canvas.height = 450;
GAME_CONFIG.performance.targetFPS = 30;
GAME_CONFIG.ui.fontSize = '14px';
*/

/**
 * Difficulty Presets
 */

// EASY MODE
/*
GAME_CONFIG.enemies.visionRange = 200;
GAME_CONFIG.difficulty.baseEnemyCount = 2;
GAME_CONFIG.dungeon.enemyScaling = 1.0;
GAME_CONFIG.player.dodgeCooldown = 500;
*/

// HARD MODE
/*
GAME_CONFIG.enemies.visionRange = 400;
GAME_CONFIG.enemies.attackCooldown = 1000;
GAME_CONFIG.difficulty.baseEnemyCount = 5;
GAME_CONFIG.dungeon.enemyScaling = 1.25;
GAME_CONFIG.dungeon.bossScaling = 2.0;
*/

// HARDCORE MODE (Permadeath, One Life)
/*
GAME_CONFIG.hardcore = {
  enabled: true,
  permaDeath: true,
  noRespec: true,
  enemyScaling: 1.3
};
*/

/**
 * How to use this config:
 * 
 * 1. Change values above to customize your game
 * 2. For presets, uncomment the block you want to use
 * 3. Only one preset should be uncommented at a time
 * 4. Game will use defaults for any values you don't specify
 * 5. No need to reload, changes apply on next game start
 * 
 * Example: To enable debug mode:
 *   GAME_CONFIG.debug.showFPS = true;
 *   Then refresh the page
 */

/**
 * Advanced: Custom Character Stat Multipliers
 * Uncomment and modify to scale all character stats
 */
/*
GAME_CONFIG.characterStatMultiplier = {
  maxHp: 1.2,
  attack: 1.1,
  defense: 1.1,
  magic: 1.1,
  agility: 1.1
};
*/

// Export for module systems (if needed)
if (typeof module !== 'undefined' && module.exports) {
  module.exports = GAME_CONFIG;
}
