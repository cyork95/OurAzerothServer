---
name: send-mail
description: Sends in-game mail with items (like bags, gold, or gear) to players on the server using a safe python script that avoids SSH quote-mangling issues.
---

# Send Mail Skill

This skill allows the agent to safely send in-game mail containing items or gold to players on the World of Warcraft server using the server's console commands.

## How to Use

Run the helper script `send_mail.py` located in this skill's `scripts/` directory:

```bash
python "c:/Users/coyof/Documents/Claude/Claude Code/OurAzerothServer/.agents/skills/send-mail/scripts/send_mail.py" "<recipient>" "<subject>" "<text>" "<itemid:count>"
```

### Examples:
*   Send 4 Linen Bags (item ID 4238) to Xorr:
    ```bash
    python "c:/Users/coyof/Documents/Claude/Claude Code/OurAzerothServer/.agents/skills/send-mail/scripts/send_mail.py" "Xorr" "Linen Bags" "Here are your bags!" "4238:4"
    ```
*   Send 10 Gold to Xorr (Gold item ID is 37701 or sent via command):
    ```bash
    python "c:/Users/coyof/Documents/Claude/Claude Code/OurAzerothServer/.agents/skills/send-mail/scripts/send_mail.py" "Xorr" "Gold Reward" "Enjoy your gold!" "37701:10"
    ```
