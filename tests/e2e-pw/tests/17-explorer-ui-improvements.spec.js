const { test, expect } = require('../utils/fixtures');
const { navigateTo } = require('../utils/helpers');

/**
 * Covers the DAM Explorer UI improvements:
 *   - the copy/move destination picker's grid/list view toggle
 *   - the fixed-height, internally-scrollable bookmark box
 *
 * These live in the optional DAM Explorer experience. When the explorer is
 * disabled (DAM_EXPLORER_ENABLED=false, the default) the legacy datagrid is
 * shown instead and none of this UI exists — so each test skips itself rather
 * than fail on an intentionally-absent feature.
 */
test.describe('DAM Explorer UI improvements', () => {
  test.beforeEach(async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForLoadState('domcontentloaded');

    // The select-all checkbox is an always-present, explorer-only control.
    // (Allow generous time — the first explorer paint waits on the root listing.)
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

    // Fixed height (h-56 ≈ 224px) with its own vertical scroll, instead of an
    // unbounded list followed by a fixed spacer below it.
    const box = await scroll.evaluate((el) => ({
      overflowY: getComputedStyle(el).overflowY,
      height: Math.round(el.getBoundingClientRect().height),
    }));

    expect(box.overflowY).toBe('auto');
    expect(box.height).toBeGreaterThan(150);
    expect(box.height).toBeLessThan(320);
  });

  test('copy/move destination picker has a working grid/list view toggle', async ({ adminPage }) => {
    // Wait for the explorer to finish its initial load so select-all has items
    // to select (toggleSelectAll selects the currently-loaded dirs/assets).
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.locator('[data-dir-id]').first().waitFor({ state: 'visible', timeout: 40000 });

    // Select the current page of items so the mass-action menu appears.
    await adminPage.locator('[data-select-all]').first().click();

    // Confirm the selection registered (the mass-action bar is reactive) before
    // opening the action menu. The "Select Action" button also contains a
    // chevron glyph, so match on a substring rather than the exact a11y name.
    await expect(adminPage.getByText(/\d+ selected/)).toBeVisible({ timeout: 20000 });
    await adminPage.locator('button:has-text("Select Action")').first().click({ timeout: 30000 });
    await adminPage.locator('li:has-text("Move to")').first().click({ timeout: 30000 });

    // The destination picker modal opens with the grid/list toggle in its header.
    const modal = adminPage.locator('[data-folder-picker]');
    await expect(modal).toBeVisible({ timeout: 15000 });
    await expect(modal.getByText('Select Destination')).toBeVisible();

    const gridBtn = modal.locator('[data-view="grid"]');
    const listBtn = modal.locator('[data-view="list"]');
    await expect(gridBtn).toBeVisible();
    await expect(listBtn).toBeVisible();

    // Switching to grid marks the grid button active and persists the choice.
    // (Asserting toggle state — not card presence — keeps this independent of
    // how many destination folders the current selection leaves un-excluded.)
    await gridBtn.click();
    await expect(gridBtn).toHaveClass(/bg-violet-100/);
    await expect(listBtn).not.toHaveClass(/bg-violet-100/);
    expect(await adminPage.evaluate(() => localStorage.getItem('dam_picker_view'))).toBe('grid');

    // Switching back to list flips the active state and persists again.
    await listBtn.click();
    await expect(listBtn).toHaveClass(/bg-violet-100/);
    await expect(gridBtn).not.toHaveClass(/bg-violet-100/);
    expect(await adminPage.evaluate(() => localStorage.getItem('dam_picker_view'))).toBe('list');
  });

  test('sidebar toggle collapses and restores the sidebar on desktop', async ({ adminPage }) => {
    // Regression: the admin core theme bundle loads after the DAM bundle and
    // re-declares a plain `.flex{display:flex}`, which overrode a non-important
    // `lg:hidden` — so on desktop the collapse toggle updated its state but the
    // sidebar never actually hid. The fix uses `lg:!hidden` (important) so the
    // DAM rule wins the cascade. This test guards the desktop viewport (the
    // default 1280px is >= the lg breakpoint) against that regression.
    const sidebar = adminPage.locator('[data-explorer-sidebar]').first();
    const toggle = adminPage.locator('[data-sidebar-toggle]').first();

    await expect(sidebar).toBeVisible();
    await expect(toggle).toBeVisible();

    const display = () => sidebar.evaluate((el) => getComputedStyle(el).display);
    const persisted = () => adminPage.evaluate(() => localStorage.getItem('dam_show_sidebar'));

    // Starts expanded.
    expect(await display()).not.toBe('none');

    // Collapse: the sidebar is actually removed from layout (display:none), not
    // just flagged in state, and the choice is persisted.
    await toggle.click();
    await expect.poll(display).toBe('none');
    expect(await persisted()).toBe('false');

    // Restore: the sidebar comes back and the persisted flag flips.
    await toggle.click();
    await expect.poll(display).not.toBe('none');
    expect(await persisted()).toBe('true');
  });

  test('toolbar star indicates and toggles the current directory bookmark', async ({ adminPage }) => {
    // The star only exists when the bookmarks feature is on
    // (DAM_EXPLORER_BOOKMARKS_ENABLED). Skip cleanly otherwise.
    const star = adminPage.locator('[data-bookmark-toggle]').first();
    const ready = await star
      .waitFor({ state: 'visible', timeout: 40000 })
      .then(() => true)
      .catch(() => false);
    test.skip(!ready, 'Bookmarks feature disabled — no toolbar star.');

    const bookmarked = () => star.getAttribute('data-bookmarked');
    const panelCount = () => adminPage.locator('[data-bookmark-id]').count();

    // Normalize to a known "not bookmarked" starting point.
    if ((await bookmarked()) === 'true') {
      await star.click();
      await expect.poll(bookmarked).toBe('false');
    }
    const before = await panelCount();

    // Add via the star: it lights up (data-bookmarked=true) and a row is added
    // to the Bookmarks panel — the two stay in sync via dam:bookmarks-changed.
    await star.click();
    await expect.poll(bookmarked).toBe('true');
    await expect.poll(panelCount).toBe(before + 1);

    // Remove via the star: it clears and the panel row is removed again.
    await star.click();
    await expect.poll(bookmarked).toBe('false');
    await expect.poll(panelCount).toBe(before);
  });

  test('changing the per-page limit keeps the current selection', async ({ adminPage }) => {
    // Changing the page size shows the same rows in a different page size, so the
    // selection must be preserved (not wiped). (Regression.)
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.locator('[data-select-all]').first().waitFor({ state: 'visible', timeout: 40000 });
    await adminPage.locator('[data-dir-id]').first().waitFor({ state: 'visible', timeout: 40000 });

    const selectedCount = adminPage.getByText(/\d+\s+selected/i);
    const perPageToggle = adminPage.locator('[data-per-page-toggle]').first();

    // Select every row on the current page → the "N selected" indicator appears.
    await adminPage.locator('[data-select-all]').first().click();
    await expect(selectedCount).toBeVisible({ timeout: 20000 });

    // Switch the per-page limit to a different value.
    const current = (await perPageToggle.locator('span').first().textContent())?.trim();
    const target = current === '100' ? '150' : '100';
    await perPageToggle.click();
    await adminPage.locator(`[data-per-page-option="${target}"]`).first().click();

    // Selection is preserved — the "N selected" indicator is still shown.
    await expect(selectedCount).toBeVisible({ timeout: 20000 });
  });

  test('can create a folder inline in the destination picker and it is auto-selected', async ({ adminPage }) => {
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.locator('[data-dir-id]').first().waitFor({ state: 'visible', timeout: 40000 });

    // Select the current page so the mass-action menu appears, then open the
    // Move destination picker.
    await adminPage.locator('[data-select-all]').first().click();
    await expect(adminPage.getByText(/\d+ selected/)).toBeVisible({ timeout: 20000 });
    await adminPage.locator('button:has-text("Select Action")').first().click({ timeout: 30000 });
    await adminPage.locator('li:has-text("Move to")').first().click({ timeout: 30000 });

    const modal = adminPage.locator('[data-folder-picker]');
    await expect(modal).toBeVisible({ timeout: 15000 });

    // Force list view for a deterministic inline-create row.
    const listBtn = modal.locator('[data-view="list"]');
    if (await listBtn.count()) await listBtn.click();

    // The inline create row renders once the modal's root listing settles (and
    // only with the dam.directory.store permission). Wait before deciding to skip.
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

    // A unique name so the row is unambiguous and never collides with seed data.
    const name = `PW Picker ${Date.now()}`;
    await input.fill(name);
    await input.press('Enter');

    // The new folder appears in the listing and is auto-selected as destination
    // (violet ring), and the inline input collapses back to the trigger button.
    const newRow = modal.locator('button', { hasText: name });
    await expect(newRow).toBeVisible({ timeout: 15000 });
    await expect(newRow).toHaveClass(/ring-violet-500/);
    await expect(input).toHaveCount(0);

    // The destination can be confirmed immediately, with a single click.
    await expect(modal.locator('button:has-text("Select Here")')).toBeEnabled();
  });
});
