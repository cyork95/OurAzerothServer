from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/azerothcore/mod-arac/blob/master/conf/arac.conf.dist')
        page.wait_for_timeout(3000)
        # Try to find the content
        try:
            content = page.query_selector('div.blob-wrapper').inner_text()
            print(content)
        except:
            print("Failed to find content")
        browser.close()

if __name__ == "__main__":
    run()
