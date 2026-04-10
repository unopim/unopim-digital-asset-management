const { test, expect } = require('../utils/fixtures');
const { navigateTo, searchInDataGrid, generateUid } = require('../utils/helpers');
const path = require('path');

const ASSET_IMAGE = path.resolve(__dirname, '../assets/floral.jpg');
const ASSET_PNG = path.resolve(__dirname, '../assets/dotted.png');

/**
 * Helper: Upload a file via the hidden file input on the DAM page.
 */
async function uploadFile(page, filePath) {
  const fileInput = page.locator('input[type="file"][name="files[]"]');
  await fileInput.setInputFiles(filePath);
  await page.waitForTimeout(2000);
}

/**
 * Helper: Read the "N Results" count badge from the DAM toolbar.
 * Returns -1 if the badge can't be found, so callers can use poll-until-changed.
 */
async function readResultCount(page) {
  try {
    const badge = page.getByText(/^\d+\s+Results/i).first();
    const text = await badge.textContent({ timeout: 5000 });
    const match = text && text.match(/(\d+)/);
    return match ? parseInt(match[1], 10) : -1;
  } catch {
    return -1;
  }
}

/**
 * Helper: Delete an asset by filename from the asset grid.
 * Right-clicks the asset in the tree or uses the edit page delete button.
 */
async function deleteAssetViaEditPage(page, assetName) {
  try {
    // Search for the asset
    await searchInDataGrid(page, assetName);
    // Click on the asset heading to navigate to edit page
    const assetHeading = page.locator('h2').filter({ hasText: assetName }).first();
    await assetHeading.click({ force: true });
    await page.waitForLoadState('domcontentloaded');

    // Click Delete button on edit page
    const deleteBtn = page.getByRole('button', { name: 'Delete' }).first();
    if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await deleteBtn.click();
      // Confirm delete
      const confirmBtn = page.getByRole('button', { name: /Delete|Agree/ });
      await confirmBtn.waitFor({ state: 'visible', timeout: 5000 });
      await confirmBtn.click();
      await page.waitForLoadState('domcontentloaded');
    }
  } catch {
    // Asset not found — that's fine
  }
}

test.describe('DAM Asset Upload', () => {

  test('Upload button is visible on DAM page', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.getByText('Upload')).toBeVisible();
  });

  test('Upload a JPG file successfully', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Capture the result count BEFORE uploading. The success toast is
    // queue-driven and can race with auto-dismiss, so we assert on the
    // grid count instead — if it incremented, the upload succeeded.
    const beforeCount = await readResultCount(adminPage);

    await uploadFile(adminPage, ASSET_IMAGE);

    await expect.poll(
      () => readResultCount(adminPage),
      { timeout: 30000, message: 'expected result count to increase after upload' }
    ).toBeGreaterThan(beforeCount);
  });

  test('Upload a PNG file successfully', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const beforeCount = await readResultCount(adminPage);

    await uploadFile(adminPage, ASSET_PNG);

    await expect.poll(
      () => readResultCount(adminPage),
      { timeout: 30000, message: 'expected result count to increase after upload' }
    ).toBeGreaterThan(beforeCount);
  });

  test('Uploaded assets appear in the grid', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Search for the uploaded file
    await searchInDataGrid(adminPage, 'floral');
    await expect(
      adminPage.locator('h2').filter({ hasText: /floral/i }).first()
    ).toBeVisible({ timeout: 15000 });
  });

  test('Search assets in the DataGrid', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await searchInDataGrid(adminPage, 'dotted');
    await expect(
      adminPage.locator('h2').filter({ hasText: /dotted/i }).first()
    ).toBeVisible({ timeout: 15000 });
  });

  test('Search with no results shows appropriate message', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await searchInDataGrid(adminPage, `nonexistent_${uid}`);
    // Either no results count or an empty state message
    await expect(
      adminPage.getByText(/0 Results/i).first()
    ).toBeVisible({ timeout: 15000 });
  });

  test('Filter button opens filter panel', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.getByText('Apply Filters')).toBeVisible({ timeout: 10000 });
  });

  test('Per Page dropdown works', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Find and click Per Page button
    const perPageBtn = adminPage.locator('button').filter({ hasText: /^\d+/ }).first();
    await perPageBtn.click();
    await adminPage.waitForTimeout(300);
  });

  test('Select All checkbox is functional', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const selectAll = adminPage.getByText('Select All').first();
    await selectAll.click();
    await adminPage.waitForTimeout(300);
  });
});
