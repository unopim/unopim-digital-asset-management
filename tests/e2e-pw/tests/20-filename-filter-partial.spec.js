const { test, expect } = require('../utils/fixtures');
const { navigateTo, ensureAssetExists } = require('../utils/helpers');

test.describe('DAM File Name Filter — Partial Match', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('file name filter returns results for partial name without extension', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // Get the visible file name from the first asset card.
    // h2 sits outside .image-card (sibling div in the v-for wrapper);
    // use the img alt attribute which Vue binds to record.file_name.
    const firstCard = adminPage.locator('.image-card').first();
    await firstCard.waitFor({ state: 'visible', timeout: 30000 });
    const img = firstCard.locator('img').first();
    await expect(img).toHaveAttribute('alt', /.+/, { timeout: 15000 });
    const fileName = await img.getAttribute('alt');
    const baseName = fileName?.split('.')[0]?.trim();
    expect(baseName).toBeTruthy();

    // Open filter panel
    await adminPage.getByText('Filter', { exact: true }).first().click();
    await adminPage.waitForTimeout(500);

    // Fill file name filter with partial name (no extension)
    const fileNameInput = adminPage.getByPlaceholder('File Name').first();
    await fileNameInput.waitFor({ state: 'visible', timeout: 10000 });

    const responsePromise = adminPage.waitForResponse(
      (res) => /\/admin\/dam\/assets(\?|$)/.test(res.url()) && res.request().method() === 'GET',
      { timeout: 15000 }
    ).catch(() => {});

    await fileNameInput.fill(baseName);
    await fileNameInput.press('Enter');
    await responsePromise;
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(500);

    // Asset should appear in results
    await expect(adminPage.locator('.image-card').first()).toBeVisible({ timeout: 15000 });
    await expect(adminPage.getByText(/\d+ Results/i).first()).toBeVisible();
  });
});
