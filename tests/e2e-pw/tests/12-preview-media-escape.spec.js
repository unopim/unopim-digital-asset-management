const path = require('path');
const { test, expect } = require('../utils/fixtures');
const { ensureAssetExists, ensureAssetOfTypeExists, navigateTo, searchInDataGrid, closeApShell } = require('../utils/helpers');

const ASSETS = path.resolve(__dirname, '../assets');

// Navigate to the edit page of the first asset whose filename contains `ext`.
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
  await closeApShell(page);
  await card.hover({ force: true });
  await page.waitForTimeout(300);
  await card.locator('.icon-edit').first().click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

async function openInfoModal(page) {
  const btn = page.locator('button').filter({ has: page.locator('.icon-information') }).first();
  await btn.waitFor({ state: 'visible', timeout: 20000 });
  await btn.click();
  await page.locator('.absolute.inset-0.bg-black\\/60').first()
    .waitFor({ state: 'visible', timeout: 20000 });
}

async function openEditorModal(page) {
  await navigateToFirstAssetWithExt(page, '.jpg');
  const btn = page.locator('button[title="Edit image"]').first();
  await btn.waitFor({ state: 'visible', timeout: 10000 });
  await btn.click();
  await page.locator('button[aria-label="Close editor"]')
    .waitFor({ state: 'visible', timeout: 10000 });
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('DAM Asset Preview — Inline Media & Escape Key', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
    await ensureAssetOfTypeExists(adminPage, `${ASSETS}/sample.mp4`, '.mp4');
    await ensureAssetOfTypeExists(adminPage, `${ASSETS}/sample.wav`, '.wav');
    await ensureAssetOfTypeExists(adminPage, `${ASSETS}/sample.pdf`, '.pdf');
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Video player — inline
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Video player', () => {

    test('Video element renders inline on asset edit page', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.mp4');
      await expect(adminPage.locator('.flex.items-center.justify-center.w-full.rounded-lg.overflow-hidden video').first()).toBeVisible({ timeout: 15000 });
    });

    test('Speed selector buttons visible for inline video', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.mp4');
      await expect(adminPage.locator('button').filter({ hasText: '1×' }).first()).toBeVisible({ timeout: 10000 });
    });

    test('Skip back 10s button visible (title=Back 10s)', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.mp4');
      await expect(adminPage.locator('button[title="Back 10s"]').first()).toBeVisible({ timeout: 10000 });
    });

    test('Skip forward 10s button visible (title=Forward 10s)', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.mp4');
      await expect(adminPage.locator('button[title="Forward 10s"]').first()).toBeVisible({ timeout: 10000 });
    });

    test('1× speed button is active by default', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.mp4');
      const oneX = adminPage.locator('button').filter({ hasText: /^1×$/ }).first();
      await oneX.waitFor({ state: 'visible', timeout: 10000 });
      const cls = await oneX.evaluate(el => el.className);
      expect(cls).toContain('bg-violet-600');
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Audio player — inline
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Audio player', () => {

    test('Play/pause button visible for inline audio', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.wav');
      await expect(adminPage.locator('button.w-14.h-14.rounded-full').first()).toBeVisible({ timeout: 10000 });
    });

    test('Seek bar visible for inline audio', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.wav');
      await expect(adminPage.locator('.relative.h-4.group.cursor-pointer').first()).toBeVisible({ timeout: 10000 });
    });

    test('Volume slider visible for inline audio', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.wav');
      await expect(adminPage.locator('input[type="range"].w-20').first()).toBeVisible({ timeout: 10000 });
    });

    test('Current time display starts at 0:00', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.wav');
      await expect(adminPage.locator('.font-mono.tabular-nums').filter({ hasText: '0:00' }).first()).toBeVisible({ timeout: 10000 });
    });

    test('Skip back 10s button visible (title=Back 10s)', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.wav');
      await expect(adminPage.locator('button[title="Back 10s"]').first()).toBeVisible({ timeout: 10000 });
    });

    test('Skip forward 10s button visible (title=Forward 10s)', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.wav');
      await expect(adminPage.locator('button[title="Forward 10s"]').first()).toBeVisible({ timeout: 10000 });
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // PDF / fallback — inline
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('PDF / fallback asset', () => {

    test('PDF renders an iframe or fallback content inline', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.pdf');
      const container = adminPage.locator('.flex.items-center.justify-center.w-full.rounded-lg.overflow-hidden').first();
      await expect(container).toBeVisible({ timeout: 15000 });
      await adminPage.waitForTimeout(500);
      const hasIframe   = await container.locator('iframe').first().isVisible().catch(() => false);
      const hasFallback = await container.locator('img').first().isVisible().catch(() => false);
      expect(hasIframe || hasFallback).toBeTruthy();
    });

  });

  // ═══════════════════════════════════════════════════════════════════════════
  // Escape key priority
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Escape key priority', () => {

    test('Escape closes info modal', async ({ adminPage }) => {
      await navigateToFirstAssetWithExt(adminPage, '.jpg');
      await openInfoModal(adminPage);

      await adminPage.keyboard.press('Escape');
      await adminPage.waitForTimeout(400);

      await expect(adminPage.locator('.absolute.inset-0.bg-black\\/60').first()).not.toBeVisible({ timeout: 5000 });
    });

    test('Escape closes editor modal', async ({ adminPage }) => {
      await openEditorModal(adminPage);

      await adminPage.keyboard.press('Escape');
      await adminPage.waitForTimeout(400);

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
  // State reset on re-open (editor modal)
  // ═══════════════════════════════════════════════════════════════════════════

  test.describe('Editor modal state', () => {

    test('Editor modal opens and closes without errors', async ({ adminPage }) => {
      await openEditorModal(adminPage);
      await expect(adminPage.locator('button[aria-label="Close editor"]')).toBeVisible({ timeout: 5000 });

      await adminPage.locator('button[aria-label="Close editor"]').click();
      await adminPage.waitForTimeout(400);
      await expect(adminPage.locator('button[aria-label="Close editor"]')).not.toBeVisible({ timeout: 5000 });
    });

  });

});
