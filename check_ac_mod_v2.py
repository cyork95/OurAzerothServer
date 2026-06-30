from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/azerothcore/mod-arac')
        page.wait_for_timeout(5000)
        # Check for src or conf
        links = page.query_selector_all('a.Link--primary')
        for link in links:
            print(link.inner_text())
        browser.close()

if __name__ == "__main__":
    run()
