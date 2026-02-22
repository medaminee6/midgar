# Quiz to User Preferences - Implementation Summary

## Overview
Implemented a system that tracks user quiz answers and stores their preferences based on selected quiz options in the `user_preferences` table.

## What Was Created/Modified

### 1. **New Entity: UserPreference** (`src/Entity/UserPreference.php`)
- Stores `user_id` and collected `tags` from quiz answers
- Tracks `created_at` and `updated_at` timestamps
- Automatically creates new record or updates existing one per user

### 2. **New Repository: UserPreferenceRepository** (`src/Repository/UserPreferenceRepository.php`)
- Handles database operations for UserPreference entity
- Methods: `save()`, `remove()`, `findByUser()`

### 3. **Updated QuizController** (`src/Controller/QuizController.php`)
**New POST route: `/quiz/submit`** 
- Collects all selected reponse IDs from quiz form
- Extracts tags from each selected reponse (comma-separated via Reponse.tag)
- Removes duplicates and merges all tags
- Creates or updates UserPreference record for current user
- Redirects to discover page (`/discover`)

### 4. **Updated Quiz Template** (`templates/quiz.html.twig`)
**Key Changes:**
- Added CSRF token meta tag in `<head>`: `<meta name="csrf-token" content="{{ csrf_token('quiz') }}">`
- Changed navigation from links to button functions
- Implemented JavaScript to:
  - Store selected answer in `localStorage['quiz_answers']` as user navigates
  - Load saved answer when returning to question
  - Submit all collected answers as POST form when "Terminer" clicked
  - Include CSRF token with form submission

### 5. **Database Migration** (`migrations/Version20260220100000.php`)
Created `user_preferences` table with:
```sql
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tags LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX (user_id),
    FOREIGN KEY (user_id) REFERENCES user(id)
)
```

## How It Works

### User Flow:
1. User visits `/quiz`
2. Selects answer for question 1 → stored in `localStorage` as `{1: reponse_id}`
3. Clicks "Suivant" → navigates to next question, previous answer preserved
4. Repeats for all questions
5. On last question, clicks "Terminer"
6. Quiz form submits POST request with all selected reponse IDs
7. Controller extracts tags from each reponse
8. Stores merged tags in `user_preferences.tags` (comma-separated)
9. Redirects to discover page

### Example Data Flow:
**Quiz Selected Answers:**
- Question 1: Reponse 5 (tag: "action,aventure")
- Question 2: Reponse 8 (tag: "magie")
- Question 3: Reponse 12 (tag: "action,mystere")

**Resulting Stored Preference:**
- `user_id`: 1
- `tags`: "action,aventure,magie,mystere"

## Technical Details

### Reponse Entity
- Already has `tag` field (string)
- Tags can be comma-separated for multiple values
- Example: `"epic,dragon,ancient"` 

### localStorage Structure
```javascript
localStorage['quiz_answers'] = {
    "1": "5",      // Question ID: Reponse ID
    "2": "8",
    "3": "12"
}
```

### Form Submission
The form created by JavaScript submits:
```
POST /quiz/submit
Body:
  reponse[]: ["5", "8", "12"]
  _token: "{csrf_token}"
```

### Database Query Result
After submission, query shows:
```sql
SELECT * FROM user_preferences WHERE user_id = 1;
-- user_id: 1, tags: "action,aventure,magie,mystere"
```

## Testing Checklist

- [ ] User navigates quiz without losing answers
- [ ] Going back preserves previously selected answers
- [ ] Clicking "Terminer" on last question submits form
- [ ] User preferences stored in database
- [ ] Redirects to discover page after submission
- [ ] Multiple users have separate preferences
- [ ] Tags are properly deduplicated and merged

## Dependencies
- Symfony 6.4+ (CSRF protection, routing)
- Doctrine ORM 3.6+ (entity mapping, migrations)
- localStorage (browser API for answer persistence)
- User entity (relation to UserPreference)

## Security
- CSRF token required for form submission
- User authentication required (via `denyAccessUnlessGranted`)
- Symfony handles CSRF validation automatically

## Notes
- Quiz answers are stored in browser `localStorage` for UX (can navigate freely)
- Only final submission stores to database
- Preferences are updated on each quiz completion (not appended)
- Tags use comma-separated format for flexibility
