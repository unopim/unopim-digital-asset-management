const ROUTES = {
  dam:             '/admin/dam',
  damAssets:       '/admin/dam/assets',
  rolesIndex:      '/admin/settings/roles',
};

async function closeApShell(page) {
  try {
    const isOpen = await page.evaluate(() => {

      const panel = document.querySelector('.ap-panel');
      if (panel && panel.getBoundingClientRect().width > 0) return true;

      const backdrops = Array.from(document.querySelectorAll('div')).filter(el => {
        const s = el.style;
        return s.position === 'fixed' && s.inset === '0px' && parseInt(s.zIndex) >= 10001;
      });
      return backdrops.length > 0;
    });
    if (isOpen) {

      const closeBtn = page.locator('.ap-panel button[title="Close"]');
      if (await closeBtn.isVisible().catch(() => false)) {
        await closeBtn.click();
      } else {

        await page.locator('.ap-shell').evaluate(shell => {
          const buttons = shell.querySelectorAll('button');
          if (buttons.length) buttons[buttons.length - 1].click();
        });
      }
      await page.waitForTimeout(300);
    }
  } catch (_) {

  }
}

async function navigateTo(page, route) {
  const url = ROUTES[route];
  if (!url) throw new Error(`Unknown route: "${route}". Available: ${Object.keys(ROUTES).join(', ')}`);

  try {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  } catch (e) {
    if (e.message.includes('ERR_ABORTED') || e.message.includes('net::ERR_')) {
      await page.waitForTimeout(1500).catch(() => {});
      await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    } else {
      throw e;
    }
  }
  await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });

  await page.getByPlaceholder('Search').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

  await closeApShell(page);
}

async function searchInDataGrid(page, text, placeholder = 'Search') {
  const searchInput = page.getByPlaceholder(placeholder, { exact: true }).first();
  await searchInput.waitFor({ state: 'visible', timeout: 30000 });
  await searchInput.fill(text);

  const responsePromise = page.waitForResponse(
    (res) => /\/admin\/dam\/assets(\?|$)/.test(res.url())
      && res.request().method() === 'GET',
    { timeout: 15000 }
  ).catch(() => {});

  await page.keyboard.press('Enter');
  await responsePromise;
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(300);
}

async function clickEditOnRow(page, rowText) {
  const row = page.locator('#app div').filter({ hasText: rowText }).first();
  await row.locator('span[title="Edit"]').first().click();
  await page.waitForLoadState('domcontentloaded');
}

async function clickDeleteOnRow(page, rowText) {
  const row = page.locator('#app div').filter({ hasText: rowText }).first();
  await row.locator('span[title="Delete"]').first().click();
}

async function confirmDelete(page) {
  await page.getByRole('button', { name: 'Delete' }).click();
  await page.waitForLoadState('domcontentloaded');
}

async function expectSuccessToast(page, pattern, timeout = 20000) {
  const { expect } = require('@playwright/test');
  const regex = pattern instanceof RegExp ? pattern : new RegExp(pattern, 'i');
  await expect(page.locator('#app').getByText(regex).first()).toBeVisible({ timeout });
}

async function clickSaveAndExpect(page, buttonName, toastPattern, urlPattern) {
  const currentUrl = page.url();
  const regex = toastPattern instanceof RegExp ? toastPattern : new RegExp(toastPattern, 'i');

  await page.getByRole('button', { name: buttonName }).click();

  await Promise.any([
    page.locator('#app').getByText(regex).first().waitFor({ state: 'visible', timeout: 20000 }),
    urlPattern
      ? page.waitForURL(urlPattern, { timeout: 20000 })
      : page.waitForURL((url) => url.toString() !== currentUrl, { timeout: 20000 }),
  ]);
}

function generateUid() {
  const { randomBytes } = require('crypto');
  return Date.now().toString(36) + randomBytes(4).toString('hex');
}

async function primeUploadDirectory(page) {
  const rootRow = page.locator('[data-dir-id]').first();
  await rootRow.waitFor({ state: 'visible', timeout: 30000 });
  await rootRow.click({ force: true });
  await page.waitForTimeout(200);
}

