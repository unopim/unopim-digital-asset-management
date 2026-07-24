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

module.exports = async function globalSetup(config) {
  fs.mkdirSync(path.dirname(STORAGE_PATH), { recursive: true });

  const baseURL = config?.projects?.[0]?.use?.baseURL || process.env.BASE_URL || 'http://127.0.0.1:8000';
  const email = process.env.ADMIN_USERNAME || 'admin@example.com';
  const password = process.env.ADMIN_PASSWORD || 'admin123';

  const ctx = await request.newContext({ baseURL });

  const loginPage = await ctx.get('/admin/login');
  if (!loginPage.ok()) {
    throw new Error(`global-setup: GET /admin/login → ${loginPage.status()}`);
  }
  const html = await loginPage.text();
  const tokenMatch = html.match(/name="_token"\s+value="([^"]+)"/);
  if (!tokenMatch) throw new Error('global-setup: could not find _token on /admin/login');
  const csrfToken = tokenMatch[1];

  const resp = await ctx.post('/admin/login', {
    form: { _token: csrfToken, email, password },
  });
  if (!resp.ok() || resp.url().includes('/login')) {
    throw new Error(`global-setup: login POST landed on ${resp.url()} (status ${resp.status()})`);
  }

  const dam = await ctx.get('/admin/dam');
  if (!dam.ok() || dam.url().includes('/login')) {
    throw new Error(`global-setup: GET /admin/dam after login → ${dam.status()} ${dam.url()}`);
  }

  await ensureCustomRole(ctx);

  await ctx.storageState({ path: STORAGE_PATH });
  await ctx.dispose();

  const state = JSON.parse(fs.readFileSync(STORAGE_PATH, 'utf8'));
  const oneDayFromNow = Math.floor(Date.now() / 1000) + 24 * 60 * 60;
  let mutated = false;
  for (const cookie of state.cookies || []) {
    if (cookie.expires === -1 || cookie.expires === undefined) {
      cookie.expires = oneDayFromNow;
      mutated = true;
    }
  }

  state.cookies = state.cookies || [];
  if (! state.cookies.some((c) => c.name === 'sidebar_collapsed')) {
    const host = (() => { try { return new URL(baseURL).hostname; } catch { return 'localhost'; } })();
    state.cookies.push({
      name: 'sidebar_collapsed',
      value: '0',
      domain: host,
      path: '/',
      expires: oneDayFromNow,
      httpOnly: false,
      secure: false,
      sameSite: 'Lax',
    });
    mutated = true;
  }

  if (mutated) fs.writeFileSync(STORAGE_PATH, JSON.stringify(state, null, 2));

  await seedAssets(baseURL);
};

async function ensureCustomRole(ctx) {
  try {
    const createPage = await ctx.get('/admin/settings/roles/create');
    if (! createPage.ok()) return;

    const html = await createPage.text();
    const tokenMatch = html.match(/name="_token"\s+value="([^"]+)"/);
    if (! tokenMatch) return;

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
