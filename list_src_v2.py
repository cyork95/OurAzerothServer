from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/heyitsbench/mod-arac/tree/master/src')
        page.wait_for_timeout(3000)
        links = page.query_selector_all('a.Link--primary')
        for link in links:
            print(link.inner_text())
        browser.close()

if __name__ == "__main__":
    run()
