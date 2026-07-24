const { test, expect } = require('../utils/fixtures');
const { navigateTo, searchInDataGrid, primeUploadDirectory } = require('../utils/helpers');
const path = require('path');

const ASSET_IMAGE = path.resolve(__dirname, '../assets/floral.jpg');
const ASSET_PNG = path.resolve(__dirname, '../assets/dotted.png');

async function uploadFile(page, filePath) {
  await primeUploadDirectory(page);

  const uploadResponse = page.waitForResponse(
    (res) => /\/admin\/dam\/assets\/upload$/.test(res.url())
      && res.request().method() === 'POST',
    { timeout: 60000 }
  ).catch(() => {});

  const fileInput = page.locator('input[type="file"][name="files[]"]');
  await fileInput.setInputFiles(filePath);
  await uploadResponse;

  await page.waitForResponse(
    (res) => /\/admin\/dam\/assets(\?|$)/.test(res.url())
      && res.request().method() === 'GET',
    { timeout: 30000 }
  ).catch(() => {});

  await page.waitForTimeout(300);
}

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

async function deleteAssetViaEditPage(page, assetName) {
  try {

    await searchInDataGrid(page, assetName);

    const assetHeading = page.locator('h2').filter({ hasText: assetName }).first();
    await assetHeading.click({ force: true });
    await page.waitForLoadState('domcontentloaded');

    const deleteBtn = page.getByRole('button', { name: 'Delete' }).first();
    if (await deleteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await deleteBtn.click();

      const confirmBtn = page.getByRole('button', { name: /Delete|Agree/ });
      await confirmBtn.waitFor({ state: 'visible', timeout: 5000 });
      await confirmBtn.click();
      await page.waitForLoadState('domcontentloaded');
    }
  } catch {

  }
}

test.describe('DAM Asset Upload', () => {

  test('Upload a JPG file successfully', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await uploadFile(adminPage, ASSET_IMAGE);

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

    await searchInDataGrid(adminPage, 'floral');
    await expect(
      adminPage.locator('h2').filter({ hasText: /floral/i }).first()
    ).toBeVisible({ timeout: 15000 });
  });

});
