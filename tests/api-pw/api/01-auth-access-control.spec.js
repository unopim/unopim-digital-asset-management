/**
 * Permissions & Access Control + Authentication.
 *
 * Verifies the Passport guard on the DAM API: authenticated requests pass,
 * anonymous and bad-token requests are rejected, and the token can be reused
 * and refreshed. Directory-scoped (403) permission checks live alongside the
 * resource specs that own each route.
 */

const { test, expect, env } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');
const { fetchAccessToken, refreshAccessToken, authHeaders } = require('../utils/authHelper');
const assetHelper = require('../helpers/assetHelper');

test.describe('Authentication & access control', () => {
  test('authorized user can reach a protected endpoint', async ({ api }) => {
    const res = await assetHelper.list(api);
    // 200 for a full-access user; never an auth failure for a valid token.
    expect(res.status).not.toBe(STATUS.UNAUTHORIZED);
    expect([STATUS.OK, STATUS.FORBIDDEN]).toContain(res.status);
  });

  test('anonymous request is rejected with 401', async ({ anonApi }) => {
    const res = await anonApi.get(ENDPOINTS.assets.index());
    expect(res.status).toBe(STATUS.UNAUTHORIZED);
  });

  test('invalid bearer token is rejected with 401', async ({ invalidTokenApi }) => {
    const res = await invalidTokenApi.get(ENDPOINTS.assets.index());
    expect(res.status).toBe(STATUS.UNAUTHORIZED);
  });

  test('malformed Authorization header is rejected', async ({ anonApi }) => {
    const res = await anonApi.get(ENDPOINTS.assets.index(), {
      headers: { Authorization: 'NotBearer abc.def.ghi' },
    });
    expect(res.status).toBe(STATUS.UNAUTHORIZED);
  });

  test('a valid token is reusable across multiple requests', async ({ api }) => {
    const first = await assetHelper.list(api);
    const second = await assetHelper.list(api);
    expect(first.status).toBe(second.status);
    expect(first.status).not.toBe(STATUS.UNAUTHORIZED);
  });
});

test.describe('Token lifecycle (OAuth2 password grant)', () => {
  // Skips cleanly when running with a pre-supplied API_TOKEN (no client creds).
  test.skip(() => !!env.apiToken, 'password-grant lifecycle is not exercised with a fixed API_TOKEN');

  test('mints a token and it authenticates the DAM API', async ({ request }) => {
    const token = await fetchAccessToken(request);
    expect(token.access_token, 'access_token present').toBeTruthy();

    const probe = await request.get(ENDPOINTS.assets.index(), { headers: authHeaders(token.access_token) });
    expect(probe.status()).not.toBe(STATUS.UNAUTHORIZED);
  });

  test('refreshes the access token when a refresh_token is issued', async ({ request }) => {
    const token = await fetchAccessToken(request);
    test.skip(!token.refresh_token, 'server did not issue a refresh_token');

    const refreshed = await refreshAccessToken(token.refresh_token, request);
    expect(refreshed.access_token).toBeTruthy();
    expect(refreshed.access_token).not.toBe(token.access_token);
  });
});
