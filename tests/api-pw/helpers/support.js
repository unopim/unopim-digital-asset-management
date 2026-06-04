/**
 * Test-support utilities for lifecycle hooks (`beforeAll`/`afterAll`), which
 * cannot consume Playwright's test-scoped fixtures. Builds a standalone,
 * authenticated {@link ApiClient} backed by its own request context.
 */

const { request } = require('@playwright/test');
const env = require('../config/env');
const { ApiClient } = require('../utils/apiHelper');
const { loadToken, fetchAccessToken } = require('../utils/authHelper');

/**
 * Create a standalone authenticated client. Remember to `dispose()` it in
 * `afterAll` to release the underlying request context.
 *
 * @returns {Promise<{client: ApiClient, dispose: () => Promise<void>}>}
 */
async function createClient() {
  const ctx = await request.newContext({ baseURL: env.baseURL });
  const cached = loadToken();
  const token = cached?.access_token || (await fetchAccessToken(ctx)).access_token;
  const client = new ApiClient(ctx, { token });
  return { client, dispose: () => ctx.dispose() };
}

module.exports = { createClient };
