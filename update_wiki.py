import subprocess
import os

ssh_key = r"C:\Users\coyof\.ssh\id_ed25519"
target_ip = "192.168.1.168"
user = "coyofroyo"

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
