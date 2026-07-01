# Handoff: Blackrock Spire & Dungeon Set 2 Guidelime Guide

**Subject:** Creation of a Guidelime guide for Lower Blackrock Spire (LBRS), Upper Blackrock Spire (UBRS), and the Dungeon Set 2 (Tier 0.5) questline.

## 1. Instructions for Antigravity
When creating this guide, you **MUST** use the `guidelime-guide-creator` skill located at `.agents/skills/guidelime-guide-creator/SKILL.md`. This skill contains the necessary markup reference for the Guidelime addon format.

The user wants a comprehensive guide covering the quests and strategies found in the provided Wowhead links:
- [Dungeon Sets 1 & 2 Quests](https://www.wowhead.com/classic/guide/dungeon-sets-1-2-quests-wow-classic)
- [LBRS Strategy & Quests](https://www.wowhead.com/classic/guide/lower-blackrock-spire-lbrs-dungeon-strategy-wow-classic)
- [UBRS Strategy & Quests](https://www.wowhead.com/classic/guide/upper-blackrock-spire-ubrs-dungeon-strategy-wow-classic)

## 2. Research Summary

### Key Quest IDs:
- **Seal of Ascension (UBRS Key):** 4742 (Starts from Vaelan in LBRS)
- **LBRS Main Quests:**
  - Warlord's Command (Horde): 4903
  - Maxwell's Mission (Alliance): 4941
  - Kibler's Exotic Pets: 4729
  - Mother's Milk: 4867
  - Urok Doomhowl: 4788
- **UBRS Main Quests:**
  - For The Horde! (Horde): 4974
  - Drakkisath's Demise: 5102
  - Finkle Einhorn, At Your Service!: 5067
- **Dungeon Set 2 (Tier 0.5) Start:**
  - An Earnest Offering (Alliance): 8911
  - An Earnest Offering (Horde): 8922

### Key Boss and NPC IDs:
- **LBRS Bosses:**
  - Highlord Omokk: 9196
  - Shadow Hunter Vosh'gajin: 9236
  - Warmaster Voone: 9237
  - Mother Smolderweb: 10596
  - Quartermaster Zigris: 9596
  - Halycon: 10220
  - Gizrul the Slavener: 10268
  - Overlord Wyrmthalak: 9568
- **UBRS Bosses:**
  - Pyroguard Emberstrider: 9816
  - Solakar Firebath: 10264
  - Goraluk Anvilcrack: 10899
  - Rend Blackhand: 10429
  - The Beast: 10430
  - General Drakkisath: 10363
- **Important NPCs:**
  - Vaelan (LBRS Entrance/Key Quest): 9516
  - Deliana (Alliance DS2): 16012
  - Anthion Harmon (LBRS Entrance/DS2): 16014

## 3. Guide Requirements
- Create separate registrations for Alliance and Horde where quest chains diverge.
- Include class-specific filters if any quest is class-restricted (e.g., `[C Warrior]`).
- Use coordinate tags `[G x,y Zone]` for quest givers and dungeon entrances.
  - Blackrock Mountain entrance is roughly `[G 29,38 Burning Steppes]`.
- Structure the guide into phases:
  1. **Pre-Dungeon Prep**: Quests to pick up outside.
  2. **Lower Blackrock Spire**: Strategy for all main bosses and the UBRS key.
     - *Omokk*: Pull to the balcony.
     - *Vosh'gajin*: Kill guards first, watch for hex.
     - *Voone*: Avoid front, watch for stuns.
     - *Urok*: Detailed event steps (Omokk's Head -> Pike).
     - *Wyrmthalak*: Clear adds, tank away from door.
  3. **Upper Blackrock Spire**: Strategy for the 10-man raid dungeon.
     - *Emberstrider*: Altar click sequence.
     - *Rend*: Arena wave management.
     - *The Beast*: Fear and fire management.
     - *Drakkisath*: Guard kiting strategy.
  4. **Dungeon Set 2 Milestones**: Integrating the upgrade quest steps.

## 4. Output Format
Provide the complete Lua code for `Guidelime.registerGuide()`. Save the final output to `guides/Guidelime_BlackrockSpire.lua`.

*Research and Handoff prepared by Jules.*
