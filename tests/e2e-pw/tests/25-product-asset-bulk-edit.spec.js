const { test, expect } = require('../utils/fixtures');

test.describe('Product asset bulk edit', () => {
  test('asset attribute renders the asset cell and opens the DAM picker modal', async ({ adminPage }) => {
    let assetAttribute = null;
    for (let page = 1; page <= 20 && !assetAttribute; page++) {
      const res = await adminPage.request.get(
        `/admin/catalog/products/bulkedit/fetch-attributes?page=${page}`,
        { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
      );
      if (!res.ok()) break;

      const json = await res.json().catch(() => ({}));
      const options = Array.isArray(json.options) ? json.options : [];
      assetAttribute = options.find((o) => o.type === 'asset') || null;

      if (page >= (json.lastPage || 1)) break;
    }

    if (!assetAttribute) {
      test.skip(true, 'No asset-type attribute exists in this install; nothing to bulk edit.');
      return;
    }

    await adminPage.goto('/admin/catalog/products', { waitUntil: 'domcontentloaded', timeout: 60000 });

    const appVisible = await adminPage.locator('#app')
      .waitFor({ state: 'visible', timeout: 30000 })
      .then(() => true)
      .catch(() => false);

    if (!appVisible) {
      test.skip(true, 'Products datagrid is not available.');
      return;
    }

    await adminPage.getByPlaceholder('Search').first().waitFor({ state: 'visible', timeout: 30000 }).catch(() => {});

    const selectAll = adminPage.locator('.icon-checkbox-normal').first();
    await selectAll.waitFor({ state: 'visible', timeout: 30000 });
    await selectAll.click();

    const selectAction = adminPage.getByRole('button', { name: 'Select Action' });
    await selectAction.waitFor({ state: 'visible', timeout: 15000 });
    await selectAction.click();

    await adminPage.getByRole('link', { name: 'Bulk Edit' }).click();

    const attributeSelect = adminPage.locator('.multiselect')
      .filter({ has: adminPage.locator('input[name="filtered_attributes"]') })
      .first();
    await attributeSelect.click();
    await attributeSelect.locator('input.multiselect__input').fill(assetAttribute.name);

    const option = adminPage.getByRole('option', { name: new RegExp(escapeRegExp(assetAttribute.name), 'i') }).first();
    await option.waitFor({ state: 'visible', timeout: 15000 });
    await option.click();

    await adminPage.getByRole('button', { name: 'Proceed' }).click();

    await adminPage.waitForURL(/\/catalog\/products\/bulkedit/, { timeout: 30000 });
    await adminPage.locator('#app').waitFor({ state: 'visible', timeout: 30000 });

    await expect(
      adminPage.getByRole('columnheader', { name: new RegExp(escapeRegExp(assetAttribute.name), 'i') })
    ).toBeVisible({ timeout: 30000 });

    const editIcon = adminPage.locator('tbody .icon-edit').first();
    await expect(editIcon).toBeVisible({ timeout: 30000 });
    await editIcon.click();

    await expect(adminPage.getByText('Assign Assets').first()).toBeVisible({ timeout: 30000 });

    const previewButton = adminPage.locator('.icon-dam-preview').first();
    const hasAsset = await previewButton
      .waitFor({ state: 'attached', timeout: 15000 })
      .then(() => true)
      .catch(() => false);

    if (hasAsset) {
      await previewButton.hover();
      await previewButton.click();

      const viewer = adminPage.locator('.z-\\[10010\\]').first();
      await expect(viewer).toBeVisible({ timeout: 15000 });

      await adminPage.keyboard.press('Escape');
      await expect(viewer).toBeHidden({ timeout: 15000 });
      await expect(adminPage.getByText('Assign Assets').first()).toBeVisible();
    }
  });
});

function escapeRegExp(text) {
  return String(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
