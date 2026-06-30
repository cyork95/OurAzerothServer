# Task: Create Guidelime Guide for Scarlet Monastery Gold Farming

**Context:**
A request has been made to create a custom Guidelime guide for gold farming in Scarlet Monastery (SM). This research prompt summarizes the key strategies and routes discovered from Wowhead to guide your implementation using the `guidelime-guide-creator` skill.

**Research Summary (Scarlet Monastery Gold Farming):**

Scarlet Monastery (Tirisfal Glades) consists of four wings, each offering unique gold-making opportunities via chest runs, herbalism, and AoE farming.

1.  **Graveyard (Leftmost Portal):**
    - **Strategy:** Stealth/Chest run.
    - **Chest Spawns:**
        - First room (clear pack of 4 elites).
        - Either side of the graveyard area (behind fenced areas).
        - Inside the crypt (turn right, corner to the left, clear 2 packs).
    - **Herbalism:** Grave Moss (inside the crypt and graveyard).
    - **Rare Bosses:** Ironspine, Azshir the Ghostweaver, Fallen Champion. Notable drops: Ironspine's Fist, Necrotic Wand.
    - **Notable Loot:** Scarlet set pieces (BoE), twinking gear (level 29 bracket).

2.  **Library (Second door from left):**
    - **Strategy:** Stealth/Pickpocket/Chest run. High mob density for pickpocketing (Worn Junkboxes).
    - **Chest Spawns:**
        - Small alcove to the left at the end of the second corridor.
        - Long straight corridor with benches (2 possible spawns on the left).
        - Right before Arcanist Doan (right corner).
    - **Herbalism:** Liferoot, Fadeleaf.
    - **Notable Loot:** Hypnotic Blade, Illusionary Rod (Boss vendor value).

3.  **Armory (Third door):**
    - **Strategy:** AoE farming or Stealth/Chest run.
    - **Chest Spawns:**
        - Left side of the courtyard (next to target dummy).
        - Northern end of the courtyard.
        - Downstairs, following the right wall (requires clearing 2 elites).
    - **Herbalism:** Fadeleaf.
    - **Notable Loot:** Scarlet set pieces (Scarlet Leggings from Herod), Ravager (high vendor value).

4.  **Cathedral (Rightmost Portal):**
    - **Strategy:** Proficient AoE farming.
    - **Chest Spawns:** Primarily focused on boss loot and rare drops.
    - **Herbalism:** Goldthorn.
    - **Notable Loot:** Scarlet Chestpiece (highly valuable BoE), Gauntlets of Divinity, Aegis of the Scarlet Commander.

**Logistics:**
- **Resetting:** After clearing desired wings (up to 5 times per hour), reset the instance to respawn chests and herbs.
- **Vendoring/Banking:**
    - **Horde:** Brill (Vendor/Mailbox).
    - **Alliance:** The Bulwark (Argent Dawn Vendor), Southshore (Nearest Mailbox).

---

# Task Prompt for Antigravity Gemini

**Objective:**
Use your `guidelime-guide-creator` skill to generate a `.lua` Guidelime guide for "SM Scarlet Monastery Gold Farming".

**Requirements:**

1.  **Metadata:**
    - **Name:** `[N SM Gold Farming Guide]`
    - **Description:** `[D Optimized routes for gold making in Scarlet Monastery (Graveyard, Library, Armory, Cathedral)]`
    - **Faction:** Universal (or separate steps for Alliance/Horde travel).

2.  **Guide Structure:**
    - **Preparation:** Instructions to travel to Scarlet Monastery in Tirisfal Glades.
    - **Graveyard Section:** Steps to check chest spawns and Grave Moss locations. Include coordinates if possible (approximate based on wing layout).
    - **Library Section:** Steps for pickpocketing routes and chest locations.
    - **Armory Section:** Instructions for the courtyard chest spawns and Herod loot.
    - **Cathedral Section:** Focus on AoE pull areas and major boss kills for vendor loot.
    - **Reset Instructions:** Reminder to exit and reset the instance after clearing.
    - **Logistics:** Directions to the nearest vendor and mailbox (Brill for Horde, The Bulwark/Southshore for Alliance).

3.  **Formatting:**
    - Use appropriate Guidelime markup (e.g., `[G coordinates]`, `[QA...]`, `[QT...]`, `[QC...]` where applicable, although this is more of a route guide than a quest guide).
    - Use `[C Class]` filters if recommending specific pulls for Mages or Paladins.

**Technical Note:**
Ensure the guide is concise and follows the `.lua` shell format required by the skill.
