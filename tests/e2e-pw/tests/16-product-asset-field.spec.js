const { test, expect } = require('../utils/fixtures');

const PRODUCT_ID = 10;

test.describe('Product asset field thumbnail', () => {
  test('selected assets render via thumbnail route (cover for audio + s3 redirect)', async ({ adminPage }) => {
    const fetchUrls = [];

    adminPage.on('response', (response) => {
      const url = response.url();
      if (url.includes('/asset_picker/get_assets')) {
        fetchUrls.push({ url, status: response.status(), body: null, response });
      }
    });

    await adminPage.goto(`/admin/catalog/products/edit/${PRODUCT_ID}`, {
      waitUntil: 'domcontentloaded',
      timeout: 60000,
    });

    await adminPage.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
    await adminPage.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

    const assetField = adminPage.locator('v-asset-field, [class*="v-asset-field"], .grid:has(.icon-dam-folder)').first();
    const fieldVisible = await assetField.isVisible().catch(() => false);

    if (!fieldVisible) {
      test.skip(true, `Product ${PRODUCT_ID} has no asset field visible on edit page`);
      return;
    }

    const pickerCalls = await Promise.all(
      fetchUrls.map(async (entry) => {
        try {
          const json = await entry.response.json();
          return Array.isArray(json) ? json : [];
        } catch (_) {
          return [];
        }
      })
    );

    const assets = pickerCalls.flat();

    if (assets.length === 0) {
      test.skip(true, `Product ${PRODUCT_ID} has no selected DAM assets`);
      return;
    }

    for (const asset of assets) {
      expect(asset.url, `asset ${asset.id} url`).toMatch(/\/admin\/dam\/file\/thumbnail/);
    }

    const firstUrl = assets[0].url;
    const probe = await adminPage.request.get(firstUrl, { maxRedirects: 5 });
    expect(probe.status(), `thumbnail/cover URL ${firstUrl} should resolve`).toBeLessThan(400);
    const contentType = probe.headers()['content-type'] || '';
    expect(contentType, `${firstUrl} content-type`).toMatch(/^image\//);
  });
});
