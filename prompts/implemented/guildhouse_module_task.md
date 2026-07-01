# Task: Install and Configure mod-guildhouse (C++ Module Migration)

Hello Antigravity,

We are upgrading our server's Guild Housing system from the legacy Lua beta script to the official C++ **mod-guildhouse** module. The source code has already been synced to the `modules/mod-guildhouse` directory in this repository.

### 1. Synchronization & Build
- **Target Source**: The source is located in `modules/mod-guildhouse` within this workspace.
- **Deployment Steps**:
  1. Synchronize the `modules/mod-guildhouse` folder to the server's core modules path (typically `/home/coyofroyo/azerothcore-wotlk/modules/`).
  2. Navigate to the build directory on the server.
  3. Re-run CMake to include the new module. Use the standard build flags configured for our environment (ensure `-DACORE_MOD_GUILDHOUSE=ON` if required, though AzerothCore usually detects new folders in `modules/` automatically).
  4. Compile and install the core (`make install`).

### 2. Database Integration
- Apply the SQL migrations found in the module's `data/sql/` directory to the `acore_world` and/or `acore_characters` databases as specified by the module's README.
- Use the standard `acore` database credentials configured on the target host.

### 3. Service Transition & Cleanup
- **Configuration**: Copy the `.conf.dist` file to `.conf` in the server's module config directory.
- **Legacy Removal**: Once the C++ module is verified as operational, remove the legacy script `lua_scripts/guild_housing.lua` from the server's `lua_scripts` directory to prevent command conflicts.
- **Restart**: Perform a safe server restart (`.server restart 60`) to load the new binary.

### 4. In-Game Setup
- Spawn the **Guild House Recruiter** (NPC ID `500030`) in major faction capitals.
- Verify that the new `.gh` command prefix is active.

### 5. Success Criteria
- [ ] Module source is compiled into the `worldserver` binary.
- [ ] SQL tables for `mod_guild_house` are populated.
- [ ] NPC 500030 is spawned and interactive.
- [ ] Command `.gh tele` successfully teleports members to their phased instance.

Please update the status in the tracker once deployment is verified.
