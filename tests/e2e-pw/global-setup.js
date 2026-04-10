const { request } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const STORAGE_PATH = path.resolve(__dirname, '.state/admin-auth.json');

/**
 * Authenticate via the API request context (server-to-server, no chromium UI),
 * so the captured cookies come from a real, completed POST /admin/login → 302
 * round-trip — not from an in-flight UI form submission. Then pin session-cookie
 * expiry so chromium doesn't drop them on reload as already-expired.
 */
module.exports = async function globalSetup(config) {
  fs.mkdirSync(path.dirname(STORAGE_PATH), { recursive: true });

  const baseURL = config?.projects?.[0]?.use?.baseURL || process.env.BASE_URL || 'http://127.0.0.1:8000';
  const email = process.env.ADMIN_USERNAME || 'admin@example.com';
  const password = process.env.ADMIN_PASSWORD || 'admin123';

  const ctx = await request.newContext({ baseURL });

  // GET the login page to seed XSRF-TOKEN + session cookies and grab the _token.
  const loginPage = await ctx.get('/admin/login');
  if (!loginPage.ok()) {
    throw new Error(`global-setup: GET /admin/login → ${loginPage.status()}`);
  }
  const html = await loginPage.text();
  const tokenMatch = html.match(/name="_token"\s+value="([^"]+)"/);
  if (!tokenMatch) throw new Error('global-setup: could not find _token on /admin/login');
  const csrfToken = tokenMatch[1];

  // POST credentials. Follow redirects so the auth-session cookie lands.
  const resp = await ctx.post('/admin/login', {
    form: { _token: csrfToken, email, password },
  });
  if (!resp.ok() || resp.url().includes('/login')) {
    throw new Error(`global-setup: login POST landed on ${resp.url()} (status ${resp.status()})`);
  }

  // Verify the session actually authenticates /admin/dam.
  const dam = await ctx.get('/admin/dam');
  if (!dam.ok() || dam.url().includes('/login')) {
    throw new Error(`global-setup: GET /admin/dam after login → ${dam.status()} ${dam.url()}`);
  }

  await ctx.storageState({ path: STORAGE_PATH });
  await ctx.dispose();

  // Playwright marks server-set "session" cookies (no Expires header) with
  // expires: -1. Chromium then drops them on context reload as expired, so
  // every test would start unauthenticated. Pin those to a future timestamp.
  const state = JSON.parse(fs.readFileSync(STORAGE_PATH, 'utf8'));
  const oneDayFromNow = Math.floor(Date.now() / 1000) + 24 * 60 * 60;
  let mutated = false;
  for (const cookie of state.cookies || []) {
    if (cookie.expires === -1 || cookie.expires === undefined) {
      cookie.expires = oneDayFromNow;
      mutated = true;
    }
  }
  if (mutated) fs.writeFileSync(STORAGE_PATH, JSON.stringify(state, null, 2));
};
