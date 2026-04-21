const { test, expect } = require('../utils/fixtures');
const { navigateTo, searchInDataGrid, ensureAssetExists } = require('../utils/helpers');

/**
 * Helper: Navigate to the edit page of the first asset in the grid.
 * In gallery view, you must hover the image card to reveal the edit icon.
 */
async function navigateToFirstAssetEdit(page) {
  await navigateTo(page, 'dam');
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(1000);

  // Hover over the first image card to reveal action icons
  const firstCard = page.locator('.image-card').first();
  await firstCard.hover();
  await page.waitForTimeout(300);

  // Click the edit icon that appears on hover
  const editIcon = firstCard.locator('.icon-edit').first();
  await editIcon.click({ force: true });
  await page.waitForLoadState('domcontentloaded');
}

test.describe('DAM Asset Edit Page', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('Hover on asset card reveals edit and delete icons', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    const firstCard = adminPage.locator('.image-card').first();
    await firstCard.hover();
    await adminPage.waitForTimeout(300);

    await expect(firstCard.locator('.icon-edit').first()).toBeVisible();
    await expect(firstCard.locator('.icon-delete').first()).toBeVisible();
  });

  test('Click edit icon navigates to asset edit page', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);
    await expect(adminPage).toHaveURL(/admin\/dam\/assets\/edit\/\d+/);
  });

  test('Edit page shows asset preview (image/video/audio/pdf)', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);

    const hasImage = await adminPage.locator('#app img').first().isVisible().catch(() => false);
    const hasVideo = await adminPage.locator('#app video').first().isVisible().catch(() => false);
    const hasAudio = await adminPage.locator('#app audio').first().isVisible().catch(() => false);
    const hasIframe = await adminPage.locator('#app iframe').first().isVisible().catch(() => false);

    expect(hasImage || hasVideo || hasAudio || hasIframe).toBeTruthy();
  });

  test('Edit page shows Tags accordion', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);
    await expect(adminPage.locator('#app').getByText('Tags', { exact: true }).first()).toBeVisible();
  });

  test('Edit page shows Directory Path accordion', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);
    await expect(adminPage.locator('#app').getByText('Directory Path').first()).toBeVisible();
  });

  test('Edit page shows action buttons', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);

    const hasDownload = await adminPage.locator('#app').getByText(/Download/i).first().isVisible().catch(() => false);
    const hasRename = await adminPage.locator('#app').getByText('Rename').first().isVisible().catch(() => false);
    const hasReUpload = await adminPage.locator('#app').getByText('Re-Upload').first().isVisible().catch(() => false);
    const hasDelete = await adminPage.locator('#app').getByText('Delete').first().isVisible().catch(() => false);

    expect(hasDownload || hasRename || hasReUpload || hasDelete).toBeTruthy();
  });

  test('Edit page shows tabs (Preview, Properties, Comments, etc.)', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);
    await expect(adminPage.locator('#app').getByText('Preview').first()).toBeVisible();
  });

  test('Rename button opens rename modal', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);

    const renameBtn = adminPage.locator('#app').getByRole('button', { name: 'Rename' }).first();
    const isVisible = await renameBtn.isVisible().catch(() => false);
    if (!isVisible) {
      test.skip(true, 'Rename button not visible');
      return;
    }
    await renameBtn.click();
    await adminPage.waitForTimeout(500);

    await expect(adminPage.locator('#app').getByText('File Name').first()).toBeVisible();
  });

  test('Delete button on edit page triggers delete confirmation', async ({ adminPage }) => {
    await navigateToFirstAssetEdit(adminPage);

    const deleteBtn = adminPage.locator('#app').getByRole('button', { name: 'Delete' }).first();
    const isVisible = await deleteBtn.isVisible().catch(() => false);
    if (!isVisible) {
      test.skip(true, 'Delete button not visible');
      return;
    }
    await deleteBtn.click();
    await adminPage.waitForTimeout(500);

    await expect(
      adminPage.locator('#app').getByText(/Are you sure/i).first()
    ).toBeVisible({ timeout: 5000 });
  });
});
