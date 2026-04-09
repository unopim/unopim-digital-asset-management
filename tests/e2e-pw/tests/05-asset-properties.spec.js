const { test, expect } = require('../utils/fixtures');
const { navigateTo, generateUid } = require('../utils/helpers');

/**
 * Helper: Navigate to the Properties tab of the first asset.
 * Uses hover + edit icon pattern from the gallery view.
 */
async function navigateToPropertiesTab(page) {
  await navigateTo(page, 'dam');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1000);

  // Hover over first image card and click edit
  const firstCard = page.locator('.image-card').first();
  await firstCard.hover();
  await page.waitForTimeout(300);
  await firstCard.locator('.icon-edit').first().click({ force: true });
  await page.waitForLoadState('networkidle');

  // Click Properties tab
  const propsTab = page.locator('#app').getByText('Properties').first();
  await propsTab.click();
  await page.waitForLoadState('networkidle');
  // Wait for the Vue component to fully render (shimmer → real content)
  await page.waitForTimeout(3000);
  // Ensure the "Create Property" button is from the real component, not shimmer
  await page.getByRole('button', { name: 'Create Property' }).waitFor({ state: 'visible', timeout: 15000 });
}

test.describe('DAM Asset Properties', () => {

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

    // Fill Name
    await adminPage.getByPlaceholder('Name').fill(propName);

    // Select Type — custom combobox component
    const typeCombo = adminPage.getByRole('combobox').filter({ hasText: 'Type' });
    await typeCombo.click();
    await adminPage.waitForTimeout(500);
    const textOption = adminPage.getByRole('option', { name: /Text/ }).first();
    await textOption.waitFor({ state: 'visible', timeout: 5000 });
    await textOption.click();
    await adminPage.waitForTimeout(300);

    // Select Language — custom combobox component
    const langCombo = adminPage.getByRole('combobox').filter({ hasText: 'Language' });
    await langCombo.click();
    await adminPage.waitForTimeout(500);
    const langOption = adminPage.getByRole('option', { name: /English/ }).first();
    await langOption.waitFor({ state: 'visible', timeout: 5000 });
    await langOption.click();
    await adminPage.waitForTimeout(300);

    // Fill Value
    await adminPage.getByPlaceholder('Value').fill(`value_${uid}`);

    // Save
    await adminPage.getByRole('button', { name: 'Save' }).click();

    // Verify success - either toast or modal closes and property appears in grid
    await Promise.any([
      adminPage.locator('#app').getByText(/created successfully/i).first()
        .waitFor({ state: 'visible', timeout: 15000 }),
      adminPage.locator('#app').getByText(propName).first()
        .waitFor({ state: 'visible', timeout: 15000 }),
    ]);
  });
});
