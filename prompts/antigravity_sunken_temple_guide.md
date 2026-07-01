# Task: Create Guidelime Guide for The Temple of Atal'Hakkar (Sunken Temple)

**Context:**
A request has been made to create a custom Guidelime guide for the "Temple of Atal'Hakkar" dungeon, including the critical Level 50 class quests. This research prompt summarizes the objectives and flow to guide your implementation using the `guidelime-guide-creator` skill.

**Research Summary (The Temple of Atal'Hakkar):**

The Sunken Temple (Swamp of Sorrows) is a multi-level dungeon known for its complex layout and specific mechanic sequences.

1.  **Dungeon Quests:**
    - **Into the Depths (3446):** Start: Marvon Rivetseeker (Tanaris). End: Altar of Hakkar.
    - **Secret of the Circle (3447):** Start: Marvon Rivetseeker. End: Idol of Hakkar. Requires completing the statue puzzle.
    - **Jammal'an the Prophet (1446):** Start: Atal'ai Exile (Hinterlands). Kill Jammal'an the Prophet.
    - **The God Hakkar (3528):** Start: Yeh'kinya (Tanaris). Summon and kill Avatar of Hakkar.
    - **Haze of Evil (4143):** Start: Gregan Brewspewer (Feralas). Collect Atal'ai Haze from Oozes/Lurkers.
    - **The Essence of Eranikus (3373):** Start: Item from Shade of Eranikus. End: Essence Font in his lair.

2.  **Level 50 Class Quests (Dungeon Phase):**
    - **Warrior: Voodoo Feathers (8425)** - Kill the 6 troll mini-bosses.
    - **Paladin: Forging the Mightstone (8418)** - Kill the 6 troll mini-bosses.
    - **Rogue: The Azure Key (8236)** - Kill Morphaz for the key.
    - **Shaman: Da Voodoo (8413)** - Kill the 6 troll mini-bosses.
    - **Warlock: Trolls of a Feather (8422)** - Kill the 6 troll mini-bosses.
    - **Hunter: The Green Drake (8232)** - Kill Morphaz for his tooth.

3.  **Key Mechanics:**
    - **Statue Circle:** To unlock the lower level, statues must be clicked in the correct order:
        1. South (Bottom)
        2. North (Top)
        3. South-East (Bottom Right)
        4. North-West (Top Left)
        5. South-West (Bottom Left)
        6. North-East (Top Right)
    - **Troll Mini-bosses:** Gasher, Mijan, Zolo, Hukku, Zul'lor, and Loro must be defeated on the upper balconies to remove the barrier to Jammal'an.

---

# Task Prompt for Antigravity Gemini

**Objective:**
Use your `guidelime-guide-creator` skill to generate a `.lua` Guidelime guide for "The Temple of Atal'Hakkar (Sunken Temple)".

**Requirements:**

1.  **Metadata:**
    - **Name:** `[N Sunken Temple Dungeon Guide]`
    - **Description:** `[D Comprehensive walkthrough for Sunken Temple quests, boss strategies, and Level 50 class quests.]`
    - **Faction:** `[GA Alliance]`

2.  **Guide Structure:**
    - **Preparation:** Instructions to pick up the main quests in Tanaris, Hinterlands, and Feralas.
    - **Entry:** Travel to the Pool of Tears in Swamp of Sorrows and enter the instance.
    - **Lower Level (The Pit):**
        - Instructions for collecting Atal'ai Haze.
        - The Statue Activation sequence (1-6).
        - Interact with the Altar of Hakkar for "Into the Depths".
    - **Upper Balconies:**
        - Pathing to kill the 6 Troll Mini-bosses.
        - Note class-specific item drops (Voodoo Feathers).
    - **The Sanctum:**
        - Kill Jammal'an the Prophet.
        - Slay the dragons Morphaz and Hazzas (Class Quest objectives).
        - Slay the Shade of Eranikus.
        - Use the Essence Font for the Eranikus quest.
    - **Avatar of Hakkar (Optional/Final):**
        - Steps to use the Egg of Hakkar at the brazier and defeat the Avatar.

3.  **Formatting:**
    - Use appropriate Guidelime markup (e.g., `[G coordinates]`, `[QA...]`, `[QT...]`, `[QC...]`).
    - Use `[C Class]` filters for the class-specific quest steps.

**Technical Note:**
Ensure the guide is concise and correctly identifies the boss sequence to prevent backtracking.
