# Research Report: Character Account Transfer (AzerothCore 3.3.5a)

## Core Logic
In AzerothCore (and most MaNGOS-based cores), the relationship between a character and its account is managed by a single column in the character database.

- **Database:** `acore_characters`
- **Table:** `characters`
- **Column:** `account` (Integer, references `acore_auth.account.id`)

### Primary SQL Command
To move a character with GUID `123` to an account with ID `45`:
```sql
UPDATE acore_characters.characters SET account = 45 WHERE guid = 123;
```

## Critical Safety Requirements
1. **Offline Status:** The character **MUST** be offline (`online = 0`) before the update. If the character is logged in, the Worldserver holds its data in memory and will overwrite the database on logout, potentially reverting the change or causing data corruption.
2. **Account Validation:** The target account ID must exist in the `acore_auth.account` table.
3. **Session Refresh:** The player must log out and back in (to the character selection screen) for the character list to refresh.

## Secondary Dependencies
- **Playerbots:** If the character is registered as a bot in `acore_playerbots.playerbots_random_bots`, the `owner` column in that table should also be updated to the new account ID to maintain ownership permissions.
- **Trusted Links:** In this specific server, `acore_playerbots.playerbots_account_links` manages trust between accounts. This may need consideration if the transfer is intended to preserve bot-control hierarchies.

---

# Task Prompt for Antigravity Gemini

**Objective:**
Implement a "Move Character to Another Account" tool within the Admin Dashboard.

**Files to Modify:**
1. `index.html` (Local source for the wiki/admin frontend)
2. `scripts/admin_index.php` (Server-side backend handler)

**Requirements:**

### 1. Frontend (index.html)
- Add a new "Character Transfer" section inside the `Character Tools` tab (look for `id="tab-char-tools"`).
- **UI Components:**
  - An input field for "Character Name" with autocomplete functionality (leveraging existing `filterAdminCharSearch` or similar logic).
  - An input field for "Target Account Name".
  - A "Move Character" button with a confirmation dialog.
- **Feedback:** Display a success/error message upon completion.

### 2. Backend (scripts/admin_index.php)
- Add a new AJAX action: `move_character`.
- **Backend Logic:**
  1. Receive `character_name` and `target_account_name`.
  2. Query `acore_characters.characters` to find the character's `guid` and current `online` status.
  3. If `online == 1`, return an error: "Character must be offline to perform transfer."
  4. Query `acore_auth.account` to find the target account's `id` based on the provided name.
  5. If the account doesn't exist, return an error.
  6. Execute the update: `UPDATE characters SET account = :new_id WHERE guid = :guid`.
  7. **Bot Check:** If the character exists in `acore_playerbots.playerbots_random_bots`, update the `owner` column to the new account ID.
  8. Return a success JSON response.

**Technical Note:**
Always use PDO prepared statements to prevent SQL injection. Ensure the character lookup is case-insensitive for better UX.
