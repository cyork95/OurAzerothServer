# Research Report: Implementing New Races in AzerothCore (WotLK 3.3.5a)

## 1. Overview
Adding a brand-new race to a World of Warcraft: Wrath of the Lich King (3.3.5a) server is a **high-complexity** architectural task. It requires synchronized modifications across the game client's data files, the character creation interface, and the server's core logic and databases.

## 2. Technical Requirements

### Client-Side Interface & Data
To make a new race selectable and visible to players, the game client must be patched with several modifications:
*   **Race Definitions (Data Files):** The client's internal data files (DBC - Data Box Category) that define races, their internal IDs, names, and faction alignments must be updated. This includes files such as `ChrRaces.dbc` and `CharBaseInfo.dbc`.
*   **Visual Customization:** Character models, textures, and customization options (skin color, hair style, etc.) must be defined in files like `CharSections.dbc`. If using non-native models, new `.m2` and `.blp` files must be added to the client.
*   **Character Creation UI:** The character selection and creation screen (GlueXML) must be modified to include the new race icon and handle the logic for selecting valid classes for that race. This usually involves editing `CharacterCreate.xml` and its associated Lua logic.

### Server-Side Data
The server must be aware of the new race to validate character creation and initialize character data:
*   **Character Initialization:** The server's world database contains tables that define the starting location, attributes (Strength, Agility, etc.), starting spells, and starting equipment for every race/class combination. These tables (typically prefixed with `player_create_info`) must be populated for the new race ID.
*   **Faction & Reputation:** The server must recognize the new race's default faction (Alliance or Horde) and set up appropriate starting reputation levels with game factions.

### Core System Logic
*   **Race Identifiers:** The AzerothCore engine utilizes internal enumerations (enums) to identify races. Adding a new race requires defining a new identifier in the core's source code (e.g., in `SharedDefines.h`) and recompiling the server.
*   **Logic Hooks:** Certain game mechanics (like race-specific mounts, languages, and racial traits) may require additional C++ scripting to function correctly for the new race ID.

## 3. Implementation Complexity & Risks

### Synchronization Challenge
The Client and Server **must** use the exact same ID for the new race. If they are out of sync, the character creation process will fail, or the player will experience fatal client crashes (Error #132).

### Client Patch Distribution
Because most of these changes are client-side, every player must download and install a custom `.MPQ` patch. This is the most significant hurdle for deployment, as it requires players to manually modify their game installation.

### Engine Hardcoding
The 3.3.5a client has several hardcoded limits regarding the number of available race slots in the UI. Adding more than the standard 10 races often requires advanced "binary patching" of the `Wow.exe` file or complex UI overrides to create a scrolling selection list.

## 4. Recommended Roadmap
1.  **Phase 1: Research & Assets.** Identify the models and textures to be used and verify their compatibility with the 3.3.5a engine.
2.  **Phase 2: Client Prototype.** Modify the client-side data files and UI logic to see if the race appears in the character creation screen.
3.  **Phase 3: Server Integration.** Update the server-side database tables and core enums to support the new race ID.
4.  **Phase 4: Testing.** Verify that characters can be created, saved to the database, and logged into the game world without crashing.
5.  **Phase 5: Deployment.** Package the client changes into an `.MPQ` patch and provide installation instructions to the user base.

---
**Status:** Research Complete. Implementation deferred until specialized client-patching tools and core source access are prioritized.
