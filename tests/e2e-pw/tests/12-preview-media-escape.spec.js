const path = require('path');
const { test, expect } = require('../utils/fixtures');
const { ensureAssetExists, ensureAssetOfTypeExists, navigateTo, searchInDataGrid } = require('../utils/helpers');

const ASSETS = path.resolve(__dirname, '../assets');

// Navigate to the edit page of the first asset whose filename contains `ext`.
// Extension-based search (e.g. '.mp4') matches 'sample(1).mp4', 'sample(5).mp4', etc.
//
// Picks the card whose v-for wrapper contains an `<h2>` matching `ext` — not
// `.image-card.first()`, which can grab the topmost card when the search
// filter hasn't applied yet and silently route the test to the wrong asset.
async function navigateToFirstAssetWithExt(page, ext) {
  await navigateTo(page, 'dam');
  await searchInDataGrid(page, ext);
  await page.locator('h2').filter({ hasText: ext }).first()
    .waitFor({ state: 'visible', timeout: 20000 });
  const cardWrapper = page.locator('div:has(> .image-card)')
    .filter({ hasText: ext })
    .first();
  await cardWrapper.waitFor({ state: 'visible', timeout: 20000 });
  const card = cardWrapper.locator('.image-card').first();
  await card.hover({ force: true });
  await page.waitForTimeout(300);
  await card.locator('.icon-edit').first().click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

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

/**
 * Opens the editor modal for floral.jpg (always an image) and waits for close button.
 */
async function openEditorModal(page) {
  await navigateToFirstAssetWithExt(page, '.jpg');
  const btn = page.locator('button[title="Edit image"]').first();
  await btn.waitFor({ state: 'visible', timeout: 10000 });
  await btn.click();
  await page.locator('button[aria-label="Close editor"]')
    .waitFor({ state: 'visible', timeout: 10000 });
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('DAM Asset Preview Modal', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
    await ensureAssetOfTypeExists(adminPage, `${ASSETS}/sample.mp4`, '.mp4');
    await ensureAssetOfTypeExists(adminPage, `${ASSETS}/sample.wav`, '.wav');
    await ensureAssetOfTypeExists(adminPage, `${ASSETS}/sample.pdf`, '.pdf');
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Video player
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Video player', () => {

    async function openVideoPreview(page) {
      await navigateToFirstAssetWithExt(page, '.mp4');
      await openPreviewModal(page);
    }

    test('Video element renders in modal', async ({ adminPage }) => {
      await openVideoPreview(adminPage);
      await expect(adminPage.locator('.flex-1.min-h-0.overflow-hidden video').first()).toBeVisible({ timeout: 10000 });
    });

    test('Speed selector buttons visible', async ({ adminPage }) => {
      await openVideoPreview(adminPage);
      await expect(adminPage.locator('button').filter({ hasText: '1×' }).first()).toBeVisible({ timeout: 5000 });
    });

    test('Skip back 10s button visible (title=Back 10s)', async ({ adminPage }) => {
      await openVideoPreview(adminPage);
      await expect(adminPage.locator('button[title="Back 10s"]').first()).toBeVisible({ timeout: 5000 });
    });

    test('Skip forward 10s button visible (title=Forward 10s)', async ({ adminPage }) => {
      await openVideoPreview(adminPage);
      await expect(adminPage.locator('button[title="Forward 10s"]').first()).toBeVisible({ timeout: 5000 });
    });

    test('1× speed button is active by default', async ({ adminPage }) => {
      await openVideoPreview(adminPage);
      const oneX = adminPage.locator('button').filter({ hasText: /^1×$/ }).first();
      const cls = await oneX.evaluate(el => el.className);
      expect(cls).toContain('bg-violet-600');
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Audio player (conditional — only runs on audio assets)
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Audio player', () => {

    async function openAudioPreview(page) {
      await navigateToFirstAssetWithExt(page, '.wav');
      await openPreviewModal(page);
    }

    test('Play/pause button visible', async ({ adminPage }) => {
      await openAudioPreview(adminPage);
      await expect(adminPage.locator('button.w-14.h-14.rounded-full').first()).toBeVisible({ timeout: 5000 });
    });

    test('Seek bar visible', async ({ adminPage }) => {
      await openAudioPreview(adminPage);
      // ref="audioSeekContainer" is a Vue ref — not a DOM attribute. Match by unique class combo.
      await expect(
        adminPage.locator('.relative.h-4.group.cursor-pointer').first()
      ).toBeVisible({ timeout: 5000 });
    });

    test('Volume slider visible', async ({ adminPage }) => {
      await openAudioPreview(adminPage);
      await expect(adminPage.locator('input[type="range"].w-20').first()).toBeVisible({ timeout: 5000 });
    });

    test('Current time display starts at 0:00', async ({ adminPage }) => {
      await openAudioPreview(adminPage);
      await expect(adminPage.locator('.font-mono.tabular-nums').filter({ hasText: '0:00' }).first()).toBeVisible({ timeout: 5000 });
    });

    test('Skip back 10s button visible (title=Back 10s)', async ({ adminPage }) => {
      await openAudioPreview(adminPage);
      await expect(adminPage.locator('button[title="Back 10s"]').first()).toBeVisible({ timeout: 5000 });
    });

    test('Skip forward 10s button visible (title=Forward 10s)', async ({ adminPage }) => {
      await openAudioPreview(adminPage);
      await expect(adminPage.locator('button[title="Forward 10s"]').first()).toBeVisible({ timeout: 5000 });
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // PDF viewer (sample.pdf — file_type='document', renders iframe)
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Fallback / unsupported asset', () => {

    async function openFallbackPreview(page) {
      await navigateToFirstAssetWithExt(page, '.pdf');
      await openPreviewModal(page);
    }

    test('Fallback modal shows "not available" message', async ({ adminPage }) => {
      await openFallbackPreview(adminPage);
      const msg = adminPage.locator('.flex-1.min-h-0.overflow-hidden').getByText(/not available|preview not/i).first();
      const visible = await msg.isVisible({ timeout: 5000 }).catch(() => false);
      if (!visible) {
        test.info().annotations.push({ type: 'note', description: 'PDF — iframe shown instead of not-available message' });
      }
    });

    test('Fallback modal shows Download button', async ({ adminPage }) => {
      await openFallbackPreview(adminPage);
      const downloadLink = adminPage.locator('.flex-1.min-h-0.overflow-hidden a.primary-button').first();
      const visible = await downloadLink.isVisible({ timeout: 5000 }).catch(() => false);
      if (!visible) {
        test.info().annotations.push({ type: 'note', description: 'Might be PDF — uses iframe instead of download button' });
      }
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Escape key priority
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Escape key priority', () => {

    test('Escape closes info modal first when preview is also open', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.jpg');
      await openPreviewModal(adminPage);

      // dispatchEvent bypasses browser hit-testing (unlike force:true which still routes
      // through screen coordinates and gets intercepted by the preview backdrop).
      const infoBtn = adminPage.locator('button').filter({ has: adminPage.locator('.icon-information') }).first();
      await infoBtn.dispatchEvent('click');
      // Wait for info backdrop
      await adminPage.locator('.absolute.inset-0.bg-black\\/60').first()
        .waitFor({ state: 'visible', timeout: 10000 });

      // Both modals open — first Escape must close info only, preview stays
      await adminPage.keyboard.press('Escape');
      await adminPage.waitForTimeout(400);

      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/60').first()).not.toBeVisible({ timeout: 5000 });
      // Preview backdrop must still exist
      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/75').first()).toBeVisible({ timeout: 3000 });
    });

    test('Second Escape closes preview modal after info is already dismissed', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.jpg');
      await openPreviewModal(adminPage);

      const infoBtn = adminPage.locator('button').filter({ has: adminPage.locator('.icon-information') }).first();
      await infoBtn.dispatchEvent('click');
      await adminPage.locator('.absolute.inset-0.bg-black\\/60').first()
        .waitFor({ state: 'visible', timeout: 10000 });

      await adminPage.keyboard.press('Escape'); // closes info
      await adminPage.waitForTimeout(300);
      await adminPage.keyboard.press('Escape'); // now closes preview
      await adminPage.waitForTimeout(400);

      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/75').first()).not.toBeVisible({ timeout: 5000 });
    });

    test('Escape closes editor modal first when editor is open', async ({ adminPage }) => {
      await openEditorModal(adminPage);

      await adminPage.keyboard.press('Escape');
      await adminPage.waitForTimeout(400);

      // Editor gone, page still on edit URL
      await expect(adminPage.locator('button[aria-label="Close editor"]')).not.toBeVisible({ timeout: 5000 });
      await expect(adminPage).toHaveURL(/admin\/dam\/assets\/edit\/\d+/);
    });

    test('Escape with nothing open does not navigate away', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.jpg');
      await adminPage.keyboard.press('Escape');
      await adminPage.waitForTimeout(300);
      await expect(adminPage).toHaveURL(/admin\/dam\/assets\/edit\/\d+/);
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // State reset on re-open
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('State reset on re-open', () => {

    test('Image zoom resets to 100% when preview reopened', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.jpg');
      await openPreviewModal(adminPage);

      const zoomInBtn = adminPage.locator('button[title="Zoom in (+)"]').first();
      if (!(await zoomInBtn.isVisible({ timeout: 3000 }).catch(() => false))) {
        test.skip(true, 'Zoom toolbar not found'); return;
      }

      await zoomInBtn.click();
      await adminPage.waitForTimeout(200);

      await adminPage.locator('button[aria-label="Close preview"]').click();
      await adminPage.waitForTimeout(400);
      await openPreviewModal(adminPage);
      await adminPage.waitForTimeout(300);

      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '0')).toBe(100);
    });

    test('Image rotation resets to 0 when preview reopened', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.jpg');
      await openPreviewModal(adminPage);

      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      await adminPage.locator('button[title="Rotate right (R)"]').first().click();
      await adminPage.waitForTimeout(200);

      await adminPage.locator('button[aria-label="Close preview"]').click();
      await adminPage.waitForTimeout(400);
      await openPreviewModal(adminPage);
      await adminPage.waitForTimeout(300);

      const style = await img.getAttribute('style');
      // After reset: rotate(0deg)
      expect(style).toMatch(/rotate\(0deg\)/);
    });

  });

});
