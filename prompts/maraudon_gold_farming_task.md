# Task: Create Guidelime Guide for Maraudon Gold Farming

**Context:**
A request has been made to create a custom Guidelime guide for gold farming in Maraudon. This research prompt summarizes the key strategies and routes to guide your implementation using the `guidelime-guide-creator` skill.

**Research Summary (Maraudon Gold Farming):**

Maraudon (Desolace) is a premier solo gold farming location for level 60 players (and high-level WotLK players for quick raw gold). The primary strategy involves the "Princess Run" plus optional side bosses and gathering.

1.  **Preparation:**
    - **Requirement:** Scepter of Celebras (to teleport directly to Earth Song Falls).
    - **Classes:** Best for Hunters, Warlocks, Mages, and Druids.
    - **Professions:** Herbalism (Ghost Mushrooms), Mining (Mithril), Enchanting (Disenchanting blues).

2.  **The Route (Inner Maraudon):**
    - **Teleport:** Use Scepter of Celebras at the "Maraudon Portal" entrance (The Orange/Purple intersection) to teleport to Earth Song Falls.
    - **Tinkerer Gizlock:** Located in the tunnels before the waterfall. Drops: Inventor's Focal Sword, Megashot Rifle (high vendor value).
    - **Rotgrip:** Located in the water at the bottom of the waterfall. Drops: Gatorbite Axe, Albino Crocscale Boots.
    - **Princess Theradras:** The main target. Circle-kite her around the arena. Drops: Elemental Rockridge Leggings, Princess Theradras' Scepter, Blackstone Ring (high value for twinks/pre-raid).
    - **Landslide (Optional):** If able to solo, he drops high vendor value blues.

3.  **Gathering Highlights:**
    - **Ghost Mushrooms:** Found in the Grosh'gok Hoard (the area with the subterranean fungi) and near the waterfall. Very valuable for Elixir of Greater Firepower and Limited Invulnerability Potions.
    - **Mithril/Truesilver Nodes:** Scattered throughout the tunnels.

4.  **Logistics:**
    - **Resetting:** After killing Princess and Rotgrip, players usually jump down into the water behind Princess to exit via the tunnel to the "purple" side or simply log out/reset if at the entrance.
    - **Vendoring:** Shadowprey Village (Horde) or Nijel's Point (Alliance) in Desolace.

---

# Task Prompt for Antigravity Gemini

**Objective:**
Use your `guidelime-guide-creator` skill to generate a `.lua` Guidelime guide for "Maraudon Gold Farming".

**Requirements:**

1.  **Metadata:**
    - **Name:** `[N Maraudon Gold Farming]`
    - **Description:** `[D Solo routes for Princess Theradras, Rotgrip, and Ghost Mushroom farming.]`
    - **Faction:** Universal.

2.  **Guide Structure:**
    - **Preparation:** Instructions to travel to Maraudon in Desolace and ensure the Scepter of Celebras is in inventory.
    - **The Entrance:** Instructions to use the Scepter at the portal entrance.
    - **Tinkerer Gizlock:** Pathing and instructions for Gizlock.
    - **Rotgrip:** Pathing to the water and killing the croc.
    - **Princess Theradras:** Final boss instructions and looting.
    - **Ghost Mushroom Loop:** Specific instructions to check spawn points for Ghost Mushrooms.
    - **Reset/Vendor:** Instructions for resetting the instance and travel back to the nearest vendor/mailbox.

3.  **Formatting:**
    - Use appropriate Guidelime markup (e.g., `[G coordinates]`, `[QA...]`, `[QT...]` for pre-reqs if needed).
    - Use `[C Class]` filters for specific class strategies (e.g., kiting for Hunters).

**Technical Note:**
Ensure the guide is concise and follows the `.lua` shell format required by the skill.
