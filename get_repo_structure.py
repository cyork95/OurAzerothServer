import asyncio
from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/heyitsbench/mod-arac')
        page.wait_for_timeout(3000)
        items = page.query_selector_all('.Box-row .js-navigation-open')
        for item in items:
            print(item.inner_text())
        browser.close()

if __name__ == "__main__":
    run()
