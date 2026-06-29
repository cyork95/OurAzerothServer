# Project Wiki & Agent Guidelines: OurAzerothServer

This workspace manages the orchestration, configuration, and maintenance of a single-player World of Warcraft (Wrath of the Lich King 3.3.5a) server.

---

## Architecture Overview

- **Workstation (Windows Host)**: Houses this VS Code workspace. Extracted client files (DBCs, maps, vmaps, mmaps) are stored locally here before transfer.
- **Headless Server (Linux Target)**:
  - **OS**: Ubuntu 26.04 LTS (x86_64)
  - **Target IP**: `192.168.1.168` (Static DHCP Reservation on Zyxel EX5512-T0)
  - **SSH User**: `coyofroyo`
  - **SSH Key Path (Windows)**: `C:\Users\coyof\.ssh\id_ed25519`
  - **Sudo Privilege**: Passwordless sudo configured for `coyofroyo`.

---

## Server Paths & Directory Structure

- **Core Source Code**: `/home/coyofroyo/azerothcore-wotlk`
- **Build Directory**: `/home/coyofroyo/azerothcore-wotlk/build`
- **Installation Directory**: `/home/coyofroyo/azeroth-server`
- **Data (DBC/Maps)**: `/home/coyofroyo/azeroth-server/data`
- **Web Root (Armory)**: `/var/www/html/armory`

---

## Database Stack (MariaDB)

- **World DB**: `acore_world`
- **Characters DB**: `acore_characters`
- **Auth DB**: `acore_auth`
- **Database User**: `acore`
- **Port**: `3306`

---

## Enabled Server Modules & Configuration Notes

1. **`mod-playerbots`**: Simulates local AI player economy and dungeon grouping.
2. **`mod-ollama-chat`**: Links Playerbots to local Ollama LLM for spatial chat.
3. **`mod-autobalance`**: Scales dungeon/raid difficulty dynamically based on party size.
4. **`mod-ahbot`**: Simulates a live auction house economy.
   > [!NOTE]
   > The bot is configured in `mod_ahbot.conf` to use **Account ID `1`** and **Character GUID `1`** (the bot character *Gnenlirgunk*) as the listing owner. This bypasses the default configuration crash and populates the Auction House automatically.
5. **`mod-transmog`**: Implements transmogrification features.

---

## Operational Guide (Run & Control)

### Starting the Server
The server runs inside a detached `tmux` session named `azeroth` on the Ubuntu target.
To start the services:
```bash
ssh -i C:\Users\coyof\.ssh\id_ed25519 coyofroyo@192.168.1.168 "~/start.sh"
```

### Attaching to Console
To monitor logs, issue server commands (e.g. creating accounts, checking bot status):
1. SSH into the server:
   ```bash
   ssh -i C:\Users\coyof\.ssh\id_ed25519 coyofroyo@192.168.1.168
   ```
2. Attach to the tmux session:
   ```bash
   tmux attach -t azeroth
   ```
3. Use `Ctrl+B`, then `D` to safely detach without killing the server.

### Stopping the Server
Inside the tmux session or via SSH:
```bash
ssh -i C:\Users\coyof\.ssh\id_ed25519 coyofroyo@192.168.1.168 "~/stop.sh"
```

---

## Custom Client Addon Patches & Shims (WotLK 3.3.5a Compatibility)

To bridge newer UI coordinate, map, and database APIs down to the legacy WotLK client, we injected custom compatibility patches in the client-side files:

### 1. HereBeDragons-Pins / WorldMapFrame MapCanvas Shim
Modern MapCanvas APIs are absent in 3.3.5a. We injected global shims on the `WorldMapFrame` inside `Questie/Compat/Compat.lua`:
- `WorldMapFrame.pinPools = {}`
- `WorldMapFrame:GetMapID()` maps to `QuestieCompat.GetCurrentUiMapID`.
- `WorldMapFrame:AddDataProvider(provider)` caches providers and binds `.GetMap` hooks.
- `WorldMapFrame:AcquirePin(template, icon)` parents and draws pins to `WorldMapDetailFrame`.
- `WorldMapFrame:RemovePin(pin)` and `WorldMapFrame:EnumeratePinsByTemplate(template)` manage active canvas markers.

