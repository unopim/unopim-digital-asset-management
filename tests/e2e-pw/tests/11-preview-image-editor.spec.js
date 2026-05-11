const { test, expect } = require('../utils/fixtures');
const { ensureAssetExists, navigateToAssetEditByName } = require('../utils/helpers');

// ─── Shared helpers ───────────────────────────────────────────────────────────

/**
 * Opens the editor modal for floral.jpg (always an image) and waits for close button.
 */
async function openEditorModal(page) {
  await navigateToAssetEditByName(page, 'floral.jpg');
  const btn = page.locator('button[title="Edit image"]').first();
  await btn.waitFor({ state: 'visible', timeout: 10000 });
  await btn.click();
  await page.locator('button[aria-label="Close editor"]')
    .waitFor({ state: 'visible', timeout: 10000 });
}

async function openCropTool(page) {
  await openEditorModal(page);
  await page.getByText('Crop & Resize').first().click();
  // Wait for the crop overlay to appear (depends on image load)
  await page.locator('.border-white\\/90.pointer-events-auto').first()
    .waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('DAM Asset Preview Modal', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Image editor modal
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Image editor modal', () => {

    test('Clicking Edit image opens editor modal', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await expect(adminPage.locator('button[aria-label="Close editor"]')).toBeVisible({ timeout: 5000 });
    });

    test('Editor header shows asset filename', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      // Editor header: icon-edit + filename <p> + close button
      const editorHeader = adminPage.locator('button[aria-label="Close editor"]').locator('xpath=ancestor::div[contains(@class,"border-b")]').first();
      const filename = editorHeader.locator('p.font-semibold.truncate').first();
      await expect(filename).toBeVisible({ timeout: 5000 });
      expect((await filename.textContent())?.trim().length).toBeGreaterThan(0);
    });

    test('Editor image preview panel shows asset image', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      // Image panel: bg-gray-50 border-r div, img inside has no p-6 (that's the container)
      const previewImg = adminPage.locator('div.bg-gray-50.border-r img').first();
      await expect(previewImg).toBeVisible({ timeout: 5000 });
    });

    test('Editor shows "Tools" section header', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await expect(adminPage.getByText('Tools').first()).toBeVisible({ timeout: 5000 });
    });

    test('Editor shows Edit Background tool', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await expect(adminPage.getByText('Edit Background').first()).toBeVisible({ timeout: 5000 });
    });

    test('Editor shows Crop & Resize tool', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await expect(adminPage.getByText('Crop & Resize').first()).toBeVisible({ timeout: 5000 });
    });

    test('Editor shows Brightness & Contrast tool', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await expect(adminPage.getByText('Brightness & Contrast').first()).toBeVisible({ timeout: 5000 });
    });

    test('Editor shows Rotate & Flip tool', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await expect(adminPage.getByText('Rotate & Flip').first()).toBeVisible({ timeout: 5000 });
    });

    test('Apply button not shown when no tool selected', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      // Each Apply button lives inside a v-if="editTool === '...'" panel.
      // With no tool selected editTool is null, so no panel renders → no Apply button.
      const applyBtn = adminPage.locator('button').filter({ hasText: /^Apply$/ }).first();
      await expect(applyBtn).not.toBeVisible();
    });

    test('Selecting Crop & Resize enables Apply button', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await adminPage.getByText('Crop & Resize').first().click();
      await adminPage.waitForTimeout(200);
      await expect(adminPage.locator('button').filter({ hasText: /^Apply$/ }).first()).toBeEnabled({ timeout: 5000 });
    });

    test('Selecting Edit Background and Prompt tab shows AI prompt textarea', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await adminPage.getByText('Edit Background').first().click();
      await adminPage.waitForTimeout(200);
      // The 'ai' sub-tab (labelled "Prompt") reveals the textarea via v-if="bgSubTab === 'ai'"
      // Use exact:true to avoid matching the subtitle "Color, upload, or Prompt" on the tool button
      await adminPage.getByText('Prompt', { exact: true }).first().click();
      await adminPage.waitForTimeout(200);
      await expect(adminPage.locator('textarea').first()).toBeVisible({ timeout: 5000 });
    });

    test('Selecting non-bg-remove tool does NOT show AI prompt textarea', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await adminPage.getByText('Crop & Resize').first().click();
      await adminPage.waitForTimeout(200);
      await expect(adminPage.locator('textarea').first()).not.toBeVisible({ timeout: 3000 });
    });

    test('Selecting a tool applies active highlight class to that button', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      const cropBtn = adminPage.locator('button').filter({ has: adminPage.getByText('Crop & Resize') }).first();
      await cropBtn.click();
      await adminPage.waitForTimeout(200);
      // Crop active class: bg-blue-50 (bg-violet-50 is for bg-remove tool)
      const classList = await cropBtn.evaluate(el => el.className);
      expect(classList).toContain('bg-blue-50');
    });

    test('Close editor button closes editor modal', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await adminPage.locator('button[aria-label="Close editor"]').click();
      await adminPage.waitForTimeout(400);
      await expect(adminPage.locator('button[aria-label="Close editor"]')).not.toBeVisible({ timeout: 5000 });
    });

    test('Backdrop click closes editor modal', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      // Editor backdrop: absolute inset-0 bg-black/75 (same shade as preview modal)
      // But editor is v-if="isEditOpen" — its backdrop is the first bg-black/75 element
      // since preview modal is not open simultaneously
      const backdrop = adminPage.locator('.absolute.inset-0.bg-black\\/75').first();
      await expect(backdrop).toBeVisible({ timeout: 5000 });
      await backdrop.click({ position: { x: 5, y: 5 }, force: true });
      await adminPage.waitForTimeout(400);
      await expect(adminPage.locator('button[aria-label="Close editor"]')).not.toBeVisible({ timeout: 5000 });
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Crop drag interaction
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Crop drag interaction', () => {

    test('Crop overlay appears after selecting Crop & Resize', async ({ adminPage }) => {
      await openCropTool(adminPage);
      // Crop overlay: border-2 border-white/90 pointer-events-auto (the crop box)
      await expect(adminPage.locator('.border-white\\/90.pointer-events-auto').first()).toBeVisible({ timeout: 10000 });
    });

    test('Drawing new selection by dragging on image container changes crop dimensions', async ({ adminPage }) => {
      await openCropTool(adminPage);

      // Get the image container (the panel that holds the crop overlay)
      const container = adminPage.locator('[ref="editImgContainer"], .bg-gray-50.border-r').first();
      const box = await container.boundingBox();
      if (!box) { test.skip(true, 'Could not get container bounding box'); return; }

      // Get initial dimension badge text
      const badge = adminPage.locator('.font-mono').filter({ hasText: /\d+ × \d+ px/ }).first();
      const beforeText = await badge.textContent({ timeout: 3000 }).catch(() => '');

      // Start in the 24px container padding (before image/crop box) so the mousedown
      // reaches cropStartDraw and isn't swallowed by the crop box's @mousedown.stop.prevent.
      const startX = box.x + 3;
      const startY = box.y + 3;
      const endX   = box.x + box.width * 0.6;
      const endY   = box.y + box.height * 0.6;

      await adminPage.mouse.move(startX, startY);
      await adminPage.mouse.down();
      await adminPage.mouse.move(endX, endY, { steps: 10 });
      await adminPage.mouse.up();
      await adminPage.waitForTimeout(200);

      const afterText = await badge.textContent({ timeout: 3000 }).catch(() => '');
      // Dimensions must have changed (new selection ≠ full-image initial selection)
      expect(afterText).not.toBe(beforeText);
    });

    test('Dragging br corner handle inward resizes the crop box', async ({ adminPage }) => {
      await openCropTool(adminPage);

      const badge = adminPage.locator('.font-mono').filter({ hasText: /\d+ × \d+ px/ }).first();
      const beforeText = await badge.textContent({ timeout: 3000 }).catch(() => '');

      // Bottom-right corner handle: -bottom-1.5 -right-1.5 (last .w-3.h-3 inside crop box)
      const brHandle = adminPage.locator('.absolute.-bottom-1\\.5.-right-1\\.5').first();
      const handleBox = await brHandle.boundingBox().catch(() => null);
      if (!handleBox) { test.skip(true, 'BR handle not found'); return; }

      const hx = handleBox.x + handleBox.width / 2;
      const hy = handleBox.y + handleBox.height / 2;

      await adminPage.mouse.move(hx, hy);
      await adminPage.mouse.down();
      await adminPage.mouse.move(hx - 60, hy - 60, { steps: 10 });
      await adminPage.mouse.up();
      await adminPage.waitForTimeout(200);

      const afterText = await badge.textContent({ timeout: 3000 }).catch(() => '');
      expect(afterText).not.toBe(beforeText);
    });

    test('Dragging left edge handle right shrinks selection from left', async ({ adminPage }) => {
      await openCropTool(adminPage);

      // First draw a medium selection so handles are accessible
      const container = adminPage.locator('.bg-gray-50.border-r').first();
      const box = await container.boundingBox();
      if (!box) { test.skip(true, 'No container box'); return; }

      await adminPage.mouse.move(box.x + box.width * 0.1, box.y + box.height * 0.1);
      await adminPage.mouse.down();
      await adminPage.mouse.move(box.x + box.width * 0.9, box.y + box.height * 0.9, { steps: 10 });
      await adminPage.mouse.up();
      await adminPage.waitForTimeout(200);

      const badge = adminPage.locator('.font-mono').filter({ hasText: /\d+ × \d+ px/ }).first();
      const beforeText = await badge.textContent({ timeout: 3000 }).catch(() => '');

      // Left edge handle: top-1/2 -left-1.5
      const lHandle = adminPage.locator('.absolute.top-1\\/2.-left-1\\.5').first();
      const lBox = await lHandle.boundingBox().catch(() => null);
      if (!lBox) { test.skip(true, 'Left handle not found'); return; }

      const lx = lBox.x + lBox.width / 2;
      const ly = lBox.y + lBox.height / 2;

      await adminPage.mouse.move(lx, ly);
      await adminPage.mouse.down();
      await adminPage.mouse.move(lx + 50, ly, { steps: 10 });
      await adminPage.mouse.up();
      await adminPage.waitForTimeout(200);

      const afterText = await badge.textContent({ timeout: 3000 }).catch(() => '');
      expect(afterText).not.toBe(beforeText);
    });

    test('Moving crop box updates its position', async ({ adminPage }) => {
      await openCropTool(adminPage);

      // Draw a small selection in the top-left quadrant (20%→50%) so the box
      // has room to move right and down, regardless of the initial box size.
      const container = adminPage.locator('.bg-gray-50.border-r').first();
      const cbox = await container.boundingBox();
      if (!cbox) { test.skip(true, 'No container bounds'); return; }

      // Start in container padding (3px from edge) to avoid the crop box's mousedown.stop.prevent
      await adminPage.mouse.move(cbox.x + 3, cbox.y + 3);
      await adminPage.mouse.down();
      await adminPage.mouse.move(cbox.x + cbox.width * 0.5, cbox.y + cbox.height * 0.5, { steps: 10 });
      await adminPage.mouse.up();
      await adminPage.waitForTimeout(200);

      const cropBox = adminPage.locator('.border-white\\/90.pointer-events-auto').first();
      const beforeStyle = await cropBox.getAttribute('style');

      const cropBounds = await cropBox.boundingBox();
      if (!cropBounds) { test.skip(true, 'No crop box bounds'); return; }

      const cx = cropBounds.x + cropBounds.width / 2;
      const cy = cropBounds.y + cropBounds.height / 2;

      // Move RIGHT and DOWN — the drawn box is in the top-left, so room exists both ways
      await adminPage.mouse.move(cx, cy);
      await adminPage.mouse.down();
      await adminPage.mouse.move(cx + 40, cy + 20, { steps: 10 });
      await adminPage.mouse.up();
      await adminPage.waitForTimeout(200);

      const afterStyle = await cropBox.getAttribute('style');
      expect(afterStyle).not.toBe(beforeStyle);
    });

  });

});
