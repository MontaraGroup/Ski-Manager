import { test, expect } from '@playwright/test';

test.describe('Public Landing Page', () => {
  test('renders hero, snow conditions, timeline, and registration CTA', async ({ page }) => {
    await page.goto('/');

    // Check island mounting
    const island = page.locator('[data-island="HomePage"]');
    await expect(island).toBeVisible();

    // Mountain hero heading
    const heroHeading = page.locator('h1');
    await expect(heroHeading).toContainText('Build, groom, and run');

    // Live snow conditions panel
    const snowBoard = page.getByText(/Live snow conditions|Park City base/i).first();
    await expect(snowBoard).toBeVisible();

    // 5-step operational timeline
    await expect(page.getByText('1. Layout lifts & slopes')).toBeVisible();
    await expect(page.getByText('5. Season transition')).toBeVisible();

    // Registration CTA
    const startCta = page.locator('a[href="/register"]').first();
    await expect(startCta).toBeVisible();
  });
});
