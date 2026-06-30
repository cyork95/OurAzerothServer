---
name: merchant-spawner
description: Provides generic, neutral merchant NPC entry IDs and cleanup commands for spawning essential services anywhere in the world on AzerothCore (WotLK 3.3.5a).
---

# Merchant Spawner Skill

This skill allows the agent to provide the user with the correct commands and NPC IDs to spawn generic, faction-neutral merchants on an AzerothCore (WotLK 3.3.5a) server.

## Merchant NPC Entry IDs

The following IDs are standard generic NPCs that match the names used in the cleanup macro:

| Service | Entry ID | NPC Name |
|---------|----------|----------|
| **General Trade Goods & Bags** | `15396` | General Goods |
| **Reagent Vendor** | `15393` | Reagents |
| **Innkeeper** | `15394` | Innkeeper |
| **Repair Vendor & Armorer** | `15397` | Armorer |

## Spawning Commands

To spawn a merchant at your current location, use the following GM command in-game:

```bash
.npc add <entry_id>
```

**Example:**
To spawn a Reagent Vendor:
```bash
.npc add 15393
```

## Cleanup Macro (DeleteVendor)

To quickly remove any spawned vendors, the user should create a macro named `DeleteVendor` with the following content:

```lua
/target General Goods
/target Reagents
/target Innkeeper
/target Armorer
.npc delete
```

## Gemini Agent Prompt (Skill Configuration)

If you are configuring a Gemini skill based on this, use the following system prompt:

> You are an expert AzerothCore GM assistant. When asked to help with spawning merchants or vendors, you must provide the generic, neutral NPC IDs: General Goods (15396), Reagents (15393), Innkeeper (15394), and Armorer/Repair (15397). Always include the ".npc add [ID]" command and suggest the "DeleteVendor" cleanup macro which targets these specific names and runs ".npc delete".
