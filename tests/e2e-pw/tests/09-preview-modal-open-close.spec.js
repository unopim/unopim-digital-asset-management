const { test, expect } = require('../utils/fixtures');
const { ensureAssetExists, navigateToAssetEditByName } = require('../utils/helpers');

// ─── Shared helpers ───────────────────────────────────────────────────────────

async function openInfoModal(page) {
  const btn = page.locator('button').filter({ has: page.locator('.icon-information') }).first();
  await btn.waitFor({ state: 'visible', timeout: 20000 });
  await btn.click();
  await page.locator('.absolute.inset-0.bg-black\\/60').first()
    .waitFor({ state: 'visible', timeout: 20000 });
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('DAM Asset Preview — Inline & Info Modal', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Inline preview
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Inline preview', () => {

    test('Inline preview container is visible on asset edit page', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      const container = adminPage.locator('.flex.items-center.justify-center.w-full.rounded-lg.overflow-hidden.bg-gray-50').first();
      await expect(container).toBeVisible({ timeout: 15000 });
    });

    test('Image renders inline without any button click', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      const img = adminPage.locator('.flex.items-center.justify-center.w-full.rounded-lg.overflow-hidden.bg-gray-50 img').first();
      await expect(img).toBeVisible({ timeout: 15000 });
    });

    test('Info button is visible on asset edit page', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      const infoBtn = adminPage.locator('button').filter({ has: adminPage.locator('.icon-information') }).first();
      await expect(infoBtn).toBeVisible({ timeout: 15000 });
    });

    test('Edit image button is visible for image assets', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await expect(adminPage.locator('button[title="Edit image"]').first()).toBeVisible({ timeout: 15000 });
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Info modal — open / close
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Info modal — open / close', () => {

    test('Info button opens info modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/60').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal backdrop absent before info button click', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/60').first()).not.toBeVisible({ timeout: 3000 });
    });

    test('Close button dismisses info modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      // Close button is the only button inside the info modal header
      const closeBtn = adminPage.locator('.fixed.inset-0.z-\\[10010\\] button.rounded-full').first();
      await closeBtn.click();
      await adminPage.waitForTimeout(400);
      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/60').first()).not.toBeVisible({ timeout: 5000 });
    });

    test('Clicking backdrop closes info modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      const backdrop = adminPage.locator('.absolute.inset-0.bg-black\\/60').first();
      await backdrop.click({ position: { x: 5, y: 5 }, force: true });
      await adminPage.waitForTimeout(400);
      await expect(backdrop).not.toBeVisible({ timeout: 5000 });
    });

    test('Info modal can be opened and closed multiple times', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      for (let i = 0; i < 2; i++) {
        await openInfoModal(adminPage);
        const backdrop = adminPage.locator('.absolute.inset-0.bg-black\\/60').first();
        await backdrop.click({ position: { x: 5, y: 5 }, force: true });
        await adminPage.waitForTimeout(400);
        await expect(backdrop).not.toBeVisible({ timeout: 5000 });
      }
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Info modal — content
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Info modal — content', () => {

    test('Info modal shows extension badge', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      const modal = adminPage.locator('.fixed.inset-0.z-\\[10010\\]').first();
      await expect(modal.locator('span.rounded.font-semibold').first()).toBeVisible({ timeout: 5000 });
    });

    test('Info modal shows asset filename', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      const modal = adminPage.locator('.fixed.inset-0.z-\\[10010\\]').first();
      const namePara = modal.locator('p.flex-1.text-sm.font-semibold').first();
      await expect(namePara).toBeVisible({ timeout: 5000 });
    });

    test('Info modal content area has file details rows', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openInfoModal(adminPage);
      const modal = adminPage.locator('.fixed.inset-0.z-\\[10010\\]').first();
      // At least one detail row visible in the body section
      await expect(modal.locator('.flex.items-center.justify-between.py-3').first()).toBeVisible({ timeout: 5000 });
    });

  });

});