### 2. C_QuestLog.GetQuestInfo Fallback
Modern QuestLog API calls crash on 3.3.5a due to missing fields. We shimmed `C_QuestLog.GetQuestInfo` in `Compat.lua` to safely return `nil`. This forces Guidelime to fall back cleanly to Questie's WotLK query wrapper (`QUESTIE.getQuestName`), preventing blank guide frames.

### 3. LibStub CreateFrame Memory & Scroll Frame Auto-Namer
- Anonymous frames using the `"UIPanelScrollFrameTemplate"` cause a concatenation crash on WotLK if they lack a global name. We hooked the global `CreateFrame` function in `Guidelime/Libs/LibStub/LibStub.lua` to automatically assign unique names to anonymous scroll frames and bind their `.ScrollBar` properties.
- We optimized the `CreateFrame` hook to utilize a single static `SetEnabledShim` function reference instead of allocating unique closure functions for every frame, eliminating fatal client `"memory allocation error: block too big"` crashes.

### 4. Vector Coordinates & GetXY Bridging
We injected custom table wrappers with `.GetXY()` methods and global `CreateVector2D` shims into `Compat.lua` to ensure that HereBeDragons-2.0's vector math works seamlessly on legacy 2D coordinate maps.

---

## Mail System Database Fix

> [!IMPORTANT]
> The server's Eluna Lua script `autobiographer.lua` has a mail event handler `OnPlayerSendMail` that was missing a return statement, causing it to return `nil` (interpreted as `false` in Eluna) and cancel all outgoing mail transactions with an `Internal mail database error` client message.
>
> We commented out the hook registration in `/home/coyofroyo/azeroth-server/bin/lua_scripts/autobiographer.lua`:
> ```lua
> -- RegisterPlayerEvent(49, OnPlayerSendMail)
> ```
> This completely restores normal mail sending and receiving functionality.

---

## Installed Guides & Bestiary Addons

### 1. Leveling Guides (Guidelime)
- **Guidelime_TUGs_Vanilla**: The standard 1-60 leveling guides (includes Dwarf, Gnome, Human, Night Elf starting zones).
- **Guidelime_Flymolo's Outland Leveling Guide**: Leveling guides for Outland (58-69).
  > [!TIP]
  > Because Guidelime filters guides based on level, Outland guides are hidden for level 1 characters by default. You can type `/lime` -> option menu, and check **"Show inapplicable guides"** to view all guide ranges.

### 2. Bestiary Addon (MobInfo-2)
- Installed from `UnePomme/ChromieRepo` (curated 3.3.5a code).
- **What it does**: Tracks mob health, mana, damage ranges, resistances, and records kill statistics/item drop rates. Adds bestiary data directly to target tooltips.
- **Commands**: Type `/mi2` in-game to configure.

---

## Server Safety & Shutdown Warnings

> [!IMPORTANT]
> To prevent loss of character progress, database rollbacks, and annoying game disconnects:
> *   **NEVER** run the `.reload all` command on a live server (it invalidates item/creature pointers in active memory, causing instant segmentation fault crashes).
> *   **Always Warn Players:** Before doing anything that has a risk of resetting or crashing the server, you **MUST** warn the players.
> *   **Clean Countdowns:** Use the built-in server shutdown utility to broadcast a countdown warning and safely flush database transactions:
>     ```bash
>     # Schedule a safe shutdown/restart in 5 minutes (300 seconds)
>     .server shutdown 300
>     ```
>     This automatically alerts all active players, displays a screen-center countdown, saves all character inventories, and shuts down safely.


