const path = require('path');
const { test, expect } = require('../utils/fixtures');
const { navigateTo, ensureAssetExists } = require('../utils/helpers');

const ASSET_IMAGE = path.resolve(__dirname, '../assets/floral.jpg');

async function startStubbedUpload(page) {
  let resolveStarted;
  const uploadStarted = new Promise((resolve) => { resolveStarted = resolve; });

  await page.route('**/admin/dam/assets/upload', async (route) => {
    resolveStarted();
    await new Promise((r) => setTimeout(r, 60000));
    await route.fulfill({ status: 200, contentType: 'application/json', body: '{}' });
  });

  const fileInput = page.locator('input[type="file"][name="files[]"]').first();
  await fileInput.setInputFiles(ASSET_IMAGE);
  await uploadStarted;
  await page.waitForTimeout(300);
}

test.describe('DAM Asset Upload — Grid lock during upload', () => {
  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1500);
  });

  test('Grid wrapper locks (pointer-events-none + aria-busy) while uploading and releases after cancel', async ({ adminPage }) => {
    const gridWrapper = adminPage.locator('div[aria-busy]').filter({ has: adminPage.locator('.image-card') }).first();

    await expect(gridWrapper).toHaveAttribute('aria-busy', 'false');

    await startStubbedUpload(adminPage);

    await expect(gridWrapper).toHaveAttribute('aria-busy', 'true');
    await expect(gridWrapper).toHaveClass(/pointer-events-none/);
    await expect(gridWrapper).toHaveClass(/opacity-60/);

    const cancelBtn = adminPage.getByRole('button', { name: 'Cancel' }).first();
    await expect(cancelBtn).toBeVisible();
    await expect(cancelBtn).toBeEnabled();
    await cancelBtn.click();
    await adminPage.waitForTimeout(500);

    await expect(gridWrapper).toHaveAttribute('aria-busy', 'false');
    await expect(gridWrapper).not.toHaveClass(/pointer-events-none/);
  });

  test('Clicking an asset row during upload does not navigate to edit page', async ({ adminPage }) => {
    const indexUrl = adminPage.url();

    await startStubbedUpload(adminPage);

    const firstCard = adminPage.locator('.image-card').first();
    await firstCard.waitFor({ state: 'visible', timeout: 10000 });

    await firstCard.click({ force: true }).catch(() => {});
    await adminPage.waitForTimeout(800);

    expect(adminPage.url()).toBe(indexUrl);
    expect(adminPage.url()).not.toMatch(/admin\/dam\/assets\/edit\/\d+/);

    const cancelBtn = adminPage.getByRole('button', { name: 'Cancel' }).first();
    await cancelBtn.click();
    await adminPage.waitForTimeout(500);
  });

  test('Upload and Cancel buttons remain interactive while grid is locked', async ({ adminPage }) => {
    await startStubbedUpload(adminPage);

    const cancelBtn = adminPage.getByRole('button', { name: 'Cancel' }).first();
    await expect(cancelBtn).toBeVisible();
    await expect(cancelBtn).toBeEnabled();

    const uploadLabel = adminPage.locator('label[for="file-upload"]').first();
    await expect(uploadLabel).toBeVisible();

    await cancelBtn.click();
    await adminPage.waitForTimeout(500);
  });

  test('After upload completes, grid becomes interactive again and row click opens edit page', async ({ adminPage }) => {
    const gridWrapper = adminPage.locator('div[aria-busy]').filter({ has: adminPage.locator('.image-card') }).first();

    await startStubbedUpload(adminPage);
    await expect(gridWrapper).toHaveAttribute('aria-busy', 'true');

    const cancelBtn = adminPage.getByRole('button', { name: 'Cancel' }).first();
    await cancelBtn.click();
    await adminPage.waitForTimeout(800);

    await expect(gridWrapper).toHaveAttribute('aria-busy', 'false');
    await expect(gridWrapper).not.toHaveClass(/pointer-events-none/);

    await adminPage.unroute('**/admin/dam/assets/upload');

    const firstCard = adminPage.locator('.image-card').first();
    await firstCard.hover();
    await adminPage.waitForTimeout(300);
    const editIcon = firstCard.locator('.icon-edit').first();
    await editIcon.click({ force: true });
    await adminPage.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  });
});
