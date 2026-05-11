const { test, expect } = require('../utils/fixtures');
const { navigateTo, searchInDataGrid } = require('../utils/helpers');
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

  test('Upload a JPG file successfully', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await uploadFile(adminPage, ASSET_IMAGE);

    // Assert by filename — robust when the file already exists and gets
    // overwritten (count stays the same but the asset is still there).
    await expect(
      adminPage.locator('h2').filter({ hasText: /floral/i }).first()
    ).toBeVisible({ timeout: 30000 });
  });

  test('Upload a PNG file successfully', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await uploadFile(adminPage, ASSET_PNG);

    await expect(
      adminPage.locator('h2').filter({ hasText: /dotted/i }).first()
    ).toBeVisible({ timeout: 30000 });
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

});
