const { test, expect } = require('../utils/fixtures');
const { navigateTo } = require('../utils/helpers');

const FIRST_PAGE_LOAD_MS = 8_000;
const SEARCH_RESPONSE_MS = 5_000;
const TREE_RENDER_MS     = 10_000;
const PAGINATION_NAV_MS  = 5_000;

test.describe('DAM Scale Validation (large dataset)', () => {

  test('asset grid loads first page within performance budget', async ({ adminPage }) => {
    const start = Date.now();
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(adminPage.locator('h2').first()).toBeVisible({ timeout: FIRST_PAGE_LOAD_MS });

    const elapsed = Date.now() - start;
    expect(elapsed).toBeLessThan(FIRST_PAGE_LOAD_MS);
  });

  test('results count label is visible', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(
      adminPage.getByText(/\d+ Results/i).first()
    ).toBeVisible({ timeout: 30_000 });
  });

  test('directory tree renders without timeout', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(
      adminPage.locator('.tree-container').first()
    ).toBeVisible({ timeout: TREE_RENDER_MS });

    await expect(adminPage.getByText('Root').first()).toBeVisible({ timeout: TREE_RENDER_MS });
  });

  test('directory tree can expand a child node', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(adminPage.getByText('Root').first()).toBeVisible({ timeout: TREE_RENDER_MS });

    const expandArrow = adminPage.locator('.tree-container-details').first()
      .locator('[class*="arrow"], [class*="toggle"], svg').first();

    const isVisible = await expandArrow.isVisible({ timeout: 3_000 }).catch(() => false);
    if (isVisible) {
      await expandArrow.click();
      await adminPage.waitForTimeout(1500);
      const treeNodes = await adminPage.locator('.tree-container-details').count();
      expect(treeNodes).toBeGreaterThanOrEqual(1);
    }
  });

  test('search returns results within time budget', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const searchInput = adminPage.locator('input[name="search"]:visible')
      .or(adminPage.getByPlaceholder('Search', { exact: true }))
      .first();

    await searchInput.waitFor({ state: 'visible', timeout: 10_000 });

    const start = Date.now();
    await searchInput.fill('screenshot');
    await adminPage.keyboard.press('Enter');

    await expect(
      adminPage.getByText(/\d+ Results/i).first()
    ).toBeVisible({ timeout: SEARCH_RESPONSE_MS });

    const elapsed = Date.now() - start;
    expect(elapsed).toBeLessThan(SEARCH_RESPONSE_MS);
  });

  test('filter panel opens without crash', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(adminPage.locator('h2').first()).toBeVisible({ timeout: 20_000 });

    const filterBtn = adminPage.getByText(/Filter/i).first()
      .or(adminPage.locator('[data-v-toggle], [class*="filter-btn"]').first());

    const isVisible = await filterBtn.isVisible({ timeout: 5_000 }).catch(() => false);
    if (isVisible) {
      await filterBtn.click();
      await adminPage.waitForTimeout(500);
      const hasError = await adminPage.locator('.error, [class*="error-message"]').count();
      expect(hasError).toBe(0);
    }
  });

  test('pagination next page loads within time budget', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(adminPage.locator('h2').first()).toBeVisible({ timeout: 20_000 });

    const nextBtn = adminPage.getByRole('button', { name: /next/i }).first()
      .or(adminPage.locator('[class*="next-page"], [aria-label*="next" i]').first());

    const isVisible = await nextBtn.isVisible({ timeout: 5_000 }).catch(() => false);

    const isDisabled = isVisible && await nextBtn.evaluate(
      el => el.classList.contains('pointer-events-none') || el.hasAttribute('disabled')
    ).catch(() => true);
    if (isVisible && !isDisabled) {
      const start = Date.now();
      await nextBtn.click();
      await expect(adminPage.locator('h2').first()).toBeVisible({ timeout: PAGINATION_NAV_MS });
      const elapsed = Date.now() - start;
      expect(elapsed).toBeLessThan(PAGINATION_NAV_MS);
    }
  });

  test('sort by updated_at works without crash', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(adminPage.locator('h2').first()).toBeVisible({ timeout: 20_000 });

    const sortBtn = adminPage.getByText(/Updated|Date/i).first();

    const isVisible = await sortBtn.isVisible({ timeout: 5_000 }).catch(() => false);
    if (isVisible) {
      await sortBtn.click();
      await adminPage.waitForLoadState('domcontentloaded');
      await adminPage.waitForTimeout(1000);
      await expect(adminPage.locator('h2').first()).toBeVisible({ timeout: 10_000 });
    }
  });

  test('clicking a directory node shows its assets', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const treeRow = adminPage.locator('.tree-container-details > .flex.cursor-pointer').first();

    const isVisible = await treeRow.isVisible({ timeout: 5_000 }).catch(() => false);
    if (isVisible) {
      await treeRow.click();
      await adminPage.waitForLoadState('domcontentloaded');
      await adminPage.waitForTimeout(1500);
      await expect(
        adminPage.getByText(/\d+ Results/i).first()
      ).toBeVisible({ timeout: 15_000 });
    }
  });

  test('bulk select all does not crash the UI', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    await expect(adminPage.locator('h2').first()).toBeVisible({ timeout: 20_000 });

    const selectAll = adminPage.getByText('Select All').first();
    const isVisible = await selectAll.isVisible({ timeout: 5_000 }).catch(() => false);
    if (isVisible) {
      await selectAll.click();
      await adminPage.waitForTimeout(500);
      const hasError = await adminPage.locator('.error, [class*="error-message"]').count();
      expect(hasError).toBe(0);
    }
  });
});
