# Game Integration Guide

## Overview

A fully-integrated turn-based battle system has been implemented into your Symfony website. The game:

✅ Uses real data from your database (Personnages, Universes, Enemies)  
✅ Uses your website's design system (CSS variables, colors, buttons)  
✅ Stores battle results in the database  
✅ Integrates seamlessly with your existing user authentication  

---

## Architecture

### Backend Structure

#### New Entity: `Enemy`
Located in `src/Entity/Enemy.php`

```php
- id (Primary Key)
- name (string)
- enemyType (goblin, skeleton, wraith, etc.)
- description (text)
- Combat Stats:
  - strength (attack power)
  - agility (turn order, dodge)
  - magic (spell power)
  - defense (damage reduction)
  - maxHp (health)
- difficultyTier (1-4, for scaling)
- colorHex (visual color for UI)
- portraitImage (BLOB for enemy avatar)
- behaviorType (patrol, aggressive, ranged, healer)
- loot (xp, gold rewards)
- universe_id (foreign key to Universe)
```

#### New Repository: `EnemyRepository`
Located in `src/Repository/EnemyRepository.php`

Provides methods:
- `findByUniverse($universeId)` - Get all enemies for a universe
- `findByDifficultyTier($tier)` - Get enemies by difficulty
- `findRandomForBattle($universeId, $tier, $count)` - Get random enemies for battle

#### New API Controller: `GameApiController`
Located in `src/Controller/GameApiController.php`

API Endpoints:
- `GET /api/game/personnage/{id}` - Fetch player stats
- `GET /api/game/universe/{id}` - Fetch universe data
- `GET /api/game/universe/{id}/enemies` - Get all enemies for universe
- `GET /api/game/universe/{id}/enemy-for-battle` - Get random enemy for battle
- `POST /api/game/profile/{id}/battle-result` - Save battle result

#### New Game Controller: `GameController`
Located in `src/Controller/GameController.php`

Routes:
- `GET /battle/{personnageId}` - Start a battle with a personnage
- `GET /battle/start/{personnageId}` - Alias for start battle

---

### Frontend Structure

#### Game Modules (JavaScript)

**Location:** `public/assets/js/`

1. **api.js** - API communication handler
   - All backend HTTP requests
   - Automatic CSRF token handling
   - Error handling and logging

2. **ai.js** - Enemy AI decision making
   - Analyzes battle state
   - Makes intelligent action choices
   - Provides action flavor text (narrative descriptions)

3. **battle.js** - Turn-based battle logic
   - Turn management (who goes first based on agility)
   - Damage calculation (attack, defense, magic)
   - Action execution
   - Win/lose conditions

4. **ui.js** - HTML-based battle UI
   - Renders character cards with stats
   - Health bars with visual feedback
   - Action buttons (Attack, Skill, Defend, Dodge)
   - Battle log with message history
   - Battle result display

5. **game.js** - Main game controller
   - Initializes game state
   - Manages battle flow
   - Handles player input
   - Saves results to database

#### Styling

**Location:** `public/css/battle.css`

Uses website design system:
- `--bg-primary`, `--bg-secondary` for backgrounds
- `--accent-primary`, `--accent-hover` for highlights
- `--text-primary`, `--text-secondary` for text
- `--border-color`, `--shadow-color` for borders/shadows
- Fully responsive design for mobile/tablet/desktop

#### Template

**Location:** `templates/battle.html.twig`

- Extends base.html.twig (uses website header/footer)
- Container for battle UI
- Container for battle results
- Script initialization
- Automatic error handling

---

## Setup Instructions

### Step 1: Create Enemy Table

Run the migration to create the `enemy` table:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

If migrations fail due to SQLite compatibility issues with old migrations, delete the database and recreate:

