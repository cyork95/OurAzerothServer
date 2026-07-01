# Task: Create Guidelime Guide for Blackrock Depths & Dungeon Set 2

**Context:**
A request has been made to create a comprehensive Guidelime guide for Blackrock Depths (BRD), covering both the Detention Block and Shadowforge City sections, as well as integrating key steps for the Dungeon Set 2 (T0.5) questline. Use your `guidelime-guide-creator` skill to implement this guide.

**Research Summary (Blackrock Depths & D2 Quests):**

1.  **Dungeon Layout & Progression:**
    - **Run 1: Detention Block (Shadowforge Key):**
        - Accept [QA3801/3802 Dark Iron Legacy] from the ghost of Franclorn Forgewright (NPC 8888) in Forgewright's Tomb (while dead).
        - Kill High Interrogator Gerstahn (NPC 9018) for [CI11140 Prison Cell Key].
        - Defeat Ring of the Law (Arena).
        - Kill Fineous Darkvire (NPC 9056) for [CI10999 Ironfel].
        - Return to the Shrine of Thaurissan to turn in [QT3801/3802] and get [CI11000 Shadowforge Key].
    - **Run 2: Shadowforge City (Emperor Run):**
        - Use Shadowforge Key to access East Garrison.
        - Activate Shadowforge Lock to open the way to Shadowforge City.
        - Kill General Angerforge (NPC 9033) and Golem Lord Argelmach (NPC 8983).
        - Enter The Grim Guzzler. Method 1: Buy 6 Ale Mugs (Item 11325) from Plugger Spazzring for Private Rocknot (NPC 9503). Method 2: Love Potion quest.
        - Defeat Phalanx (NPC 9502) to break the door.
        - Ambassador Flamelash (NPC 9156).
        - The Lyceum: Collect 2x [CI11885 Shadowforge Torch] from Shadowforge Flame Keepers (NPC 9956) and light braziers.
        - Defeat Magmus (NPC 9938).
        - Kill Emperor Dagran Thaurissan (NPC 9019). Keep Princess Moira Bronzebeard (NPC 8929) alive for quests.

2.  **Dungeon Set 2 (T0.5) Integration:**
    - **Part 3 (The Challenge):** Summon Theldren (NPC 16059) in the Ring of the Law using [UI21986 Banner of Provocation] if on [QA9015/9016]. Collect [CI22047 Top Piece of Lord Valthalak's Amulet] for [QC9015/9016].
    - **Part 4 (Three Kings of Flame):** Collect [CI21987 Incendicite of Incendius] from Lord Incendius (NPC 9017) for [QC8961].

3.  **Key Quests:**
    - **Alliance:** [QA4263 Incendius!], [QA4362 The Fate of the Kingdom].
    - **Horde:** [QA3907 Disharmony of Fire], [QA4003 The Royal Rescue].
    - **Neutral:** [QA4123 The Heart of the Mountain], [QA7848 Attunement to the Core].

---

# Task Prompt for Antigravity Gemini

**Objective:**
Use your `guidelime-guide-creator` skill to generate a `.lua` Guidelime guide for "Blackrock Depths Complete & D2 Quests".

**Requirements:**

1.  **Metadata:**
    - **Name:** `[N Blackrock Depths & Dungeon Set 2]`
    - **Description:** `[D Full BRD progression guide including Shadowforge Key, Emperor run, and Tier 0.5 quest steps.]`
    - **Faction:** Universal (use `[GA Alliance]` and `[GA Horde]` tags for faction-specific steps).

2.  **Guide Structure:**
    - **Preparation:** Traveling to Blackrock Mountain.
    - **Detention Block Section:** Focus on getting the Shadowforge Key.
    - **Shadowforge City Section:** Focus on the back half bosses and reaching the Emperor.
    - **D2 Quest Steps:** Specific instructions for summoning Theldren and looting Incendius.
    - **Quest Tracking:** Include `[QA...]`, `[QC...]`, and `[QT...]` for major quests.
    - **Navigation:** Use `[G coordinates]` for key locations like the Shrine of Thaurissan, The Vault, and The Lyceum braziers.

3.  **Formatting:**
    - Use appropriate Guidelime markup as defined in your skill.
    - Ensure the file is valid Lua and can be registered via `Guidelime.registerGuide()`.

**Technical Note:**
Ensure the guide is structured logically for a group run. Prioritize the Shadowforge Key if the player doesn't have it.
