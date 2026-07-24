const { test, expect } = require('../utils/fixtures');
const {
  navigateTo,
  ensureAssetExists,
  ensureAssetOfTypeExists,
  searchInDataGrid,
  primeUploadDirectory,
  closeApShell,
} = require('../utils/helpers');
const path = require('path');

const PDF_ASSET   = path.resolve(__dirname, '../assets/sample.pdf');
const VIDEO_ASSET = path.resolve(__dirname, '../assets/sample.mp4');

async function navigateToExplorer(page) {
  await navigateTo(page, 'dam');

  const tabBar = page.locator('.flex.items-end.gap-0').first();
  return tabBar.isVisible({ timeout: 5000 }).catch(() => false);
}

test.describe('DAM Asset Card — Gallery view', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('extension badge on card shows red class for PDF asset', async ({ adminPage }) => {
    await ensureAssetOfTypeExists(adminPage, PDF_ASSET, 'sample.pdf');
    await navigateTo(adminPage, 'dam');
    await searchInDataGrid(adminPage, 'sample.pdf');

    const badge = adminPage
      .locator('.image-card span.bg-red-600')
      .first();
    await expect(badge).toBeVisible({ timeout: 15000 });
    await expect(badge).toHaveText(/PDF/i);
  });

  test('extension badge on card shows violet class for video asset', async ({ adminPage }) => {
    await ensureAssetOfTypeExists(adminPage, VIDEO_ASSET, 'sample.mp4');
    await navigateTo(adminPage, 'dam');
    await searchInDataGrid(adminPage, 'sample.mp4');

    const badge = adminPage
      .locator('.image-card span.bg-violet-600')
      .first();
    await expect(badge).toBeVisible({ timeout: 15000 });
    await expect(badge).toHaveText(/MP4/i);
  });

  test('play overlay icon is visible on card for video asset', async ({ adminPage }) => {
    await ensureAssetOfTypeExists(adminPage, VIDEO_ASSET, 'sample.mp4');
    await navigateTo(adminPage, 'dam');
    await searchInDataGrid(adminPage, 'sample.mp4');

    const playIcon = adminPage
      .locator('.image-card .icon-play')
      .first();
    await expect(playIcon).toBeVisible({ timeout: 15000 });
  });

  test('audio icon is visible on card for audio asset', async ({ adminPage }) => {
    await ensureAssetOfTypeExists(
      adminPage,
      path.resolve(__dirname, '../assets/sample.mp3'),
      'sample.mp3'
    );
    await navigateTo(adminPage, 'dam');
    await searchInDataGrid(adminPage, 'sample.mp3');

    const cardVisible = await adminPage
      .locator('.image-card')
      .first()
      .isVisible({ timeout: 10000 })
      .catch(() => false);
    if (!cardVisible) {
      test.skip(true, 'Audio asset card not visible — upload may be unsupported in this environment');
      return;
    }

    const audioIcon = adminPage
      .locator('.image-card .icon-information')
      .first();
    await expect(audioIcon).toBeVisible({ timeout: 10000 });
  });

  test('empty state renders when selected directory has no assets', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');

    await searchInDataGrid(adminPage, 'zzzzzz_no_match_ever_xyzxyz');

    const emptyImg = adminPage
      .locator('img[src*="no-records-found"]')
      .first();
    await expect(emptyImg).toBeVisible({ timeout: 15000 });
  });

  test('gallery card does NOT have draggable attribute', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');

    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });

    const card = adminPage.locator('.image-card').first();

    const draggable = await card.getAttribute('draggable');
    expect(draggable).not.toBe('true');
  });

});

test.describe('DAM Asset Card — Explorer view', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('explorer grid card has draggable="true" attribute', async ({ adminPage }) => {
    const explorerActive = await navigateToExplorer(adminPage);
    if (!explorerActive) {
      test.skip(true, 'Explorer mode not enabled (DAM_EXPLORER_ENABLED=false)');
      return;
    }

    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });

    const card = adminPage.locator('.image-card').first();
    await expect(card).toHaveAttribute('draggable', 'true');
  });

  test('three-dot button on asset card in explorer opens actions menu', async ({ adminPage }) => {
    const explorerActive = await navigateToExplorer(adminPage);
    if (!explorerActive) {
      test.skip(true, 'Explorer mode not enabled (DAM_EXPLORER_ENABLED=false)');
      return;
    }

    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });
    await closeApShell(adminPage);

    const assetCard = adminPage.locator('.image-card').first().locator('..');
    await assetCard.hover();
    await assetCard.locator('.dam-ctx-trigger').first().click();

    const ctxMenu = adminPage.locator('div.fixed.min-w-\\[185px\\]').first();
    await expect(ctxMenu).toBeVisible({ timeout: 5000 });

    await adminPage.mouse.click(10, 10);
  });

  test('three-dot button on directory in explorer grid opens actions menu', async ({ adminPage }) => {
    const explorerActive = await navigateToExplorer(adminPage);
    if (!explorerActive) {
      test.skip(true, 'Explorer mode not enabled (DAM_EXPLORER_ENABLED=false)');
      return;
    }

    const folderIcon = adminPage.locator('.icon-dam-folder').first();
    const hasFolders = await folderIcon.isVisible({ timeout: 5000 }).catch(() => false);
    if (!hasFolders) {
      test.skip(true, 'No sub-directories visible in explorer');
      return;
    }

    const folderCard = folderIcon.locator('..').first();
    await folderCard.hover();
    await folderCard.locator('.dam-ctx-trigger').first().click();

    const ctxMenu = adminPage.locator('div.fixed.min-w-\\[185px\\]').first();
    await expect(ctxMenu).toBeVisible({ timeout: 5000 });

    await adminPage.mouse.click(10, 10);
  });

  test('actions menu stays within the viewport bottom', async ({ adminPage }) => {
    const explorerActive = await navigateToExplorer(adminPage);
    if (!explorerActive) {
      test.skip(true, 'Explorer mode not enabled (DAM_EXPLORER_ENABLED=false)');
      return;
    }

    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });
    await closeApShell(adminPage);

    const { height } = adminPage.viewportSize();
    const assetCard = adminPage.locator('.image-card').last().locator('..');
    await assetCard.scrollIntoViewIfNeeded();
    await assetCard.hover();
    await assetCard.locator('.dam-ctx-trigger').first().click();

    const ctxMenu = adminPage.locator('div.fixed.min-w-\\[185px\\]').first();
    await expect(ctxMenu).toBeVisible({ timeout: 5000 });

    const box = await ctxMenu.boundingBox();
    expect(box.y + box.height).toBeLessThanOrEqual(height + 2);

    await adminPage.mouse.click(10, 10);
  });

});