async function dismissUploadPanel(page) {
  const panel = page.locator('[data-dam-upload-panel]');

  for (let attempt = 0; attempt < 5; attempt++) {
    const closeButton = panel.locator('button').first();

    if (! (await closeButton.isVisible().catch(() => false))) {
      return;
    }

    await closeButton.click({ force: true }).catch(() => {});
    await page.waitForTimeout(200);
  }
}

async function openFilterDrawer(page) {
  await dismissUploadPanel(page);
  await page.getByText('Filter', { exact: true }).first().click();
  await page.locator('[data-datagrid-filter]').first().waitFor({ state: 'visible', timeout: 15000 });
}

async function expandFilter(page, columnIndex) {
  const row = page.locator(`[data-datagrid-filter="${columnIndex}"]`);
  await row.waitFor({ state: 'visible', timeout: 15000 });

  if ((await row.locator('button[data-filter-toggle]').getAttribute('aria-expanded')) !== 'true') {
    await row.locator('button[data-filter-toggle]').click();
  }

  return row;
}

async function ensureAssetExists(page) {
  const path = require('path');
  await navigateTo(page, 'dam');
  await page.waitForTimeout(500);

  const existing = page.locator('.image-card').first();
  if (await existing.isVisible().catch(() => false)) return;

  await primeUploadDirectory(page);

  const fileInput = page.locator('input[type="file"][name="files[]"]');
  await fileInput.waitFor({ state: 'attached', timeout: 15000 });
  await fileInput.setInputFiles(path.resolve(__dirname, '../assets/floral.jpg'));

  await Promise.race([
    page.locator('#app').getByText(/uploaded successfully/i).first()
      .waitFor({ state: 'visible', timeout: 30000 }),
    page.locator('.image-card').first().waitFor({ state: 'visible', timeout: 30000 }),
  ]).catch(() => {});

  await navigateTo(page, 'dam');
  await page.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });
}

async function ensureAssetOfTypeExists(page, filePath, searchName) {
  await navigateTo(page, 'dam');
  await page.waitForTimeout(500);

  await searchInDataGrid(page, searchName);

  const exists = await page.locator('h2').filter({ hasText: searchName }).first()
    .isVisible({ timeout: 3000 })
    .catch(() => false);

  await navigateTo(page, 'dam');
  await page.waitForTimeout(300);

  if (exists) return;

  await primeUploadDirectory(page);

  const fileInput = page.locator('input[type="file"][name="files[]"]');
  await fileInput.waitFor({ state: 'attached', timeout: 15000 });
  await fileInput.setInputFiles(filePath);

  await Promise.race([
    page.locator('#app').getByText(/uploaded successfully/i).first()
      .waitFor({ state: 'visible', timeout: 30000 }),
    page.locator('h2').filter({ hasText: searchName }).first()
      .waitFor({ state: 'visible', timeout: 30000 }),
  ]).catch(() => {});

  await navigateTo(page, 'dam');
}

async function navigateToAssetEditByName(page, searchName) {
  await navigateTo(page, 'dam');
  await searchInDataGrid(page, searchName);

  await page.locator('h2').filter({ hasText: searchName }).first()
    .waitFor({ state: 'visible', timeout: 15000 });

  const cardWrapper = page.locator('div:has(> .image-card)')
    .filter({ hasText: searchName })
    .first();
  await cardWrapper.waitFor({ state: 'visible', timeout: 15000 });
  const card = cardWrapper.locator('.image-card').first();
  await closeApShell(page);
  await card.hover({ force: true });
  await page.waitForTimeout(300);
  await card.locator('.icon-edit').first().click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

module.exports = {
  primeUploadDirectory,
  ROUTES,
  navigateTo,
  searchInDataGrid,
  clickEditOnRow,
  clickDeleteOnRow,
  confirmDelete,
  expectSuccessToast,
  clickSaveAndExpect,
  generateUid,
  ensureAssetExists,
  ensureAssetOfTypeExists,
  openFilterDrawer,
  expandFilter,
  dismissUploadPanel,
  navigateToAssetEditByName,
  closeApShell,
};
