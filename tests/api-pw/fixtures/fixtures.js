/**
 * Playwright fixtures for the DAM API suite.
 *
 * Mirrors the style of `tests/e2e-pw/utils/fixtures.js`: extend the base test
 * with custom fixtures. Here we expose ready-to-use API clients so specs never
 * deal with tokens or request contexts directly:
 *
 *   - `api`         → authenticated ApiClient (bearer token from global-setup),
 *   - `anonApi`     → unauthenticated ApiClient (for 401 / access-control tests),
 *   - `token`       → the raw bearer token string,
 *   - `uid`         → a unique-per-test identifier for data isolation.
 *
 * The authenticated token is minted once in global-setup and cached to disk;
 * the fixture simply reads it back, so the suite hits `/oauth/token` once total.
 */

const base = require('@playwright/test');
const env = require('../config/env');
const { ApiClient } = require('../utils/apiHelper');
const { loadToken, fetchAccessToken } = require('../utils/authHelper');

const test = base.test.extend({
  /** Bearer token — from the global-setup cache, falling back to a fresh mint. */
  token: async ({}, use) => {
    const cached = loadToken();
    let token = cached?.access_token;
    if (!token) {
      // Fallback so a single spec can be run in isolation without global-setup.
      const minted = await fetchAccessToken();
      token = minted.access_token;
    }
    await use(token);
  },

  /** Authenticated API client bound to this test's TestInfo for auto-attach. */
  api: async ({ request, token }, use, testInfo) => {
    await use(new ApiClient(request, { token, testInfo }));
  },

  /** Unauthenticated client — drives 401 / unauthorized-access assertions. */
  anonApi: async ({ request }, use, testInfo) => {
    await use(new ApiClient(request, { token: null, testInfo }));
  },

  /** Client carrying a deliberately invalid token for auth-failure tests. */
  invalidTokenApi: async ({ request }, use, testInfo) => {
    await use(new ApiClient(request, { token: 'invalid.token.value', testInfo }));
  },

  /** Unique identifier for per-test data isolation. */
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
