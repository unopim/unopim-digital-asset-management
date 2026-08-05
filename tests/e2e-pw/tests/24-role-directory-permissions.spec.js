

const { test, expect } = require('../utils/fixtures');

const CUSTOM_ROLE_NAME = 'DAM E2E Custom Role';

const CHILD_DIRECTORY_NAME = 'DAM E2E Permission Dir';

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

async function resolveCsrfToken(page) {
  const html = await page.request.get('/admin/settings/roles/create').then((r) => r.text());
  const match = html.match(/name="_token"\s+value="([^"]+)"/);
  if (!match) throw new Error('Could not resolve a CSRF token');
  return match[1];
}

async function ensureChildDirectoryId(page, rootId) {
  const resp = await page.request.get(
    `/admin/dam/directory/children-directory/${rootId}?limit=200`,
    { headers: { Accept: 'application/json' } }
  );

  if (resp.ok()) {
    const json = await resp.json();
    const rows = (json && json.data) || [];

    const seeded = rows.find((d) => d.name === CHILD_DIRECTORY_NAME);
    if (seeded) return seeded.id;

    const leaf = rows.find((d) => !d.has_children);
    if (leaf) return leaf.id;

    if (rows.length > 0) return rows[0].id;
  }

  const token = await resolveCsrfToken(page);

  const created = await page.request.post('/admin/dam/directory/store', {
    form: {
      _token:    token,
      name:      CHILD_DIRECTORY_NAME,
      parent_id: String(rootId),
    },
  });

  if (!created.ok()) {
    throw new Error(`directory store returned ${created.status()}`);
  }

  const body = await created.json();
  const id = body && body.data && body.data.id;
  if (!id) throw new Error('Could not resolve the created directory id');

  return id;
}

async function isNodeExpanded(page, id) {
  return (await page.locator(`.v-tree-item[data-id="${id}"].active`).count()) > 0;
}

async function expandTreeNode(page, id) {
  const item = page.locator(`.v-tree-item[data-id="${id}"]`);
  await item.waitFor({ state: 'attached', timeout: 10000 });

  if (await isNodeExpanded(page, id)) return;

  const chevron = page.locator(`[data-dam-chevron="${id}"]`);
  await chevron.waitFor({ state: 'visible', timeout: 10000 });
  await chevron.click({ force: true });

  await expect(page.locator(`.v-tree-item[data-id="${id}"].active`)).toHaveCount(1, {
    timeout: 10000,
  });
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
    const childId = await ensureChildDirectoryId(adminPage, rootId);

    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const rootItem = adminPage.locator(`.v-tree-item[data-id="${rootId}"]`);
    await rootItem.waitFor({ state: 'attached', timeout: 10000 });

    const chevron = adminPage.locator(`[data-dam-chevron="${rootId}"]`);
    await chevron.waitFor({ state: 'visible', timeout: 10000 });

    const childItem = adminPage.locator(`.v-tree-item[data-id="${childId}"]`);

    if (await isNodeExpanded(adminPage, rootId)) {
      await chevron.click({ force: true });
      await expect(
        adminPage.locator(`.v-tree-item[data-id="${rootId}"].active`)
      ).toHaveCount(0, { timeout: 10000 });
    }

    await expect(childItem).toBeHidden({ timeout: 10000 });

    await chevron.click({ force: true });

    await expect(
      adminPage.locator(`.v-tree-item[data-id="${rootId}"].active`)
    ).toHaveCount(1, { timeout: 10000 });

    await expect(childItem).toBeVisible({ timeout: 10000 });
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
    const targetId = await ensureChildDirectoryId(adminPage, rootId);

    await gotoCustomRoleEdit(adminPage);
    await waitForTreeReady(adminPage);

    const rootCb = () => adminPage.locator(`input.dam-perm-cb[data-id="${rootId}"]`);
    const targetCb = () => adminPage.locator(`input.dam-perm-cb[data-id="${targetId}"]`);
    const targetLabel = () => adminPage.locator(`label:has(input.dam-perm-cb[data-id="${targetId}"])`);

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

    await expandTreeNode(adminPage, rootId);
    await targetCb().waitFor({ state: 'attached', timeout: 10000 });

    if (await targetCb().isChecked()) {
      await targetLabel().click();
      await expect(targetCb()).not.toBeChecked();
      await saveViaBar();

      await gotoCustomRoleEdit(adminPage);
      await waitForTreeReady(adminPage);
      await expandTreeNode(adminPage, rootId);
      await targetCb().waitFor({ state: 'attached', timeout: 10000 });
    }

    await expect(targetCb()).not.toBeChecked();
    await targetLabel().click();
    await expect(targetCb()).toBeChecked();
    await saveViaBar();

    await adminPage.goto(`/admin/settings/roles/edit/${roleId}`, {
      waitUntil: 'domcontentloaded',
      timeout: 30000,
    });
    await adminPage.waitForURL(/\/admin\/settings\/roles\/edit\/\d+/, { timeout: 15000 });
    await waitForTreeReady(adminPage);
    await expandTreeNode(adminPage, rootId);
    await targetCb().waitFor({ state: 'attached', timeout: 10000 });
    await expect(targetCb()).toBeChecked();
    await expect(rootCb()).toBeChecked();
  });

});
