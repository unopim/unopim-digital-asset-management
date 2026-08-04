const { test, expect } = require('../utils/fixtures');
const {
  navigateTo,
  ensureAssetExists,
  openFilterDrawer,
  expandFilter,
  dismissUploadPanel,
} = require('../utils/helpers');

test.describe('DAM Assets — Core filter drawer parity', () => {

  test.beforeEach(async ({ adminPage }) => {
    await adminPage.addInitScript(() => {
      try {
        localStorage.removeItem('dam_explorer_filter_state');
        localStorage.removeItem('datagrids');
      } catch (_) {}
    });

    await ensureAssetExists(adminPage);
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');
  });

  test('filter rows start collapsed and summarise their value', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    const fileNameRow = adminPage.locator('[data-datagrid-filter="file_name"]');

    await expect(fileNameRow.locator('[data-filter-name]')).toHaveText('File Name');
    await expect(fileNameRow.locator('[data-filter-summary]')).toHaveText('All');
    await expect(fileNameRow.getByPlaceholder('File Name').first()).toBeHidden();

    await expandFilter(adminPage, 'file_name');

    await expect(fileNameRow.getByPlaceholder('File Name').first()).toBeVisible();
    await expect(fileNameRow.locator('[data-filter-summary]')).toBeHidden();
  });

  test('applying a filter shows the count badge and filters the grid', async ({ adminPage }) => {
    const firstCard = adminPage.locator('.image-card').first();
    await firstCard.waitFor({ state: 'visible', timeout: 30000 });
    const fileName = await firstCard.locator('img').first().getAttribute('alt');
    const baseName = fileName?.split('.')[0]?.trim();
    expect(baseName).toBeTruthy();

    await openFilterDrawer(adminPage);

    const fileNameRow = await expandFilter(adminPage, 'file_name');
    const fileNameInput = fileNameRow.getByPlaceholder('File Name').first();

    await fileNameInput.fill(baseName);
    await fileNameInput.press('Enter');

    await expect(adminPage.locator('[data-applied-filter-count]')).toHaveText('1');
    await expect(fileNameRow.locator('p').filter({ hasText: baseName }).first()).toBeVisible();

    await dismissUploadPanel(adminPage);
    await adminPage.getByRole('button', { name: 'Apply' }).click();

    await expect(adminPage.locator('[data-datagrid-filter]').first()).toBeHidden({ timeout: 15000 });
    await expect(adminPage.locator('.image-card').first()).toBeVisible({ timeout: 15000 });
    await expect(adminPage.locator('[data-applied-filter-count]')).toHaveText('1');
  });

  test('clear all resets every applied filter', async ({ adminPage }) => {
    await openFilterDrawer(adminPage);

    await expect(adminPage.locator('button[data-clear-all-filters]')).toBeDisabled();

    const fileNameRow = await expandFilter(adminPage, 'file_name');
    const fileNameInput = fileNameRow.getByPlaceholder('File Name').first();

    await fileNameInput.fill('parity-check');
    await fileNameInput.press('Enter');

    await expect(adminPage.locator('button[data-clear-all-filters]')).toBeEnabled();

    await dismissUploadPanel(adminPage);
    await adminPage.getByRole('button', { name: 'Apply' }).click();
    await adminPage.waitForTimeout(500);

    await openFilterDrawer(adminPage);
    await dismissUploadPanel(adminPage);
    await adminPage.locator('button[data-clear-all-filters]').click();

    await expect(adminPage.locator('[data-applied-filter-count]')).toHaveCount(0, { timeout: 15000 });
  });
});
