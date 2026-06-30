import asyncio
from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/heyitsbench/mod-arac')
        page.wait_for_timeout(5000)
        # Github uses different classes now, let's try to get all links that look like files
        links = page.query_selector_all('a.Link--primary')
        for link in links:
            text = link.inner_text()
            href = link.get_attribute('href')
            print(f"{text} -> {href}")
        browser.close()

if __name__ == "__main__":
    run()
