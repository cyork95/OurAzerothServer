# OurAzeroth Server Workspace

This workspace manages the configuration, database migrations, client-side addons compatibility patches, and administrative web console deployments for a single-player World of Warcraft (Wrath of the Lich King 3.3.5a) server.

---

## Architecture & Server Topology

The architecture consists of a local development workstation orchestrating deployments on a dedicated headless server on the local network.

### 1. Workstation Host (Windows Dev environment)
- **Role**: Code edits, compatibility shims development, manual database verification, and repository hosting.
- **Git Repository**: Houses local copies of configurations, deployment scripts, client-side overrides, and custom guide structures.

### 2. Headless Server Target (Linux Host)
- **Operating System**: Ubuntu 26.04 LTS (x86_64)
- **Network Address**: `192.168.1.168` (Static DHCP Reservation on Zyxel EX5512-T0 router)
- **SSH User**: `coyofroyo`
- **SSH Key Path (Windows)**: `C:\Users\coyof\.ssh\id_ed25519` (Configured with passwordless `sudo` privileges)

### 3. Server Paths
- **Core Source Code**: `/home/coyofroyo/azerothcore-wotlk`
- **Build Directory**: `/home/coyofroyo/azerothcore-wotlk/build`
- **Installation Directory**: `/home/coyofroyo/azeroth-server`
- **Data Files (DBC/Maps)**: `/home/coyofroyo/azeroth-server/data`
- **Web Root (Armory/Admin Panel)**: `/var/www/html/admin/`

---

## Database Stack (MariaDB)

The database server runs on Port `3306` on the Ubuntu target:
*   **World Database**: `acore_world` (Creature, item, quest templates)
*   **Characters Database**: `acore_characters` (Player profiles, inventories, mail, bot custom followers)
*   **Auth Database**: `acore_auth` (User login accounts, GM privileges, and account linkage)
*   **Database User**: `acore`

---

## Enabled Server Modules

1.  **`mod-playerbots`**: Simulates a live local AI player economy, dungeon groups, and whispers.
2.  **`mod-ollama-chat`**: Links playerbots to a local LLM backend (running Llama 3.1 8B fine-tuned `dolphin3` engine) for immersive chat.
3.  **`mod-autobalance`**: Dynamically scales dungeon and raid health/damage difficulty based on party size.
4.  **`mod-ahbot`**: Populates the Auction House automatically. Uses Account ID `1` and Character GUID `1` (*Gnenlirgunk*) to bypass defaults and ensure a functional economy.
5.  **`mod-transmog`**: Provides custom transmogrification service NPCs.

---

## Custom Client Addon Patches & Compatibility Shims

Legacy WotLK 3.3.5a game clients crash or fail when newer addon APIs (e.g. MapCanvas, Vector math) are requested. We inject the following custom shims to bridge compatibility:

### 1. UIPanelScrollFrame Auto-Namer & Memory Fix
- **File**: `Interface/AddOns/Guidelime/Libs/LibStub/LibStub.lua`
- **Fix**: Hooked `CreateFrame` to auto-assign unique global names to anonymous scroll panels. Uses a single static `SetEnabled` shim reference instead of unique closure allocations to prevent fatal client `"memory allocation error: block too big"` crashes.

### 2. HereBeDragons-Pins / WorldMapFrame MapCanvas Shim
- **File**: `Interface/AddOns/Questie/Compat/Compat.lua`
- **Fix**: Shims MapCanvas API functions (`GetMapID`, `AddDataProvider`, `AcquirePin`, `RemovePin`) onto `WorldMapFrame` and parents them to the WotLK `WorldMapDetailFrame`.

### 3. C_QuestLog.GetQuestInfo Fallback
- **File**: `Interface/AddOns/Questie/Compat/Compat.lua`
- **Fix**: Shims `C_QuestLog.GetQuestInfo` to safely return `nil`, forcing Guidelime to fall back cleanly to Questie's WotLK query wrapper (`QUESTIE.getQuestName`) rather than throwing fatal client errors.

### 4. Vector Coordinates & GetXY Bridging
- **File**: `Interface/AddOns/Questie/Compat/Compat.lua`
- **Fix**: Injects global `CreateVector2D` shims and table structures with `.GetXY()` to support HereBeDragons-2.0's vector math calculations.

### 5. C_Timer Shim
- **File**: `Interface/AddOns/Questie/Compat/Compat.lua` (and local copies in `scripts/CTimerShim.lua`)
- **Fix**: Provides `C_Timer.After` compatibility using global frame `OnUpdate` ticks to execute delayed actions safely wrapped in `pcall` logic.

---

## Operational Guide

### Starting the Server
Services are run inside a detached `tmux` session named `azeroth` on the Ubuntu server. Launch it from the workstation using:
```bash
ssh -i C:\Users\coyof\.ssh\id_ed25519 coyofroyo@192.168.1.168 "~/start.sh"
```

### Stopping the Server
Stop the services and save active players' state safely:
```bash
ssh -i C:\Users\coyof\.ssh\id_ed25519 coyofroyo@192.168.1.168 "~/stop.sh"
```

### Server Console Access
To run administrative commands (e.g. creating accounts, checking bot status):
1.  SSH into the server: `ssh -i C:\Users\coyof\.ssh\id_ed25519 coyofroyo@192.168.1.168`
2.  Attach to the active TMUX console: `tmux attach -t azeroth`
3.  *Safe Detach*: Press `Ctrl + B` then `D` to close the connection without halting the server process.

### Safe Countdown Restart/Shutdown
To prevent rollbacks and save character state:
*   **DO NOT** run `.reload all` (causes segment-fault crashes).
*   Issue a countdown save command inside the server console:
    ```text
    .server shutdown 300
    ```
    This schedules a shutdown in 5 minutes (300 seconds), broadcasts a countdown to active players, and flushes database transactions.

---

## Admin Portal & Wiki Maintenance

The admin portal/wiki is served from the server at `https://yorkdevelops.com/OurAzerothServer/` (hosted on GitHub Pages) and mirrored on the local network target at `/var/www/html/admin/index.php`.

### Deployment Pipeline
1.  Do not edit server index pages directly. Always modify the local [index.html](index.html) in this workspace.
2.  Run the local deployment pipeline:
    ```bash
    python update_wiki.py
    ```
3.  **What it does**:
    - Triggers the database character logs exporter on the remote server.
    - Downloads the fresh `wiki_data.json` data dump to the local directory.
    - Stages and commits `index.html` and `wiki_data.json` to Git.
    - Pushes the updates to GitHub `master`, which triggers the GitHub Pages automatic deployment pipeline.

---

## Important Links & Tools

*   **Server Wiki / Portal**: [yorkdevelops.com/OurAzerothServer/](https://yorkdevelops.com/OurAzerothServer/) (Served via GitHub Pages)
*   **Google Sheets Bug Tracker**: [Server Bug Tracker / Task List](https://docs.google.com/spreadsheets/d/1b-7MRCZahkF4Thrb-_QIzMAWEakXTTGtrdt3MRMBR-4/edit?gid=1752953381#gid=1752953381)
*   **External Addons Source**: [Felbite Addons Database](https://felbite.com/addons/) (Curated 3.3.5a client addons and WeakAuras)
