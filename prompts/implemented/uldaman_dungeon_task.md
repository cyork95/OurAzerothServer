# Task: Create Guidelime Guide for Uldaman Dungeon

**Context:**
A user has requested a custom Guidelime guide for the Uldaman dungeon in World of Warcraft Classic. This task requires researching the dungeon's quests, bosses, and layout to create an optimized leveling and dungeon completion guide.

**Objective:**
Research the Uldaman dungeon strategy and map out the necessary steps to generate a `.lua` Guidelime guide using the `guidelime-guide-creator` skill.

---

# Research Task for Antigravity Gemini

**Reference:** [Uldaman Dungeon Strategy Guide - WoW Classic](https://www.wowhead.com/classic/guide/uldaman-dungeon-strategy-wow-classic)

**1. Dungeon Research & Mapping:**
- **Quests:** Identify all Alliance and Horde quests associated with Uldaman.
    - Find the Quest IDs (e.g., `QA123`).
    - Identify the Quest Givers (NPC names and locations).
    - Note prerequisites and where to accept/turn in each quest.
- **Bosses:** List all bosses and their locations within the dungeon.
    - Notable loot and any quest-related drops.
- **Layout/Route:** Map out an efficient path through the dungeon to complete all quests and kill all major bosses.
    - Note coordinates for key locations (entrances, quest objectives, bosses) using the `[G x.x, y.y ZoneName]` format.

**2. Guide Creation Requirements:**
Use your `guidelime-guide-creator` skill (located in `.agents/skills/guidelime-guide-creator/SKILL.md`) to generate a `.lua` Guidelime guide for "Uldaman Dungeon".

- **Metadata:**
    - **Name:** `[N Uldaman Dungeon Guide]`
    - **Description:** `[D Optimized route for completing all quests and bosses in Uldaman (Levels 35-45)]`
    - **Faction:** Universal (or separate guides/steps for Alliance and Horde where necessary).
- **Structure:**
    - **Preparation:** Instructions for gathering quests outside the dungeon (Badlands, Ironforge, Orgrimmar, etc.).
    - **Entrance:** Directions to the two dungeon entrances (Front and Back/Side).
    - **Walkthrough:** Step-by-step checklist of boss kills and quest objective completions within the instance.
    - **Hearth/Turn-in:** Instructions for leaving the dungeon and turning in the completed quests.

**Technical Note:**
Ensure the guide uses correct Guidelime markup (e.g., `[QA...]`, `[QC...]`, `[QT...]`, `[G...]`). Use `[C Class]` filters if there are class-specific quests (e.g., Paladin or Mage quests in Uldaman).

*Research and Handoff prepared by Jules.*
