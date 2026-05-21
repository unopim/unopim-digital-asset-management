const { test, expect } = require('../utils/fixtures');

const CUSTOM_ROLE_NAME = 'DAM E2E Custom Role';

/**
 * Resolve the seeded role's id via the datagrid JSON endpoint.
 * global-setup.js creates a role named CUSTOM_ROLE_NAME with
 * permission_type=custom. The roles index page renders rows via async
 * datagrid AJAX, so we hit the same endpoint directly to avoid racing
 * the grid render — and to skip locator selectors that drift with admin
 * theme updates.
 */
async function resolveCustomRoleId(page) {
  const resp = await page.request.get('/admin/settings/roles', {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
  });
  if (! resp.ok()) {
    throw new Error(`roles datagrid endpoint returned ${resp.status()}`);
  }

  const json = await resp.json();
  const records = (json && json.records) || json.data || [];
  const match = records.find((r) => r.name === CUSTOM_ROLE_NAME);
  if (! match || ! match.id) {
    throw new Error(`Could not locate "${CUSTOM_ROLE_NAME}" in roles datagrid response`);
  }

  return match.id;
}

async function gotoCustomRoleEdit(page) {
  const id = await resolveCustomRoleId(page);
  await page.goto(`/admin/settings/roles/edit/${id}`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  });
  await page.waitForURL(/\/admin\/settings\/roles\/edit\/\d+/, { timeout: 15000 });
}

test.describe('Role Edit — DAM Directory Permissions Tab', () => {
  test('renders the DAM Directory Permissions tab on a custom role edit page', async ({ adminPage }) => {
    await gotoCustomRoleEdit(adminPage);

    await expect(
      adminPage.locator('#dam-directory-permissions-tab')
    ).toBeVisible({ timeout: 10000 });

    await expect(
      adminPage.locator('#dam-directory-permissions-tab').getByText('DAM Directory Permissions').first()
    ).toBeVisible();
  });

  test('renders a directory tree inside the tab', async ({ adminPage }) => {
    await gotoCustomRoleEdit(adminPage);

    const tab = adminPage.locator('#dam-directory-permissions-tab');

    await expect(tab.locator('.v-tree-container').first()).toBeAttached();
    await expect(
      tab.locator('input[type="checkbox"][name="directories[]"]').first()
    ).toBeAttached();
  });

  test('renders the hidden management marker so save flow knows to sync', async ({ adminPage }) => {
    await gotoCustomRoleEdit(adminPage);

    const marker = adminPage.locator(
      '#dam-directory-permissions-tab input[name="dam_directory_grants_managed"]'
    );

    await expect(marker).toBeAttached();
    await expect(marker).toHaveValue('1');
  });

  test('tab hides when permission_type is switched to "all"', async ({ adminPage }) => {
    await gotoCustomRoleEdit(adminPage);

    // Confirm tab is currently visible for the custom-permission baseline.
    await expect(adminPage.locator('#dam-directory-permissions-tab')).toBeVisible({ timeout: 10000 });

    // Flip permission_type to "all" via the select control. The control is a
    // shared admin Vue component; clicking the visible "Custom" label opens
    // the dropdown then the "All" option commits.
    const permissionSelect = adminPage.locator('#permission_type').first();
    await permissionSelect.click({ force: true });
    await adminPage.getByText('All', { exact: true }).first().click({ force: true });

    await expect(adminPage.locator('#dam-directory-permissions-tab')).toBeHidden({ timeout: 5000 });
  });
});
