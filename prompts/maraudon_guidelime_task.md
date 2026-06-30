# Task: Create Guidelime Guide for Maraudon Dungeon

**Context:**
A request has been made to create a custom Guidelime guide for the Maraudon dungeon (Level 45-52) in Desolace. This guide should cover quest pickup, the dungeon walkthrough, and boss encounters.

**Objective:**
Use your `guidelime-guide-creator` skill to generate a `.lua` Guidelime guide for "Maraudon Dungeon Strategy".

**Requirements:**

1.  **Research (Groundedness):**
    - Research and identify the correct Quest IDs for WotLK 3.3.5a for the following quests:
        - *Corruption of Earth and Seed* (Quest giver: Selendra)
        - *The Legends of Maraudon* (Quest giver: Cavindra)
        - *Vyletongue Corruption* (Quest giver: Willow)
        - *Twisted Evils* (Quest giver: Willow)
        - *Vile Refuse* (Quest giver: Willow)
        - *Seed of Life* (Dropped by Princess Theradras)
    - Find the exact map coordinates for the quest givers Selendra, Cavindra, and Willow in Desolace.

2.  **Metadata:**
    - **Name:** `[N Maraudon Dungeon Guide]`
    - **Description:** `[D Full walkthrough for Maraudon, including Orange, Purple, and Inner wings.]`
    - **Faction:** Universal (ensure travel steps cover both Alliance and Horde if applicable).

3.  **Guide Structure:**
    - **Quest Pickup:** Instructions to visit Selendra, Cavindra, and Willow in Desolace to pick up all available quests.
    - **Entrance Walkthrough:** Directions to the Maraudon entrance (The Valley of Spears).
    - **Orange Wing (Vile Reef):** Steps for Noxxion and Razorlash.
    - **Purple Wing (Wicked Grotto):** Steps for Lord Vyletongue and Meshlok the Harvester (Rare).
    - **Inner Maraudon (Earth Song Falls):** Steps for Celebras the Cursed, Landslide, Tinkerer Gizlock, Rotgrip, and Princess Theradras.
    - **Quest Completion:** Include tags for completing quest objectives (`[QC...]`) and turning them in (`[QT...]`).

4.  **Formatting:**
    - Use appropriate Guidelime markup (e.g., `[G coordinates]`, `[QA...]`, `[QT...]`, `[QC...]`).
    - Reference the `guidelime-guide-creator` skill for markup syntax.

**Technical Note:**
Ensure the guide is concise, follows the `.lua` shell format required by the skill, and uses verified Quest IDs and coordinates discovered during your research.
