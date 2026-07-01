from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/heyitsbench/mod-arac/blob/master/data/sql/db-world/arac.sql')
        page.wait_for_timeout(3000)
        # Try to get the raw content or the text from the blob
        content = page.query_selector('.blob-wrapper').inner_text()
        print(content)
        browser.close()

if __name__ == "__main__":
    run()
