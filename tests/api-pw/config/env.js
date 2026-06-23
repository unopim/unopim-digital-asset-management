/**
 * Centralised environment configuration for the DAM API test suite.
 *
 * Every value is overridable through environment variables so the same suite
 * runs unchanged against local (`artisan serve`), CI and staging targets.
 * Nothing secret is committed — see `.env.example` for the variables to set.
 */

/**
 * Minimal, dependency-free `.env` loader. Reads `tests/api-pw/.env` (if present)
 * and populates any variable not already set in the real environment, so an
 * exported var always wins over the file. Avoids pulling in `dotenv`.
 */
(function loadDotEnv() {
  const fs = require('fs');
  const path = require('path');
  const file = path.resolve(__dirname, '../.env');
  if (!fs.existsSync(file)) return;
  for (const rawLine of fs.readFileSync(file, 'utf8').split('\n')) {
    const line = rawLine.trim();
    if (!line || line.startsWith('#')) continue;
    const eq = line.indexOf('=');
    if (eq === -1) continue;
    const key = line.slice(0, eq).trim();
    let value = line.slice(eq + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    if (!(key in process.env)) process.env[key] = value;
  }
})();

/** Trim a trailing slash so `${baseURL}${path}` never produces a double slash. */
function trimTrailingSlash(value) {
  return typeof value === 'string' ? value.replace(/\/+$/, '') : value;
}

const env = {
  /** Base URL of the UnoPim instance under test. */
  baseURL: trimTrailingSlash(process.env.BASE_URL || 'http://127.0.0.1:8000'),

  /**
   * OAuth2 password-grant configuration. The DAM REST API is guarded by
   * Passport (`auth:api`), so every request needs a bearer token minted from
   * these credentials. See README → Authentication.
   */
  oauth: {
    /** Passport token endpoint. Default is the Passport-shipped route. */
    tokenUrl:     process.env.OAUTH_TOKEN_URL || '/oauth/token',
    clientId:     process.env.OAUTH_CLIENT_ID || '',
    clientSecret: process.env.OAUTH_CLIENT_SECRET || '',
    username:     process.env.ADMIN_USERNAME || 'admin@example.com',
    password:     process.env.ADMIN_PASSWORD || 'admin123',
    scope:        process.env.OAUTH_SCOPE || '*',
  },

  /**
   * Optional pre-minted bearer token. When set it is reused verbatim and the
   * password-grant flow is skipped entirely — handy in environments where the
   * token is provisioned out-of-band.
   */
  apiToken: process.env.API_TOKEN || '',

  /** Locale sent on requests that are locale-aware (e.g. properties). */
  locale: process.env.API_LOCALE || 'en_US',

  /** Soft threshold (ms) used to flag slow responses in attachments. */
  slowResponseMs: Number(process.env.SLOW_RESPONSE_MS || 2000),
};

module.exports = env;
