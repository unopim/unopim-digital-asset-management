const base = require('@playwright/test');

exports.test = base.test.extend({

  adminPage: async ({ page }, use) => {
    await use(page);
  },

  uid: async ({}, use) => {
    const { randomBytes } = require('crypto');
    await use(Date.now().toString(36) + randomBytes(4).toString('hex'));
  },
});

exports.expect = base.expect;
