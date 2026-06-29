---
name: changelog-tracker
description: Automatically updates the server wiki's changelog timeline whenever server files, configurations, database, or addon shims are modified.
---

# Changelog Tracker Skill

This skill enforces version control and maintains transparency for server modifications. 

## When to Use
Whenever you make **any** functional changes to the server setup:
- Database schema changes or template tuning (e.g. AHBot settings, creature rates, etc.)
- Code modifications/patches in `azerothcore-wotlk` C++ source files
- Configuration edits in `worldserver.conf` or module `.conf` files
- Client-side addon patches (Questie compatibility, Guidelime shims, etc.)

## Instructions
1. After completing any change, connect to the server and locate the web wiki entry file: `/var/www/html/wiki/index.php`.
2. Locate the Change Log tab section (`<div id="tab-changelog" ...>`).
3. Add a new item or version entry representing your changes:
   - Use standard HTML formatting matching the timeline layout.
   - Group changes under categorized headings (e.g. `🔧 Client Addon Patches`, `👥 Bot Companions`, `⚙️ Server & Database`).
4. Ensure the current version is updated appropriately (e.g. increment minor version `v1.0.1`, `v1.1.0` depending on modification scale).
5. Explain the changes clearly so the players can verify them in-game.
