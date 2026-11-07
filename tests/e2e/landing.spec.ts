import { test, expect } from '@playwright/test';

test.describe('Public Landing Page', () => {
  test('renders hero, snow conditions, timeline, and registration CTA', async ({ page }) => {
    await page.goto('/');

    // Check island mounting
    const island = page.locator('[data-island="HomePage"]');
    await expect(island).toBeVisible();

    // Mountain hero heading
    const heroHeading = page.locator('h1');
    await expect(heroHeading).toBeVisible({ timeout: 15_000 });
    await expect(heroHeading).toContainText(/Build, groom, and run|Run a ski resort/i);

    // Live snow conditions panel
    const snowBoard = page.getByText(/Live snow conditions|Live Snow Report|Park City base/i).first();
    await expect(snowBoard).toBeVisible();

    // Registration CTA
    const startCta = page.locator('a[href="/register"]').first();
    await expect(startCta).toBeVisible();
  });

  test('opens Resort dropdown in front of the hero section without clipping', async ({ page, isMobile }) => {
    test.skip(isMobile, 'Desktop navbar dropdown only applies to desktop viewports');
    await page.goto('/');

    const resortTrigger = page.locator('.site-nav button:has-text("Resort"), .site-nav summary:has-text("Resort")').first();
    await expect(resortTrigger).toBeVisible();
    await resortTrigger.click();

    const dropdownMenu = page.locator('[role="menu"], .site-nav details[open] > div, .site-nav details[open] > ul').first();
    await expect(dropdownMenu).toBeVisible();

    const overviewItem = dropdownMenu.getByText('Overview').first();
    await expect(overviewItem).toBeVisible();

    // Confirm the dropdown menu's bounding box extends below the navbar into the hero area
    const navBox = await page.locator('.site-nav').boundingBox();
    const menuBox = await dropdownMenu.boundingBox();
    expect(navBox).toBeTruthy();
    expect(menuBox).toBeTruthy();
    expect(menuBox!.y + menuBox!.height).toBeGreaterThan(navBox!.y + navBox!.height);
  });
});
