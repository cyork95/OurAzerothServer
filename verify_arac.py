import asyncio
from playwright.async_api import async_playwright
import os

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()

        # 1. Verify Wiki Changes
        await page.goto("http://localhost:8080/index.html")

        # Take screenshot of compatibility grid
        # The grid is under the "Custom Followers" tab
        await page.click("text=Custom Followers")
        await page.wait_for_selector(".matrix-table")
        await page.screenshot(path="verification/wiki_compatibility_grid.png")

        # Check for ARAC in enabled modules (under "Server Modules" tab)
        await page.click("text=Server Modules")
        await page.wait_for_selector("text=All Races All Classes (ARAC)")
        await page.screenshot(path="verification/wiki_arac_description.png")

        # 2. Verify Admin Panel
        await page.goto("http://localhost:8080/scripts/admin_index.php")
        # Find the ARAC toggle
        try:
            await page.wait_for_selector("text=All Races All Classes (ARAC)", timeout=5000)
            await page.screenshot(path="verification/admin_arac_toggle.png")
        except:
            print("ARAC toggle not found in admin panel (expected if DB not connected, but check UI)")
            await page.screenshot(path="verification/admin_panel_full.png")

        await browser.close()

if __name__ == "__main__":
    os.makedirs("verification", exist_ok=True)
    asyncio.run(verify())
