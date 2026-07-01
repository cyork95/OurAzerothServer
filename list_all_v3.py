from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://github.com/heyitsbench/mod-arac')
        page.wait_for_timeout(5000)
        # Github uses react now, let's try to get file names
        files = page.query_selector_all('tr.react-directory-row td.Box-row a, a.Link--primary')
        for f in files:
            print(f.inner_text())
        browser.close()

if __name__ == "__main__":
    run()
