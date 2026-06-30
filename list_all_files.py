from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/heyitsbench/mod-arac')
        page.wait_for_timeout(5000)
        # Get all links that go to a blob or tree in this repo
        links = page.query_selector_all('a[href*="/heyitsbench/mod-arac/tree/master/"], a[href*="/heyitsbench/mod-arac/blob/master/"]')
        found = set()
        for link in links:
            href = link.get_attribute('href')
            text = link.inner_text().strip()
            if href and text and href not in found:
                print(f"{text} -> {href}")
                found.add(href)
        browser.close()

if __name__ == "__main__":
    run()
