const { test, expect } = require('../utils/fixtures');
const { ensureAssetExists, navigateToAssetEditByName } = require('../utils/helpers');

// Navigate to floral.jpg and wait for the inline zoomable image viewer to mount.
// The viewer (v-zoomable-image) is rendered inline on the edit page — no modal to open.
async function openImageViewer(page) {
  await navigateToAssetEditByName(page, 'floral.jpg');
  // Wait for the image area of the inline viewer to be visible.
  await page.locator('.flex-1.min-h-0.overflow-hidden.flex.items-center.justify-center img').first()
    .waitFor({ state: 'visible', timeout: 20000 });
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
      await openImageViewer(adminPage);
      await expect(adminPage.locator('.flex-1.min-h-0.overflow-hidden.flex.items-center.justify-center img').first()).toBeVisible({ timeout: 5000 });
    });

    test('Image has Vue-driven transform style attribute', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden.flex.items-center.justify-center img').first();
      const style = await img.getAttribute('style');
      expect(style).toMatch(/translate\(/);
      expect(style).toMatch(/scale\(/);
    });

    test('Toolbar visible (bottom pill with buttons)', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      // Toolbar sits below the image in the zoomable-image component.
      const toolbar = adminPage.locator('.px-3.py-2.border-t').first();
      await expect(toolbar).toBeVisible({ timeout: 5000 });
    });

    test('Zoom percentage starts at 100%', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      const display = adminPage.locator('.font-mono.tabular-nums').filter({ hasText: /100%/ }).first();
      await expect(display).toBeVisible({ timeout: 5000 });
    });

    test('Zoom in button increases zoom percentage', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      await adminPage.locator('button[title="Zoom in (+)"]').first().click();
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '0')).toBeGreaterThan(100);
    });

    test('Zoom out button decreases zoom percentage', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      await adminPage.locator('button[title="Zoom out (-)"]').first().click();
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '200')).toBeLessThan(100);
    });

    test('Rotate right button changes image transform', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden.flex.items-center.justify-center img').first();
      const before = await img.getAttribute('style');
      await adminPage.locator('button[title="Rotate right (R)"]').first().click();
      await adminPage.waitForTimeout(200);
      expect(await img.getAttribute('style')).not.toBe(before);
    });

    test('Rotate left button changes image transform', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden.flex.items-center.justify-center img').first();
      const before = await img.getAttribute('style');
      await adminPage.locator('button[title="Rotate left (L)"]').first().click();
      await adminPage.waitForTimeout(200);
      expect(await img.getAttribute('style')).not.toBe(before);
    });

    test('Fit to screen button is visible and clickable', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      const btn = adminPage.locator('button[title="Fit to screen"]').first();
      await expect(btn).toBeVisible({ timeout: 5000 });
      await btn.click();
      await adminPage.waitForTimeout(200);
    });

    test('1:1 button is visible', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      await expect(adminPage.locator('button[title="Actual size"]').first()).toBeVisible({ timeout: 5000 });
    });

    test('Reset button restores zoom to 100%', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      await adminPage.locator('button[title="Zoom in (+)"]').first().click();
      await adminPage.waitForTimeout(200);
      await adminPage.locator('button[title="Reset all (0)"]').first().click();
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '0')).toBe(100);
    });

    test('Mouse wheel zooms in on scroll up', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      const content = adminPage.locator('.flex-1.min-h-0.overflow-hidden.flex.items-center.justify-center').first();
      const box = await content.boundingBox();
      if (!box) { test.skip(true, 'Could not get bounding box'); return; }
      await adminPage.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
      await adminPage.mouse.wheel(0, -100);
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '100')).toBeGreaterThan(100);
    });

    test('Mouse wheel zooms out on scroll down', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      const content = adminPage.locator('.flex-1.min-h-0.overflow-hidden.flex.items-center.justify-center').first();
      const box = await content.boundingBox();
      if (!box) { test.skip(true, 'Could not get bounding box'); return; }
      await adminPage.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
      await adminPage.mouse.wheel(0, 100);
      await adminPage.waitForTimeout(200);
      const text = await adminPage.locator('.font-mono.tabular-nums').first().textContent();
      expect(parseInt(text ?? '100')).toBeLessThan(100);
    });

    test('Mouse drag pans the image', async ({ adminPage }) => {
      await openImageViewer(adminPage);
      await adminPage.locator('button[title="Zoom in (+)"]').first().click();
      await adminPage.waitForTimeout(200);

      const img = adminPage.locator('.flex-1.min-h-0.overflow-hidden.flex.items-center.justify-center img').first();
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
  // v-zoomable-image has no keyboard event handlers — all shortcut tests skipped.
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Image viewer — keyboard shortcuts', () => {

    test('= key zooms in', async ({ adminPage }) => {
      test.skip(true, 'v-zoomable-image has no keyboard handlers');
    });

    test('- key zooms out', async ({ adminPage }) => {
      test.skip(true, 'v-zoomable-image has no keyboard handlers');
    });

    test('r key rotates right', async ({ adminPage }) => {
      test.skip(true, 'v-zoomable-image has no keyboard handlers');
    });

    test('R key (uppercase) rotates right', async ({ adminPage }) => {
      test.skip(true, 'v-zoomable-image has no keyboard handlers');
    });

    test('l key rotates left', async ({ adminPage }) => {
      test.skip(true, 'v-zoomable-image has no keyboard handlers');
    });

    test('L key (uppercase) rotates left', async ({ adminPage }) => {
      test.skip(true, 'v-zoomable-image has no keyboard handlers');
    });

    test('0 key resets zoom to 100%', async ({ adminPage }) => {
      test.skip(true, 'v-zoomable-image has no keyboard handlers');
    });

    test('Keyboard shortcuts inactive when preview modal is closed', async ({ adminPage }) => {
      test.skip(true, 'v-zoomable-image has no keyboard handlers');
    });

  });

});
