import { test, expect } from '@playwright/test';

test.describe('Legal and Information Pages', () => {
  const pages = [
    { path: '/about', island: 'AboutPage', heading: 'About Ski Manager' },
    { path: '/terms', island: 'LegalPage', heading: 'Terms of Service' },
    { path: '/privacy', island: 'LegalPage', heading: 'Privacy Policy' },
    { path: '/cookies', island: 'LegalPage', heading: 'Cookie Policy' },
    { path: '/disclaimer', island: 'LegalPage', heading: 'Disclaimer' },
  ];

  for (const p of pages) {
    test(`renders ${p.path} with island ${p.island}`, async ({ page }) => {
      await page.goto(p.path);

      const island = page.locator(`[data-island="${p.island}"]`);
      await expect(island).toBeVisible();

      await expect(page.getByRole('heading', { name: p.heading, level: 1 })).toBeVisible();
    });
  }
});
