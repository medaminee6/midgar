# MIDGAR Game - Quick Start Guide

## Installation & Running

### Prerequisites
- A modern web browser (Chrome, Firefox, Safari, or Edge)
- Access to your Symfony development server

### Setup

1. **Ensure Symfony server is running**:
```bash
# From the project root (c:\Users\feral\OneDrive\Desktop\midgar-quiz_v2\midgar-quiz_v2\)
symfony serve
# Or with PHP directly
php -S 127.0.0.1:8000 -t public
```

2. **Open the game in your browser**:
```
http://localhost:8000/game/
```

The game will automatically initialize and show the main menu.

## First Game Walkthrough

### Step 1: Main Menu
- Wait for the stats to load (or press Enter immediately)
- You'll see your total runs, wins, and achievements
- **Press ENTER** to begin

### Step 2: Character Selection
- You'll see 4 characters with their stats and abilities
- Use **LEFT/RIGHT arrow keys** to browse
- **Press ENTER** to select your character
- **Recommended for first game**: Kael the Warrior (easiest)

### Step 3: Universe Selection
- Choose which world to explore
- Each universe has different enemies and colors
- Use **LEFT/RIGHT arrow keys** to browse
- **Press ENTER** to confirm
- **Recommended for first game**: Aethermoor (Magical realm)

### Step 4: Overworld Exploration
Now you're in the game!
- Use **ARROW KEYS or WASD** to move around
- The world is procedurally generated each time
- Look for the **purple glowing portal** (dungeon entrance)
- Along the way:
  - **SPACE** to dodge roll (quick defensive move)
  - **E or Q** to use your character's special ability
  - Defeat enemies to track your progress
- When you find the portal, approach it and **Press SPACE** to enter

### Step 5: Dungeon Crawling
- Navigate through 5+ rooms of increasing difficulty
- Each room has enemies you must defeat
- Boss room at the end with a stronger enemy
- **Combat Controls**:
  - **ARROW KEYS/WASD** - Move
  - **E/Q** - Use ability (must reach enemy with ability range)
  - **SPACE** - Dodge roll
- Clear all enemies to proceed
- **Press E** when enemies are defeated to move to next room
- **Defeat the final boss** to win!

### Step 6: Game Over
- Your performance is evaluated
- A title is awarded based on your results
- Stats are automatically saved
- **Press SPACE/ENTER** to return to menu

## Tips for Playing

### Combat Tips
1. **Use Your Abilities** - They deal much more damage than normal attacks
2. **Dodge Rolling** - Essential for avoiding big attacks when surrounded
3. **Keep Moving** - Don't stand still; enemies will surround you
4. **Mana Management** - Abilities consume mana; don't waste it early
5. **Boss Patterns** - Learn enemy attack patterns and dodge accordingly

### Character Tips
- **Kael (Warrior)**: Tank with high HP, good for beginners
- **Lyra (Mage)**: Ranged damage, can hurt from distance
- **Vex (Rogue)**: Fast and evasive, hit-and-run tactics
- **Aldric (Paladin)**: Balanced and healing, most forgiving

### Earning Titles

Challenge yourself to earn special titles:
- **Untouched** - Don't take any damage
- **Speedrunner** - Complete dungeon in under 2 minutes
- **Void Conqueror** - Reach depth 10 (5 dungeons)
- **Monster Slayer** - Defeat 100+ enemies
- **Arcane Master** - Use 20+ abilities

## Keyboard Controls Summary

```
WASD or Arrow Keys  = Move character
SPACE              = Dodge roll (dodge attacks)
E or Q             = Use special ability
ENTER              = Confirm selection / Start game
LEFT/RIGHT Arrows  = Navigate menu selections
```

## Troubleshooting

### Game won't load
- Make sure Symfony server is running (should hear "Ready to handle connections")
- Try clearing browser cache (Ctrl+Shift+Delete)
- Check console for errors (F12 → Console tab)

### Enemies too hard / too easy
- You can play on different universes with different enemy difficulty
- Try a different character class
- Very first run should be manageable - you get better at dodging!

### Stuck on a level
- Remember you can dodge with SPACE
- Don't waste abilities early - save them for boss
- Try moving more - combat is about positioning

### Save data not working
- Check browser's localStorage is enabled
- Game saves automatically after each run
- Stats are stored per browser (different browsers = different saves)

## What to Expect

- **First run**: 10-15 minutes for exploration + combat
- **Learning curve**: 3-4 runs to master controls
- **Skill cap**: High difficulty once you unlock deeper dungeons
- **Replayability**: Procedural generation means every run is unique

## Have Fun!

This is a complete game with:
- ✅ 4 characters with unique abilities
- ✅ 4 universes with different enemy types
- ✅ Procedurally generated worlds and dungeons
- ✅ Smart AI enemies with adaptive behavior
- ✅ Progression system with titles and highscores
- ✅ Smooth 60 FPS gameplay

Get ready for adventure in **MIDGAR**!
