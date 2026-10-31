import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

// Primary landing journey on a phone. Locators target structure (ids, dir,
// data-test), not copy, so the spec holds in Arabic and English.
test.beforeEach(async ({ browser }) => {
  await browser.setViewportSize({ width: 360, height: 780 });
});

test('the landing is right-to-left by default with hero, payments and register sections', async ({ app, browser }) => {
  await app.open('/');

  await expect(browser.locator('html')).toHaveAttribute('dir', 'rtl');
  await expect(browser.locator('#hero h1')).toBeVisible();
  await expect(browser.locator('#money')).toBeAttached();
  await expect(browser.locator('[data-test="register-submit"]')).toBeAttached();
});

test('the hero call to action brings the register form into view', async ({ app, browser }) => {
  await app.open('/');

  await browser.locator('#hero h1 ~ div button').first().click();

  await expect(browser.locator('#register')).toBeInViewport();
});

test('the store address preview follows the slug field', async ({ app, browser }) => {
  await app.open('/');

  await browser.locator('#store_slug').fill('noor-boutique');

  await expect(browser.locator('[data-test="slug-preview"]')).toContainText('noor-boutique');
});

test('an invalid registration keeps the visitor on the form with errors', async ({ app, browser }) => {
  await app.open('/');

  await browser.locator('#store_name').fill('Noor');
  await browser.locator('#store_slug').fill('admin');
  await browser.locator('#owner_name').fill('Noor');
  await browser.locator('#owner_email').fill('not-an-email');
  await browser.locator('[data-test="register-submit"]').click();

  await expect(browser.locator('#register')).toBeVisible();
  await expect(browser.locator('[data-test="registration-status"]')).toHaveCount(0);
});
