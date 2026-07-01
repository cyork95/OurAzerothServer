import asyncio
from playwright.async_api import async_playwright
import os

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()
        page.set_viewport_size({"width": 1280, "height": 2000})

        # 1. Verify Wiki Changes
        await page.goto("http://localhost:8080/index.html")

        # Click "Custom Followers" tab
        await page.click("text=Custom Followers")
        await asyncio.sleep(1)

        # Scroll to compatibility grid
        grid_selector = "text=In-Game Race & Class Compatibility Grid"
        await page.locator(grid_selector).scroll_into_view_if_needed()
        await page.screenshot(path="verification/wiki_compatibility_grid_v2.png")

        # Check for ARAC in enabled modules (under "Server Modules" tab)
        await page.click("text=Server Modules")
        await asyncio.sleep(1)
        await page.locator("text=All Races All Classes (ARAC)").scroll_into_view_if_needed()
        await page.screenshot(path="verification/wiki_arac_description_v2.png")

        # 2. Verify Admin Panel
        await page.goto("http://localhost:8080/scripts/admin_index.php")
        await asyncio.sleep(2)

        # The admin panel has tabs too. Let's find where "Server Custom Modules Control" is.
        # It's usually in a "Dashboard" or "Config" tab.
        # Looking at previous greps, it was in the main content.

        try:
            # Scroll to bottom where the toggles usually are
            await page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
            await asyncio.sleep(1)
            await page.screenshot(path="verification/admin_panel_bottom.png")

            toggle_selector = "text=All Races All Classes (ARAC)"
            await page.locator(toggle_selector).scroll_into_view_if_needed()
            await page.screenshot(path="verification/admin_arac_toggle_v2.png")
        except Exception as e:
            print(f"Error finding toggle: {e}")

        await browser.close()

if __name__ == "__main__":
    os.makedirs("verification", exist_ok=True)
    asyncio.run(verify())
