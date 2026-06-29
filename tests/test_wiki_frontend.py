import pytest
import os
import subprocess
import time
import socket
from playwright.sync_api import Page, expect

def get_free_port():
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(('', 0))
        return s.getsockname()[1]

@pytest.fixture(scope="module")
def web_server():
    port = get_free_port()
    process = subprocess.Popen(["python3", "-m", "http.server", str(port)])

    # Polling to wait for server to start
    url = f"http://localhost:{port}"
    timeout = 5
    start_time = time.time()
    while time.time() - start_time < timeout:
        try:
            with socket.create_connection(("localhost", port), timeout=1):
                break
        except (OSError, ConnectionRefusedError):
            time.sleep(0.1)
    else:
        process.terminate()
        pytest.fail("Web server failed to start")

    yield url
    process.terminate()

def test_wiki_title(page: Page, web_server):
    page.goto(web_server)
    assert page.title() == "OurAzeroth Server Wiki"

def test_tab_switching(page: Page, web_server):
    page.goto(web_server)

    # Test switching to Custom Followers
    page.click("#tabbtn-custom-followers")
    expect(page.locator("#tab-custom-followers")).to_be_visible()
    expect(page.locator("#tab-client-setup")).not_to_be_visible()

    # Test switching to Bot Commands
    page.click("#tabbtn-bot-commands")
    expect(page.locator("#tab-bot-commands")).to_be_visible()

def test_data_loading(page: Page, web_server):
    page.goto(web_server)

    # Navigate to Server Modules where events are listed
    page.click("#tabbtn-server-modules")

    # Open the details element that contains the full event list
    page.click("summary:has-text('Complete List of All Database Game Events')")

    # Check if events are loaded (based on wiki_data.json)
    # The wiki loads events into #wiki-events-body
    events_table = page.locator("#wiki-events-body")
    expect(events_table.get_by_text("Midsummer Fire Festival", exact=True)).to_be_visible()

def test_character_search(page: Page, web_server):
    page.goto(web_server)
    page.click("#tabbtn-character-bio")

    # Type a name from wiki_data.json
    # The input id is bioCharSearchInput
    search_input = page.locator("#bioCharSearchInput")
    search_input.fill("Aarinar")

    # Check if results appear in the dropdown
    dropdown = page.locator("#bioCharSearchDropdown")
    expect(dropdown).to_be_visible()
    expect(dropdown.get_by_text("Aarinar")).to_be_visible()

    # Click on the character in the dropdown
    dropdown.get_by_text("Aarinar").click()

    # Verify biography section shows up
    expect(page.locator("#charBioContent")).to_be_visible()
