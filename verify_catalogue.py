import asyncio
from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://www.azerothcore.org/catalogue.html#/details/236337938')
        page.wait_for_timeout(5000)  # Wait for JS to load
        content = page.content()
        print(content)
        browser.close()

if __name__ == "__main__":
    run()
