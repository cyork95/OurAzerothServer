# Handoff: Universal Weapon and Armor Training System

**Subject:** Implementation of a system to allow all classes to learn and use all weapon and armor types, with a configurable cost penalty.

## 1. Context
We want to remove the hardcoded class restrictions on weapon and armor types. For example, a Priest should be able to learn and equip Plate, and a Mage should be able to learn and equip Two-Handed Swords. To maintain some balance, training these "non-native" skills should be significantly more expensive.

## 2. Technical Research Results

### A. Equipment Restrictions
In AzerothCore, equipment validation happens in `Player::CanEquipItem`, which calls `CanUseItem`.
The core check is:
```cpp
if ((proto->AllowableClass & getClassMask()) == 0)
    return EQUIP_ERR_YOU_CAN_NEVER_USE_THAT_ITEM;
```
To bypass this, we need a module that hooks into the equipment system. Since `CanUseItem` is not directly hooked, we should use `OnBeforeEquip` or similar to return `EQUIP_ERR_OK` if the player has the required proficiency spell.

### B. Training Restrictions
Trainers check the `trainer_spell` table. Many spells have a `reqClass` bitmask.
Key Spell IDs:
- **Plate Mail:** 201
- **Mail:** 415
- **Leather:** 414
- **Two-Handed Swords:** 202
- **Polearms:** 200
- **Bows:** 264

## 3. Implementation Requirements (Antigravity)

### C++ Module: `mod-universal-training`
1. **Bypass Equipment Checks:**
   - Hook `OnBeforeEquip`.
   - If the error is `EQUIP_ERR_YOU_CAN_NEVER_USE_THAT_ITEM`, check if the item class/subclass matches a proficiency spell the player *does* have (even if non-native). Return `EQUIP_ERR_OK` if they have the skill.

2. **Trainer Cost Scaling:**
   - Hook `OnTrainerList` (interception of the `SMSG_TRAINER_LIST` packet or the `GetTrainerSpells` logic).
   - Identify proficiency spells.
   - If the spell is NOT native to the player's class (e.g., a Rogue learning Plate), multiply the `moneyCost` by a configurable value (default: 100x).

3. **Configuration:**
   - `UniversalTraining.Enable`: (Bool)
   - `UniversalTraining.CostMultiplier`: (Float, default 100.0)
   - `UniversalTraining.LearnAllOnStart`: (Bool, optional)

### SQL Support
- Provide a script to set `reqClass = 0` for all weapon and armor spells in `trainer_spell`.

## 4. Known Issues
- Client-side UI will still show items in red if they aren't natively supported. This is a client DBC limitation.
- LFG "Need" rolls might also need a bypass in `Player::CanRollForItemInLFG`.

*Research and Handoff prepared by Jules.*
