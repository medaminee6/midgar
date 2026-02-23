/**
 * Game Data - Characters and Universe definitions
 * Stores all static game data including character stats and universe themes
 */

const CHARACTERS = [
  {
    id: 'warrior',
    name: 'Kael the Warrior',
    universe: 'aethermoor',
    portrait: 'kael.png',
    description: 'A mighty warrior with high attack and defense',
    stats: {
      maxHp: 120,
      attack: 18,
      defense: 16,
      magic: 6,
      agility: 10
    },
    passiveAbility: {
      name: 'Iron Skin',
      description: 'Reduce damage by 10%',
      effect: (damage) => damage * 0.9
    },
    activeAbility: {
      name: 'Whirlwind Slash',
      description: 'Deal 2.5x damage in 200ms radius',
      cooldown: 6000,
      damage: 2.5,
      range: 200,
      cost: 15
    }
  },
  {
    id: 'mage',
    name: 'Lyra the Mage',
    universe: 'crystalholm',
    portrait: 'lyra.png',
    description: 'A powerful mage with high magic and low defense',
    stats: {
      maxHp: 80,
      attack: 8,
      defense: 8,
      magic: 20,
      agility: 14
    },
    passiveAbility: {
      name: 'Mana Shield',
      description: 'Convert 20% spell damage to mana cost',
      effect: (damage) => damage * 0.8
    },
    activeAbility: {
      name: 'Fireball',
      description: 'Deal 3x magic damage to enemies in 250px radius',
      cooldown: 5000,
      damage: 3,
      range: 250,
      cost: 25,
      useMagic: true
    }
  },
  {
    id: 'rogue',
    name: 'Vex the Rogue',
    universe: 'shadowfen',
    portrait: 'vex.png',
    description: 'A swift rogue with high agility and dodge chance',
    stats: {
      maxHp: 90,
      attack: 14,
      defense: 10,
      magic: 10,
      agility: 18
    },
    passiveAbility: {
      name: 'Evasion',
      description: 'Increase dodge chance by 15%',
      effect: (damage) => damage
    },
    activeAbility: {
      name: 'Shadow Strike',
      description: 'Dash forward and deal 2x damage to first enemy hit',
      cooldown: 4000,
      damage: 2,
      range: 300,
      cost: 10
    }
  },
  {
    id: 'paladin',
    name: 'Aldric the Paladin',
    universe: 'holylands',
    portrait: 'aldric.png',
    description: 'A balanced hero with support abilities',
    stats: {
      maxHp: 100,
      attack: 12,
      defense: 14,
      magic: 12,
      agility: 12
    },
    passiveAbility: {
      name: 'Holy Protection',
      description: 'Restore 5 HP every 3 seconds',
      effect: (damage) => damage
    },
    activeAbility: {
      name: 'Divine Light',
      description: 'Heal self for 40 HP and damage enemies in 200px for 1.5x',
      cooldown: 8000,
      damage: 1.5,
      range: 200,
      cost: 20,
      healing: 40
    }
  }
];

const UNIVERSES = [
  {
    id: 'aethermoor',
    name: 'Aethermoor',
    description: 'A realm of mystical forests and ancient ruins',
    colors: {
      primary: '#2d5016',
      secondary: '#5a9d3a',
      accent: '#f4a460',
      dark: '#1a2f0a'
    },
    tileTypes: ['grass', 'forest', 'water', 'stone'],
    enemyTypes: ['goblin', 'wolf', 'treant'],
    dungeonStyle: 'ancient',
    backgroundColor: '#1a3a1a'
  },
  {
    id: 'crystalholm',
    name: 'Crystalholm',
    description: 'Mountains of shimmering crystals and magical energy',
    colors: {
      primary: '#1e3a8a',
      secondary: '#3b82f6',
      accent: '#60a5fa',
      dark: '#0f172a'
    },
    tileTypes: ['snow', 'crystal', 'ice', 'stone'],
    enemyTypes: ['ice_elemental', 'frost_spider', 'crystal_golem'],
    dungeonStyle: 'crystal',
    backgroundColor: '#0f2744'
  },
  {
    id: 'shadowfen',
    name: 'Shadowfen',
    description: 'A dark swamp shrouded in perpetual twilight',
    colors: {
      primary: '#2d1b4e',
      secondary: '#7c3aed',
      accent: '#a78bfa',
      dark: '#1f0f3d'
    },
    tileTypes: ['swamp', 'mud', 'dark_water', 'bones'],
    enemyTypes: ['shadow_beast', 'poisonous_spider', 'spectre'],
    dungeonStyle: 'cursed',
    backgroundColor: '#1a0f2e'
  },
  {
    id: 'holylands',
    name: 'Holy Lands',
    description: 'Blessed temples and sacred ground filled with light',
    colors: {
      primary: '#fbbf24',
      secondary: '#f59e0b',
      accent: '#fca5a5',
      dark: '#92400e'
    },
    tileTypes: ['sand', 'temple', 'holy_ground', 'light'],
    enemyTypes: ['corrupted_angel', 'shadow_knight', 'void_beast'],
    dungeonStyle: 'temple',
    backgroundColor: '#fef3c7'
  }
];

