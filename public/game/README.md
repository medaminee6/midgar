# MIDGAR - Dungeon Crawler Game

A lightweight, beautiful, and fully-functional 2D top-down dungeon crawler inspired by the original Legend of Zelda (NES), built with vanilla JavaScript and HTML5 Canvas.

## Overview

MIDGAR is a complete dungeon crawler game with:
- **4 Playable Characters** with unique stats and abilities
- **4 Distinct Universes** with different enemy types and themes
- **Procedural Generation** for both overworld and dungeons
- **Smart Enemy AI** with state machines and adaptive tactics
- **RPG Stat System** with attack, defense, magic, and agility
- **Progression System** with titles, highscores, and profile persistence
- **Smooth 60 FPS Gameplay** with particle effects and responsive controls

## How to Play

### Running the Game

1. Open your web browser
2. Navigate to `http://localhost:8000/game/` (or your local dev server URL)
3. The game will load and start at the main menu

### Game States

**Main Menu**
- Shows overall statistics from all previous runs
- Press ENTER to start a new game

**Character Selection**
- Choose from 4 unique characters: Kael (Warrior), Lyra (Mage), Vex (Rogue), Aldric (Paladin)
- Use LEFT/RIGHT arrow keys to navigate
- Press ENTER to confirm

**Universe Selection**
- Choose from 4 universes: Aethermoor, Crystalholm, Shadowfen, Holy Lands
- Each universe has unique enemies and visual themes
- Use LEFT/RIGHT arrow keys to navigate
- Press ENTER to confirm

**Overworld**
- Explore a procedurally generated world
- Use ARROW KEYS or WASD to move
- SPACE to dodge roll (consume stamina, reduce damage)
- E or Q to use your character's special ability
- Find the purple portal to enter the dungeon

**Dungeon**
- Navigate through procedurally generated rooms
- Combat enemies and defeat the boss to complete the dungeon
- Each deeper level has stronger enemies
- Clear all enemies to proceed to the next room
- Press E to advance to next room when cleared

**Game Over**
- Your performance is evaluated and a title is awarded
- Statistics are saved to localStorage
- Press SPACE or ENTER to return to main menu

## Controls

| Key | Action |
|-----|--------|
| Arrow Keys / WASD | Move character |
| SPACE | Dodge roll (reduces damage, costs stamina) |
| E / Q | Use active ability |
| ENTER | Confirm selection / Start game |
| LEFT / RIGHT | Navigate menu selections |

## Character Classes

### Kael the Warrior
- **Stats**: High Attack (18), High HP (120), Good Defense (16)
- **Passive**: Iron Skin - Reduces damage by 10%
- **Active**: Whirlwind Slash - 2.5x damage in 200px radius (6s cooldown, 15 mana)
- **Best For**: Aggressive play, tanking damage

### Lyra the Mage
- **Stats**: High Magic (20), Low HP (80), Good Agility (14)
- **Passive**: Mana Shield - Reduces spell damage by 20%
- **Active**: Fireball - 3x magic damage in 250px radius (5s cooldown, 25 mana)
- **Best For**: Range advantage, magic-based combat

### Vex the Rogue
- **Stats**: High Agility (18), Balanced Attack (14), Low HP (75)
- **Passive**: Evasion - 25% dodge chance
- **Active**: Shadow Strike - 2x damage with guaranteed hit (4s cooldown, 20 mana)
- **Best For**: Quick, evasive gameplay

### Aldric the Paladin
- **Stats**: Balanced (Attack: 16, Defense: 18, Magic: 12, HP: 100)
- **Passive**: Holy Protection - Heal 5% of damage taken
- **Active**: Divine Light - 2x damage healing (8s cooldown, 30 mana)
- **Best For**: Sustainable, middle-ground gameplay

## Universes

### Aethermoor
- **Theme**: Magic Crystal Realm
- **Enemies**: Goblins, Ice Elementals, Frost Spiders, Spectres
- **Colors**: Cool blues and cyans with purple accents

### Crystalholm
- **Theme**: Mineral Kingdom
- **Enemies**: Wolves, Crystal Golems, Poisonous Spiders, Shadow Beasts
- **Colors**: Bright whites, blues, and translucent effects

### Shadowfen
- **Theme**: Dark Swamplands
- **Enemies**: Wraiths, Corrupted Angels, Shadow Knights, Void Beasts
- **Colors**: Deep purples, dark greens, and ominous shadows

### Holy Lands
- **Theme**: Divine Celestial Realm
- **Enemies**: Lesser Demons, Shade Creatures, Cursed Guardians, Radiant Overlords
- **Colors**: Bright golds, holy whites, and divine energy effects

## Game Mechanics

### Combat System
- **Damage Calculation**: `damage * (1 + (attack - defense) * 0.05) * magic_modifier`
- **Defense**: Reduces incoming damage based on your defense stat
- **Dodge**: Chance to completely avoid damage based on agility stat
- **Invulnerability**: 300ms invulnerability window after taking damage

