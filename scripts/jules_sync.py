import os
import sys
import json
import requests
import gspread
from oauth2client.service_account import ServiceAccountCredentials

# Google Sheets Configuration
SHEET_NAME = "OurAzeroth Bug Tracker"
CREDS_FILE = os.path.join(os.path.dirname(__file__), "google_creds.json")

# GitHub Configuration
GITHUB_REPO = "cyork95/OurAzerothServer"  # Mapped from user information
GITHUB_TOKEN = os.getenv("GITHUB_TOKEN")

def connect_to_sheet():
    if not os.path.exists(CREDS_FILE):
        print(f"Error: Google Credentials JSON file not found at {CREDS_FILE}")
        sys.exit(1)
        
    scope = ["https://spreadsheets.google.com/feeds", "https://www.googleapis.com/auth/drive"]
    creds = ServiceAccountCredentials.from_json_keyfile_name(CREDS_FILE, scope)
    client = gspread.authorize(creds)
    return client.open(SHEET_NAME).sheet1

def create_github_issue(title, body, labels):
    if not GITHUB_TOKEN:
        print("Error: GITHUB_TOKEN environment variable not set.")
        sys.exit(1)
        
    url = f"https://api.github.com/repos/{GITHUB_REPO}/issues"
    headers = {
        "Authorization": f"token {GITHUB_TOKEN}",
        "Accept": "application/vnd.github.v3+json"
    }
    data = {
        "title": title,
        "body": body,
        "labels": labels
    }
    
    res = requests.post(url, headers=headers, json=data)
    if res.status_code == 201:
        issue_data = res.json()
        print(f"Created GitHub Issue #{issue_data['number']}: {title}")
        return issue_data['number']
    else:
        print(f"Failed to create GitHub Issue: {res.status_code} - {res.text}")
        return None

def sync_tracker():
    sheet = connect_to_sheet()
    rows = sheet.get_all_records()
    
    # Headers typically: Timestamp, Title, Description, Type, Priority, Status, GitHub_Issue_ID
    # We scan rows, 1-indexed for gspread updates (+2 because headers is row 1 and list is 0-indexed)
    for idx, row in enumerate(rows):
        status = str(row.get("Status", "")).strip().lower()
        title = row.get("Title", "")
        desc = row.get("Description", "")
        priority = row.get("Priority", "Medium")
        issue_type = row.get("Type", "Bug")
        issue_id = row.get("GitHub_Issue_ID", "")
        
        # 1. Process New / Pending Entries -> Create GitHub Issue
        if status in ("", "pending") and title:
            print(f"Processing new entry: {title}...")
            
            body = (
                f"### System Submitted {issue_type}\n\n"
                f"**Description:**\n{desc}\n\n"
                f"**Priority:** {priority}\n"
                f"**Reported via server wiki tracker.**\n\n"
                f"Please review the `AGENTS.md` parameters to deploy any C++ or scripting fixes required."
            )
            
            labels = ["jules", issue_type.lower()]
            issue_number = create_github_issue(title, body, labels)
            
            if issue_number:
                # Update spreadsheet row: Status -> In Progress, Issue ID -> issue_number
                row_num = idx + 2
                sheet.update_cell(row_num, 6, "In Progress")  # Col 6 = Status
                sheet.update_cell(row_num, 7, str(issue_number))  # Col 7 = GitHub_Issue_ID
                
        # 2. Check Active Issues -> If GitHub Issue is closed, update status to 'Fixed'
        elif status == "in progress" and issue_id:
            issue_num = int(issue_id)
            url = f"https://api.github.com/repos/{GITHUB_REPO}/issues/{issue_num}"
            headers = {
                "Authorization": f"token {GITHUB_TOKEN}",
                "Accept": "application/vnd.github.v3+json"
            }
            res = requests.get(url, headers=headers)
            if res.status_code == 200:
                issue_info = res.json()
                if issue_info.get("state") == "closed":
                    print(f"GitHub Issue #{issue_num} has been closed (resolved). Updating sheet status...")
                    row_num = idx + 2
                    sheet.update_cell(row_num, 6, "Fixed")
            else:
                print(f"Error querying GitHub Issue #{issue_num}: {res.status_code}")

if __name__ == "__main__":
    sync_tracker()
