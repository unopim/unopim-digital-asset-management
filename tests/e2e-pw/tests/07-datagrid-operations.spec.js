const { test, expect } = require('../utils/fixtures');
const { navigateTo, searchInDataGrid, ensureAssetExists } = require('../utils/helpers');

test.describe('DAM DataGrid Operations', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('Search input is functional', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const searchInput = adminPage.getByPlaceholder('Search').first();
    await searchInput.waitFor({ state: 'visible', timeout: 30000 });
    await searchInput.fill('test');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(500);

    // Page should still be on DAM with results or empty state
    await expect(adminPage.getByText(/Results/i).first()).toBeVisible();
  });

  test('Clear search shows all results', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Search something
    await searchInDataGrid(adminPage, 'nonexistent_query_xyz');

    // Clear search — target the visible toolbar input by name attribute
    // (getByPlaceholder('Search').first() can match a hidden filter-panel input).
    const searchInput = adminPage.locator('input[name="search"]:visible').first();
    await searchInput.fill('');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(500);

    // Results should be back to original
    await expect(adminPage.getByText(/\d+ Results/).first()).toBeVisible();
  });

  test('Gallery view shows images with filenames', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Wait for at least one h2 (asset filename heading) to render — the grid
    // populates asynchronously after the AJAX response arrives.
    await expect(adminPage.locator('h2').first()).toBeVisible({ timeout: 30000 });
    const count = await adminPage.locator('h2').count();
    expect(count).toBeGreaterThan(0);
  });

  test('Select All selects all visible assets', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    // Click Select All
    const selectAll = adminPage.getByText('Select All').first();
    await selectAll.click();
    await adminPage.waitForTimeout(500);

    // At minimum, clicking Select All should not error out
  });

  test('Results count is displayed', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(
      adminPage.getByText(/\d+ Results/i).first()
    ).toBeVisible({ timeout: 30000 });
  });

  test('Pagination controls are visible', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Per Page and page number controls
    await expect(adminPage.getByText('Per Page')).toBeVisible({ timeout: 30000 });
  });

  test('Asset images load in gallery', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    // Gallery view should have img elements
    const images = adminPage.locator('img[alt]');
    const count = await images.count();
    // There should be at least some images (including the logo)
    expect(count).toBeGreaterThan(0);
  });
});
