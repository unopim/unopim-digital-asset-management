const { test, expect } = require('../utils/fixtures');
const { navigateTo, generateUid, ensureAssetExists } = require('../utils/helpers');

async function navigateToPropertiesTab(page) {
  await navigateTo(page, 'dam');
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(2000);

  const firstCard = page.locator('.image-card').first();
  await firstCard.waitFor({ state: 'visible', timeout: 20000 });
  await firstCard.hover();
  await page.waitForTimeout(500);
  await firstCard.locator('.icon-edit').first().click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

  const propsTab = page.locator('#app').getByText('Properties').first();
  await propsTab.click();
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

  await page.getByRole('button', { name: 'Create Property' }).waitFor({ state: 'visible', timeout: 15000 });
}

test.describe('DAM Asset Properties', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('Properties tab loads and shows title', async ({ adminPage }) => {
    await navigateToPropertiesTab(adminPage);
    await expect(adminPage.locator('#app').getByText('Asset Properties').first()).toBeVisible({ timeout: 15000 });
  });

  test('Create Property button is visible', async ({ adminPage }) => {
    await navigateToPropertiesTab(adminPage);
    await expect(
      adminPage.getByRole('button', { name: 'Create Property' })
    ).toBeVisible({ timeout: 15000 });
  });

  test('Create Property modal opens', async ({ adminPage }) => {
    await navigateToPropertiesTab(adminPage);
    await adminPage.getByRole('button', { name: 'Create Property' }).click();
    await adminPage.waitForTimeout(500);

    await expect(adminPage.locator('#app').getByText('Create Property').first()).toBeVisible();
    await expect(adminPage.getByPlaceholder('Name').first()).toBeVisible();
  });

  test('Create Property with empty name shows validation error', async ({ adminPage }) => {
    await navigateToPropertiesTab(adminPage);
    await adminPage.getByRole('button', { name: 'Create Property' }).click();
    await adminPage.waitForTimeout(500);

    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(
      adminPage.getByText(/The Name field is required/i).first()
    ).toBeVisible();
  });

  test('Create Property successfully', async ({ adminPage }) => {
    const uid = generateUid();
    const propName = `prop_${uid}`;

    await navigateToPropertiesTab(adminPage);
    await adminPage.getByRole('button', { name: 'Create Property' }).click();
    await adminPage.waitForTimeout(500);

    await adminPage.getByPlaceholder('Name').fill(propName);

    const typeCombo = adminPage.getByRole('combobox').filter({ hasText: 'Type' });
    await typeCombo.click();
    await adminPage.waitForTimeout(500);
    const textOption = adminPage.getByRole('option', { name: /Text/ }).first();
    await textOption.waitFor({ state: 'visible', timeout: 5000 });
    await textOption.click();
    await adminPage.waitForTimeout(300);

    const langCombo = adminPage.getByRole('combobox').filter({ hasText: 'Language' });
    await langCombo.click();
    await adminPage.waitForTimeout(500);
    const langOption = adminPage.getByRole('option', { name: /English/ }).first();
    await langOption.waitFor({ state: 'visible', timeout: 5000 });
    await langOption.click();
    await adminPage.waitForTimeout(300);

    await adminPage.getByPlaceholder('Value').fill(`value_${uid}`);

    await adminPage.getByRole('button', { name: 'Save' }).click();

    await Promise.any([
      adminPage.locator('#app').getByText(/created successfully/i).first()
        .waitFor({ state: 'visible', timeout: 15000 }),
      adminPage.locator('#app').getByText(propName).first()
        .waitFor({ state: 'visible', timeout: 15000 }),
    ]);
  });
});
