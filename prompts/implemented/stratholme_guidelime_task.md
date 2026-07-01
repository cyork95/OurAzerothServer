# Task: Create Guidelime Guide for Stratholme Undead Dungeon Quests

**Context:**
A request has been made to create a custom Guidelime guide for Stratholme Undead (UD) dungeon quests and strategy, including the Dungeon Set 2 (Tier 0.5) quest chain. This research prompt summarizes the key quests, NPC locations, and dungeon flow based on WotLK 3.3.5a data to guide your implementation using the `guidelime-guide-creator` skill.

**Research Summary (Stratholme Undead):**

Stratholme Undead is a level 55-60 dungeon located in the northern part of Eastern Plaguelands. Access is typically via the Service Gate (requires Key to the City).

### 1. Quest List & IDs (WotLK 3.3.5a)

- **The Flesh Does Not Lie**
  - NPC: Betina Bigglezink (Light's Hope Chapel)
  - Objective: Collect 20 Plagued Flesh Samples.
  - *Note: Verify ID (Likely in the 52xx range).*

- **The Active Agent (Quest ID: 5213)**
  - NPC: Betina Bigglezink (Light's Hope Chapel)
  - Objective: Retrieve Scourge Data from one of the ziggurats.

- **Above and Beyond (Quest ID: 5263)**
  - NPC: Duke Nicholas Zverenhoff (Light's Hope Chapel)
  - Objective: Kill Baron Rivendare and retrieve his head.

- **Houses of the Holy (Quest ID: 5243)**
  - NPC: Leonid Barthalomew the Revered (Light's Hope Chapel)
  - Objective: Collect Stratholme Holy Water from supply crates.

- **The Medallion of Faith**
  - NPC: Aurius (Entrance of UD side)
  - Objective: Bring Medallion of Faith (from Live side) to Aurius.

- **Dead Man's Plea (Quest ID: 8963)**
  - NPC: Anthion Harmon (Ghost outside UD entrance)
  - Objective: Complete the 45-minute "Baron Run" to save Ysida Harmon. Part of the Tier 0.5 chain.

- **Menethil's Gift**
  - Part of the long Darrowshire/Ras Frostwhisper chain.

### 2. Boss IDs
- **Magistrate Barthilas:** 10435
- **Baroness Anastari:** 10436
- **Maleki the Pallid:** 10437
- **Nerub'enkan:** 10438
- **Ramstein the Gorger:** 10439
- **Baron Rivendare:** 10440

### 3. Dungeon Route & Strategy
1. **Enter via Service Gate:** [G27, 12 Eastern Plaguelands].
2. **Clear three Ziggurats:**
   - **Nerub'enkan's Ziggurat**
   - **Baroness Anastari's Ziggurat**
   - **Maleki the Pallid's Ziggurat**
   *Note: All Acolytes inside must be killed to unlock the Slaughter Square gate.*
3. **Magistrate Barthilas:** Kill him near the gate to Slaughter Square.
4. **Slaughter Square:** Clear Abominations to spawn Ramstein the Gorger.
5. **Ramstein the Gorger:** Kill him and then the waves of Black Guard Sentry.
6. **Baron Rivendare:** Final boss. Avoid Unholy Aura and Cleave.

---

# Task Prompt for Guidelime Guide Creator Agent

**Objective:**
Use your `guidelime-guide-creator` skill to generate a `.lua` Guidelime guide for "Stratholme Undead Dungeon Quests".

**Requirements:**

1.  **Metadata:**
    - **Name:** `[N55-60 Stratholme Undead Quests]`
    - **Description:** `[D Comprehensive guide for Stratholme Undead quests and 45-minute Baron Run]`
    - **Faction:** Universal (Quest NPCs are Neutral/Argent Dawn).

2.  **Guide Structure:**
    - **Prerequisites:** Instructions to pick up quests at Light's Hope Chapel [G75, 52 Eastern Plaguelands].
    - **Entrance:** Instructions to use the Service Gate [G27, 12 Eastern Plaguelands].
    - **Dungeon Steps:**
      - Progress through the three ziggurats (Nerub'enkan, Anastari, Maleki).
      - Collection objectives: `[QC5213]`, `[QC5243]`.
      - Kill Magistrate Barthilas.
      - Slaughter Square gauntlet.
      - Baron Rivendare: `[QC5263]` and the timed objective for `[QC8963]`.
    - **Turn-ins:** Return to Light's Hope Chapel.

3.  **Formatting:**
    - Use appropriate Guidelime markup (`[QA...]`, `[QT...]`, `[QC...]`, `[G...]`).
    - Integrate strategy tips (e.g., clearing acolytes).

**Technical Note:**
Ensure the guide is concise and follows the `.lua` shell format required by the skill.
