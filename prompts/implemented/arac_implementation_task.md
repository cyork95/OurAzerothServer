# Task: Implement All Races All Classes (ARAC) Mod

## Objective
Implement the "All Races All Classes" (ARAC) module on the AzerothCore server and ensure all client/server synchronization steps are completed.

## Resources
- **Repository:** [heyitsbench/mod-arac](https://github.com/heyitsbench/mod-arac)
- **Catalogue ID:** 236337938

## Implementation Steps

### 1. Server-Side Module Installation
- Clone the `mod-arac` repository into the `modules/` directory of the AzerothCore source.
- Re-run CMake and rebuild the server to include the new C++ module.

### 2. Database Updates
- Apply the SQL script found at `data/sql/db-world/arac.sql` in the repository to the `world` database.
- This script updates `char_titles`, `player_classlevelstats`, `player_levelstats`, and `skill_race_class_info`.

### 3. Server DBC Updates
- The mod requires updated DBC files on the server to recognize the new combinations.
- Copy the contents of the `patch-contents/DBFilesContent` directory from the repository to the server's `data/dbc/` directory.

### 4. Client-Side Patching
- Provide the `Patch-A.MPQ` file (found in the repository root) to players.
- Instructions for players: Place `Patch-A.MPQ` into the `WoW/Data/` directory.

### 5. Configuration
- Create or update `/home/coyofroyo/azeroth-server/etc/modules/mod_arac.conf`.
- Ensure the following setting is present:
  ```conf
  ARAC.Enable = 1
  ```
- Note: This setting is now manageable via the Admin Console.

## Verification Checklist
- [ ] Server builds successfully with `mod-arac`.
- [ ] SQL updates applied to the `world` database without errors.
- [ ] Server starts and loads the `mod-arac` module.
- [ ] A player using the `Patch-A.MPQ` can create previously "invalid" combinations (e.g., Human Druid, Undead Paladin).
- [ ] The Admin Console toggle correctly enables/disables the feature by modifying `mod_arac.conf`.
