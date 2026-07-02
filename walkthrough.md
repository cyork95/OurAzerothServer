# Walkthrough: Dashboard Enhancements, Custom Mail, & Item Model Selector

We have successfully implemented, verified, and deployed the advanced GM features, Custom Mail System, and Item Visual Model selector to the OurAzeroth Server Admin Panel.

## Changes Made

### 1. Dynamic Read-Only Ollama Status Card (Dashboard)
* **Tuner Tab Removal**: Removed the separate "Ollama LLM Tuner" tab and navigation links from the admin panel completely, removing edit form complexity.
* **Dashboard status Card**: Added a new read-only **Ollama AI Spatial Chat Status** card at the bottom of the main dashboard tab.
* **Dynamic Backend Checks**: Updated the `get_system_stats` PHP action to:
  * Parse config settings dynamically from `/home/coyofroyo/azeroth-server/etc/modules/mod_ollama_chat.conf` (quotes optional).
  * Run a live socket check (`fsockopen`) on host/port connection parameters to determine if the local Ollama API is active. This replaced the initial cURL check which crashed on runtime because the Ubuntu PHP 8.5 stack lacks the `php-curl` extension.
* **Auto-update Loop**: Linked retrieved data to the main 5-second JS stats update loop, keeping Host Connection, Port, Active Model, Temperature, and API Status dynamically updated and populated on the dashboard.

### 2. GM Tools (Quick Mail Packages & Unlocks)
* **Gold packages**: Added `🪙 Send 1 Gold` and `🪙 Send 10 Gold` options in addition to the `100 Gold` and `1000 Gold` packages.
* **GM Cheats**: Integrated quick actions for unlocking taxi flight paths (`.taxi taximax`) and revealing maps (`.character learn 2259`).

### 3. Custom Mail & Attachment Spawner Card
* Added a new **Custom Mail & Item Spawner Service** inside the *Character Tools* tab.
* **Recipient field**: Automatically pre-fills when you click/select a character from the main editor grid above.
* **Item Autocomplete attachments**: Allows typing an item name or template ID. Queries the `item_template` database on-the-fly, lets you specify stack counts, and dynamically renders visual attachment slots (supports up to 12 items).
* **SOAP dispatch**: Converts custom Gold inputs to copper and executes `.send money` and `.send items` commands. If both are attached, it splits them into two clean in-game mail deliveries so the character receives both.
* **Autocomplete Input Fix**: Resolved an API input format mismatch where the backend read from `$_GET['query']` but the autocomplete searched via POST request. Modified the backend action to accept both `$_POST['query']` and `$_GET['query']`, allowing autocomplete lookups to function perfectly.

### 4. Custom Item Visual Model (Display ID) Copier
* **Display ID field**: Added a numeric input to the *Item Creator & Spawner* tab to customize the weapon/armor visual appearance (Display ID).
* **Copy Look Autocomplete**: Added a search box `Copy Look from Database Item`. Start typing any item name (e.g. *Ashbringer*, *Aegis of the Scarlet Commander*). Clicking it retrieves that item's `displayid` from the database and fills it in.
* **Backend binding**: Updated the item search endpoint to query `displayid` and modified the custom item insertion query to bind and write the chosen model into `acore_world.item_template`.

### 5. Layout Alignment Fixes
* **Column Wrapping**: Grouped the `Character Editor & Teleporter` card and the `Custom Mail & Item Spawner Service` card inside a single left-hand vertical column container (`display: flex; flex-direction: column`).
* Grouped the `Create Game Account` card inside a separate right-hand vertical column. This aligns the two columns side-by-side without vertical clipping or wrapping gaps.

### 6. Spawner Recipient Autocomplete Search
* **Autocomplete Integration**: Converted the recipient text box `Mail Directly to Character (Name)` (`#itmCharName`) in the **Item Creator & Spawner** form into an interactive autocomplete search.
* Starts searching the database characters list as you type (minimum 2 characters), displaying a dropdown list showing character names and their levels. Clicking a character automatically selects them.

