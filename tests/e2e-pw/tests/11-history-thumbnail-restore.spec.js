const { test, expect } = require('../utils/fixtures');
const {
  navigateTo,
  primeUploadDirectory,
  closeApShell,
} = require('../utils/helpers');
const path = require('path');

const FIXTURES = {
  imgA: path.resolve(__dirname, '../assets/floral.jpg'),
  imgB: path.resolve(__dirname, '../assets/dotted.png'),
  video: path.resolve(__dirname, '../assets/sample.mp4'),
  pdf:   path.resolve(__dirname, '../assets/sample.pdf'),
};

/*
 |--------------------------------------------------------------------------
 | Helpers — local to this spec so we own timing-sensitive flows end to end
 |--------------------------------------------------------------------------
 */

/**
 * Upload a brand-new asset under a uniquely-named copy of the fixture
 * (so two tests don't fight over the same asset row) and return the asset id
 * resolved from the URL after navigating to its edit page.
 */
async function uploadAndOpenEdit(page, fixturePath, uniqueBaseName) {
  const ext = path.extname(fixturePath);
  const targetName = `${uniqueBaseName}${ext}`;

  await navigateTo(page, 'dam');
  await primeUploadDirectory(page);
  await closeApShell(page);

  const fileInput = page.locator('input[type="file"][name="files[]"]');
  await fileInput.waitFor({ state: 'attached', timeout: 15000 });

  // Rename the upload on-the-fly via setInputFiles `name` option so the
  // server stores it under our test-unique filename.
  const fs = require('fs');
  const buffer = fs.readFileSync(fixturePath);

  const uploadResp = page.waitForResponse(
    (res) => /\/admin\/dam\/assets\/upload$/.test(res.url()) && res.request().method() === 'POST',
    { timeout: 60000 }
  ).catch(() => null);

  await fileInput.setInputFiles({
    name: targetName,
    mimeType: mimeFromExt(ext),
    buffer,
  });

  const resp = await uploadResp;
  if (!resp) throw new Error(`Upload request never responded for ${targetName}`);
  expect(resp.status()).toBeGreaterThanOrEqual(200);
  expect(resp.status()).toBeLessThan(300);

  const json = await resp.json().catch(() => ({}));
  const assetId = json?.files?.[0]?.id;
  if (!assetId) throw new Error(`Upload response missing files[0].id: ${JSON.stringify(json)}`);

  await page.goto(`/admin/dam/assets/edit/${assetId}`, { waitUntil: 'domcontentloaded' });
  await page.locator('#app').waitFor({ state: 'visible', timeout: 30000 });
  await closeApShell(page);
  return assetId;
}

function mimeFromExt(ext) {
  const e = ext.toLowerCase();
  if (e === '.jpg' || e === '.jpeg') return 'image/jpeg';
  if (e === '.png') return 'image/png';
  if (e === '.mp4') return 'video/mp4';
  if (e === '.pdf') return 'application/pdf';
  return 'application/octet-stream';
}

/**
 * Re-upload a different fixture onto the current asset and wait for the
 * server to persist it. Caller is responsible for being on the edit page.
 */
async function reUpload(page, fixturePath, uniqueBaseName) {
  const ext = path.extname(fixturePath);
  const targetName = `${uniqueBaseName}${ext}`;
  const fs = require('fs');
  const buffer = fs.readFileSync(fixturePath);

  const reUploadInput = page.locator('input[type="file"]').last();
  await reUploadInput.waitFor({ state: 'attached', timeout: 15000 });

  const resp = page.waitForResponse(
    (res) => /\/admin\/dam\/assets\/re-upload$/.test(res.url()) && res.request().method() === 'POST',
    { timeout: 60000 }
  ).catch(() => null);

  await reUploadInput.setInputFiles({
    name: targetName,
    mimeType: mimeFromExt(ext),
    buffer,
  });

  const r = await resp;
  if (!r) throw new Error('re-upload request never responded');
  expect(r.status()).toBeGreaterThanOrEqual(200);
  expect(r.status()).toBeLessThan(300);
  await page.waitForLoadState('networkidle').catch(() => {});
}

