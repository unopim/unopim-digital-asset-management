const { request, chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const STORAGE_PATH = path.resolve(__dirname, '.state/admin-auth.json');

const SEED_ASSETS = [
  { filePath: path.resolve(__dirname, 'assets/floral.jpg'), searchName: 'floral.jpg' },
  { filePath: path.resolve(__dirname, 'assets/sample.mp4'), searchName: 'sample.mp4' },
  { filePath: path.resolve(__dirname, 'assets/sample.wav'), searchName: 'sample.wav' },
  { filePath: path.resolve(__dirname, 'assets/sample.pdf'), searchName: 'sample.pdf' },
];

/**
 * Authenticate via the API request context (server-to-server, no chromium UI),
 * so the captured cookies come from a real, completed POST /admin/login → 302
 * round-trip — not from an in-flight UI form submission. Then pin session-cookie
 * expiry so chromium doesn't drop them on reload as already-expired.
 *
 * After auth, seeds non-image test assets (mp4/wav/pdf) via a real Chromium
 * browser so they exist for every spec regardless of --grep or CI sharding.
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

  // Seed a custom-permission role so the role-edit DAM Permissions tab has a
  // target role to attach grants to. The tab's v-if hides it for
  // permission_type='all' roles (super-admin) which is the only role shipped
  // by default, so without this seed the tab is never rendered in e2e runs.
  await ensureCustomRole(ctx);

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

  // Seed test assets (floral.jpg + mp4/wav/pdf) via real Chromium so they exist for every spec.
  await seedAssets(baseURL);
};

async function ensureCustomRole(ctx) {
  try {
    const createPage = await ctx.get('/admin/settings/roles/create');
    if (! createPage.ok()) return;

    const html = await createPage.text();
    const tokenMatch = html.match(/name="_token"\s+value="([^"]+)"/);
    if (! tokenMatch) return;

    // Duplicate name on re-runs returns a redirect-back with a validation
    // error; harmless since the prior row still satisfies the DAM permissions
    // tab requirement of having at least one custom role to edit.
    await ctx.post('/admin/settings/roles/create', {
      form: {
        _token:          tokenMatch[1],
        name:            'DAM E2E Custom Role',
        description:     'Seeded by Playwright global-setup so the DAM directory permission manager has an assignable role.',
        permission_type: 'custom',
      },
    });
  } catch (err) {
    console.warn(`global-setup: ensureCustomRole failed — ${err.message}`);
  }
}

async function seedAssets(baseURL) {
  const { ensureAssetOfTypeExists } = require('./utils/helpers');

  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
  });

  try {
    const context = await browser.newContext({ storageState: STORAGE_PATH, baseURL });
    const page = await context.newPage();

    for (const { filePath, searchName } of SEED_ASSETS) {
      try {
        await ensureAssetOfTypeExists(page, filePath, searchName);
      } catch (err) {
        console.warn(`global-setup: seed "${searchName}" failed — ${err.message}`);
      }
    }

    await context.close().catch(() => {});
  } finally {
    await browser.close().catch(() => {});
  }
}
