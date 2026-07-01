import asyncio
from playwright.async_api import async_playwright
import os

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()
        await page.set_viewport_size({"width": 1280, "height": 3000})

        # 1. Verify Wiki Changes
        print("Verifying Wiki...")
        await page.goto("http://localhost:8080/index.html")
        await asyncio.sleep(2)

        # Click "Custom Followers" tab
        await page.click("text=Custom Followers")
        await asyncio.sleep(1)

        # Scroll to compatibility grid
        grid_selector = "text=In-Game Race & Class Compatibility Grid"
        await page.locator(grid_selector).scroll_into_view_if_needed()
        await page.screenshot(path="verification/wiki_compatibility_grid_final.png")

        # Check for ARAC in enabled modules (under "Server Modules" tab)
        await page.click("text=Server Modules")
        await asyncio.sleep(1)
        await page.locator("text=All Races All Classes (ARAC)").scroll_into_view_if_needed()
        await page.screenshot(path="verification/wiki_arac_description_final.png")

        # 2. Verify Admin Panel
        print("Verifying Admin Panel...")
        await page.goto("http://localhost:8080/scripts/admin_index.php")
        await asyncio.sleep(2)

        # It should be on the Dashboard tab by default (tab-dashboard)
        # Let's find "Server Custom Modules Control"
        try:
            target = page.locator("text=All Races All Classes (ARAC)")
            await target.scroll_into_view_if_needed()
            await page.screenshot(path="verification/admin_arac_toggle_final.png")
            print("Admin toggle verified.")
        except Exception as e:
            print(f"Error finding toggle: {e}")
            await page.screenshot(path="verification/admin_error.png")

        await browser.close()

if __name__ == "__main__":
    os.makedirs("verification", exist_ok=True)
    asyncio.run(verify())
