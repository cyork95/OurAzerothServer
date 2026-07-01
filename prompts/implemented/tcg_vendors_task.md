# Task: Install and Integrate TCG Vendors Module

**Context:**
A request has been made to restore the functionality of the TCG and promotional vendors using the `mod-tcg-vendors` module. This module provides item redemption logic for several NPCs, a boss drop system for TCG codes, and GM management tools.

**Objective:**
Install the `mod-tcg-vendors` module and integrate its configuration into the Admin Console and its documentation into the Server Wiki.

**Module Source:**
`https://github.com/lightninjay/mod-tcg-vendors`

---

## 1. Installation Requirements

### A. Module Placement
Clone the repository into the AzerothCore modules directory:
`/home/coyofroyo/azerothcore-wotlk/modules/mod-tcg-vendors`

### B. Database Updates
Apply the following SQL files from the module:

**Characters Database (`acore_characters`):**
- `data/sql/characters/base/create_tcg_redeemed_table.sql`
- `data/sql/characters/base/create_tcg_codes_table.sql`

**World Database (`acore_world`):**
- `data/sql/world/base/zzz_tcg_vendors_setup.sql`
- `data/sql/world/base/Update_Warbot_Fuel.sql`

### C. Compilation
Recompile the AzerothCore source to include the new module.

---

## 2. Admin Console Integration

**Files to Modify:**
- `index.html` (Local source for the admin frontend)
- `scripts/admin_index.php` (Server-side backend handler)

**Requirements:**

- Add a new "TCG & Promotions" configuration card in the **Dashboard & Controls** tab (or a dedicated tab if appropriate).
- **UI Components:**
    - `TCGVendors.Mode`: Select dropdown (0: Disabled, 1: Free, 2: Blizz-like, 3: Item-Specific Code).
    - `TCGVendors.LandroBoxesMultiRedeem`: Toggle/Checkbox.
    - **Boss Drop Settings:**
        - `TCGVendors.BossDrop.Enabled`: Toggle switch.
        - `TCGVendors.BossDrop.CreatureIds`: Textarea/Input (Comma-separated IDs).
        - `TCGVendors.BossDrop.ItemIds`: Textarea/Input (Comma-separated IDs).
        - `TCGVendors.BossDrop.MailParticipants`: Select dropdown (0: Loot only, 1: Mail only, 2: Mail AND Loot).
- **Backend Logic:**
    - Implement an AJAX action to read and write these settings to:
      `/home/coyofroyo/azeroth-server/etc/modules/mod-tcg-vendors.conf`
    - Call `reload config` via SOAP after saving.

---

## 3. Server Wiki Documentation

**File to Modify:**
- `index.html` (Wiki sections)

**Requirements:**

- Add a new "TCG & Promotional Rewards" section to the wiki.
- **NPC Reference Table:**
    | NPC | Location | Category |
    |-----|----------|----------|
    | **Landro Longshot** | Booty Bay | TCG Card Set Rewards |
    | **Ransin Donner** | Ironforge (Forlorn Cavern) | Blizzcon Promotions |
    | **Zas'Tysh** | Orgrimmar (Valley of Heroes) | Blizzcon Promotions |
    | **Garel Redrock** | Ironforge (Forlorn Cavern) | Murlocs & Special Promo |
    | **Tharl Stonebleeder** | Orgrimmar (Valley of Heroes) | Murlocs & Special Promo |
    | **Edward Cairn** | Undercity | Worldwide Invitational |
    | **Ian Drake** | Stormwind | Worldwide Invitational |
- **Redemption Guide:**
    - Explain how the current `TCGVendors.Mode` works for players.
    - Document the Boss Drop system (if enabled) and how players can find/receive codes.

---

**Technical Note:**
Ensure all PHP database interactions use PDO prepared statements. Maintain the consistent glass-morphism UI style present in the current dashboard.
