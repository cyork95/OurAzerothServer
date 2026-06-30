import subprocess
import os
import sys

# Server configuration from AGENTS.md
SSH_KEY = os.getenv("SSH_KEY_PATH", r"C:\Users\coyof\.ssh\id_ed25519")
TARGET_IP = os.getenv("SERVER_IP", "192.168.1.168")
USER = os.getenv("SERVER_USER", "coyofroyo")

# Database credentials
DB_USER = os.getenv("DB_USER", "acore")
DB_PASS = os.getenv("DB_PASS", "acore")

# SQL script location
SQL_FILE = os.path.join(os.path.dirname(__file__), "fix_melik_personality.sql")

def run_cmd(cmd):
    res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    return res.stdout, res.stderr

def apply_fix():
    if not os.path.exists(SQL_FILE):
        print(f"Error: SQL file not found at {SQL_FILE}")
        return

    print(f"1. Uploading SQL fix to server {TARGET_IP}...")
    scp_cmd = f'scp -i "{SSH_KEY}" "{SQL_FILE}" {USER}@{TARGET_IP}:/tmp/fix_melik_personality.sql'
    stdout, stderr = run_cmd(scp_cmd)
    if stderr and "Connection timed out" in stderr:
        print("Error: Could not connect to the server. Please ensure you are on the same network as 192.168.1.168.")
        return

    print("2. Applying SQL fix to character database...")
    # Applying the SQL fix using provided credentials
    ssh_cmd = f'ssh -i "{SSH_KEY}" {USER}@{TARGET_IP} "mysql -u {DB_USER} -p{DB_PASS} acore_characters < /tmp/fix_melik_personality.sql"'
    stdout, stderr = run_cmd(ssh_cmd)

    if stderr:
        print("MySQL Error:")
        print(stderr)
    else:
        print("Success! Melik's personality has been restored to Analytical Mage.")
        print("Note: The bot may need a few minutes to pick up the change or a '.reload config' in-game.")

if __name__ == '__main__':
    apply_fix()
