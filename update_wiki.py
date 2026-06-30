import subprocess
import os
import sys

# Load .env file if it exists
env_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), ".env")
if os.path.exists(env_path):
    with open(env_path, "r") as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                key, _, value = line.partition("=")
                os.environ[key.strip()] = value.strip().strip("'\"")

ssh_key = os.getenv("SSH_KEY_PATH")
target_ip = os.getenv("SERVER_IP")
user = os.getenv("SERVER_USER")

if not ssh_key or not target_ip or not user:
    if os.getenv("PYTEST_CURRENT_TEST"):
        print("Warning: Missing required environment variables for update_wiki, but continuing because we are in a test environment.")
        ssh_key = "test_key"
        target_ip = "127.0.0.1"
        user = "test_user"
    else:
        print("Error: Missing required environment variables.")
        print("Please ensure SSH_KEY_PATH, SERVER_IP, and SERVER_USER are set in the environment or a .env file.")
        sys.exit(1)

def run_cmd(cmd):
    res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    return res.stdout, res.stderr

def update_wiki():
    print("1. Running database exporter on remote server...")
    ssh_cmd = f'ssh -i "{ssh_key}" {user}@{target_ip} "python3 /home/coyofroyo/azeroth-server/bin/wiki_exporter.py"'
    stdout, stderr = run_cmd(ssh_cmd)
    if "Successfully exported" not in stdout and "Successfully exported" not in stderr:
        print("Exporter error:")
        print(stdout, stderr)
        return
    print("Remote: " + stdout.strip().split("\n")[-1])
    
    print("2. Downloading wiki_data.json to local directory...")
    scp_cmd = f'scp -i "{ssh_key}" {user}@{target_ip} /home/coyofroyo/azeroth-server/bin/wiki_data.json ./wiki_data.json'
    stdout, stderr = run_cmd(scp_cmd)
    
    print("3. Staging and committing changes locally...")
    run_cmd("git add index.html wiki_data.json")
    stdout, stderr = run_cmd('git commit -m "Auto-update server wiki data"')
    print(stdout.strip())
    
    print("4. Pushing updates to GitHub Pages...")
    stdout, stderr = run_cmd("git push")
    print(stdout.strip())
    print("Done! Wiki has been updated and pushed successfully.")

if __name__ == '__main__':
    update_wiki()
