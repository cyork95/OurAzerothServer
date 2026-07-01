# Handoff: Razorfen Kraul Guidelime Guide

**Subject:** Maintenance and expansion of the Razorfen Kraul (RFK) dungeon guide.

## 1. Instructions for Antigravity
When updating or expanding this guide, you **MUST** use the `guidelime-guide-creator` skill located at `.agents/skills/guidelime-guide-creator/SKILL.md`. This skill contains the necessary markup reference for the Guidelime addon format.

## 2. Research Summary
The guide was created based on the strategy and quest list for Razorfen Kraul in WoW Classic.

### Key NPC and Boss IDs:
- **Roogug (Optional/Warrior):** 6168
- **Aggem Thorncurse:** 4424
- **Death Speaker Jargba:** 4428
- **Overlord Ramtusk:** 4420
- **Agathelos the Raging:** 4422
- **Charlga Razorflank (Final):** 4421
- **Willix the Importer (Escort):** 4508

### Quest IDs:
- **The Crone of the Kraul (Alliance):** 1101
- **Mortality Wanes (Alliance):** 1142
- **Fire Hardened Mail (Alliance Warrior):** 1701
- **A Vengeful Fate (Horde):** 1102
- **Going, Going, Guano! (Horde):** 1109
- **An Unholy Alliance (Horde):** 6522
- **Brutal Armor (Horde Warrior):** 1838
- **Blueleaf Tubers (Neutral):** 1221
- **Willix the Importer (Neutral Escort):** 1144

## 3. Guide Location
The local source for the guide is `guides/Guidelime_RazorfenKraul.lua`. It includes separate registrations for Alliance and Horde, with class filtering `[C Warrior]` for class-specific quests.

## 4. Deployment Instructions
To deploy this guide to the live server:
1. SSH into the headless server (`192.168.1.168`).
2. Navigate to the Guidelime guides directory: `/home/coyofroyo/azeroth-server/interface/AddOns/Guidelime/Data/`.
3. Create a new directory for custom guides if it doesn't exist (e.g., `Guidelime_Custom`).
4. Transfer `Guidelime_RazorfenKraul.lua` to that directory and ensure it is registered in the addon's `.toc` file or loaded via a main data file.
   - *Note:* Guidelime typically scans the `Data` folder for registered guides.

*Research and Handoff prepared by Jules.*
