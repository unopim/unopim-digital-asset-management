const base = require('@playwright/test');
const path = require('path');
const fs = require('fs');

const STORAGE_STATE = path.resolve(__dirname, '../.state/admin-auth.json');

exports.test = base.test.extend({
  /**
   * Authenticated admin page fixture.
   * Uses storageState from config. If session expired, logs in fresh and updates the state file.
   */
  adminPage: async ({ page, browser }, use) => {
    await page.goto('/admin/dam', { waitUntil: 'domcontentloaded', timeout: 60000 });

    if (page.url().includes('/login')) {
      await page.goto('/admin/login', { waitUntil: 'networkidle', timeout: 60000 });
      const emailField = page.getByRole('textbox', { name: 'Email Address' });
      await emailField.waitFor({ state: 'visible', timeout: 15000 });
      await emailField.fill('admin@example.com');
      await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
      await page.getByRole('button', { name: 'Sign In' }).click();
      await page.waitForURL('**/admin/dashboard**', { timeout: 60000 });
      await page.context().storageState({ path: STORAGE_STATE });
    }

    await use(page);
  },

  /** Unique identifier for test data isolation in parallel execution */
  uid: async ({}, use) => {
    const { randomBytes } = require('crypto');
    await use(Date.now().toString(36) + randomBytes(4).toString('hex'));
  },
});

exports.expect = base.expect;
