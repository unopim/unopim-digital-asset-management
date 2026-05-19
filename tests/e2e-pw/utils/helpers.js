const ROUTES = {
  dam:             '/admin/dam',
  damAssets:       '/admin/dam/assets',
  rolesIndex:      '/admin/settings/roles',
};

/**
 * Navigate directly to a DAM admin route.
 * @param {import('@playwright/test').Page} page
 * @param {keyof ROUTES} route
 */
async function navigateTo(page, route) {
  const url = ROUTES[route];
  if (!url) throw new Error(`Unknown route: "${route}". Available: ${Object.keys(ROUTES).join(', ')}`);
  // domcontentloaded — not networkidle. The DAM page has constant background
  // traffic (queue polling, completeness updates) that prevents network idle.
  // One retry: artisan serve is single-threaded; Chrome's parallel sub-resource
  // requests can cause ERR_ABORTED on the first attempt.
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
  // Sentinel: the toolbar's Search input only mounts after the Vue grid component
  // is interactive. Far cheaper than networkidle and doesn't deadlock.
  await page.getByPlaceholder('Search').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});
}

/**
 * Search in a DataGrid using the search input.
 *
 * Waits for the datagrid AJAX response triggered by Enter so the grid
 * actually reflects the filter before callers inspect it. Polling with a
 * fixed sleep races on slower CI shards and leaves stale pre-filter cards
 * visible — every helper that asserts on `.image-card.first()` ends up
 * inspecting the wrong asset.
 */
async function searchInDataGrid(page, text, placeholder = 'Search') {
  const searchInput = page.getByPlaceholder(placeholder).first();
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

/**
 * Click the Edit action on a DataGrid row matching the given text.
 */
async function clickEditOnRow(page, rowText) {
  const row = page.locator('#app div').filter({ hasText: rowText }).first();
  await row.locator('span[title="Edit"]').first().click();
  await page.waitForLoadState('domcontentloaded');
}

/**
 * Click the Delete action on a DataGrid row matching the given text.
 */
async function clickDeleteOnRow(page, rowText) {
  const row = page.locator('#app div').filter({ hasText: rowText }).first();
  await row.locator('span[title="Delete"]').first().click();
}

/**
 * Confirm the delete modal.
 */
async function confirmDelete(page) {
  await page.getByRole('button', { name: 'Delete' }).click();
  await page.waitForLoadState('domcontentloaded');
}

/**
 * Assert a success toast message is visible.
 */
async function expectSuccessToast(page, pattern, timeout = 20000) {
  const { expect } = require('@playwright/test');
  const regex = pattern instanceof RegExp ? pattern : new RegExp(pattern, 'i');
  await expect(page.locator('#app').getByText(regex).first()).toBeVisible({ timeout });
}

/**
 * Click Save and verify success via toast or URL redirect.
 */
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

/**
 * Generate a unique identifier for test data isolation.
 */
function generateUid() {
  const { randomBytes } = require('crypto');
  return Date.now().toString(36) + randomBytes(4).toString('hex');
}

/**
 * Force the DAM directory tree to broadcast `current-directory` before
 * touching the upload widget. The tree fires that emit lazily — on a fresh
 * page, the file input is already in the DOM (canUploadHere=true via
 * aclBypass) but `v-dam-upload.currentDirectory` is still null, so the
 * upload POST goes out with no `directory_id` and the server returns 422.
 * Clicking the root row makes the emit deterministic.
 */
async function primeUploadDirectory(page) {
  const rootRow = page.locator('[data-dir-id]').first();
  await rootRow.waitFor({ state: 'visible', timeout: 30000 });
  await rootRow.click({ force: true });
  await page.waitForTimeout(200);
}

/**
 * Ensure at least one asset exists in the DAM grid. Uploads a seed image if empty.
 * Required because Playwright sharding can run asset-dependent specs in a shard
 * that doesn't include 03-asset-upload.
 */
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

  // Wait for the upload to land — either the success toast OR a card appearing.
  await Promise.race([
    page.locator('#app').getByText(/uploaded successfully/i).first()
      .waitFor({ state: 'visible', timeout: 30000 }),
    page.locator('.image-card').first().waitFor({ state: 'visible', timeout: 30000 }),
  ]).catch(() => {});

  await navigateTo(page, 'dam');
  await page.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });
}

/**
 * Ensure an asset with the given filename exists in DAM. Uploads filePath if missing.
 * Searches by searchName (no extension matching needed, just a substring).
 *
 * Existence is checked by matching the grid heading text, not just by any
 * `.image-card` being visible — a stale pre-filter render leaves an unrelated
 * card on screen and would otherwise skip the upload.
 */
async function ensureAssetOfTypeExists(page, filePath, searchName) {
  await navigateTo(page, 'dam');
  await page.waitForTimeout(500);

  await searchInDataGrid(page, searchName);

  const exists = await page.locator('h2').filter({ hasText: searchName }).first()
    .isVisible({ timeout: 3000 })
    .catch(() => false);

  // Navigate fresh to clear the search filter regardless of result
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

/**
 * Search for an asset by name in the DAM grid and navigate to its edit page.
 *
 * Verifies the grid actually rendered a matching `<h2>` before clicking the
 * first `.image-card`. Without that check, a stale pre-filter render sends
 * callers to whichever asset happens to be first in the grid, not the one
 * they searched for.
 */
async function navigateToAssetEditByName(page, searchName) {
  await navigateTo(page, 'dam');
  await searchInDataGrid(page, searchName);

  await page.locator('h2').filter({ hasText: searchName }).first()
    .waitFor({ state: 'visible', timeout: 15000 });

  // Click the .image-card whose v-for wrapper also contains the matching h2.
  // Picking .image-card.first() blindly catches the topmost card when the
  // search filter hasn't applied yet — sending callers to the wrong asset's
  // edit page.
  const cardWrapper = page.locator('div:has(> .image-card)')
    .filter({ hasText: searchName })
    .first();
  await cardWrapper.waitFor({ state: 'visible', timeout: 15000 });
  const card = cardWrapper.locator('.image-card').first();
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
  navigateToAssetEditByName,
};
