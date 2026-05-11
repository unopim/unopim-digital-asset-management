const { test, expect } = require('../utils/fixtures');
const { ensureAssetExists, navigateToAssetEditByName } = require('../utils/helpers');

// ─── Shared helpers ───────────────────────────────────────────────────────────

/**
 * Opens the fullscreen preview modal and waits for the viewer backdrop.
 * Viewer backdrop: absolute inset-0 bg-black/75 (inside v-if="isOpen" div).
 */
async function openPreviewModal(page) {
  const btn = page.locator('button[title="Preview"]').first();
  await btn.waitFor({ state: 'visible', timeout: 20000 });
  await btn.click();
  // Wait for the viewer's own backdrop (distinct from info modal's bg-black/60)
  await page.locator('.absolute.inset-0.bg-black\\/75').first()
    .waitFor({ state: 'visible', timeout: 20000 });
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('DAM Asset Preview Modal', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Preview modal — open / close
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Preview modal — open / close', () => {

    test('Preview button opens fullscreen modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      await expect(adminPage.locator('.rounded-xl.shadow-2xl').first()).toBeVisible({ timeout: 10000 });
    });

    test('Modal backdrop is absent before preview button click', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      // v-if="isOpen" keeps the whole overlay out of DOM; must not be visible
      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/75').first()).not.toBeVisible({ timeout: 3000 });
    });

    test('X button (aria-label=Close preview) closes the modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      await adminPage.locator('button[aria-label="Close preview"]').click();
      await adminPage.waitForTimeout(400);
      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/75').first()).not.toBeVisible({ timeout: 5000 });
    });

    test('Clicking the backdrop closes the modal', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      const backdrop = adminPage.locator('.absolute.inset-0.bg-black\\/75').first();
      await backdrop.click({ position: { x: 5, y: 5 }, force: true });
      await adminPage.waitForTimeout(400);
      await expect(backdrop).not.toBeVisible({ timeout: 5000 });
    });

    test('Body scroll locked when modal open', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      expect(await adminPage.evaluate(() => document.body.style.overflow)).toBe('hidden');
    });

    test('Body scroll restored after modal closes', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      await adminPage.locator('button[aria-label="Close preview"]').click();
      await adminPage.waitForTimeout(400);
      expect(await adminPage.evaluate(() => document.body.style.overflow)).toBe('');
    });

    test('Modal can be opened and closed multiple times', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      for (let i = 0; i < 2; i++) {
        await openPreviewModal(adminPage);
        await adminPage.locator('button[aria-label="Close preview"]').click();
        await adminPage.waitForTimeout(400);
        await expect(adminPage.locator('.absolute.inset-0.bg-black\\/75').first()).not.toBeVisible({ timeout: 5000 });
      }
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Preview modal — header
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Preview modal — header', () => {

    test('Header shows extension badge', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      // Modal header is the border-b bar; badge is a span.rounded.font-semibold inside it
      const header = adminPage.locator('.border-b.border-gray-200').first();
      await expect(header.locator('span.rounded.font-semibold').first()).toBeVisible({ timeout: 5000 });
    });

    test('Header shows asset filename (non-empty)', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      // text-sm distinguishes the viewer-modal <p> from the hidden hover-tooltip <p>
      const filenamePara = adminPage.locator('p.text-sm.font-semibold.truncate').first();
      await expect(filenamePara).toBeVisible({ timeout: 5000 });
      expect((await filenamePara.textContent())?.trim().length).toBeGreaterThan(0);
    });

    test('Header has close button with aria-label', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      await expect(adminPage.locator('button[aria-label="Close preview"]')).toBeVisible({ timeout: 5000 });
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Preview modal — content area
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Preview modal — content area', () => {

    test('Content area is visible and contains at least one media element', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      await openPreviewModal(adminPage);
      // Scope to viewer modal's content specifically (bg-gray-50 distinguishes it from the
      // base admin layout wrapper which also has flex-1 min-h-0 overflow-hidden)
      const content = adminPage.locator('.flex-1.min-h-0.overflow-hidden.bg-gray-50').first();
      await expect(content).toBeVisible({ timeout: 5000 });
      await adminPage.waitForTimeout(500);
      const hasImg    = await content.locator('img').first().isVisible().catch(() => false);
      const hasVideo  = await content.locator('video').first().isVisible().catch(() => false);
      const hasAudio  = await content.locator('audio').first().isVisible().catch(() => false);
      const hasIframe = await content.locator('iframe').first().isVisible().catch(() => false);
      expect(hasImg || hasVideo || hasAudio || hasIframe).toBeTruthy();
    });

  });

});
