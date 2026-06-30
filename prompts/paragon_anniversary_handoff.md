# Paragon Anniversary Integration Handoff

## Project: Paragon Anniversary
Source: github.com/Grim-Batol/Paragon-Anniversary

## Summary
The Paragon Anniversary system is a post-80 progression module for AzerothCore. It allows players to gain Paragon Levels by earning XP after reaching the level cap. Each level grants a Paragon Point used for stat increases (Strength, Agility, etc.) and unlocking special rewards.

## Implementation Details
1. **Database:** Configuration is stored in the `acore_ale` database, specifically the `paragon_config` table.
2. **Eluna:** The system runs as a Lua script within the Eluna engine.
3. **Admin Panel:** Integration has been added to `scripts/admin_index.php` for managing global settings and rewards.
4. **Wiki:** A new section has been added to `index.html` to explain the system to players.

## Admin Features
- Enable/Disable system.
- Toggle Account-wide progression.
- Set Level Cap for Paragon entry.
- Configure XP rates for Paragon levels.

## Verification
- Unit tests for SOAP commands are in `tests/test_admin_soap.py`.
- Visual verification of wiki documentation performed via Playwright.
