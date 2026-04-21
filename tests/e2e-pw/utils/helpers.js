const ROUTES = {
  dam:             '/admin/dam',
  damAssets:       '/admin/dam/assets',
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
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
  // Sentinel: the toolbar's Search input only mounts after the Vue grid component
  // is interactive. Far cheaper than networkidle and doesn't deadlock.
  await page.getByPlaceholder('Search').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});
}

/**
 * Search in a DataGrid using the search input.
 */
async function searchInDataGrid(page, text, placeholder = 'Search') {
  const searchInput = page.getByPlaceholder(placeholder).first();
  await searchInput.waitFor({ state: 'visible', timeout: 30000 });
  await searchInput.fill(text);
  await page.keyboard.press('Enter');
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(500);
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

module.exports = {
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
};
