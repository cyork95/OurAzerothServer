from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto('https://www.azerothcore.org/catalogue.html')
        page.wait_for_timeout(5000)
        # Type into search box if it exists
        search_box = page.query_selector('input[type="text"]')
        if search_box:
            search_box.fill('All Races All Classes')
            page.wait_for_timeout(2000)

        # Get all links
        links = page.query_selector_all('a')
        for link in links:
            href = link.get_attribute('href')
            text = link.inner_text()
            if "details" in str(href) or "heyitsbench" in text or "arac" in text.lower():
                print(f"{text} -> {href}")
        browser.close()

if __name__ == "__main__":
    run()
