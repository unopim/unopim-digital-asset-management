const base = require('@playwright/test');

exports.test = base.test.extend({
  /**
   * Authenticated admin page fixture.
   * Trusts the storageState set by global-setup.js. No upfront navigation, no
   * framenavigated handler — both caused 60s deadlocks. If storageState fails
   * to authenticate, tests will land on /login and fail with a clear error
   * pointing at global-setup, which is correct: re-login should happen there,
   * not per-test.
   */
  adminPage: async ({ page }, use) => {
    await use(page);
  },

  /** Unique identifier for test data isolation in parallel execution */
  uid: async ({}, use) => {
    const { randomBytes } = require('crypto');
    await use(Date.now().toString(36) + randomBytes(4).toString('hex'));
  },
});

exports.expect = base.expect;
