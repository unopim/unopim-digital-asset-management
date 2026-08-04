

const base = require('@playwright/test');
const env = require('../config/env');
const { ApiClient } = require('../utils/apiHelper');
const { loadToken, fetchAccessToken } = require('../utils/authHelper');

const test = base.test.extend({

  token: async ({}, use) => {
    const cached = loadToken();
    let token = cached?.access_token;
    if (!token) {

      const minted = await fetchAccessToken();
      token = minted.access_token;
    }
    await use(token);
  },

  api: async ({ request, token }, use, testInfo) => {
    await use(new ApiClient(request, { token, testInfo }));
  },

  anonApi: async ({ request }, use, testInfo) => {
    await use(new ApiClient(request, { token: null, testInfo }));
  },

  invalidTokenApi: async ({ request }, use, testInfo) => {
    await use(new ApiClient(request, { token: 'invalid.token.value', testInfo }));
  },

  uid: async ({}, use) => {
    const { randomBytes } = require('crypto');
    await use(Date.now().toString(36) + randomBytes(4).toString('hex'));
  },
});

module.exports = {
  test,
  expect: base.expect,
  env,
};
