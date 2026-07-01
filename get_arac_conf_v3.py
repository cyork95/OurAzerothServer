from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        # Go to the raw file on Github
        page.goto('https://raw.githubusercontent.com/azerothcore/mod-arac/master/conf/arac.conf.dist')
        content = page.content()
        # raw.githubusercontent.com usually returns just the text, but playwright might wrap it in <html>
        print(content)
        browser.close()

if __name__ == "__main__":
    run()
