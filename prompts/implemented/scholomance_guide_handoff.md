# Handoff: Scholomance Guidelime Guide

**Subject:** Refinement and Expansion of the Scholomance Dungeon Guide.

## 1. Instructions for Antigravity
When refining or expanding this guide, you **MUST** use the `guidelime-guide-creator` skill located at `.agents/skills/guidelime-guide-creator/SKILL.md`. Ensure that all markup follows the Guidelime/RestedXP format.

## 2. Research Summary
The guide was created based on available data for Scholomance in WoW Classic / 3.3.5a. Due to connectivity issues and search limitations, some Quest IDs are missing and should be verified against the live database when possible.

### Key NPC and Boss IDs:
- **Darkmaster Gandling (Final Boss):** 1853
- **Kirtonos the Herald:** 10506
- **Jandice Barov:** 10503
- **Ras Frostwhisper:** 10508
- **Doctor Theolen Krastinov:** 11261
- **Eva Sarkhoff (Quest Giver):** 10216
- **Duke Nicholas Zverenhoff:** 11039
- **Deliana (Alliance DS2):** 16013
- **Mokvar (Horde DS2):** 16012

### Quest IDs:
- **The Truth Comes Crashing Down:** 5262
- **Above and Beyond:** 5263
- **Lord Maxwell Tyrosus:** 5264
- **The Argent Hold:** 5265
- **Better Late Than Never:** 5021
- **An Earnest Proposition (Horde DS2):** 8920
- **An Earnest Request (Alliance DS2):** 8919 (Needs Verification)

### Missing Data:
- Quest IDs for `Kirtonos the Herald`, `Krastinov's Bag of Horrors`, and `The Lich, Ras Frostwhisper` need to be retrieved from the `quest_template` table in the `acore_world` database.
- Coordinates for specific boss rooms within the dungeon should be added if available.

## 3. Guide Location
The local source for the guide is `guides/Guidelime_Scholomance.lua`.

## 4. Deployment Instructions
1. SSH into the headless server (`192.168.1.168`).
2. Transfer `Guidelime_Scholomance.lua` to `/home/coyofroyo/azeroth-server/interface/AddOns/Guidelime/Data/`.
3. Verify that the guide appears in the Guidelime selection menu in-game.

*Research and Handoff prepared by Jules.*
