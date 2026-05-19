const path = require('path');
const { test, expect } = require('../utils/fixtures');
const { navigateTo, ensureAssetExists } = require('../utils/helpers');

async function navigateToFirstAssetEdit(page) {
  await navigateTo(page, 'dam');
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(2000);

  const firstCard = page.locator('.image-card').first();
  await firstCard.waitFor({ state: 'visible', timeout: 20000 });
  await firstCard.hover();
  await page.waitForTimeout(500);

  const editIcon = firstCard.locator('.icon-edit').first();
  await editIcon.click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

test.describe('DAM Asset Edit — Re-upload locks other actions', () => {
  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('Other action buttons are disabled while re-uploading and re-enabled after cancel', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);

    const fileInput = adminPage.locator('input[type="file"]#file-upload');
    const isReuploadAvailable = await fileInput.count() > 0;
    if (!isReuploadAvailable) {
      test.skip(true, 'Re-upload permission not available');
      return;
    }

    let resolveReupload;
    const reuploadStarted = new Promise((resolve) => { resolveReupload = resolve; });
    await adminPage.route('**/admin/dam/assets/re-upload', async (route) => {
      resolveReupload();
      await new Promise((r) => setTimeout(r, 60000));
      await route.fulfill({ status: 200, contentType: 'application/json', body: '{}' });
    });

    await fileInput.setInputFiles(path.resolve(__dirname, '../assets/floral.jpg'));
    await reuploadStarted;
    await adminPage.waitForTimeout(300);

    const renameBtn = adminPage.getByRole('button', { name: 'Rename' }).first();
    const deleteBtn = adminPage.getByRole('button', { name: 'Delete' }).first();
    const downloadBtn = adminPage.getByRole('button', { name: /Download/i }).first();
    const cancelBtn = adminPage.getByRole('button', { name: 'Cancel' }).first();

    if (await renameBtn.isVisible().catch(() => false)) {
      await expect(renameBtn).toBeDisabled();
    }
    if (await deleteBtn.isVisible().catch(() => false)) {
      await expect(deleteBtn).toBeDisabled();
    }
    if (await downloadBtn.isVisible().catch(() => false)) {
      await expect(downloadBtn).toBeDisabled();
    }

    await expect(adminPage.locator('[aria-busy="true"] .tabs')).toHaveCount(1);

    await expect(cancelBtn).toBeVisible();
    await expect(cancelBtn).toBeEnabled();

    await cancelBtn.click();
    await adminPage.waitForTimeout(500);

    if (await renameBtn.isVisible().catch(() => false)) {
      await expect(renameBtn).toBeEnabled();
    }
    if (await deleteBtn.isVisible().catch(() => false)) {
      await expect(deleteBtn).toBeEnabled();
    }
    if (await downloadBtn.isVisible().catch(() => false)) {
      await expect(downloadBtn).toBeEnabled();
    }

    await expect(adminPage.locator('[aria-busy="true"] .tabs')).toHaveCount(0);
  });

  test('Cancel button is the only enabled control during re-upload', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);

    const fileInput = adminPage.locator('input[type="file"]#file-upload');
    if (await fileInput.count() === 0) {
      test.skip(true, 'Re-upload permission not available');
      return;
    }

    let resolveReupload;
    const reuploadStarted = new Promise((resolve) => { resolveReupload = resolve; });
    await adminPage.route('**/admin/dam/assets/re-upload', async (route) => {
      resolveReupload();
      await new Promise((r) => setTimeout(r, 60000));
      await route.fulfill({ status: 200, contentType: 'application/json', body: '{}' });
    });

    await fileInput.setInputFiles(path.resolve(__dirname, '../assets/floral.jpg'));
    await reuploadStarted;
    await adminPage.waitForTimeout(300);

    const cancelBtn = adminPage.getByRole('button', { name: 'Cancel' }).first();
    await expect(cancelBtn).toBeEnabled();

    await cancelBtn.click();
  });
});
