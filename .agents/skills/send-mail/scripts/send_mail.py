import sys
import subprocess

if len(sys.argv) < 5:
    print("Usage: python send_mail.py [recipient] [subject] [text] [itemid:count]")
    print("Example: python send_mail.py Xorr 'Bags' 'Enjoy the bags!' '4238:4'")
    sys.exit(1)

recipient = sys.argv[1]
subject = sys.argv[2]
text = sys.argv[3]
items = sys.argv[4]

ssh_key = r"C:\Users\coyof\.ssh\id_ed25519"
target_ip = "192.168.1.168"
user = "coyofroyo"

# Package inside clean tmux send-keys array to avoid any quote shell parsing issues
cmd = f'tmux send-keys -t azeroth:1 \'.send items {recipient} "{subject}" "{text}" {items}\' Enter'
ssh_cmd = ["ssh", "-i", ssh_key, f"{user}@{target_ip}", cmd]

print(f"Mailing items '{items}' to {recipient}...")
res = subprocess.run(ssh_cmd, capture_output=True, text=True, encoding="utf-8")
if res.returncode == 0:
    print("Command sent to server console successfully!")
else:
    print("Error sending command:")
    print(res.stderr)
