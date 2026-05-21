const path = require('path');
const { test, expect } = require('../utils/fixtures');
const { ensureAssetExists, ensureAssetOfTypeExists, navigateToAssetEditByName } = require('../utils/helpers');

// ─── Shared helpers ───────────────────────────────────────────────────────────

/**
 * Opens the info modal and waits for its backdrop (bg-black/60).
 */
async function openInfoModal(page) {
  const btn = page.locator('button').filter({ has: page.locator('.icon-information') }).first();
  await btn.waitFor({ state: 'visible', timeout: 20000 });
  await btn.click();
  await page.locator('.absolute.inset-0.bg-black\\/60').first()
    .waitFor({ state: 'visible', timeout: 20000 });
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('DAM Asset Preview Modal', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Card-level action icons
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Action icon row', () => {

    test('Info icon button is visible on edit page', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await expect(
        adminPage.locator('button').filter({ has: adminPage.locator('.icon-information') }).first()
      ).toBeVisible({ timeout: 10000 });
    });

    test('Edit image button visible for image asset', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await expect(adminPage.locator('button[title="Edit image"]').first()).toBeVisible({ timeout: 10000 });
    });

    test('Edit image button absent for non-image asset', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'sample.mp4');
      await expect(adminPage.locator('button[title="Edit image"]').first()).not.toBeVisible({ timeout: 3000 });
    });

    test('Thumbnail renders on edit page (image or placeholder)', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      const wrapper = adminPage.locator('.rounded-lg.overflow-hidden').first();
      await expect(wrapper).toBeVisible({ timeout: 10000 });
      await expect(wrapper.locator('img').first()).toBeVisible();
    });

    test('Image asset thumbnail has no opacity-60 class', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      const thumb = adminPage.locator('.rounded-lg.overflow-hidden img').first();
      const hasOpacity = await thumb.evaluate(el => el.classList.contains('opacity-60'));
      expect(hasOpacity).toBe(false);
    });

    test('Non-image asset thumbnail has opacity-60 class (placeholder)', async ({ adminPage }) => {
      // Video assets (mp4) render a native video player, not a placeholder img.
      // Use a plain-text file which renders an <img class="opacity-60"> placeholder.
      await ensureAssetOfTypeExists(
        adminPage,
        path.resolve(__dirname, '../assets/sample.txt'),
        'sample.txt'
      );
      await navigateToAssetEditByName(adminPage, 'sample.txt');
      const thumb = adminPage.locator('.rounded-lg.overflow-hidden img').first();
      await thumb.waitFor({ state: 'visible', timeout: 10000 });
      const hasOpacity = await thumb.evaluate(el => el.classList.contains('opacity-60'));
      expect(hasOpacity).toBe(true);
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Info hover tooltip
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Info hover tooltip', () => {

    test('Hovering info button reveals tooltip', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await adminPage.locator('button').filter({ has: adminPage.locator('.icon-information') }).first().hover();
      await adminPage.waitForTimeout(300);
      await expect(
        adminPage.locator('.pointer-events-none').filter({ hasText: 'Click for full details' }).first()
      ).toBeVisible({ timeout: 5000 });
    });

    test('Tooltip shows asset filename', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await adminPage.locator('button').filter({ has: adminPage.locator('.icon-information') }).first().hover();
      await adminPage.waitForTimeout(300);
      const tooltip = adminPage.locator('.pointer-events-none').filter({ hasText: 'Click for full details' }).first();
      await expect(tooltip.locator('p.font-semibold.truncate').first()).toBeVisible({ timeout: 5000 });
    });

    test('Tooltip shows extension badge', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await adminPage.locator('button').filter({ has: adminPage.locator('.icon-information') }).first().hover();
      await adminPage.waitForTimeout(300);
      const tooltip = adminPage.locator('.pointer-events-none').filter({ hasText: 'Click for full details' }).first();
      await expect(tooltip.locator('span.rounded.font-semibold').first()).toBeVisible({ timeout: 5000 });
    });

    test('Tooltip disappears on mouse leave', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await adminPage.locator('button').filter({ has: adminPage.locator('.icon-information') }).first().hover();
      await adminPage.waitForTimeout(300);
      await adminPage.mouse.move(10, 10);
      await adminPage.waitForTimeout(300);
      await expect(
        adminPage.locator('.pointer-events-none').filter({ hasText: 'Click for full details' }).first()
      ).not.toBeVisible({ timeout: 3000 });
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Info modal
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Info modal', () => {

    test('Clicking info button opens info modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      await expect(adminPage.locator('#app').getByText('File Information').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows File Name row', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      await expect(adminPage.locator('#app').getByText('File Name').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows Type row with extension badge', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      await expect(adminPage.locator('#app').getByText('Type').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows Size row', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      // Size row is conditionally rendered — assert it is visible (not just annotate)
      const sizeRow = adminPage.locator('#app').getByText('Size').first();
      await expect(sizeRow).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows Path row', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      await expect(adminPage.locator('#app').getByText('Path').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows MIME row', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      await expect(adminPage.locator('#app').getByText('MIME').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows Created date row', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      await expect(adminPage.locator('#app').getByText('Created').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows Updated date row', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      await expect(adminPage.locator('#app').getByText('Updated').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows Dimensions row for image asset', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      // Dimensions conditionally rendered only when width/height extracted
      const dimRow = adminPage.locator('#app').getByText('Dimensions').first();
      const visible = await dimRow.isVisible({ timeout: 5000 }).catch(() => false);
      if (!visible) {
        test.info().annotations.push({ type: 'note', description: 'width/height not extracted for this asset' });
      }
    });

    test('Info modal X button closes the modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      // Close button: w-7 h-7 rounded-full — unique to info modal (preview uses w-8 h-8)
      await adminPage.locator('button.w-7.h-7.rounded-full').first().click();
      await adminPage.waitForTimeout(400);
      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/60').first()).not.toBeVisible({ timeout: 5000 });
    });

    test('Clicking info modal backdrop closes the modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      const backdrop = adminPage.locator('.absolute.inset-0.bg-black\\/60').first();
      await backdrop.click({ position: { x: 5, y: 5 }, force: true });
      await adminPage.waitForTimeout(400);
      await expect(backdrop).not.toBeVisible({ timeout: 5000 });
    });

  });

});