```bash
rm var/app.db
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

### Step 2: Load Test Data

Populate enemies using fixtures:

```bash
php bin/console doctrine:fixtures:load --append
```

This creates 5-7 enemies for each existing universe using the `GameFixtures` class.

### Step 3: Test the Integration

1. Navigate to http://localhost:8000/personnages
2. Select any personnage
3. Look for a "Battle" button/link (you may need to add this)
4. Click to start a battle
5. Fight the randomly selected enemy
6. Win/lose and see results

---

## Usage

### For End Users

**Access Battle:**
```
/battle/{personnageId}
```

Example: `/battle/5` starts a battle with personnage ID 5

**Battle Flow:**
1. Player and enemy stats appear
2. Turn order determined by agility
3. Choose action: Attack, Skill, Defend, Dodge
4. Enemy takes action (AI-controlled)
5. Alternate turns until winner emerges
6. View results and return to personnages

### Battle Mechanics

**Turn Order:**
- Determined by agility stat
- Higher agility = goes first

**Actions:**
- **Attack:** Physical damage = (strength × defense reduction) + variance
- **Skill:** Magic damage = (magic × 1.5 × defense reduction)
- **Defend:** Reduce next damage by 40%
- **Dodge:** 40% chance to evade next attack

**Damage Formula:**
```
baseDamage × (1 - (defenseValue / (defenseValue + 50))) × (1 ± 10% variance)
```

**Enemy AI:**  
- Evaluates battle state (health, player stats)
- Makes strategic decisions
- Adapts to player's strengths/weaknesses
- Not random - intelligent behavior

---

## Adding Custom Enemies

### Option 1: Via Admin Panel (Recommended)

Create a form/admin interface to add enemies. See `example.sql` below:

### Option 2: directly via SQL

```sql
INSERT INTO enemy (
    name, enemy_type, description, 
    strength, agility, magic, defense, max_hp,
    difficulty_tier, color_hex, behavior_type,
    loot_xp, loot_gold,
    universe_id, created_at, updated_at
) VALUES (
    'Fire Elemental',
    'elemental',
    'A being of pure flame and heat',
    10, 12, 15, 6, 40,
    2, '#FF6347', 'ranged',
    30, 20,
    1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
);
```

### Option 3: Via Entity Form

Create a Symfony Form for Enemy (similar to PersonnageType):

```bash
php bin/console make:form EnemyType --entity=Enemy
```

Then create controller:

```bash
php bin/console make:crud Enemy
```

---

## API Response Examples

### GET /api/game/personnage/1
```json
{
  "id": 1,
  "name": "Kael",
  "classRole": "Warrior",
  "universe_id": 1,
  "stats": {
    "attack": 18,
    "defense": 15,
    "magic": 5,
    "agility": 8,
    "maxHp": 120
  },
  "portrait": "base64-encoded-image-data..."
}
```

### GET /api/game/universe/1/enemy-for-battle
```json
{
  "id": 5,
  "name": "Skeletal Archer",
  "type": "skeleton",
  "description": "A risen warrior...",
  "stats": {
    "attack": 6,
    "defense": 3,
    "magic": 2,
    "agility": 10,
    "maxHp": 20
  },
  "difficulty": 1,
  "color": "#FFFACD",
  "behavior": "ranged",
  "loot": {
    "xp": 12,
    "gold": 8
  }
}
```

### POST /api/game/profile/1/battle-result
Request:
```json
{
  "victory": true,
  "enemyName": "Skeletal Archer",
  "damageDealt": 15,
  "damageTaken": 8,
  "xpGained": 12,
  "goldGained": 8,
  "turnsUsed": 4
}
```

Response:
```json
{
  "success": true,
  "message": "Battle result saved",
  "xpEarned": 12,
  "goldEarned": 8
}
```

---

## Extending the Game

### Add New Behaviors

In `ai.js`, add to `decideBestAction()`:

```javascript
// If player has status effect, attack
if (player.hasStatusEffect('poison')) {
  return { type: 'attack', priority: 9 };
}
```

### Add Status Effects

Modify `Battle` class in `battle.js`:

```javascript
applyStatusEffect(target, effect, duration) {
  target.statusEffects = target.statusEffects || {};
  target.statusEffects[effect] = duration;
}

processStatusEffects(target, damage) {
  if (target.statusEffects?.poison) {
    damage += Math.round(damage * 0.1); // 10% poison damage
  }
}
```

### Add Special Abilities

Modify action types in `battle.js`:

```javascript
case 'special_ability':
  damage = this.calculateSpecialAbilityDamage(attacker);
  message = `${attacker.name} uses a special ability!`;
  break;
