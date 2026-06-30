# Research Report: Allowing All Weapon and Armor Training

## 1. Feasibility Analysis
It is highly feasible to allow all classes to learn all weapon and armor types in AzerothCore (WotLK 3.3.5a). This can be achieved through a combination of database modifications and a custom C++ module.

### Equipment Logic (Verified in Source)
- **`Player::CanEquipItem`**: This is the main entry point for equipment validation. It calls `CanUseItem`.
- **`Player::CanUseItem(ItemTemplate const* proto)`**: This function checks if the player's class is in the `AllowableClass` mask of the item template. If `(proto->AllowableClass & getClassMask()) == 0`, it returns `EQUIP_ERR_YOU_CAN_NEVER_USE_THAT_ITEM`.
- **`Player::CanUseItem(Item* pItem)`**: Also checks if the player has the required skill/spell. For example, Spell 201 is Plate Mail proficiency.

### Training Logic
- Trainers pull from the `trainer_spell` table.
- Most proficiency spells have a `reqClass` bitmask that restricts which classes can see and learn them.

## 2. Implementation Strategy (for Antigravity)

### Goal
Create a module or set of scripts that:
1. Allows all classes to learn all weapon and armor spells from trainers.
2. Bypasses the core class-based equipment restrictions.
3. Implements a cost penalty for "non-native" training.

### Recommended Approach: Custom C++ Module
A custom AzerothCore module is the most stable and performant way to handle this.

**Key Hooks to implement:**
- `OnBeforeEquip` (or overriding `CanEquipItem` via a ScriptMgr hook): To bypass the `AllowableClass` check.
- `OnTrainerList`: To intercept the trainer's spell list. If a player is a "Cloth" class learning "Plate", the cost should be multiplied.

## 3. Database Changes Needed
A SQL script is required to remove `reqClass` restrictions from proficiency spells:
```sql
-- Remove class restrictions for proficiency spells (Example IDs)
-- 201: Plate, 415: Mail, 414: Leather, 6124: Cloth
-- 202: 2H Swords, 200: Polearms, etc.
UPDATE trainer_spell SET reqClass = 0 WHERE spell IN (201, 415, 414, 6124, 202, 200, 197, 199, 264, 5011, 266);
```

## 4. Technical Constraints
- **Client Tooltips:** The WotLK client will still show the item requirement in red (e.g., "Classes: Warrior, Paladin, Death Knight") and the item name in red if the player is a Priest, because this data is hardcoded in the client's `Item.dbc`. This is purely visual; the server-side bypass will allow equipping it.
- **Animations:** Standard animations for all weapons exist for all races/genders, so combat should look correct.

---
**Status:** Research Complete. Ready for Handoff.
