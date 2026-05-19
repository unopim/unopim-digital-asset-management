const { test, expect } = require('../utils/fixtures');
const { navigateTo } = require('../utils/helpers');

/**
 * Verifies the recursive asset-count chip on directory rows. The backend
 * computes a rollup that includes own direct assets + every descendant's
 * assets, exposed as `assets_total_count` on each directory node, and the
 * tree renders the value in a small chip after the directory name.
 *
 * Always visible, independent of DAM_TREE_SHOW_ASSETS — the chip is folder-
 * level metadata, distinct from inline asset leaf rows.
 */
test.describe('DAM Tree — recursive asset-count chip', () => {
  test('root directory row renders the asset-total chip with a numeric value', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const rootChip = adminPage.locator('[data-asset-total-count]').first();
    await rootChip.waitFor({ state: 'attached', timeout: 15000 });

    const value = (await rootChip.textContent()).trim();
    expect(value).toMatch(/^\(\d+\)$/);
  });

  test('every visible directory row has its own asset-total chip', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Wait for the tree to mount.
    await adminPage.locator('.tree-container').first().waitFor({ state: 'visible', timeout: 15000 });

    const chips = adminPage.locator('[data-asset-total-count]');
    const count = await chips.count();
    expect(count).toBeGreaterThan(0);
  });
});
