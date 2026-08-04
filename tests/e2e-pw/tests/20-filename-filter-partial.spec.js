const { test, expect } = require('../utils/fixtures');
const { navigateTo, ensureAssetExists, openFilterDrawer, expandFilter } = require('../utils/helpers');

test.describe('DAM File Name Filter — Partial Match', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('file name filter returns results for partial name without extension', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const firstCard = adminPage.locator('.image-card').first();
    await firstCard.waitFor({ state: 'visible', timeout: 30000 });
    const img = firstCard.locator('img').first();
    await expect(img).toHaveAttribute('alt', /.+/, { timeout: 15000 });
    const fileName = await img.getAttribute('alt');
    const baseName = fileName?.split('.')[0]?.trim();
    expect(baseName).toBeTruthy();

    await openFilterDrawer(adminPage);

    const fileNameRow = await expandFilter(adminPage, 'file_name');

    const fileNameInput = fileNameRow.getByPlaceholder('File Name').first();
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

    await expect(adminPage.locator('.image-card').first()).toBeVisible({ timeout: 15000 });
    await expect(adminPage.getByText(/\d+ Results/i).first()).toBeVisible();
  });
});
