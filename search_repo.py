from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        # Search for "Enable" in the repo
        page.goto('https://github.com/heyitsbench/mod-arac/search?q=Enable')
        page.wait_for_timeout(5000)
        content = page.content()
        print(content)
        browser.close()

if __name__ == "__main__":
    run()
python3 search_repo.py > search_results.html
grep -i "Enable" search_results.html | head -n 20
