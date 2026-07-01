from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/azerothcore/mod-arac/blob/master/conf/arac.conf.dist')
        page.wait_for_timeout(5000)
        # Github blob view has a "Raw" button. We can just go to the raw URL.
        raw_url = page.url.replace("github.com", "raw.githubusercontent.com").replace("/blob/", "/")
        page.goto(raw_url)
        print(page.content())
        browser.close()

if __name__ == "__main__":
    run()
