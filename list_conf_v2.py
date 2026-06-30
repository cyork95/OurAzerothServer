from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/heyitsbench/mod-arac')
        page.wait_for_timeout(5000)
        # Find the 'conf' folder link
        conf_link = page.query_selector('a[href*="/tree/master/conf"]')
        if conf_link:
            print(f"Conf folder exists at: {conf_link.get_attribute('href')}")
            page.goto('https://github.com' + conf_link.get_attribute('href'))
            page.wait_for_timeout(3000)
            files = page.query_selector_all('a.Link--primary')
            for f in files:
                print(f"File: {f.inner_text()}")
        else:
            print("Conf folder not found at root")
            # Maybe it's in src?
        browser.close()

if __name__ == "__main__":
    run()
