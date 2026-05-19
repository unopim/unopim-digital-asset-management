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

/**
 * Navigate to floral.jpg (always an image) and open the preview modal.
 */
async function openImagePreview(page) {
  await navigateToAssetEditByName(page, 'floral.jpg');
  await openPreviewModal(page);
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('DAM Asset Preview Modal', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Image viewer — toolbar buttons
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Image viewer', () => {

    test('Image renders in modal content area', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      await expect(adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first()).toBeVisible({ timeout: 5000 });
    });

    test('Image has Vue-driven transform style attribute', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      const style = await img.getAttribute('style');
      // imgTransformStyle = "translate(Xpx,Ypx) scale(Z) rotate(Ddeg)"
      expect(style).toMatch(/translate\(/);
      expect(style).toMatch(/scale\(/);
    });

    test('Toolbar visible (bottom pill with buttons)', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      // Toolbar: absolute bottom-3, left-1/2, rounded-full, bg-black/60
      const toolbar = adminPage.locator('.absolute.bottom-3.rounded-full').first();
      await expect(toolbar).toBeVisible({ timeout: 5000 });
    });

    test('Zoom percentage starts at 100%', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const display = adminPage.locator('.font-mono.tabular-nums').filter({ hasText: /100%/ }).first();
      await expect(display).toBeVisible({ timeout: 5000 });
    });

    test('Zoom in button increases zoom percentage', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      await adminPage.locator('button[title="Zoom in (+)"]').first().click();
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '0')).toBeGreaterThan(100);
    });

    test('Zoom out button decreases zoom percentage', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      await adminPage.locator('button[title="Zoom out (-)"]').first().click();
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '200')).toBeLessThan(100);
    });

    test('Rotate right button changes image transform', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      const before = await img.getAttribute('style');
      await adminPage.locator('button[title="Rotate right (R)"]').first().click();
      await adminPage.waitForTimeout(200);
      expect(await img.getAttribute('style')).not.toBe(before);
    });

    test('Rotate left button changes image transform', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      const before = await img.getAttribute('style');
      await adminPage.locator('button[title="Rotate left (L)"]').first().click();
      await adminPage.waitForTimeout(200);
      expect(await img.getAttribute('style')).not.toBe(before);
    });

    test('Fit to screen button is visible and clickable', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const btn = adminPage.locator('button[title="Fit to screen"]').first();
      await expect(btn).toBeVisible({ timeout: 5000 });
      await btn.click();
      await adminPage.waitForTimeout(200);
    });

    test('1:1 button is visible', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      await expect(adminPage.locator('button[title="Actual size"]').first()).toBeVisible({ timeout: 5000 });
    });

    test('Reset button restores zoom to 100%', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      await adminPage.locator('button[title="Zoom in (+)"]').first().click();
      await adminPage.waitForTimeout(200);
      await adminPage.locator('button[title="Reset all (0)"]').first().click();
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '0')).toBe(100);
    });

    test('Mouse wheel zooms in on scroll up', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const content = adminPage.locator('.flex-1.min-h-0.overflow-hidden').first();
      const box = await content.boundingBox();
      if (!box) { test.skip(true, 'Could not get bounding box'); return; }
      await adminPage.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
      // deltaY < 0 → zoom in (factor 1.1)
      await adminPage.mouse.wheel(0, -100);
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '100')).toBeGreaterThan(100);
    });

    test('Mouse wheel zooms out on scroll down', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const content = adminPage.locator('.flex-1.min-h-0.overflow-hidden').first();
      const box = await content.boundingBox();
      if (!box) { test.skip(true, 'Could not get bounding box'); return; }
      await adminPage.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
      // deltaY > 0 → zoom out (factor 0.9)
      await adminPage.mouse.wheel(0, 100);
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '100')).toBeLessThan(100);
    });

    test('Mouse drag pans the image', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      // Zoom in so drag is meaningful
      await adminPage.locator('button[title="Zoom in (+)"]').first().click();
      await adminPage.waitForTimeout(200);

      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      const box = await img.boundingBox();
      if (!box) { test.skip(true, 'Could not get image bounding box'); return; }

      const cx = box.x + box.width / 2;
      const cy = box.y + box.height / 2;
      const beforeStyle = await img.getAttribute('style');

      await adminPage.mouse.move(cx, cy);
      await adminPage.mouse.down();
      await adminPage.mouse.move(cx + 80, cy + 40);
      await adminPage.mouse.up();
      await adminPage.waitForTimeout(200);

      const afterStyle = await img.getAttribute('style');
      expect(afterStyle).not.toBe(beforeStyle);
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Image viewer — keyboard shortcuts
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Image viewer — keyboard shortcuts', () => {

    test('= key zooms in', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      await adminPage.keyboard.press('Equal');
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '0')).toBeGreaterThan(100);
    });

    test('- key zooms out', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      await adminPage.keyboard.press('Minus');
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '200')).toBeLessThan(100);
    });

    test('r key rotates right', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      const before = await img.getAttribute('style');
      await adminPage.keyboard.press('r');
      await adminPage.waitForTimeout(200);
      expect(await img.getAttribute('style')).not.toBe(before);
    });

    test('R key (uppercase) rotates right', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      const before = await img.getAttribute('style');
      await adminPage.keyboard.press('R');
      await adminPage.waitForTimeout(200);
      expect(await img.getAttribute('style')).not.toBe(before);
    });

    test('l key rotates left', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      const before = await img.getAttribute('style');
      await adminPage.keyboard.press('l');
      await adminPage.waitForTimeout(200);
      expect(await img.getAttribute('style')).not.toBe(before);
    });

    test('L key (uppercase) rotates left', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden img').first();
      const before = await img.getAttribute('style');
      await adminPage.keyboard.press('L');
      await adminPage.waitForTimeout(200);
      expect(await img.getAttribute('style')).not.toBe(before);
    });

    test('0 key resets zoom to 100%', async ({ adminPage }) => {
      await openImagePreview(adminPage);
      await adminPage.keyboard.press('Equal'); // zoom in first
      await adminPage.waitForTimeout(200);
      await adminPage.keyboard.press('0');
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '0')).toBe(100);
    });

    test('Keyboard shortcuts inactive when preview modal is closed', async ({ adminPage }) => {
      await navigateToAssetEditByName(adminPage, 'floral.jpg');
      // Press = without opening the preview modal — page must not change
      await adminPage.keyboard.press('Equal');
      await adminPage.waitForTimeout(200);
      await expect(adminPage).toHaveURL(/admin\/dam\/assets\/edit\/\d+/);
    });

  });

});
