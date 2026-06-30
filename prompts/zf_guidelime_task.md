# Task: Create Guidelime Guide for Zul'Farrak Dungeon Quests

**Context:**
A request has been made to create a custom Guidelime guide for Zul'Farrak (ZF) dungeon quests and strategy. This research prompt summarizes the key quests, NPC locations, and dungeon flow based on WoW Classic resources to guide your implementation using the `guidelime-guide-creator` skill.

**Research Summary (Zul'Farrak):**

Zul'Farrak is a level 44-54 dungeon located in the northwest corner of Tanaris.

### 1. Quest List & Prerequisites
Ensure the player has accepted all these quests before entering:

- **Scarab Shells (Quest ID: 2865)**
  - NPC: Tran'rek (Gadgetzan, Tanaris [51.4, 28.8])
  - Objective: Collect 5 Unbroken Scarab Shells from Scarabs in ZF.
- **Troll Temper (Quest ID: 2769)**
  - NPC: Trenton Lighthammer (Gadgetzan, Tanaris [51.6, 28.8])
  - Objective: Collect 20 Troll Temper from Trolls in ZF.
- **The Divino-matic Rod (Quest ID: 2843)**
  - NPC: Chief Engineer Bilgewhizzle (Gadgetzan, Tanaris [52.4, 28.6])
  - Objective: Retrieve the Divino-matic Rod from Sergeant Bly (Stairs Event).
- **Tiara of the Deep (Quest ID: 2862)**
  - NPC: Tally Zapmaster (Shimmering Flats, Thousand Needles [78.4, 77.2])
  - Objective: Retrieve the Tiara of the Deep from Hydraxis (Antu'sul).
- **Gahz'rilla (Quest ID: 2770)**
  - NPC: Wizzle Brassbolts (Shimmering Flats, Thousand Needles [78.2, 77.4])
  - Objective: Kill Gahz'rilla.
- **The Prophecy of Mosh'aru (Quest ID: 2605)**
  - NPC: Yeh'kinya (Steamwheedle Port, Tanaris [67.0, 22.0])
  - Objective: Retrieve the First and Second Mosh'aru Tablets from Theka the Martyr and Hydromancer Velratha.

### 2. The Mallet of Zul'Farrak (Optional/Recommended)
- Travel to **Altar of Zul** in Hinterlands.
- Kill **Qiaga the Keeper** [48, 59] to loot the **Sacred Mallet**.
- Travel to **Jintha'alor** in Hinterlands.
- Use the mallet at the **Altar of Jintha'alor** [60, 68] at the top of the city to create the **Mallet of Zul'Farrak**.

### 3. Dungeon Route & Boss Order
1. **Antu'sul:** Kill him for the Tiara and Sul'thraze piece.
2. **Theka the Martyr:** Kill for the First Mosh'aru Tablet.
3. **Witch Doctor Zum'rah:** Kill for the Ward of the Witch Doctor.
4. **The Stairs Event:**
   - Speak to the Executioner to start the event.
   - Defend the NPCs during the waves.
   - Kill **Nekrum Gutchewer** and **Shadowpriest Sezz'ziz**.
   - **Important:** Talk to Sergeant Bly after the event to fight him for the **Divino-matic Rod**.
5. **Hydromancer Velratha:** Kill for the Second Mosh'aru Tablet.
6. **Chief Ukorz Sandscalp:** Final boss in the city.
7. **Gahz'rilla:** Use the Mallet on the gong in the pool area to summon.

---

# Task Prompt for Guidelime Guide Creator Agent

**Objective:**
Use your `guidelime-guide-creator` skill to generate a `.lua` Guidelime guide for "Zul'Farrak Dungeon Quests".

**Requirements:**

1.  **Metadata:**
    - **Name:** `[N44-54 Zul'Farrak Quests]`
    - **Description:** `[D Comprehensive guide for all Zul'Farrak dungeon quests and boss order]`
    - **Faction:** Universal (Quest NPCs are Neutral/Steamwheedle Cartel).

2.  **Guide Structure:**
    - **Prerequisites:** Instructions to pick up all 6 quests in Tanaris and Shimmering Flats (include coordinates).
    - **Mallet Prep:** A brief section advising players to obtain the Mallet of Zul'Farrak in the Hinterlands if they wish to summon Gahz'rilla.
    - **Dungeon Entrance:** `Go to [G39, 21 Tanaris]`.
    - **Dungeon Steps:**
      - Clear to Antu'sul: `[QC2862]`.
      - Clear to Theka the Martyr: `[QC2605]` (Tablet 1).
      - Collect Scarab Shells: `[QC2865]`.
      - Collect Troll Temper: `[QC2769]`.
      - The Stairs Event: Complete the defense and kill Nekrum.
      - Sergeant Bly: Kill him for the Rod: `[QC2843]`.
      - Gahz'rilla: Summon and kill: `[QC2770]`.
    - **Turn-ins:** Instructions to return to Gadgetzan, Steamwheedle Port, and Shimmering Flats to turn in all quests.

3.  **Formatting:**
    - Use appropriate Guidelime markup (`[QA...]`, `[QT...]`, `[QC...]`, `[G...]`).
    - Ensure the boss order is logical and matches the research summary.

**Technical Note:**
Ensure the guide is concise and follows the `.lua` shell format required by the skill.
