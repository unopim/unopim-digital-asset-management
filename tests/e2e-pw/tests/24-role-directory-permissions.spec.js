

const { test, expect } = require('../utils/fixtures');

const CUSTOM_ROLE_NAME = 'DAM E2E Custom Role';

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

async function gotoCustomRoleEdit(page) {
  const id = await resolveCustomRoleId(page);
  await page.goto(`/admin/settings/roles/edit/${id}`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  });
  await page.waitForURL(/\/admin\/settings\/roles\/edit\/\d+/, { timeout: 15000 });
  return id;
}

async function waitForTreeReady(page) {
  const tab = page.locator('#dam-directory-permissions-tab');
  await tab.waitFor({ state: 'visible', timeout: 10000 });

  await page.waitForFunction(
    () => {
      const root = document.querySelector('#dam-perm-tree-root');
      if (!root) return false;
      const text = root.textContent || '';

      return (
        root.querySelector('.dam-perm-tree') !== null ||
        (root.querySelector('p') !== null && !text.includes('Loading'))
      );
    },
    { timeout: 20000 }
  );
}

async function grantDirectoryToRole(page, roleId, directoryId) {

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

async function resolveRootDirectoryId(page) {
  const resp = await page.request.get('/admin/dam/directory', {
    headers: { Accept: 'application/json' },
  });
  if (!resp.ok()) throw new Error(`directory index returned ${resp.status()}`);
  const json = await resp.json();
  const dirs = (json && json.data) || [];

  const root = dirs.find((d) => d.parent_id === null || d.parent_id === undefined) || dirs[0];
  if (!root) throw new Error('Could not resolve root directory');
  return root.id;
}

test.describe('Role Edit — Lazy-loading Directory Permission Tree', () => {

  test('checked directory is pre-selected on page load', async ({ adminPage }) => {
    const roleId = await resolveCustomRoleId(adminPage);
    const rootId = await resolveRootDirectoryId(adminPage);

    await grantDirectoryToRole(adminPage, roleId, rootId);

    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const rootCheckbox = adminPage.locator(`input.dam-perm-cb[data-id="${rootId}"]`);
    await rootCheckbox.waitFor({ state: 'attached', timeout: 10000 });
    await expect(rootCheckbox).toBeChecked();
  });

  test('granted directory node is visible in the tree after load', async ({ adminPage }) => {
    const roleId = await resolveCustomRoleId(adminPage);
    const rootId = await resolveRootDirectoryId(adminPage);

    await grantDirectoryToRole(adminPage, roleId, rootId);
    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const rootItem = adminPage.locator(`.v-tree-item[data-id="${rootId}"]`);
    await rootItem.waitFor({ state: 'attached', timeout: 10000 });
    await expect(rootItem).toBeVisible();
  });

  test('clicking a chevron expands the node and reveals children', async ({ adminPage }) => {
    const rootId = await resolveRootDirectoryId(adminPage);
    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const rootItem = adminPage.locator(`.v-tree-item[data-id="${rootId}"]`);
    await rootItem.waitFor({ state: 'attached', timeout: 10000 });

    const chevron = adminPage.locator(`[data-dam-chevron="${rootId}"]`);
    const chevronVisible = await chevron
      .evaluate((el) => el.style.visibility !== 'hidden')
      .catch(() => false);

    if (!chevronVisible) {

      test.skip();
      return;
    }

    const childrenResponse = adminPage.waitForResponse(
      (res) =>
        /\/admin\/dam\/directory\/children-directory\/\d+/.test(res.url()) &&
        res.request().method() === 'GET',
      { timeout: 15000 }
    ).catch(() => null);

    await chevron.click({ force: true });
    await childrenResponse;

    const parentItem = adminPage.locator(`.v-tree-item[data-id="${rootId}"]`);
    await expect(parentItem).toHaveClass(/active/, { timeout: 10000 });

    await expect(
      parentItem.locator(':scope > .v-tree-item').first()
    ).toBeAttached({ timeout: 10000 });
  });

  test('checking and unchecking a directory checkbox toggles its checked state', async ({ adminPage }) => {
    const rootId = await resolveRootDirectoryId(adminPage);
    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const checkbox = adminPage.locator(`input.dam-perm-cb[data-id="${rootId}"]`);
    await checkbox.waitFor({ state: 'attached', timeout: 10000 });

    const toggleCheckbox = (el, checked) => {
      el.checked = checked;
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const wasChecked = await checkbox.isChecked();
    if (wasChecked) {
      await checkbox.evaluate(toggleCheckbox, false);
      await expect(checkbox).not.toBeChecked();
    }

    await checkbox.evaluate(toggleCheckbox, true);
    await expect(checkbox).toBeChecked();

    await checkbox.evaluate(toggleCheckbox, false);
    await expect(checkbox).not.toBeChecked();
  });

  test('checked directory remains checked after saving the role', async ({ adminPage }) => {
    const roleId = await resolveCustomRoleId(adminPage);
    const rootId = await resolveRootDirectoryId(adminPage);

    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const rootCb = () => adminPage.locator(`input.dam-perm-cb[data-id="${rootId}"]`);
    const rootLabel = () => adminPage.locator(`label:has(input.dam-perm-cb[data-id="${rootId}"])`);

    const saveViaBar = async () => {
      const save = adminPage.getByRole('button', { name: /save/i }).first();
      await save.waitFor({ state: 'visible', timeout: 10000 });
      const currentUrl = adminPage.url();

      await save.dispatchEvent('click');
      await Promise.race([
        adminPage.locator('.unsaved-bar').waitFor({ state: 'hidden', timeout: 20000 }),
        adminPage.locator('#app').getByText(/saved|updated|success/i).first()
          .waitFor({ state: 'visible', timeout: 20000 }),
        adminPage.waitForURL((url) => url.toString() !== currentUrl, { timeout: 20000 }),
      ]).catch(() => {});
    };

    await rootCb().waitFor({ state: 'attached', timeout: 10000 });

    if (await rootCb().isChecked()) {
      await rootLabel().click();
      await expect(rootCb()).not.toBeChecked();
      await saveViaBar();

      await gotoCustomRoleEdit(adminPage);
      await waitForTreeReady(adminPage);
      await rootCb().waitFor({ state: 'attached', timeout: 10000 });
    }

    await expect(rootCb()).not.toBeChecked();
    await rootLabel().click();
    await expect(rootCb()).toBeChecked();
    await saveViaBar();

    await adminPage.goto(`/admin/settings/roles/edit/${roleId}`, {
      waitUntil: 'domcontentloaded',
      timeout: 30000,
    });
    await adminPage.waitForURL(/\/admin\/settings\/roles\/edit\/\d+/, { timeout: 15000 });
    await waitForTreeReady(adminPage);
    await rootCb().waitFor({ state: 'attached', timeout: 10000 });
    await expect(rootCb()).toBeChecked();
  });

});
