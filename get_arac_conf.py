from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        # Try both locations
        page.goto('https://github.com/azerothcore/mod-arac/tree/master/conf')
        page.wait_for_timeout(3000)
        links = page.query_selector_all('a.Link--primary')
        for link in links:
            print(f"File in conf/: {link.inner_text()}")

        page.goto('https://github.com/azerothcore/mod-arac/blob/master/conf/arac.conf.dist')
        page.wait_for_timeout(2000)
        if "404" not in page.title():
             print("Found arac.conf.dist")

        browser.close()

if __name__ == "__main__":
    run()
