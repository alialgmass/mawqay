import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

test('the admin login page opens', async ({ app, browser }) => {
  await app.open('/admin/login');
  await expect(browser.locator('input[type="email"]')).toBeVisible();
  await expect(browser.locator('input[type="password"]')).toBeVisible();
});
