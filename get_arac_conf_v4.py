from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        # The repo might be azerothcore/mod-arac
        url = 'https://github.com/azerothcore/mod-arac/blob/master/conf/arac.conf.dist'
        page.goto(url)
        page.wait_for_timeout(5000)
        try:
            # New Github UI uses different selectors
            content = page.locator('div.react-blob-print-hide').inner_text()
            print(content)
        except:
             try:
                 content = page.query_selector('.blob-wrapper').inner_text()
                 print(content)
             except:
                 print("Failed to find content with both selectors")
        browser.close()

if __name__ == "__main__":
    run()