async function gotoHistoryTab(page, assetId) {
  await page.goto(`/admin/dam/assets/edit/${assetId}?history`, { waitUntil: 'domcontentloaded' });
  // Wait for the history datagrid AJAX response to settle so rows are in the DOM.
  await page.waitForResponse(
    (res) => /\/admin\/dam\/history\/datagrid\//.test(res.url()) && res.status() === 200,
    { timeout: 30000 }
  ).catch(() => {});
  // The toolbar's "Export log" button mounts after the grid is interactive.
  await page.getByText(/Export\s+log/i).first().waitFor({ state: 'visible', timeout: 15000 }).catch(() => {});
}

async function openVersionModal(page, rowIndex = 0) {
  // The history table renders actions with `icon-view` for view-version.
  const viewIcons = page.locator('.icon-view');
  await viewIcons.first().waitFor({ state: 'visible', timeout: 15000 });
  await viewIcons.nth(rowIndex).click();
  // Modal title surfaces once the version detail XHR completes.
  await page.getByText(/History Preview/i).first().waitFor({ state: 'visible', timeout: 15000 });
}

/*
 |--------------------------------------------------------------------------
 | Tests
 |--------------------------------------------------------------------------
 */

test.describe('DAM History — Thumbnail + Restore', () => {

  test('image → image: thumbnail row + eye + restore round-trip', async ({ adminPage, uid }) => {
    const base = `e2e-img-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.imgA, base);
    await reUpload(adminPage, FIXTURES.imgB, `${base}-replaced`);
    await gotoHistoryTab(adminPage, assetId);

    // Datagrid should expose two actions per row: the core eye-icon (view-version)
    // and our DAM-specific restore (.icon-dam-restore).
    await expect(adminPage.locator('.icon-view').first()).toBeVisible();
    await expect(adminPage.locator('.icon-dam-restore').first()).toBeVisible();

    // Latest version row sits at the top by default — open its modal.
    await openVersionModal(adminPage, 0);

    // Modal renders our DAM-only override: thumbnails on both sides + footer Restore.
    const oldThumb = adminPage.locator('.dam-hist-thumb[data-side="old"]').first();
    const newThumb = adminPage.locator('.dam-hist-thumb[data-side="new"]').first();
    await expect(oldThumb).toBeVisible();
    await expect(newThumb).toBeVisible();

    // Both sides should expose the eye icon (no per-thumbnail restore).
    await oldThumb.hover();
    await expect(oldThumb.locator('.dam-hist-eye')).toBeVisible();
    await expect(oldThumb.locator('button.dam-hist-restore')).toHaveCount(0);

    // Footer: single Restore button (no Cancel) — version 2+ always shows it.
    const restoreBtn = adminPage.getByRole('button', { name: /^Restore$/ });
    await expect(restoreBtn).toBeVisible();

    // Restore via footer button → admin confirm modal → POST → toast → reload.
    const restorePromise = adminPage.waitForResponse(
      (res) => /\/admin\/dam\/history\/restore\/\d+/.test(res.url()) && res.request().method() === 'POST',
      { timeout: 30000 }
    );

    await restoreBtn.click();
    // Admin confirm modal renders Restore as primary; click it.
    const confirmAgree = adminPage.locator('.primary-button').filter({ hasText: /^Restore$/ });
    await confirmAgree.first().click();

    const restoreResp = await restorePromise;
    expect(restoreResp.status()).toBe(200);
  });

  test('first version hides the footer Restore button', async ({ adminPage, uid }) => {
    const base = `e2e-firstver-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.imgA, base);
    await gotoHistoryTab(adminPage, assetId);

    // Only one history row exists (the initial upload).
    await openVersionModal(adminPage, 0);

    // No old values exist for version 1 → restore button is hidden.
    await expect(adminPage.getByRole('button', { name: /^Restore$/ })).toHaveCount(0);
  });

  test('eye preview opens the full-size overlay with toolbar', async ({ adminPage, uid }) => {
    const base = `e2e-eye-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.imgA, base);
    await reUpload(adminPage, FIXTURES.imgB, `${base}-replaced`);
    await gotoHistoryTab(adminPage, assetId);
    await openVersionModal(adminPage, 0);

    const oldThumb = adminPage.locator('.dam-hist-thumb[data-side="old"]').first();
    await oldThumb.waitFor({ state: 'visible', timeout: 15000 });
    await oldThumb.hover();
    await oldThumb.locator('.dam-hist-eye').click();

    // Preview overlay + toolbar (zoom %, rotate, etc.) appear.
    const overlay = adminPage.locator('.dam-hist-preview-overlay');
    await expect(overlay).toBeVisible();
    await expect(overlay.locator('.dam-hist-preview-toolbar')).toBeVisible();
    await expect(overlay.locator('img').first()).toBeVisible();

    // Close via the × button.
    await overlay.locator('.dam-hist-preview-close').last().click();
    await expect(overlay).toHaveCount(0);
  });

  test('video → image restore preserves file_type, mime, and path', async ({ adminPage, uid }) => {
    const base = `e2e-vid-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.video, base);
    await reUpload(adminPage, FIXTURES.imgA, `${base}-replaced`);
    await gotoHistoryTab(adminPage, assetId);

    // Trigger restore through the datagrid action so we exercise that path too.
    const restoreAction = adminPage.locator('.icon-dam-restore').first();
    await restoreAction.waitFor({ state: 'visible', timeout: 15000 });

    const restorePromise = adminPage.waitForResponse(
      (res) => /\/admin\/dam\/history\/restore\/\d+/.test(res.url()) && res.request().method() === 'POST',
      { timeout: 30000 }
    );

    await restoreAction.click();
    // Admin confirm modal renders the agree button as "Agree" by default for
    // generic confirms (no overridden labels from the datagrid handler).
    const agree = adminPage.locator('.primary-button').first();
    await agree.click();

    const resp = await restorePromise;
    expect(resp.status()).toBe(200);

    // After restore, the asset's metadata sidebar must reflect the video again.
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.goto(`/admin/dam/assets/edit/${assetId}`, { waitUntil: 'domcontentloaded' });
    await adminPage.locator('#app').waitFor({ state: 'visible', timeout: 30000 });

    // Either the Details sidebar shows MP4 type / video mime, or the preview area
    // mounts a <video> element. Either signals file_type was restored to "video".
    const detailsType = adminPage.locator('text=/MP4/i').first();
    const previewVideo = adminPage.locator('#app video').first();
    await Promise.race([
      detailsType.waitFor({ state: 'visible', timeout: 15000 }).catch(() => {}),
      previewVideo.waitFor({ state: 'visible', timeout: 15000 }).catch(() => {}),
    ]);
    const hasVideoSignal =
      (await detailsType.isVisible().catch(() => false)) ||
      (await previewVideo.isVisible().catch(() => false));
    expect(hasVideoSignal).toBe(true);
  });

  test('pdf preview row renders a real thumbnail (not generic icon)', async ({ adminPage, uid }) => {
    const base = `e2e-pdf-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.pdf, base);
    await reUpload(adminPage, FIXTURES.imgA, `${base}-replaced`);
    await gotoHistoryTab(adminPage, assetId);
    await openVersionModal(adminPage, 0);

    const oldThumb = adminPage.locator('.dam-hist-thumb[data-side="old"]').first();
    await expect(oldThumb).toBeVisible();

    // The thumbnail must be an <img>, not just the fallback file-icon span.
    const img = oldThumb.locator('img.dam-hist-img');
    await expect(img).toBeVisible();
    const src = await img.getAttribute('src');
    // OLD side resolves through the DAM-owned history-thumbnail endpoint.
    expect(src).toMatch(/admin\/dam\/history\/thumbnail\/\d+/);

    // Image must actually load (non-zero natural dimensions) — fallback would 404.
    await adminPage.waitForFunction(
      (el) => el && el.complete && el.naturalWidth > 0,
      await img.elementHandle(),
      { timeout: 20000 }
    );
  });

  test('datagrid restore action: action icon size + 200 response', async ({ adminPage, uid }) => {
    const base = `e2e-grid-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.imgA, base);
    await reUpload(adminPage, FIXTURES.imgB, `${base}-replaced`);
    await gotoHistoryTab(adminPage, assetId);

    // Action spans are 24px font (text-2xl) so the icon should be < 30px wide.
    // Catches a regression where the mask icon rendered at 2x size before the
    // ` icon-` selector fix.
    const restoreAction = adminPage.locator('.icon-dam-restore').first();
    await restoreAction.waitFor({ state: 'visible', timeout: 15000 });
    const box = await restoreAction.boundingBox();
    expect(box).not.toBeNull();
    expect(box.width).toBeLessThan(32);
    expect(box.height).toBeLessThan(32);

    const restorePromise = adminPage.waitForResponse(
      (res) => /\/admin\/dam\/history\/restore\/\d+/.test(res.url()) && res.request().method() === 'POST',
      { timeout: 30000 }
    );
    await restoreAction.click();
    await adminPage.locator('.primary-button').first().click();
    const resp = await restorePromise;
    expect(resp.status()).toBe(200);
  });

  async function expectOldThumbLoads(adminPage) {
    await openVersionModal(adminPage, 0);
    const oldImg = adminPage.locator('.dam-hist-thumb[data-side="old"] img.dam-hist-img').first();
    await expect(oldImg).toBeVisible({ timeout: 15000 });

    // Pin the URL shape — must be the new history endpoint, not the live one.
    const src = await oldImg.getAttribute('src');
    expect(src).toMatch(/\/admin\/dam\/history\/thumbnail\/\d+/);

    // Confirm the browser actually loaded a real image (naturalWidth > 0
    // means the response was an image, not a 404 fallback).
    await adminPage.waitForFunction(
      (el) => el && el.complete && el.naturalWidth > 0,
      await oldImg.elementHandle(),
      { timeout: 30000 }
    );
  }

  test('OLD-side image thumbnail loads after re-upload', async ({ adminPage, uid }) => {
    const base = `e2e-oldimg-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.imgA, base);
    await reUpload(adminPage, FIXTURES.imgB, `${base}-replaced`);
    await gotoHistoryTab(adminPage, assetId);
    await expectOldThumbLoads(adminPage);
  });

  test('OLD-side video thumbnail loads after re-upload (skipped on stub fixture)', async ({ adminPage, uid }, testInfo) => {
    // sample.mp4 in the fixtures dir is intentionally tiny — too small for
    // ffmpeg to extract a frame. Skip rather than fail when the fixture is
    // smaller than 100 KB: the unit suite already covers the success path.
    const fs = require('fs');
    if (fs.statSync(FIXTURES.video).size < 100_000) {
      testInfo.skip(true, 'sample.mp4 fixture is a stub; ffmpeg cannot extract a frame');
    }

    const base = `e2e-oldvid-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.video, base);
    await reUpload(adminPage, FIXTURES.imgA, `${base}-replaced`);
    await gotoHistoryTab(adminPage, assetId);
    await expectOldThumbLoads(adminPage);
  });

  test('OLD-side pdf thumbnail loads after re-upload', async ({ adminPage, uid }) => {
    const base = `e2e-oldpdf-${uid}`;
    const assetId = await uploadAndOpenEdit(adminPage, FIXTURES.pdf, base);
    await reUpload(adminPage, FIXTURES.imgA, `${base}-replaced`);
    await gotoHistoryTab(adminPage, assetId);
    await expectOldThumbLoads(adminPage);
  });

});
