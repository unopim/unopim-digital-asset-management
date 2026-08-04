const { test, expect } = require('../utils/fixtures');

const CANDIDATE_CATEGORY_IDS = [2, 3, 4, 5, 6, 7, 8];

async function openCategoryWithAssetField(page) {
  for (const id of CANDIDATE_CATEGORY_IDS) {
    await page.goto(`/admin/catalog/categories/edit/${id}`, {
      waitUntil: 'domcontentloaded',
      timeout: 60000,
    });

    const visible = await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 })
      .then(() => true)
      .catch(() => false);

    if (! visible) {
      continue;
    }

    await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

    const tile = page.locator('label').filter({ hasText: 'Add Asset' }).first();

    if (await tile.isVisible().catch(() => false)) {
      return { id, tile };
    }
  }

  return null;
}

async function assignFirstAsset(page, tile) {
  await tile.click();

  const firstCheckbox = page.locator('input[id^="mass_action_select_record_"]').first();
  await firstCheckbox.waitFor({ state: 'attached', timeout: 20000 });
  await page.locator('input[id^="mass_action_select_record_"] + span').first().click();

  await page.locator('span.secondary-button').filter({ hasText: 'Assign Assets' }).first().click();
}

test.describe('Category asset field', () => {

  test('assigning an asset renders it and marks the form unsaved', async ({ adminPage }) => {
    const found = await openCategoryWithAssetField(adminPage);

    if (! found) {
      test.skip(true, 'No category with an asset-type field is configured');
      return;
    }

    const hiddenInputs = adminPage.locator('input[type="hidden"][name*="[]"][name^="additional_data"]');

    await assignFirstAsset(adminPage, found.tile);

    await expect
      .poll(async () => (await hiddenInputs.evaluateAll(nodes => nodes.filter(n => n.value).length)), {
        timeout: 20000,
      })
      .toBeGreaterThan(0);

    await expect(adminPage.getByText('You have unsaved changes').first())
      .toBeVisible({ timeout: 15000 });
    await expect(adminPage.locator('button[data-unsaved-save]').first())
      .toBeVisible();
  });

  test('a failed asset lookup keeps the current selection instead of emptying the field', async ({ adminPage }) => {
    const found = await openCategoryWithAssetField(adminPage);

    if (! found) {
      test.skip(true, 'No category with an asset-type field is configured');
      return;
    }

    const hiddenInputs = adminPage.locator('input[type="hidden"][name*="[]"][name^="additional_data"]');

    await assignFirstAsset(adminPage, found.tile);

    await expect
      .poll(async () => (await hiddenInputs.evaluateAll(nodes => nodes.filter(n => n.value).length)), {
        timeout: 20000,
      })
      .toBeGreaterThan(0);

    const assigned = await hiddenInputs.evaluateAll(nodes => nodes.map(n => n.value).filter(Boolean));

    await adminPage.route('**/dam/picker/get**', route => route.fulfill({ status: 500, body: '{}' }));

    await adminPage.locator('label').filter({ hasText: 'Add Asset' }).first().click();

    const checkboxes = adminPage.locator('input[id^="mass_action_select_record_"] + span');
    await checkboxes.first().waitFor({ state: 'visible', timeout: 20000 });
    await checkboxes.nth(1).click();

    await adminPage.locator('span.secondary-button').filter({ hasText: 'Assign Assets' }).first().click();

    await expect(adminPage.locator('#app').getByText(/not found for display/i).first())
      .toBeVisible({ timeout: 15000 });

    const stillAssigned = await hiddenInputs.evaluateAll(nodes => nodes.map(n => n.value).filter(Boolean));
    expect(stillAssigned).toEqual(assigned);
  });
});
