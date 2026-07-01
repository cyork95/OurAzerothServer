# Task: Refine Guidelime Guide for Dire Maul Dungeon & Related Quests

**Context:**
A comprehensive Guidelime guide for the Dire Maul dungeon complex (Level 55-60) in Feralas has been created at `guides/Guidelime_DireMaul.lua`. This includes class books, wing walkthroughs, Paladin mount quests, and the Quel'Serrar questline.

**Objective:**
Use your `guidelime-guide-creator` skill to review, refine, and maintain the `.lua` Guidelime guide for "Dire Maul: The Complete Guide".

**Requirements:**

1.  **Research (WotLK 3.3.5a):**
    - **Class Books (Royal Seal of Eldre'Thalas):**
        - Quest IDs: Druid (7506), Hunter (7503), Mage (7500), Paladin (7501), Priest (7504), Rogue (7498), Shaman (7505), Warlock (7502), Warrior (7499).
        - Turn-in: Lorekeeper Lydros in the Library (Dire Maul North/West access).
    - **Dire Maul East (Warpwood Quarter):**
        - *Pusillin and the Elder Azj'Tordin* (Quest ID: 7441). Giver: Elder Azj'Tordin (76.8, 37.4 Feralas).
        - *Lethtendris's Web* (Quest ID: 7488 Alliance / 7489 Horde).
        - *Shards of the Felvine* (Quest ID: 5526).
    - **Dire Maul West (Capital Gardens):**
        - *The Treasure of the Shen'dralar* (Quest ID: 7462).
        - *The Madness Within* (Quest ID: 7461).
    - **Dire Maul North (Gordok Commons):**
        - *Free Knot!* (Quest ID: 7429).
        - *The Gordok Ogre Suit* (Quest ID: 5518).
        - *Unfinished Gordok Business* (Quest ID: 7703).
    - **Paladin Charger Mount:**
        - Quest IDs: Lord Grayson Shadowbreaker (7638), To Show Due Judgment (7639), Exorcising Terrordale (7640), Grimand's Finest Work (7648), Ancient Equine Spirit (7643), Blessed Arcanite Barding (7644), Judgment and Redemption (7647).
        - NPCs: Lord Grayson Shadowbreaker (Stormwind Cathedral), Grimand Elmore (Stormwind Dwarven District).
    - **Quel'Serrar:**
        - Starts with *Nostro's Compendium of Dragon Slaying* (Item ID: 18401).
        - *The Forging of Quel'Serrar* (Quest ID: 7481).

2.  **Metadata:**
    - **Name:** `[N Dire Maul Complete Guide]`
    - **Description:** `[D Guide for all three wings of Dire Maul, including Class Books, Paladin Mount, and Quel'Serrar.]`
    - **Faction:** Universal (use class/faction filters `[A Alliance]`, `[H Horde]`, `[C Paladin]`, etc.).

3.  **Guide Structure:**
    - **Prep:** Picking up external quests in Feralas and major cities.
    - **Dire Maul East:** Chase Pusillin for the Crescent Key. Kill Lethtendris and Alzzin.
    - **Dire Maul West:** Activate the 5 pylons. Kill Immol'thar and Prince Tortheldrin. Visit the Library.
    - **Dire Maul North:** Normal vs Tribute run instructions. Freeing Knot Thimblejack.
    - **Special Quests:** Specific sections for Paladins (Mount) and Warriors/Paladins (Quel'Serrar).

4.  **Formatting:**
    - Use appropriate Guidelime markup (e.g., `[G coordinates]`, `[QA...]`, `[QT...]`, `[QC...]`).
    - Use `[A ClassName]` filters for class-specific steps (especially for class books and mount quests).
    - Reference the `guidelime-guide-creator` skill for markup syntax.

**Technical Note:**
Ensure the guide is concise, follows the `.lua` shell format required by the skill, and uses the verified Quest IDs and coordinates provided.
