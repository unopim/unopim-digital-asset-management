/**
 * Authentication utilities for the DAM REST API.
 *
 * The API is guarded by Laravel Passport (`auth:api`), so callers need an
 * OAuth2 bearer token. This module centralises:
 *   - minting a token via the password grant,
 *   - refreshing it via the refresh-token grant,
 *   - building the Authorization/Accept header block,
 *   - caching the token to disk so it is reused across the whole run.
 *
 * `global-setup.js` mints the token once and persists it; the `api` fixture
 * reads it back, so individual specs never authenticate themselves.
 */

const fs = require('fs');
const path = require('path');
const { request } = require('@playwright/test');
const env = require('../config/env');
const { ENDPOINTS } = require('../constants/endpoints');

/** Where the minted token is cached between global-setup and the specs. */
const TOKEN_STATE_PATH = path.resolve(__dirname, '../.state/api-auth.json');

/**
 * Standard auth header block for an authenticated JSON request.
 *
 * @param {string} token Bearer access token.
 * @returns {Record<string,string>}
 */
function authHeaders(token) {
  return {
    Authorization: `Bearer ${token}`,
    Accept:        'application/json',
  };
}

/**
 * Mint an access token via the OAuth2 password grant.
 *
 * When `API_TOKEN` is supplied in the environment it is returned verbatim and
 * no network call is made.
 *
 * @param {import('@playwright/test').APIRequestContext} [ctx] Optional shared context.
 * @returns {Promise<{access_token:string, refresh_token?:string, token_type?:string, expires_in?:number}>}
 */
async function fetchAccessToken(ctx) {
  if (env.apiToken) {
    return { access_token: env.apiToken, token_type: 'Bearer' };
  }

  const owned = !ctx;
  const context = ctx || (await request.newContext({ baseURL: env.baseURL }));

  try {
    const response = await context.post(env.oauth.tokenUrl || ENDPOINTS.oauthToken, {
      headers: { Accept: 'application/json' },
      form: {
        grant_type:    'password',
        client_id:     env.oauth.clientId,
        client_secret: env.oauth.clientSecret,
        username:      env.oauth.username,
        password:      env.oauth.password,
        scope:         env.oauth.scope,
      },
    });

    const body = await response.json().catch(() => ({}));

    if (!response.ok() || !body.access_token) {
      throw new Error(
        `OAuth token request failed → ${response.status()} ${JSON.stringify(body)}\n` +
        'Check OAUTH_CLIENT_ID / OAUTH_CLIENT_SECRET / ADMIN_USERNAME / ADMIN_PASSWORD ' +
        'and OAUTH_TOKEN_URL in your environment (.env).'
      );
    }

    return body;
  } finally {
    if (owned) await context.dispose();
  }
}

/**
 * Exchange a refresh token for a fresh access token (refresh-token grant).
 *
 * @param {string} refreshToken
 * @param {import('@playwright/test').APIRequestContext} [ctx]
 * @returns {Promise<object>} New token payload.
 */
async function refreshAccessToken(refreshToken, ctx) {
  const owned = !ctx;
  const context = ctx || (await request.newContext({ baseURL: env.baseURL }));

  try {
    const response = await context.post(env.oauth.tokenUrl || ENDPOINTS.oauthToken, {
      headers: { Accept: 'application/json' },
      form: {
        grant_type:    'refresh_token',
        refresh_token: refreshToken,
        client_id:     env.oauth.clientId,
        client_secret: env.oauth.clientSecret,
        scope:         env.oauth.scope,
      },
    });

    const body = await response.json().catch(() => ({}));
    if (!response.ok() || !body.access_token) {
      throw new Error(`OAuth refresh failed → ${response.status()} ${JSON.stringify(body)}`);
    }
    return body;
  } finally {
    if (owned) await context.dispose();
  }
}

/** Persist a token payload so specs reuse it instead of re-authenticating. */
function saveToken(tokenPayload) {
  fs.mkdirSync(path.dirname(TOKEN_STATE_PATH), { recursive: true });
  fs.writeFileSync(TOKEN_STATE_PATH, JSON.stringify(tokenPayload, null, 2));
}

/**
 * Read the cached token payload written by global-setup.
 *
 * @returns {{access_token:string}|null}
 */
function loadToken() {
  try {
    return JSON.parse(fs.readFileSync(TOKEN_STATE_PATH, 'utf8'));
  } catch (_) {
    return null;
  }
}

module.exports = {
  TOKEN_STATE_PATH,
  authHeaders,
  fetchAccessToken,
  refreshAccessToken,
  saveToken,
  loadToken,
};
