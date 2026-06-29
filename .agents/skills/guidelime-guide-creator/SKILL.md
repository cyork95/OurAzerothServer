---
name: guidelime-guide-creator
description: Write custom leveling, dungeon, class, and event guides for the Guidelime / RestedXP addon format.
---

# Guidelime Guide Creator & Markup Reference

Use this skill to design, write, edit, and troubleshoot custom Guidelime guides (`.lua` files) for World of Warcraft (Vanilla, TBC, and WotLK 3.3.5a).

---

## 1. Guide Shell & Metadata Headers
Every guide is a `.lua` file containing a `Guidelime.registerGuide()` call:

```lua
Guidelime.registerGuide([[
[GA Alliance]           -- Faction (Alliance / Horde)
[N1-6 Elwynn Forest]    -- Guide Name (Shown in selection menu)
[NX1-6 Dun Morogh]      -- Next Guide suggestion
[D TUGs Custom Guide]   -- Description text (Shown in details panel)
[GA Human]              -- Race applicability restriction (Human, Dwarf, Gnome, NightElf, Orc, Tauren, Troll, Undead, Draenei, BloodElf)

-- [Guide Steps go here]
]], "Custom Guides Pack")
```

---

## 2. Guide Steps & Quest Markup

Each line represents an action or a checklist step. Use these tag codes inside brackets:

### Quest Tracking
*   **Accept Quest:** `[QA123]` — Tracks when quest ID `123` is accepted.
*   **Turn In Quest:** `[QT123]` — Tracks when quest ID `123` is turned in.
*   **Complete Quest:** `[QC123]` — Tracks when quest objectives for ID `123` are finished.

### Location & Travel
*   **Go To coordinates:** `[G12.3,45.6 ZoneName]` — Shows coordinates `(12.3, 45.6)` on the map/minimap and the navigation arrow pointing to it.
*   **Hearthstone:** `[H Hearthstone Destination]` — Instructs player to use Hearthstone to a destination.
*   **Set Hearthstone:** `[S Innkeeper Name]` — Instructs player to set hearthstone at an inn.
*   **Fly to destination:** `[F FlightPath Destination]` — Instructs player to fly to a flight path.
*   **Get Flight Point:** `[P FlightPoint Location]` — Instructs player to learn/activate a new flight node.

### Items, Spells, and NPCs
*   **Collect Item:** `[CI11515,6 Corrupted Soul Shards]` — Objective to collect item ID `11515` (requires 6 of them).
*   **Use Item:** `[UI5996 Elixir of Water Breathing]` — Instructs player to use item ID `5996`.
*   **Target NPC:** `[TAR29611 King Varian Wrynn]` — Targets NPC ID `29611` when within range.
*   **Cast Spell:** `[SP3561 Stormwind Teleport]` — Instructs player to cast spell ID `3561`.
*   **Visit Trainer:** `[T]` — Tells player to train new skills.

### Character Progression & Reputations
*   **Train Spell/Skill:** `[LE Tailoring 125]` — Learns spell/skill ID or level.
*   **Level up Skill:** `[SK Tailoring 45]` — Level Tailoring skill up to level 45.
*   **Level up Experience:** `[XP18]` — Tells player to grind until they reach level 18.
*   **Reputation Milestone:** `[REP Timbermaw Hold -3000]` — Grind rep until Unfriendly (-3000 rep points).

---

## 3. Applicability Filters (Conditionals)

Add tags at the end of lines to limit them to specific classes, races, or setups:
*   **Class Filter:** `[A Mage]` or `[A Rogue]` — Line will only show for that class.
*   **Race Filter:** `[A NightElf]` — Line will only show for Night Elves.
*   **Combined Filter:** `[A Priest][A Dwarf]` — Only shows for Dwarf Priests.

---

## 4. Custom Guide Code Template (Example)

Here is a ready-to-copy custom blueprint guide for Westfall Deadmines prep:

```lua
Guidelime.registerGuide([[
[GA Alliance]
[N18-20 Deadmines Prep]
[NX20-22 Redridge Mountains]
[D Pre-requisites and setup steps for entering Deadmines]

Set your Hearthstone: Set hearth in [S Westfall Inn]
Activate the Flight Point: [P Sentinel Hill, Westfall]
Accept the Defias Chain: Accept [QA2040 The Defias Brotherhood]
Accept cave quest 1: Accept [QA167 Red Silk Bandanas]
Accept cave quest 2: Accept [QA168 The Unsent Letter]

Go into the cave: Go to [G41.2,72.4 Westfall Cave]
Loot the Bandanas: Do: [QC167]
Obtain the Letter: Do: [QC168]

Enter the dungeon portal: Go to [G42.5,73.9 Deadmines Portal]
Do the dungeon now. The endboss drops the quest head: Accept: [QA373]
Use your [H] Hearthstone.
Turn in all Deadmines quests: TurnIn: [QT167], [QT168], [QT2040], [QT373]
]], "Custom Guides Pack")
```
