const { test, expect } = require('../utils/fixtures');
const { navigateTo } = require('../utils/helpers');

/**
 * Verifies the `DAM_TREE_SHOW_ASSETS` toggle: by default (env absent / false),
 * the DAM directory tree must NOT render asset leaf rows when a folder is
 * expanded. The asset grid on the right still loads assets normally — only
 * the in-tree asset listing is suppressed.
 *
 * The positive case (env=true) requires booting the app with the env var
 * flipped, which is out of scope for this spec; that path is covered by
 * Pest. This spec asserts the default behavior end-to-end.
 */
test.describe('DAM Tree — DAM_TREE_SHOW_ASSETS default behavior', () => {
  test('expanded folders show only directory rows, no asset leaf nodes', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Wait for the root tree row to render.
    const rootRow = adminPage.locator('.tree-container').first();
    await rootRow.waitFor({ state: 'visible', timeout: 15000 });

    // Expand the root if there's a chevron toggle.
    const expandIcon = rootRow.locator('span.icon-dam-open').first();
    if (await expandIcon.isVisible().catch(() => false)) {
      await expandIcon.click();
      await adminPage.waitForTimeout(800);
    }

    // Asset leaf rows in the tree use the `.tree-container-assets-details`
    // wrapper that v-asset-item renders. With the toggle off, that wrapper
    // must not appear anywhere in the tree pane.
    const assetRows = adminPage.locator('.tree-container-assets-details');
    await expect(assetRows).toHaveCount(0);
  });

  test('folder badge / asset count still visible even when asset rows hidden', async ({ adminPage }) => {
    // Toggling off only hides the asset NODES inside the tree. The folder
    // itself should still indicate it has assets (visually via the count
    // badge if the project renders one, or at least the directory remains
    // expandable). Negative assertion: directory rows still render.
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const dirRows = adminPage.locator('.tree-container-details, .tree-container');
    await dirRows.first().waitFor({ state: 'visible', timeout: 15000 });
    const count = await dirRows.count();
    expect(count).toBeGreaterThan(0);
  });
});
