

const fs = require('fs');
const path = require('path');
const { request } = require('@playwright/test');
const env = require('../config/env');
const { ENDPOINTS } = require('../constants/endpoints');

const TOKEN_STATE_PATH = path.resolve(__dirname, '../.state/api-auth.json');

function authHeaders(token) {
  return {
    Authorization: `Bearer ${token}`,
    Accept:        'application/json',
  };
}

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

function saveToken(tokenPayload) {
  fs.mkdirSync(path.dirname(TOKEN_STATE_PATH), { recursive: true });
  fs.writeFileSync(TOKEN_STATE_PATH, JSON.stringify(tokenPayload, null, 2));
}

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
