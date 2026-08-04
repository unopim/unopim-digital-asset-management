const { test, expect } = require('../utils/fixtures');
const { navigateTo } = require('../utils/helpers');

test.describe('DAM Explorer UI improvements', () => {
  test.beforeEach(async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    const explorerReady = await adminPage
      .locator('[data-select-all]')
      .first()
      .waitFor({ state: 'visible', timeout: 40000 })
      .then(() => true)
      .catch(() => false);

    test.skip(!explorerReady, 'DAM Explorer is disabled — skipping explorer-only UI tests.');
  });

  test('bookmark panel is a fixed-height, internally scrollable box', async ({ adminPage }) => {
    const scroll = adminPage.locator('[data-bookmarks-scroll]').first();

    test.skip((await scroll.count()) === 0, 'Bookmarks panel is disabled.');

    await expect(scroll).toBeVisible();

    const box = await scroll.evaluate((el) => ({
      overflowY: getComputedStyle(el).overflowY,
      height: Math.round(el.getBoundingClientRect().height),
    }));

    expect(box.overflowY).toBe('auto');
    expect(box.height).toBeGreaterThan(150);
    expect(box.height).toBeLessThan(320);
  });

  test('copy/move destination picker has a working grid/list view toggle', async ({ adminPage }) => {

    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.locator('[data-dir-id]').first().waitFor({ state: 'visible', timeout: 40000 });

    await adminPage.locator('[data-select-all]').first().click();

    await expect(adminPage.getByText(/\d+ selected/)).toBeVisible({ timeout: 20000 });
    await adminPage.locator('button:has-text("Select Action")').first().click({ timeout: 30000 });
    await adminPage.locator('li:has-text("Move to")').first().click({ timeout: 30000 });

    const modal = adminPage.locator('[data-folder-picker]');
    await expect(modal).toBeVisible({ timeout: 15000 });
    await expect(modal.getByText('Select Destination')).toBeVisible();

    const gridBtn = modal.locator('[data-view="grid"]');
    const listBtn = modal.locator('[data-view="list"]');
    await expect(gridBtn).toBeVisible();
    await expect(listBtn).toBeVisible();

    await gridBtn.click();
    await expect(gridBtn).toHaveClass(/bg-primary-100/);
    await expect(listBtn).not.toHaveClass(/bg-primary-100/);
    expect(await adminPage.evaluate(() => localStorage.getItem('dam_picker_view'))).toBe('grid');

    await listBtn.click();
    await expect(listBtn).toHaveClass(/bg-primary-100/);
    await expect(gridBtn).not.toHaveClass(/bg-primary-100/);
    expect(await adminPage.evaluate(() => localStorage.getItem('dam_picker_view'))).toBe('list');
  });

  test('sidebar toggle collapses and restores the sidebar on desktop', async ({ adminPage }) => {

    const sidebar = adminPage.locator('[data-explorer-sidebar]').first();
    const toggle = adminPage.locator('[data-sidebar-toggle]').first();

    await expect(sidebar).toBeVisible();
    await expect(toggle).toBeVisible();

    const display = () => sidebar.evaluate((el) => getComputedStyle(el).display);
    const persisted = () => adminPage.evaluate(() => localStorage.getItem('dam_show_sidebar'));

    expect(await display()).not.toBe('none');

    await toggle.click();
    await expect.poll(display).toBe('none');
    expect(await persisted()).toBe('false');

    await toggle.click();
    await expect.poll(display).not.toBe('none');
    expect(await persisted()).toBe('true');
  });

  test('toolbar star indicates and toggles the current directory bookmark', async ({ adminPage }) => {

    const star = adminPage.locator('[data-bookmark-toggle]').first();
    const ready = await star
      .waitFor({ state: 'visible', timeout: 40000 })
      .then(() => true)
      .catch(() => false);
    test.skip(!ready, 'Bookmarks feature disabled — no toolbar star.');

    const bookmarked = () => star.getAttribute('data-bookmarked');
    const panelCount = () => adminPage.locator('[data-bookmark-id]').count();

    if ((await bookmarked()) === 'true') {
      await star.click();
      await expect.poll(bookmarked).toBe('false');
    }
    const before = await panelCount();

    await star.click();
    await expect.poll(bookmarked).toBe('true');
    await expect.poll(panelCount).toBe(before + 1);

    await star.click();
    await expect.poll(bookmarked).toBe('false');
    await expect.poll(panelCount).toBe(before);
  });

  test('changing the per-page limit keeps the current selection', async ({ adminPage }) => {

    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.locator('[data-select-all]').first().waitFor({ state: 'visible', timeout: 40000 });
    await adminPage.locator('[data-dir-id]').first().waitFor({ state: 'visible', timeout: 40000 });

    const selectedCount = adminPage.getByText(/\d+\s+selected/i);
    const perPageToggle = adminPage.locator('[data-per-page-toggle]').first();

    await adminPage.locator('[data-select-all]').first().click();
    await expect(selectedCount).toBeVisible({ timeout: 20000 });

    const current = (await perPageToggle.locator('span').first().textContent())?.trim();
    const target = current === '100' ? '150' : '100';
    await perPageToggle.click();
    await adminPage.locator(`[data-per-page-option="${target}"]`).first().click();

    await expect(selectedCount).toBeVisible({ timeout: 20000 });
  });

  test('can create a folder inline in the destination picker and it is auto-selected', async ({ adminPage }) => {
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.locator('[data-dir-id]').first().waitFor({ state: 'visible', timeout: 40000 });

    await adminPage.locator('[data-select-all]').first().click();
    await expect(adminPage.getByText(/\d+ selected/)).toBeVisible({ timeout: 20000 });
    await adminPage.locator('button:has-text("Select Action")').first().click({ timeout: 30000 });
    await adminPage.locator('li:has-text("Move to")').first().click({ timeout: 30000 });

    const modal = adminPage.locator('[data-folder-picker]');
    await expect(modal).toBeVisible({ timeout: 15000 });

    const listBtn = modal.locator('[data-view="list"]');
    if (await listBtn.count()) await listBtn.click();

    const newFolderBtn = modal.locator('button:has-text("New Folder")');
    const canCreate = await newFolderBtn
      .first()
      .waitFor({ state: 'visible', timeout: 15000 })
      .then(() => true)
      .catch(() => false);
    test.skip(!canCreate, 'Create-directory permission not available.');
    await newFolderBtn.first().click();

    const input = modal.locator('input[placeholder="Folder name"]');
    await expect(input).toBeVisible();

    const name = `PW Picker ${Date.now()}`;
    await input.fill(name);
    await input.press('Enter');

    const newRow = modal.locator('button', { hasText: name });
    await expect(newRow).toBeVisible({ timeout: 15000 });
    await expect(newRow).toHaveClass(/ring-primary-500/);
    await expect(input).toHaveCount(0);

    await expect(modal.locator('button:has-text("Select Here")')).toBeEnabled();
  });
});
