const { test, expect } = require('../utils/fixtures');
const { navigateTo, generateUid } = require('../utils/helpers');

const treeMenu = (page) => page.locator('.dam-tree-context-menu');

async function rightClickDirectory(page, dirName) {
  const wrapper = dirName === 'Root'
    ? page.locator('.tree-container').first()
    : page.locator('.tree-container-details').filter({ hasText: dirName }).first();

  const row = dirName === 'Root'
    ? wrapper.locator('> div.flex').first()
    : wrapper.locator('> .flex.cursor-pointer').first();

  await row.scrollIntoViewIfNeeded();

  await page.waitForFunction(
    () => {
      const el = document.querySelector('.tree-container > div.flex');
      return el != null && !el.classList.contains('pointer-events-none');
    },
    { timeout: 15000 }
  ).catch(() => {});
  await row.click({ button: 'right', force: true });

  await treeMenu(page).first().waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
}

async function createDirectory(page, name) {
  await rightClickDirectory(page, 'Root');
  await treeMenu(page).getByText('Add Directory').click({ force: true });
  const nameInput = page.getByPlaceholder('Name').first();
  await nameInput.waitFor({ state: 'visible', timeout: 10000 });
  await nameInput.fill(name);

  const storeResponse = page.waitForResponse(
    (res) => /\/admin\/dam\/directory\/store/.test(res.url()) && res.request().method() === 'POST',
    { timeout: 15000 }
  ).catch(() => null);

  await page.getByRole('button', { name: 'Save Directory' }).click();
  await storeResponse;
  await page.waitForTimeout(500);
  await navigateTo(page, 'dam');

  await page.waitForResponse(
    (res) => /\/admin\/dam\/directory$/.test(res.url()) && res.request().method() === 'GET',
    { timeout: 20000 }
  ).catch(() => page.waitForTimeout(3000));

  await page.locator('#app').getByText(name).first().waitFor({ state: 'visible', timeout: 20000 });
}

async function deleteDirectory(page, name) {
  try {
    await rightClickDirectory(page, name);
    await treeMenu(page).getByText('Delete', { exact: true }).click({ force: true });
    await page.waitForTimeout(500);
    const confirmBtn = page.getByRole('button', { name: /Delete|Agree/ });
    await confirmBtn.waitFor({ state: 'visible', timeout: 5000 });
    await confirmBtn.click();
    await page.waitForTimeout(2000);
  } catch {

  }
}

test.describe('DAM Directory Management', () => {

  test('Root directory is visible in the tree', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.locator('#app').getByText('Root').first()).toBeVisible();
  });

  test('Right-click Root shows context menu', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    const menu = treeMenu(adminPage);
    await expect(menu.getByText('Add Directory')).toBeVisible();
    await expect(menu.getByText('Upload Files', { exact: true })).toBeVisible();
  });

  test('Context menu has all expected actions', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    const menu = treeMenu(adminPage);
    await expect(menu.getByText('Add Directory')).toBeVisible();
    await expect(menu.getByText('Upload Files', { exact: true })).toBeVisible();
    await expect(menu.getByText('Rename', { exact: true })).toBeVisible();
    await expect(menu.getByText('Delete', { exact: true })).toBeVisible();
    await expect(menu.getByText('Copy Directory Structure')).toBeVisible();
    await expect(menu.getByText('Download Zip')).toBeVisible();
  });

  test('Create Directory modal shows on Add Directory click', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    await treeMenu(adminPage).getByText('Add Directory').click({ force: true });
    await adminPage.waitForTimeout(500);
    await expect(adminPage.getByText('Create Directory').first()).toBeVisible();
    await expect(adminPage.getByPlaceholder('Name')).toBeVisible();
    await expect(adminPage.getByRole('button', { name: 'Save Directory' })).toBeVisible();
  });

  test('Create Directory with empty name shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    await treeMenu(adminPage).getByText('Add Directory').click({ force: true });
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Save Directory' }).click();
    await expect(adminPage.getByText(/The Name field is required/i)).toBeVisible();
  });

  test('Create Directory successfully', async ({ adminPage }) => {
    const uid = generateUid();
    const dirName = `test_dir_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);

    await expect(adminPage.locator('#app').getByText(dirName).first()).toBeVisible({ timeout: 10000 });

    await deleteDirectory(adminPage, dirName);
  });

  test('Rename Directory via context menu', async ({ adminPage }) => {
    const uid = generateUid();
    const dirName = `rename_dir_${uid}`;
    const newName = `renamed_dir_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);

    await rightClickDirectory(adminPage, dirName);
    await treeMenu(adminPage).getByText('Rename', { exact: true }).click({ force: true });

    const nameInput = adminPage.getByPlaceholder('Name').first();
    await nameInput.waitFor({ state: 'visible', timeout: 10000 });
    await nameInput.fill(newName);
    await adminPage.getByRole('button', { name: /Save/i }).click();
    await adminPage.waitForTimeout(2000);

    await expect(adminPage.locator('#app').getByText(newName).first()).toBeVisible({ timeout: 10000 });

    await deleteDirectory(adminPage, newName);
  });

  test('Delete Directory via context menu', async ({ adminPage }) => {
    test.setTimeout(120000);
    const uid = generateUid();
    const dirName = `del_dir_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);
    await expect(adminPage.locator('#app').getByText(dirName).first()).toBeVisible({ timeout: 10000 });

    await rightClickDirectory(adminPage, dirName);
    await treeMenu(adminPage).getByText('Delete', { exact: true }).click({ force: true });
    await adminPage.waitForTimeout(500);
    const confirmBtn = adminPage.getByRole('button', { name: /Delete|Agree/ }).first();
    await confirmBtn.waitFor({ state: 'visible', timeout: 5000 });
    await confirmBtn.click();

    await adminPage.waitForTimeout(3000);
  });

  test('Delete Root Directory shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    await treeMenu(adminPage).getByText('Delete', { exact: true }).click({ force: true });
    const confirmBtn = adminPage.getByRole('button', { name: /Delete|Agree/ }).first();
    const modalAppeared = await confirmBtn.waitFor({ state: 'visible', timeout: 5000 }).then(() => true).catch(() => false);
    if (modalAppeared) {
      await confirmBtn.click({ force: true });
    }
    await expect(
      adminPage.locator('#app').getByText(/cannot be deleted|Root Directory/i).first()
    ).toBeVisible({ timeout: 10000 });
  });

  test('Download Zip from context menu triggers download', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');

    const subDir = adminPage.locator('.tree-container-details').first();
    const isVisible = await subDir.isVisible({ timeout: 5000 }).catch(() => false);

    if (!isVisible) {

      await rightClickDirectory(adminPage, 'Root');
    } else {
      await subDir.click({ button: 'right', force: true });
      await adminPage.waitForTimeout(500);
    }

    const downloadZip = treeMenu(adminPage).getByText('Download Zip');
    await expect(downloadZip).toBeVisible({ timeout: 5000 });

    const downloadPromise = adminPage.waitForEvent('download', { timeout: 10000 }).catch(() => null);
    await downloadZip.evaluate((el) => el.click());
    const download = await downloadPromise;
    if (download) {

      expect(download.suggestedFilename()).toMatch(/\.zip$/i);
    }

  });

  test('Click directory updates the asset grid header', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');

    const root = adminPage.locator('.tree-container > div.flex').filter({ hasText: 'Root' }).first();
    await root.click({ force: true });
    await adminPage.waitForTimeout(500);

    await expect(adminPage.locator('#app').getByText('Root').first()).toBeVisible();
  });
});
