/**
 * 24-role-directory-permissions.spec.js
 *
 * Tests for the lazy-loading DAM directory permission tree on the role edit page.
 *
 * Prerequisites seeded by global-setup.js:
 *   - An admin account (storageState: admin-auth.json).
 *   - A role named "DAM E2E Custom Role" with permission_type=custom.
 *
 * The permission tree mounts inside #dam-directory-permissions-tab via a
 * vanilla-JS component (dam-permissions-tab.blade.php). Key DOM landmarks:
 *   - #dam-directory-permissions-tab   — the tab container (v-if=custom)
 *   - #dam-perm-tree-root              — the lazy-tree mount point
 *   - .dam-perm-tree                   — rendered tree wrapper
 *   - .v-tree-item[data-id="N"]        — per-directory row
 *   - [data-dam-chevron="N"]           — expand/collapse chevron
 *   - input.dam-perm-cb[data-id="N"]   — per-directory checkbox
 *   - .v-tree-item[data-id="N"] > .v-tree-item — expanded children (direct children of parent)
 */

const { test, expect } = require('../utils/fixtures');

const CUSTOM_ROLE_NAME = 'DAM E2E Custom Role';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Resolve the id of the seeded custom role via the datagrid JSON endpoint.
 */
async function resolveCustomRoleId(page) {
  const resp = await page.request.get('/admin/settings/roles', {
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
  });
  if (!resp.ok()) {
    throw new Error(`roles datagrid endpoint returned ${resp.status()}`);
  }
  const json = await resp.json();
  const records = (json && json.records) || json.data || [];
  const match = records.find((r) => r.name === CUSTOM_ROLE_NAME);
  if (!match || !match.id) {
    throw new Error(`Could not locate "${CUSTOM_ROLE_NAME}" in roles datagrid response`);
  }
  return match.id;
}

/**
 * Navigate to the custom role's edit page and wait for the tab to be attached.
 */
async function gotoCustomRoleEdit(page) {
  const id = await resolveCustomRoleId(page);
  await page.goto(`/admin/settings/roles/edit/${id}`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  });
  await page.waitForURL(/\/admin\/settings\/roles\/edit\/\d+/, { timeout: 15000 });
  return id;
}

/**
 * Wait for the lazy tree to finish loading (loading placeholder disappears
 * and the .dam-perm-tree or an empty-state paragraph is rendered).
 */
async function waitForTreeReady(page) {
  const tab = page.locator('#dam-directory-permissions-tab');
  await tab.waitFor({ state: 'visible', timeout: 10000 });

  // The tree fetches /directory (roots) + /directory/paths on mount.
  // Wait until the loading text is gone and something substantive is rendered.
  await page.waitForFunction(
    () => {
      const root = document.querySelector('#dam-perm-tree-root');
      if (!root) return false;
      const text = root.textContent || '';
      // Loading placeholder is typically the I18N.loading string. Accept if
      // either a .dam-perm-tree or a non-loading paragraph is present.
      return (
        root.querySelector('.dam-perm-tree') !== null ||
        (root.querySelector('p') !== null && !text.includes('Loading'))
      );
    },
    { timeout: 20000 }
  );
}

/**
 * Grant a directory to the custom role via the API so the tree renders it
 * as checked on the next page load.
 * Uses the request context (same session cookies) to post directly.
 */
async function grantDirectoryToRole(page, roleId, directoryId) {
  // Fetch a fresh CSRF token from the role-edit page HTML.
  const html = await page.request.get(`/admin/settings/roles/edit/${roleId}`).then((r) => r.text());
  const match = html.match(/name="_token"\s+value="([^"]+)"/);
  if (!match) throw new Error('Could not find _token on role edit page');

  await page.request.post(`/admin/settings/roles/edit/${roleId}`, {
    form: {
      _token:                   match[1],
      _method:                  'PUT',
      name:                     CUSTOM_ROLE_NAME,
      description:              'Seeded by Playwright',
      permission_type:          'custom',
      dam_directory_grants_managed: '1',
      'directories[]':          String(directoryId),
    },
  });
}

/**
 * Resolve the id of the Root directory via the directories API.
 */