const ENEMY_TEMPLATES = {
  goblin: {
    name: 'Goblin',
    hp: 25,
    attack: 8,
    defense: 3,
    magic: 2,
    agility: 12,
    speed: 1.5,
    range: 100,
    color: '#6b8e23',
    size: 20,
    behavior: 'melee_aggressive'
  },
  wolf: {
    name: 'Wolf',
    hp: 35,
    attack: 11,
    defense: 5,
    magic: 0,
    agility: 15,
    speed: 2,
    range: 80,
    color: '#8b7355',
    size: 25,
    behavior: 'melee_pack'
  },
  treant: {
    name: 'Treant',
    hp: 60,
    attack: 13,
    defense: 12,
    magic: 8,
    agility: 5,
    speed: 0.8,
    range: 150,
    color: '#228b22',
    size: 30,
    behavior: 'ranged'
  },
  ice_elemental: {
    name: 'Ice Elemental',
    hp: 40,
    attack: 6,
    defense: 4,
    magic: 16,
    agility: 13,
    speed: 1.8,
    range: 200,
    color: '#add8e6',
    size: 22,
    behavior: 'ranged'
  },
  frost_spider: {
    name: 'Frost Spider',
    hp: 30,
    attack: 12,
    defense: 4,
    magic: 8,
    agility: 16,
    speed: 2.2,
    range: 60,
    color: '#87ceeb',
    size: 18,
    behavior: 'melee_aggressive'
  },
  crystal_golem: {
    name: 'Crystal Golem',
    hp: 80,
    attack: 14,
    defense: 15,
    magic: 5,
    agility: 3,
    speed: 0.6,
    range: 120,
    color: '#87ceeb',
    size: 35,
    behavior: 'melee_tank'
  },
  shadow_beast: {
    name: 'Shadow Beast',
    hp: 45,
    attack: 15,
    defense: 6,
    magic: 10,
    agility: 14,
    speed: 2,
    range: 100,
    color: '#4b0082',
    size: 28,
    behavior: 'melee_aggressive'
  },
  poisonous_spider: {
    name: 'Poisonous Spider',
    hp: 35,
    attack: 10,
    defense: 3,
    magic: 12,
    agility: 17,
    speed: 2.3,
    range: 150,
    color: '#8b00ff',
    size: 20,
    behavior: 'ranged_aggressive'
  },
  spectre: {
    name: 'Spectre',
    hp: 40,
    attack: 12,
    defense: 2,
    magic: 14,
    agility: 16,
    speed: 2.1,
    range: 200,
    color: '#9370db',
    size: 24,
    behavior: 'ranged'
  },
  corrupted_angel: {
    name: 'Corrupted Angel',
    hp: 70,
    attack: 16,
    defense: 10,
    magic: 14,
    agility: 12,
    speed: 1.9,
    range: 180,
    color: '#daa520',
    size: 32,
    behavior: 'ranged_aggressive'
  },
  shadow_knight: {
    name: 'Shadow Knight',
    hp: 60,
    attack: 18,
    defense: 12,
    magic: 8,
    agility: 10,
    speed: 1.5,
    range: 100,
    color: '#2f4f4f',
    size: 30,
    behavior: 'melee_aggressive'
  },
  void_beast: {
    name: 'Void Beast',
    hp: 90,
    attack: 20,
    defense: 10,
    magic: 16,
    agility: 11,
    speed: 1.8,
    range: 200,
    color: '#1a1a2e',
    size: 35,
    behavior: 'ranged_aggressive'
  }
};

const BOSS_TEMPLATES = {
  guardian: {
    name: 'Guardian of the Realm',
    hp: 200,
    attack: 22,
    defense: 14,
    magic: 12,
    agility: 8,
    speed: 1.2,
    color: '#ff6347',
    size: 40,
    abilities: ['shield_bash', 'summon_minions', 'power_attack']
  },
  archlich: {
    name: 'Archlich',
    hp: 180,
    attack: 18,
    defense: 8,
    magic: 24,
    agility: 12,
    speed: 1.4,
    color: '#4169e1',
    size: 38,
    abilities: ['arcane_blast', 'time_warp', 'summon_minions']
  },
  shadow_lord: {
    name: 'Shadow Lord',
    hp: 240,
    attack: 24,
    defense: 12,
    magic: 14,
    agility: 14,
    speed: 1.6,
    color: '#2f4f4f',
    size: 42,
    abilities: ['dark_pulse', 'shadow_clone', 'drain_life']
  },
  radiant_overlord: {
    name: 'Radiant Overlord',
    hp: 220,
    attack: 20,
    defense: 16,
    magic: 18,
    agility: 10,
    speed: 1.3,
    color: '#ffd700',
    size: 40,
    abilities: ['holy_wrath', 'heal', 'divine_judgment']
  }
};

const TITLE_POOL = [
  'Void Conqueror',
  'Untouched',
  'Arcane Master',
  'Dungeon Breaker',
  'Monster Slayer',
  'Speedrunner',
  'Ghost',
  'Executioner',
  'Unchained',
  'Lifebringer',
  'Shadowwalker',
  'Frostborn',
  'Timeless',
  'Ascendant',
  'Mythbreaker'
];
