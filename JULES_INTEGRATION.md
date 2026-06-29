# Automated Bug & Feature Solver: Google Jules Integration Guide

This guide details how to build an autonomous developer loop linking your server wiki's **Bug & Feature Tracker** (Google Form) to **Google Jules** via a Google Sheet responses database.

---

## The Automation Loop

```
[Server Wiki (Form)] ──> [Google Sheet] ──> [jules_sync.py] ──> [GitHub Issues] ──> [Google Jules (AI Agent)]
                                                                                         │
[Sheet Status: 'Fixed'] <─────────────────── [Auto-Deploy PR Merge] <────────────────────┘
```

---

## Step 1: Connecting your Repository to Google Jules

1. Go to [jules.google.com](https://jules.google.com).
2. Log in with your Google account.
3. Click **Connect Repository** and select `OurAzerothServer` (from GitHub).
4. Jules will scan your codebase, including the [.agents/AGENTS.md](file:///.agents/AGENTS.md) guide, so it understands your server IP (`192.168.1.168`), directories, compilers, modules, and database structures.

---

## Step 2: Google Forms & Sheets Setup

1. Create a Google Form at [forms.google.com](https://forms.google.com) with the following fields:
   * **Title** (Short summary of the bug/feature)
   * **Details / Steps to Reproduce** (Context for Jules)
   * **Type** (Drop-down: `Bug` / `Enhancement`)
   * **Priority** (Drop-down: `Low` / `Medium` / `High`)
   * **Status** (Leave empty or hide — our sync script will write `Pending` / `In Progress` / `Fixed`)
2. In the Form editor, go to **Responses** -> **Link to Sheets** -> Create a new spreadsheet (e.g. `OurAzeroth Bug Tracker`).
3. Click the **Send** button on the top right -> **Embed HTML (<>)** -> Copy the URL in the `src` attribute.
4. Replace the placeholder iframe URL in [index.html](file:///c:/Users/coyof/Documents/Claude/Claude%20Code/OurAzerothServer/index.html#L1995-L2005) with your live Google Form URL.

---

## Step 3: Setting Up Google Cloud API Credentials

To let our bridge script read/write to the Google Sheet:
1. Go to the [Google Cloud Console](https://console.cloud.google.com).
2. Create a project and enable the **Google Sheets API** and **Google Drive API**.
3. Create a **Service Account** under **IAM & Admin** -> **Credentials**.
4. Generate a **JSON Key** for the Service Account, download it, name it `google_creds.json`, and place it in your local `scripts/` directory.
5. Open your Google Sheet, click **Share**, and invite the Service Account's email address (found in the JSON file) as an **Editor**.

---

## Step 4: The Sync Bridge Script

Create a personal access token (PAT) on GitHub with `repo` scopes and save it as an environment variable (`GITHUB_TOKEN`). 

Below is the bridge script [jules_sync.py](file:///c:/Users/coyof/Documents/Claude/Claude%20Code/OurAzerothServer/scripts/jules_sync.py) which polls the Sheet, spawns GitHub Issues (tagged `jules` to trigger the agent), and updates statuses.