```

### Add Loot System

Extend `battle-result` endpoint:

```php
$loot = [
    'xp' => $enemy->getLootXp(),
    'gold' => $enemy->getLootGold(),
    'items' => [], // Add item drops
    'equipment' => null, // Rare drops
];
```

### Add Achievements

Store achievements in user profile:

```php
public function awardAchievement(User $user, string $achievementId) {
    // Check battle condition
    // Add to user achievements
    // Grant reward
}
```

---

## Database Structure

### Enemy Table

```sql
CREATE TABLE enemy (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    enemy_type VARCHAR(100) NOT NULL,
    description LONGTEXT,
    strength INTEGER,
    agility INTEGER,
    magic INTEGER,
    defense INTEGER,
    max_hp INTEGER,
    difficulty_tier INTEGER,
    color_hex VARCHAR(7),
    portrait_image BLOB,
    behavior_type VARCHAR(50),
    loot_xp INTEGER,
    loot_gold INTEGER,
    universe_id INTEGER NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (universe_id) REFERENCES universe (id)
);

CREATE INDEX idx_universe ON enemy (universe_id);
CREATE INDEX idx_type ON enemy (enemy_type);
CREATE INDEX idx_difficulty ON enemy (difficulty_tier);
```

---

## Troubleshooting

### API Returns 404

- Check if personnage/universe/enemy exists
- Verify FK relationships in database
- Check data types in API responses

### UI Not Displaying

- Ensure `battle.css` is loaded
- Check browser console for JS errors
- Verify all scripts are included in `battle.html.twig`

### Battle Not Saving

- Check user authentication
- Verify profile ID is passed
- Look at Network tab in browser DevTools
- Check server logs for errors

### Enemy Stats Too Strong/Weak

- Adjust `difficultyTier` for enemies
- Modify stat multipliers in fixtures
- Add difficulty scaling in battle.js

### Migration Errors

If you get "DEFAULT CHARACTER SET" errors:

1. Edit the problematic migration file
2. Remove MySQL-specific syntax
3. Use SQLite-compatible SQL
4. Re-run migrations

Example fix:
```php
// WRONG (MySQL):
$this->addSql('...') DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

// CORRECT (SQLite):
$this->addSql('...');
```

---

## Performance Considerations

- Enemies loaded on-demand (better than preloading)
- Battle logic runs client-side (reduces server load)
- API responses cached (if implemented)
- Battle history optional (only save if needed)

---

## Security

✅ CSRF token validation on profile endpoints  
✅ User authentication required  
✅ Data validation on all inputs  
✅ Stats from database (no client-side modification)  
✅ Battle results stored server-side  

---

## Future Enhancements

- [ ] Battle history/statistics
- [ ] Achievement system
- [ ] Leaderboards
- [ ] Multiplayer pvp battles
- [ ] Equipment/item system
- [ ] Skill customization
- [ ] Experience/leveling
- [ ] Battle animations
- [ ] Sound effects
- [ ] Friend battles
- [ ] Tournament modes
- [ ] Seasonal challenges

---

## Files Created/Modified

### New Files
- `src/Entity/Enemy.php`
- `src/Repository/EnemyRepository.php`
- `src/Controller/GameApiController.php`
- `src/Controller/GameController.php`
- `src/DataFixtures/GameFixtures.php`
- `public/assets/js/api.js`
- `public/assets/js/ai.js`
- `public/assets/js/battle.js`
- `public/assets/js/ui.js`
- `public/assets/js/game.js`
- `public/css/battle.css`
- `templates/battle.html.twig`
- `migrations/Version20260221150000.php`

### Support Files
- `docs/GAME_INTEGRATION_GUIDE.md` (this file)

---

## Links

- Game API: `/api/game`
- Start Battle: `/battle/{personnageId}`
- Personnages: `/personnages`
- Admin (if created): `/admin/enemy`

---

## Contact/Support

For issues or questions about the game integration:

1. Check the troubleshooting section
2. Review the architecture overview
3. Examine the source code (well-commented)
4. Check browser DevTools console for errors
5. Look at server logs: `var/log/`

---

**Game Integration Complete!** 🎮

Your Symfony application now has a fully-integrated, data-driven turn-based battle system!
