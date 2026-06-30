# Implementation Plan: Survival Profession

## Overview
The "Survival" profession is envisioned as a secondary profession (similar to Cooking, Fishing, and First Aid) that provides utility for adventurers in the open world. Inspired by Turtle WoW, its primary feature is the ability to craft and deploy **Tents**, which provide players with Rested XP while they are in the wild.

## Proposed Features
1.  **Tent Crafting**:
    - Multiple tiers of tents (e.g., Simple Tent, Sturdy Tent, Luxurious Tent) requiring various materials like Linen/Silk/Mooncloth and Light/Heavy/Rugged Leather.
2.  **Rested XP**:
    - When a tent is deployed, it creates a "Rested Area" (similar to an Inn) around it.
    - Players standing within the tent's radius gain rested experience over time.
3.  **Campfires & Utility**:
    - Integration with existing Campfire mechanics (or enhanced versions).
    - Possible additional items: Bedrolls (for quicker rested gain), Portable Cooking Pits.

## Technical Requirements & Feasibility

Implementing a brand-new profession in AzerothCore (WotLK 3.3.5a) is a **high-complexity** task because it requires synchronized changes between the server and the client.

### 1. Client-Side Modifications (Hardest Part)
To make the profession visible in the UI and allow players to learn it, the following DBC (Data Box Category) files must be edited:
-   **SkillLine.dbc**: To define the "Survival" profession ID, name, and description.
-   **SkillLineAbility.dbc**: To link recipes and the "Survival" skill to specific spells.
-   **Spell.dbc**: To add the crafting spells and the "Place Tent" spells.
-   **Languages.dbc**: If localizing for different languages.

**Impact**: Players would need to download a custom `.MPQ` patch to see the profession in their skill book. Without this, the server can track the skill, but the UI will be broken or invisible.

### 2. Server-Side Implementation
-   **Database (acore_world)**:
    - `item_template`: Create new tent items and recipes.
    - `spell_dbc`: Matching entries for the custom spells.
    - `skill_discovery_template`: (Optional) For learning recipes while crafting.
-   **GameObjects**:
    - The tent itself would be a `GAMEOBJECT_TYPE_GOOBER` or `GAMEOBJECT_TYPE_GENERIC`.
    - It needs a custom script to handle the "Rested" aura or area trigger.

### 3. Scripting (C++ or Eluna)
-   **Tent Logic**:
    - When the tent item is used, spawn a GameObject at the player's location.
    - Set a timer for the GameObject to despawn (e.g., 20-30 minutes).
    - **Rested Aura**: Implement a periodic check or an area trigger that applies `SPELL_AURA_MOD_RESTED_REGEN_MULTIPLIER` or directly modifies the player's `rest_bonus`.

## Implementation Roadmap
1.  **Phase 1: Database & Scripting (Proof of Concept)**
    - Implement a "Tent" item that spawns a temporary GameObject via an Eluna script or C++ module.
    - Verify that the GameObject can grant rested XP.
2.  **Phase 2: Skill Integration**
    - Assign a Skill ID to the Survival profession.
    - Implement server-side skill tracking (players can gain points, but UI is missing).
3.  **Phase 3: Client Patching**
    - Use DBC editors to create a custom patch for `SkillLine.dbc`.
    - Distribute the patch to players.

## Conclusion
While the scripting of a "Tent" system is relatively straightforward (Medium effort), the requirement for **Client-Side DBC editing** makes this a "Detailed" implementation. It is highly recommended to use a C++ module (like a modified `mod-tent-system` if one exists or is developed) to manage the server-side logic cleanly.