### Enemy AI
The game features intelligent enemy AI with state machines:
1. **IDLE** - Resting and recovering
2. **PATROL** - Random wandering (30px/s)
3. **CHASE** - Pursuing the player when in range (300px detection)
4. **ATTACK** - Melee attack (close range) or ranged attacks (distance)
5. **RETREAT** - Backing away when health is low
6. **CALL_ALLIES** - Pack enemies attempt to flank the player

### Procedural Generation
- **Overworld**: Generated using Perlin-like noise, 1280x1280px world
- **Dungeon**: Room-based (600x600px per room), 5+ rooms per dungeon depth
- **Difficulty Scaling**: Enemy stats increase by 15% per dungeon depth level

### Progression & Titles

Titles are awarded based on performance:
- **Untouched**: Complete a dungeon without taking damage
- **Speedrunner**: Complete a dungeon in under 2 minutes
- **Void Conqueror**: Reach dungeon depth 10
- **Monster Slayer**: Defeat 100+ enemies in one run
- **Arcane Master**: Use 20+ abilities in one run
- **[Many More!]**: Random titles from the title pool

Highscores track:
- Character used
- Universe explored
- Dungeon depth reached
- Total enemies defeated
- Total damage taken
- Time taken
- Title earned
- Timestamp

### Profile System
- **Storage**: localStorage (browser storage)
- **Persistence**: All saves are automatic
- **Data Tracked**: Runs, wins, character stats, universe stats, titles earned

## File Structure

```
/public/game/
├── index.html          # Game entry point
├── js/
│   ├── main.js         # Main game controller & game loop
│   ├── data.js         # All static game data (characters, universes, enemies, bosses)
│   ├── utils.js        # Utility functions (math, collision, noise, drawing)
│   ├── player.js       # Player class with movement & combat
│   ├── enemy.js        # Enemy & Boss classes
│   ├── ai.js           # Enemy AI with state machine
│   ├── world.js        # Procedural overworld generation
│   ├── dungeon.js      # Procedural dungeon generation
│   ├── ui.js           # UI rendering (HUD, menus, screens)
│   └── progression.js  # Profile & title system
└── css/
    └── style.css       # Game styling
```

## Technical Details

### Architecture
- **Pattern**: Object-oriented with class-based design
- **Game Loop**: requestAnimationFrame for 60 FPS
- **State Machine**: Character selection → Universe selection → Overworld → Dungeon → Game Over
- **Rendering**: Canvas 2D context, camera follow system

### Performance
- Optimized collision detection with spatial partitioning
- Particle system for visual effects
- Enemy pooling for efficient memory usage
- Single canvas rendering pass per frame

### Browser Compatibility
- Requires: ES6 JavaScript support
- Works on: Chrome, Firefox, Safari, Edge (modern versions)
- Performance: 60 FPS on most machines

## Customization & Extension

The game architecture allows for easy expansion:

### Adding a New Character
1. Add to `CHARACTERS` array in `data.js`:
```javascript
{
  id: 'class-id',
  name: 'Character Name',
  stats: { maxHp, attack, defense, magic, agility },
  passiveAbility: { name, description, effect },
  activeAbility: { name, cooldown, damage, range, cost }
}
```

### Adding a New Universe
1. Add to `UNIVERSES` array in `data.js`:
```javascript
{
  id: 'universe-id',
  name: 'Universe Name',
  tileTypes: [color1, color2, color3, color4],
  enemyTypes: ['enemy1', 'enemy2', ...]
}
```

### Adding a New Enemy Type
1. Add to `ENEMY_TEMPLATES` array in `data.js`
2. Reference enemy type in a universe's `enemyTypes` array

### Adding Abilities
Edit the `activeAbility` or `passiveAbility` properties in character definitions.

## Known Limitations

- No audio/sound effects (can be added with Web Audio API)
- No multiplayer (would require backend/WebSocket)
- No controller/gamepad support (keyboard only)
- Limited to browser localStorage for saves (max ~5-10MB)

## Performance Notes

Optimal performance:
- 1280x1024 or higher resolution
- Modern hardware (2010+)
- 60 FPS consistently
- Memory usage: ~20-50MB

## Future Enhancement Ideas

- [ ] Sound/music system
- [ ] Controller support (gamepad API)
- [ ] Mobile touch controls
- [ ] Leaderboard (server-side)
- [ ] Custom character creation
- [ ] More enemy types and bosses
- [ ] Special items and equipment
- [ ] Status effects (poison, freeze, burn)
- [ ] Environmental hazards
- [ ] Dialogue system
- [ ] Quest system
- [ ] Multiplayer co-op

## Credits

Designed and built as a complete game framework using:
- Vanilla JavaScript (ES6+)
- HTML5 Canvas API
- Browser localStorage API

Inspired by: The Legend of Zelda (NES 1986)

## License

This is a learning project and demonstration of game development with vanilla JavaScript. Feel free to use and modify as you see fit.

---

**Enjoy your adventure in MIDGAR!**
