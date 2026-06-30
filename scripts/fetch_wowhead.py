import asyncio
from playwright.async_api import async_playwright

async def run(url):
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()
        try:
            await page.goto(url, timeout=60000)
            # Wait for content to load
            await page.wait_for_selector('body')
            content = await page.content()
            # print first 2000 chars to avoid hitting output limit if it's too big,
            # but I really want the text.
            text = await page.evaluate("() => document.body.innerText")
            print(text)
        except Exception as e:
            print(f"Error: {e}")
        await browser.close()

if __name__ == "__main__":
    import sys
    if len(sys.argv) > 1:
        asyncio.run(run(sys.argv[1]))
