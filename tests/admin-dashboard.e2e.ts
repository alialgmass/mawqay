import { test } from '@e2e-dev/web';
import { credentials, expect } from 'e2e';

// The panel defaults to Arabic (RTL), so locators target structure, not copy.
test('an admin signs in and reaches the dashboard', async ({ app, browser }) => {
  const admin = credentials.user('admin');

  await app.open('/admin/login');
  await browser.locator('input[type="email"]').fill(admin.username);
  await browser.locator('input[type="password"]').fill(admin.password);
  await browser.locator('button[type="submit"]').click();

  await expect(browser).toHaveURL('/admin');
  await expect(browser.locator('nav.fi-sidebar-nav')).toBeVisible();
  await expect(browser.locator('.fi-wi-account')).toBeVisible();
});

test('a wrong password keeps the admin on the login page', async ({ app, browser }) => {
  await app.open('/admin/login');
  await browser.locator('input[type="email"]').fill(credentials.user('admin').username);
  await browser.locator('input[type="password"]').fill('definitely-wrong-password');
  await browser.locator('button[type="submit"]').click();

  await expect(browser).toHaveURL('/admin/login');
  await expect(browser.locator('.fi-fo-field-wrp-error-message')).toBeVisible();
});

test('the API rejects invalid login credentials', async ({ app }) => {
  const response = await fetch(new URL('/api/v1/auth/login', app.baseUrl), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ phone: '+201000000000', password: 'definitely-wrong-password' }),
  });

  expect([401, 422]).toContain(response.status);
});
