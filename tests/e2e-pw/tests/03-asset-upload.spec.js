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
    await page.waitForLoadState('networkidle');

    // Click Delete button on edit page
    const deleteBtn = page.getByRole('button', { name: 'Delete' }).first();
    if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await deleteBtn.click();
      // Confirm delete
      const confirmBtn = page.getByRole('button', { name: /Delete|Agree/ });
      await confirmBtn.waitFor({ state: 'visible', timeout: 5000 });
      await confirmBtn.click();
      await page.waitForLoadState('networkidle');
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
    await adminPage.waitForLoadState('networkidle');

    await uploadFile(adminPage, ASSET_IMAGE);

    // Verify success toast
    await expect(
      adminPage.getByText(/uploaded successfully/i).first()
    ).toBeVisible({ timeout: 20000 });
  });

  test('Upload a PNG file successfully', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('networkidle');

    await uploadFile(adminPage, ASSET_PNG);

    // Verify success toast
    await expect(
      adminPage.getByText(/uploaded successfully/i).first()
    ).toBeVisible({ timeout: 20000 });
  });

  test('Uploaded assets appear in the grid', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('networkidle');

    // Search for the uploaded file
    await searchInDataGrid(adminPage, 'floral');
    await expect(
      adminPage.locator('h2').filter({ hasText: /floral/i }).first()
    ).toBeVisible({ timeout: 15000 });
  });

  test('Search assets in the DataGrid', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('networkidle');

    await searchInDataGrid(adminPage, 'dotted');
    await expect(
      adminPage.locator('h2').filter({ hasText: /dotted/i }).first()
    ).toBeVisible({ timeout: 15000 });
  });

  test('Search with no results shows appropriate message', async ({ adminPage }) => {
    const uid = generateUid();
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('networkidle');

    await searchInDataGrid(adminPage, `nonexistent_${uid}`);
    // Either no results count or an empty state message
    await expect(
      adminPage.getByText(/0 Results/i).first()
    ).toBeVisible({ timeout: 15000 });
  });

  test('Filter button opens filter panel', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('networkidle');

    await adminPage.getByText('Filter', { exact: true }).click();
    await expect(adminPage.getByText('Apply Filters')).toBeVisible({ timeout: 10000 });
  });

  test('Per Page dropdown works', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('networkidle');

    // Find and click Per Page button
    const perPageBtn = adminPage.locator('button').filter({ hasText: /^\d+/ }).first();
    await perPageBtn.click();
    await adminPage.waitForTimeout(300);
  });

  test('Select All checkbox is functional', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('networkidle');

    const selectAll = adminPage.getByText('Select All').first();
    await selectAll.click();
    await adminPage.waitForTimeout(300);
  });
});
