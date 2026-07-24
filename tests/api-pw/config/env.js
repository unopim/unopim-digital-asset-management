

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

function trimTrailingSlash(value) {
  return typeof value === 'string' ? value.replace(/\/+$/, '') : value;
}

const env = {

  baseURL: trimTrailingSlash(process.env.BASE_URL || 'http://127.0.0.1:8000'),

  oauth: {

    tokenUrl:     process.env.OAUTH_TOKEN_URL || '/oauth/token',
    clientId:     process.env.OAUTH_CLIENT_ID || '',
    clientSecret: process.env.OAUTH_CLIENT_SECRET || '',
    username:     process.env.ADMIN_USERNAME || 'admin@example.com',
    password:     process.env.ADMIN_PASSWORD || 'admin123',
    scope:        process.env.OAUTH_SCOPE || '*',
  },

  apiToken: process.env.API_TOKEN || '',

  locale: process.env.API_LOCALE || 'en_US',

  slowResponseMs: Number(process.env.SLOW_RESPONSE_MS || 2000),
};

module.exports = env;
