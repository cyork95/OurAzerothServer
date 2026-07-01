from playwright.sync_api import sync_playwright
import sys

def run(filename):
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        url = f'https://github.com/heyitsbench/mod-arac/blob/master/src/{filename}'
        page.goto(url)
        page.wait_for_timeout(3000)
        try:
            content = page.query_selector('div.blob-wrapper').inner_text()
            print(content)
        except:
            print(f"Could not find blob-wrapper for {filename}")
        browser.close()

if __name__ == "__main__":
    run(sys.argv[1])