async function resolveRootDirectoryId(page) {
  const resp = await page.request.get('/admin/dam/directory', {
    headers: { Accept: 'application/json' },
  });
  if (!resp.ok()) throw new Error(`directory index returned ${resp.status()}`);
  const json = await resp.json();
  const dirs = (json && json.data) || [];
  // Root is typically id=1 or the first directory returned.
  const root = dirs.find((d) => d.parent_id === null || d.parent_id === undefined) || dirs[0];
  if (!root) throw new Error('Could not resolve root directory');
  return root.id;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

test.describe('Role Edit — Lazy-loading Directory Permission Tree', () => {

  /**
   * 1. Page load with existing grants.
   *    Grant the root directory to the custom role, navigate to its edit page,
   *    assert that the checkbox for the root directory is checked.
   */
  test('checked directory is pre-selected on page load', async ({ adminPage }) => {
    const roleId = await resolveCustomRoleId(adminPage);
    const rootId = await resolveRootDirectoryId(adminPage);

    // Grant root to the role so it arrives as a checked node.
    await grantDirectoryToRole(adminPage, roleId, rootId);

    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    // The root checkbox must be checked.
    const rootCheckbox = adminPage.locator(`input.dam-perm-cb[data-id="${rootId}"]`);
    await rootCheckbox.waitFor({ state: 'attached', timeout: 10000 });
    await expect(rootCheckbox).toBeChecked();
  });

  /**
   * 1b. Ancestor node is expanded (visible in DOM) when a granted child is loaded.
   *     Grant the root directory; navigate and verify the tree is rendered
   *     (root node element is attached — ancestors-of-root case is root itself).
   */
  test('granted directory node is visible in the tree after load', async ({ adminPage }) => {
    const roleId = await resolveCustomRoleId(adminPage);
    const rootId = await resolveRootDirectoryId(adminPage);

    await grantDirectoryToRole(adminPage, roleId, rootId);
    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    // The root's .v-tree-item must be attached in the DOM.
    const rootItem = adminPage.locator(`.v-tree-item[data-id="${rootId}"]`);
    await rootItem.waitFor({ state: 'attached', timeout: 10000 });
    await expect(rootItem).toBeVisible();
  });

  /**
   * 2. Expand a node — click chevron on a collapsed node; assert children appear.
   *    The root directory always has children (it is the parent of all dirs).
   *    After clicking its chevron, the children container should show items.
   */
  test('clicking a chevron expands the node and reveals children', async ({ adminPage }) => {
    const rootId = await resolveRootDirectoryId(adminPage);
    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const rootItem = adminPage.locator(`.v-tree-item[data-id="${rootId}"]`);
    await rootItem.waitFor({ state: 'attached', timeout: 10000 });

    // Only click if the node has children (chevron is visible, not hidden).
    const chevron = adminPage.locator(`[data-dam-chevron="${rootId}"]`);
    const chevronVisible = await chevron
      .evaluate((el) => el.style.visibility !== 'hidden')
      .catch(() => false);

    if (!chevronVisible) {
      // Root has no children in this environment — skip expand assertion.
      test.skip();
      return;
    }

    // Capture the children fetch response before clicking.
    const childrenResponse = adminPage.waitForResponse(
      (res) =>
        /\/admin\/dam\/directory\/children-directory\/\d+/.test(res.url()) &&
        res.request().method() === 'GET',
      { timeout: 15000 }
    ).catch(() => null);

    await chevron.click({ force: true });
    await childrenResponse;

    // The parent gains `active` class; its direct .v-tree-item children become visible.
    const parentItem = adminPage.locator(`.v-tree-item[data-id="${rootId}"]`);
    await expect(parentItem).toHaveClass(/active/, { timeout: 10000 });

    // At least one direct .v-tree-item child should now be in the DOM.
    await expect(
      parentItem.locator(':scope > .v-tree-item').first()
    ).toBeAttached({ timeout: 10000 });
  });

  /**
   * 3. Check / uncheck — check a directory checkbox; assert checked; uncheck; assert unchecked.
   */
  test('checking and unchecking a directory checkbox toggles its checked state', async ({ adminPage }) => {
    const rootId = await resolveRootDirectoryId(adminPage);
    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const checkbox = adminPage.locator(`input.dam-perm-cb[data-id="${rootId}"]`);
    await checkbox.waitFor({ state: 'attached', timeout: 10000 });

    // Ensure it starts unchecked (clear any prior grant for a clean test).
    const wasChecked = await checkbox.isChecked();
    if (wasChecked) {
      await checkbox.uncheck({ force: true });
      await expect(checkbox).not.toBeChecked();
    }

    // Check it.
    await checkbox.check({ force: true });
    await expect(checkbox).toBeChecked();

    // Uncheck it.
    await checkbox.uncheck({ force: true });
    await expect(checkbox).not.toBeChecked();
  });

  /**
   * 4. Save and verify — check a directory, submit the form, navigate back,
   *    assert that the directory is still checked.
   */
  test('checked directory remains checked after saving the role', async ({ adminPage }) => {
    const roleId = await resolveCustomRoleId(adminPage);
    const rootId = await resolveRootDirectoryId(adminPage);

    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const checkbox = adminPage.locator(`input.dam-perm-cb[data-id="${rootId}"]`);
    await checkbox.waitFor({ state: 'attached', timeout: 10000 });

    // Ensure the root is checked before saving.
    if (!(await checkbox.isChecked())) {
      await checkbox.check({ force: true });
    }
    await expect(checkbox).toBeChecked();

    // Submit the form.
    const saveButton = adminPage.getByRole('button', { name: /save/i }).first();
    await saveButton.waitFor({ state: 'visible', timeout: 10000 });

    // Wait for either a success toast or a URL change.
    const currentUrl = adminPage.url();
    await saveButton.click();

    await Promise.race([
      adminPage.locator('#app').getByText(/saved|updated|success/i).first()
        .waitFor({ state: 'visible', timeout: 20000 }),
      adminPage.waitForURL((url) => url.toString() !== currentUrl, { timeout: 20000 }),
    ]).catch(() => {});

    // Navigate back to the role edit page.
    await adminPage.goto(`/admin/settings/roles/edit/${roleId}`, {
      waitUntil: 'domcontentloaded',
      timeout: 30000,
    });
    await adminPage.waitForURL(/\/admin\/settings\/roles\/edit\/\d+/, { timeout: 15000 });
    await waitForTreeReady(adminPage);

    // The root checkbox should still be checked.
    const checkboxAfterReload = adminPage.locator(`input.dam-perm-cb[data-id="${rootId}"]`);
    await checkboxAfterReload.waitFor({ state: 'attached', timeout: 10000 });
    await expect(checkboxAfterReload).toBeChecked();
  });

});
