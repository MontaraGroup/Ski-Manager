import { test, expect } from '@playwright/test';

test.describe('Authentication Pages', () => {
  test('login page mounts and allows password visibility toggle', async ({ page }) => {
    await page.goto('/login');

    const island = page.locator('[data-island="LoginPage"]');
    await expect(island).toBeVisible();

    const emailInput = page.locator('input[name="email"]');
    const passwordInput = page.locator('input[name="password"]');
    await expect(emailInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
    await expect(passwordInput).toHaveAttribute('type', 'password');

    // Toggle password visibility
    const toggleBtn = page.locator('button[aria-label="Show password"]');
    await expect(toggleBtn).toBeVisible();
    await toggleBtn.click();
    await expect(passwordInput).toHaveAttribute('type', 'text');
    await toggleBtn.click();
    await expect(passwordInput).toHaveAttribute('type', 'password');

    // OAuth buttons
    await expect(page.locator('a[href="/auth/google"]')).toBeVisible();
    await expect(page.locator('a[href="/auth/discord"]')).toBeVisible();

    // Forgot password link (magic link)
    const forgotLink = page.locator('a:has-text("Forgot password?")');
    if (await forgotLink.count() > 0) {
      await expect(forgotLink).toHaveAttribute('href', '/login/magic-link');
    }
  });

  test('register page mounts with difficulty options and terms checkbox', async ({ page }) => {
    await page.goto('/register');

    const island = page.locator('[data-island="RegisterPage"]');
    await expect(island).toBeVisible();

    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="password_confirm"]')).toBeVisible();

    // Difficulty cards (Easy, Standard, Hard)
    const easyOption = page.locator('input[name="difficulty"][value="easy"]');
    const stdOption = page.locator('input[name="difficulty"][value="standard"]');
    const hardOption = page.locator('input[name="difficulty"][value="hard"]');
    await expect(easyOption).toBeAttached();
    await expect(stdOption).toBeAttached();
    await expect(hardOption).toBeAttached();

    // Terms checkbox
    const termsCheck = page.locator('input[name="terms"]');
    await expect(termsCheck).toBeVisible();
    await expect(termsCheck).not.toBeChecked();

    // Submit button
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('magic link page mounts and /magic-link alias redirects to /login/magic-link', async ({ page }) => {
    const response = await page.goto('/magic-link');
    expect(response?.status()).toBeLessThan(400);
    expect(page.url()).toContain('/login/magic-link');

    const island = page.locator('[data-island="MagicLinkFormPage"]');
    await expect(island).toBeVisible();

    const emailInput = page.locator('input[name="email"]');
    await expect(emailInput).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });
});