### 7. Overhauled Item Creator (Weapons, Armor, Accessories Subforms)
* **Visual Sub-tabs**: Added tab buttons inside the Item Creator to switch between `Weapon Creator`, `Armor & Shields`, and `Trinkets & Accessories`. Each card exposes only its relevant input fields (e.g., Attack Speed and Damage bounds for Weapons; Armor value and Block rating for Shields).
* **Stat Guideline Helper Labels**: Added inline labels underneath stats explaining their core in-game calculations (e.g., Stamina shows "Increases Max Health (+10 HP per point)"; Intellect shows "Increases Max Mana & Spell Crit").
* **Item Spells & Enchants Dropdowns**:
  * **Weapons**: Dropdown selector supporting Fiery Weapon (proc), Lifesteal (proc), Unholy Weapon (proc), Spell Power +30 (passive), and Healing Power +55 (passive).
  * **Armor/Shields/Accessories**: Dropdown selector supporting Spiked Shield (passive reflect), Mana Regen (+5 Mp5), and Health Regen (+5 Hp5).
* **Flexible Database Mapping**: Updated PHP action `create_custom_item` to dynamically package up to 10 customized stat modifiers (including Spell Power, Spirit, Crit, Haste, Attack Power, Block, and Defense) and bind them to active item templates.

### 8. AI Playerbots Chat Splitting (255-character Limit)
* **Problem**: WotLK client chat message limitations truncate text beyond 255 characters. Long AI responses generated by local LLMs were clipping and failing to render correctly in-game.
* **C++ Modification**: 
  * Declared a new C++ helper function `SplitMessageForChat(const std::string& msg)` inside `mod-ollama-chat-utilities.h` that parses messages and slices them cleanly into blocks of 255 characters or fewer.
  * Embedded intelligent UTF-8 multi-byte protection: Checks boundary bytes to ensure splits do not occur in the middle of a multi-byte UTF-8 sequence, scanning backwards/forwards to keep characters intact.
  * Wrapped direct chat triggers `botAI->Say`, `botAI->Yell`, `botAI->Whisper`, `botAI->SayToGuild`, `botAI->SayToParty`, `botAI->SayToRaid`, `botAI->SayToChannel`, and `targetChannel->Say` with split-send loops (`SendBotSay`, `SendChannelSay`, etc.) inside `mod-ollama-chat_events.cpp`, `mod-ollama-chat_random.cpp`, and `mod-ollama-chat_handler.cpp`.
* **Recompilation & Deploy**: Cleaned build files, ran `cmake ..` configuration hooks to generate script loaders, rebuilt worldserver dynamically in the background with `make -j6`, installed updated executable binaries, and restarted local services.
* **Outcome**: Playerbots sending long AI chats now split their thoughts into multiple sequential, clean in-game messages.

### 9. AI Playerbots Personalities Expansion (100+ Total Options)
* **Templates Pool Extended**: Injected 60 new/updated gaming and roleplaying personalities to bring the database total to around 100 unique options (e.g. *Casual Dad, LFG Spammer, Alt-oholic, Shaman Totem Lover, Hardcore Ironman, Discord Moderator, UI Modder, and Lore Historian*).
* **Wiped Historical Assignments**: Cleared persistent assignments (preserving Morgyn & Araenya's waifu assignments). All other active bots will choose from the 100+ template options.
* **Morgyn, Araenya, Mira, Wrenn, Willow, Mina, and Visas Custom Waifu Assignments**: Found the target GUIDs in the database and explicitly assigned them to the `CUSTOM_WAIFU` personality mapping, making them sweet, doting companions.
* **Sylvana Custom Elune Worshipper Assignment**: Created a new `CUSTOM_ELUNE_WORSHIPPER` template (devout, peaceful nature lover) and assigned it to `Sylvana` (GUID `1418`).

## Verification
* Checked PHP compiler syntax on server: `No syntax errors detected in /var/www/html/admin/index.php`.
* Validated LLM model output through stats API: returns `"model":"dolphin3:latest"` correctly.
* Checked active server process: `pgrep -x worldserver` is running.
