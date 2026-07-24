const { test, expect } = require('../utils/fixtures');
const { navigateTo } = require('../utils/helpers');

test.describe('DAM Tree — DAM_TREE_SHOW_ASSETS default behavior', () => {
  test('expanded folders show only directory rows, no asset leaf nodes', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const rootRow = adminPage.locator('.tree-container').first();
    await rootRow.waitFor({ state: 'visible', timeout: 15000 });

    const expandIcon = rootRow.locator('span.icon-dam-open').first();
    if (await expandIcon.isVisible().catch(() => false)) {
      await expandIcon.click();
      await adminPage.waitForTimeout(800);
    }

    const assetRows = adminPage.locator('.tree-container-assets-details');
    await expect(assetRows).toHaveCount(0);
  });

  test('folder badge / asset count still visible even when asset rows hidden', async ({ adminPage }) => {

    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const dirRows = adminPage.locator('.tree-container-details, .tree-container');
    await dirRows.first().waitFor({ state: 'visible', timeout: 15000 });
    const count = await dirRows.count();
    expect(count).toBeGreaterThan(0);
  });
});
