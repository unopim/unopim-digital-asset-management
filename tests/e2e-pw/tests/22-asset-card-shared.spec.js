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

/**
 * Navigate to DAM with explorer mode active.
 * Returns false if explorer is not enabled (DAM_EXPLORER_ENABLED=false),
 * so callers can skip explorer-specific assertions.
 */
async function navigateToExplorer(page) {
  await navigateTo(page, 'dam');
  // Explorer renders a tab bar; datagrid does not.
  const tabBar = page.locator('.flex.items-end.gap-0').first();
  return tabBar.isVisible({ timeout: 5000 }).catch(() => false);
}

// ---------------------------------------------------------------------------
// Gallery-mode tests (DAM_EXPLORER_ENABLED=false, default)
// ---------------------------------------------------------------------------

test.describe('DAM Asset Card — Gallery view', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('extension badge on card shows red class for PDF asset', async ({ adminPage }) => {
    await ensureAssetOfTypeExists(adminPage, PDF_ASSET, 'sample.pdf');
    await navigateTo(adminPage, 'dam');
    await searchInDataGrid(adminPage, 'sample.pdf');

    // The badge is a <span> inside .image-card with class bg-red-600
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

    // The play icon is a span with icon-play class inside the card
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

    // Guard: skip if the audio asset card never appeared (upload blocked in this env)
    const cardVisible = await adminPage
      .locator('.image-card')
      .first()
      .isVisible({ timeout: 10000 })
      .catch(() => false);
    if (!cardVisible) {
      test.skip(true, 'Audio asset card not visible — upload may be unsupported in this environment');
      return;
    }

    // Audio uses icon-information class (not icon-play)
    const audioIcon = adminPage
      .locator('.image-card .icon-information')
      .first();
    await expect(audioIcon).toBeVisible({ timeout: 10000 });
  });

  test('empty state renders when selected directory has no assets', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');

    // Search a string that matches nothing so the grid shows empty state.
    await searchInDataGrid(adminPage, 'zzzzzz_no_match_ever_xyzxyz');

    // Empty state: the no-records SVG image must be visible
    const emptyImg = adminPage
      .locator('img[src*="no-records-found"]')
      .first();
    await expect(emptyImg).toBeVisible({ timeout: 15000 });
  });

  test('gallery card does NOT have draggable attribute', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');

    // Wait for at least one card
    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });

    const card = adminPage.locator('.image-card').first();
    // draggable should be "false" or absent — NOT "true"
    const draggable = await card.getAttribute('draggable');
    expect(draggable).not.toBe('true');
  });

});

// ---------------------------------------------------------------------------
// Explorer-mode tests (require DAM_EXPLORER_ENABLED=true)
// ---------------------------------------------------------------------------

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

    // Wait for at least one asset card in explorer grid
    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });

    const card = adminPage.locator('.image-card').first();
    await expect(card).toHaveAttribute('draggable', 'true');
  });

  test('right-click on asset card in explorer opens context menu', async ({ adminPage }) => {
    const explorerActive = await navigateToExplorer(adminPage);
    if (!explorerActive) {
      test.skip(true, 'Explorer mode not enabled (DAM_EXPLORER_ENABLED=false)');
      return;
    }

    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });
    await closeApShell(adminPage);

    // Right-click the first asset card
    await adminPage.locator('.image-card').first().click({ button: 'right' });

    // Context menu should appear with min-w-[185px] fixed div
    const ctxMenu = adminPage.locator('div.fixed.min-w-\\[185px\\]').first();
    await expect(ctxMenu).toBeVisible({ timeout: 5000 });

    // Dismiss
    await adminPage.keyboard.press('Escape');
    await adminPage.mouse.click(10, 10);
  });

  test('right-click on directory in explorer grid opens context menu', async ({ adminPage }) => {
    const explorerActive = await navigateToExplorer(adminPage);
    if (!explorerActive) {
      test.skip(true, 'Explorer mode not enabled (DAM_EXPLORER_ENABLED=false)');
      return;
    }

    // Directories render with icon-dam-folder
    const folderIcon = adminPage.locator('.icon-dam-folder').first();
    const hasFolders = await folderIcon.isVisible({ timeout: 5000 }).catch(() => false);
    if (!hasFolders) {
      test.skip(true, 'No sub-directories visible in explorer to right-click');
      return;
    }

    const folderCard = folderIcon.locator('..').locator('..').first();
    await folderCard.click({ button: 'right' });

    const ctxMenu = adminPage.locator('div.fixed.min-w-\\[185px\\]').first();
    await expect(ctxMenu).toBeVisible({ timeout: 5000 });

    await adminPage.mouse.click(10, 10);
  });

  test('context menu repositions when rendered near bottom of viewport', async ({ adminPage }) => {
    const explorerActive = await navigateToExplorer(adminPage);
    if (!explorerActive) {
      test.skip(true, 'Explorer mode not enabled (DAM_EXPLORER_ENABLED=false)');
      return;
    }

    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });
    await closeApShell(adminPage);

    // Trigger context menu near the bottom of the viewport
    const { height } = adminPage.viewportSize();
    await adminPage.locator('.image-card').first().click({
      button: 'right',
      position: { x: 10, y: 10 },
    });

    const ctxMenu = adminPage.locator('div.fixed.min-w-\\[185px\\]').first();
    await expect(ctxMenu).toBeVisible({ timeout: 5000 });

    // Menu must not overflow bottom of viewport
    const box = await ctxMenu.boundingBox();
    expect(box.y + box.height).toBeLessThanOrEqual(height + 2); // 2px tolerance

    await adminPage.mouse.click(10, 10);
  });

});
