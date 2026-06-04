/**
 * Global setup for the DAM API suite.
 *
 * Runs once before any spec. It mints an OAuth2 bearer token (or accepts a
 * pre-supplied API_TOKEN), verifies the token actually authenticates the DAM
 * API, and caches it to `.state/api-auth.json`. Every spec then reuses that
 * token via the `token` fixture, so the whole run authenticates exactly once.
 *
 * Mirrors the responsibility split in `tests/e2e-pw/global-setup.js`: do all
 * the auth work here, not per-test.
 */

const { request } = require('@playwright/test');
const env = require('./config/env');
const { ENDPOINTS } = require('./constants/endpoints');
const { fetchAccessToken, saveToken } = require('./utils/authHelper');

module.exports = async function globalSetup() {
  const ctx = await request.newContext({ baseURL: env.baseURL });

  try {
    // 1. Mint (or accept) the token.
    const tokenPayload = await fetchAccessToken(ctx);

    // 2. Smoke-check it: a 401 here means bad credentials, not a broken spec.
    const probe = await ctx.get(ENDPOINTS.assets.index(), {
      headers: {
        Authorization: `Bearer ${tokenPayload.access_token}`,
        Accept: 'application/json',
      },
    });

    if (probe.status() === 401) {
      throw new Error(
        'global-setup: minted token was rejected (401) by the DAM API. ' +
        'Verify the Passport client and admin credentials.'
      );
    }

    // Any non-401 (200, 403 for a permission-scoped user, etc.) means the token
    // is valid and authenticated; permission scoping is exercised by the specs.
    saveToken(tokenPayload);
    // eslint-disable-next-line no-console
    console.log(`global-setup: token acquired and verified against ${env.baseURL}`);
  } finally {
    await ctx.dispose();
  }
};
