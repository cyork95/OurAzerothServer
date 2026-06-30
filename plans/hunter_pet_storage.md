# Implementation Plan: Hunter Pet Storage Expansion

## Overview
Hunters in WotLK 3.3.5a are limited to 1 active pet and 4 stable slots (5 total). This is a client-side limitation because the UI only provides slots for 5 pets. Modern WoW allows for significantly more pet storage.

## Research Findings
Based on the AzerothCore documentation for the `character_pet` table:
- **Slot 0**: Active pet (with the player).
- **Slots 1-4**: Pets in the stable.
- **Slot 100**: Pet with the player but currently dismissed.

The server core can technically support more pets if they are assigned to different slot IDs, but they will not be visible or manageable via the standard Stable Master UI.

## Proposed Solution: Pet Bank NPC
To bypass the client UI limitation, we will implement an Eluna-based "Pet Bank" NPC. This NPC will allow hunters to "Deposit" pets from their stable into a "Bank" (higher slot IDs) and "Withdraw" them back into an empty stable slot.

### Technical Implementation
1. **Custom Storage Slots**: We will use slots **10 through 60** as "Bank" storage. These slots are safely outside the range used by the client UI and the "dismissed" state (100).
2. **NPC Gossip Script**: A new NPC (ID 99999) will be registered with a gossip menu:
   - **Deposit Pet**: Lists pets currently in stable slots 1-4. Selecting one will change its `slot` in the database to the first available ID between 10 and 60.
   - **Withdraw Pet**: Lists pets currently in "Bank" slots 10-60. Selecting one will change its `slot` back to the first available stable slot (1-4).
3. **Database Interaction**:
   - `CharDBQuery`: To list pets belonging to the player and their current slots.
   - `CharDBExecute`: To update the `slot` value for a specific pet.

### Constraints & Safety
- **Hunter Only**: The NPC will only interact with Hunters.
- **Slot Availability**: The script will verify that the player has at least one free stable slot before allowing a withdrawal.
- **Data Integrity**: Using standard database updates ensures that pet stats, levels, and talents are preserved.

## Conclusion
This solution allows hunters to have up to 50 additional pets in storage without requiring custom client-side UI modifications (MPQ patches).
