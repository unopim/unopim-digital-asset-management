const { test, expect } = require('../utils/fixtures');
const { navigateTo, generateUid } = require('../utils/helpers');
const path = require('path');

const SHOW_ASSETS_ON = ['1', 'true', 'yes', 'on']
  .includes(String(process.env.DAM_TREE_SHOW_ASSETS ?? '').toLowerCase());
const REQUIRES_SHOW_ASSETS_ON = 'requires DAM_TREE_SHOW_ASSETS=true';

const treeMenu = (page) => page.locator('.dam-tree-context-menu');

async function expandDirectory(page, dirName) {
  const row = dirName === 'Root'
    ? page.locator('.tree-container > div.flex').first()
    : page.locator('.tree-container-details').filter({ hasText: dirName }).first()
        .locator('> .flex').first();
  await row.scrollIntoViewIfNeeded();
  await row.click({ force: true });
  await page.waitForTimeout(300);
}

async function rightClickDirectory(page, dirName) {
  const wrapper = dirName === 'Root'
    ? page.locator('.tree-container').first()
    : page.locator('.tree-container-details').filter({ hasText: dirName }).first();
  const row = dirName === 'Root'
    ? wrapper.locator('> div.flex').first()
    : wrapper.locator('> .flex').first();
  await row.scrollIntoViewIfNeeded();
  await row.click({ button: 'right', force: true });
  await treeMenu(page).first().waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
}

async function createDirectory(page, name) {
  await rightClickDirectory(page, 'Root');
  await treeMenu(page).getByText('Add Directory').click({ force: true });
  const nameInput = page.getByPlaceholder('Name').first();
  await nameInput.waitFor({ state: 'visible', timeout: 10000 });
  await nameInput.fill(name);
  await page.getByRole('button', { name: 'Save Directory' }).click();
  await page.waitForTimeout(1500);
  await navigateTo(page, 'dam');
  await page.locator('#app').getByText(name).first()
    .waitFor({ state: 'visible', timeout: 10000 });
}

async function deleteDirectory(page, name) {
  try {
    await rightClickDirectory(page, name);
    await treeMenu(page).getByText('Delete', { exact: true }).click({ force: true });
    await page.waitForTimeout(500);
    const btn = page.getByRole('button', { name: /Delete|Agree/ });
    await btn.waitFor({ state: 'visible', timeout: 5000 });
    await btn.click();
    await page.waitForTimeout(2000);
  } catch {}
}

async function uploadIntoSelectedDirectory(page, filePath) {
  const fileInput = page.locator('input[type="file"][name="files[]"]');
  await fileInput.waitFor({ state: 'attached', timeout: 15000 });
  await fileInput.setInputFiles(filePath);
  await Promise.race([
    page.locator('#app').getByText(/uploaded successfully/i).first()
      .waitFor({ state: 'visible', timeout: 30000 }),
    page.locator('.image-card').first().waitFor({ state: 'visible', timeout: 30000 }),
  ]).catch(() => {});
  await page.waitForTimeout(800);
}

test.describe('DAM Tree — Lazy Asset Load', () => {

  test('initial tree render does NOT include nested-directory asset nodes', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForTimeout(1500);

    const nestedAssetRows = adminPage.locator(
      '.tree-container-details .tree-container-assets-details'
    );
    expect(await nestedAssetRows.count()).toBe(0);

    await expect(adminPage.locator('#app').getByText('Root').first()).toBeVisible();
  });

  test('expanding a child directory fires a GET to directory-assets endpoint', async ({ adminPage }) => {
    test.skip(! SHOW_ASSETS_ON, REQUIRES_SHOW_ASSETS_ON);
    test.setTimeout(60000);
    const uid = generateUid();
    const dirName = `lazy_fire_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);

    const dirRow = adminPage.locator('.tree-container-details').filter({ hasText: dirName }).first()
      .locator('> .flex').first();
    await dirRow.click({ force: true });
    await adminPage.waitForTimeout(400);
    await uploadIntoSelectedDirectory(adminPage, path.resolve(__dirname, '../assets/floral.jpg'));

    await navigateTo(adminPage, 'dam');
    await adminPage.waitForTimeout(800);

    const calls = [];
    adminPage.on('request', req => {
      if (/\/admin\/dam\/directory\/directory-assets\/\d+/.test(req.url())) {
        calls.push(req.url());
      }
    });

    const targetRow = adminPage.locator('.tree-container-details').filter({ hasText: dirName }).first()
      .locator('> .flex').first();
    await targetRow.scrollIntoViewIfNeeded();
    await targetRow.click({ force: true });
    await adminPage.waitForTimeout(1500);

    expect(calls.length).toBeGreaterThanOrEqual(1);

    await deleteDirectory(adminPage, dirName);
  });

  test('expanded directory renders asset rows', async ({ adminPage }) => {
    test.skip(! SHOW_ASSETS_ON, REQUIRES_SHOW_ASSETS_ON);
    test.setTimeout(60000);
    const uid = generateUid();
    const dirName = `lazy_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);

    const dirRow = adminPage.locator('.tree-container-details').filter({ hasText: dirName }).first()
      .locator('> .flex').first();
    await dirRow.click({ force: true });
    await adminPage.waitForTimeout(500);

    await uploadIntoSelectedDirectory(adminPage, path.resolve(__dirname, '../assets/floral.jpg'));

    await navigateTo(adminPage, 'dam');
    await adminPage.waitForTimeout(800);

    await expandDirectory(adminPage, dirName);
    await adminPage.waitForTimeout(1500);

    const assetRows = adminPage.locator('.tree-container-details')
      .filter({ hasText: dirName }).first()
      .locator('.tree-container-assets-details');
    await expect(assetRows.first()).toBeVisible({ timeout: 10000 });

    await deleteDirectory(adminPage, dirName);
  });

  test('collapse then re-expand does not refetch (cache hit)', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await adminPage.waitForTimeout(800);

    const childDir = adminPage.locator('.tree-container-details').first();
    if (!(await childDir.isVisible({ timeout: 2000 }).catch(() => false))) {
      test.skip(true, 'no child directory available to test cache');
      return;
    }

    const row = childDir.locator('> .flex').first();

    await row.click({ force: true });
    await adminPage.waitForTimeout(1000);

    const calls = [];
    adminPage.on('request', req => {
      if (/\/admin\/dam\/directory\/directory-assets\/\d+/.test(req.url())) {
        calls.push(req.url());
      }
    });

    await row.click({ force: true });
    await adminPage.waitForTimeout(400);

    await row.click({ force: true });
    await adminPage.waitForTimeout(800);

    expect(calls.length).toBe(0);
  });

  test('asset upload into open directory invalidates cache and shows asset', async ({ adminPage }) => {
    test.skip(! SHOW_ASSETS_ON, REQUIRES_SHOW_ASSETS_ON);
    test.setTimeout(60000);
    const uid = generateUid();
    const dirName = `upload_cache_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);

    const dirRow = adminPage.locator('.tree-container-details').filter({ hasText: dirName }).first()
      .locator('> .flex').first();
    await dirRow.click({ force: true });
    await adminPage.waitForTimeout(500);

    await dirRow.click({ force: true });
    await adminPage.waitForTimeout(800);
    await dirRow.click({ force: true });

    await uploadIntoSelectedDirectory(adminPage, path.resolve(__dirname, '../assets/floral.jpg'));

    const assetRows = adminPage.locator('.tree-container-details')
      .filter({ hasText: dirName }).first()
      .locator('.tree-container-assets-details');
    await expect(assetRows.first()).toBeVisible({ timeout: 15000 });

    await deleteDirectory(adminPage, dirName);
  });

  test('drop zones mount on both source and target after expand', async ({ adminPage }) => {
    test.skip(! SHOW_ASSETS_ON, REQUIRES_SHOW_ASSETS_ON);
    test.setTimeout(90000);
    const uid = generateUid();
    const srcName = `dz_src_${uid}`;
    const dstName = `dz_dst_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, srcName);
    await createDirectory(adminPage, dstName);

    const srcSelectRow = adminPage.locator('.tree-container-details').filter({ hasText: srcName }).first()
      .locator('> .flex').first();
    await srcSelectRow.click({ force: true });
    await adminPage.waitForTimeout(400);
    await uploadIntoSelectedDirectory(adminPage, path.resolve(__dirname, '../assets/floral.jpg'));

    await navigateTo(adminPage, 'dam');
    await adminPage.waitForTimeout(800);
    await expandDirectory(adminPage, srcName);
    await adminPage.waitForTimeout(1500);
    await expandDirectory(adminPage, dstName);
    await adminPage.waitForTimeout(1500);

    const srcAssets = adminPage.locator('.tree-container-details').filter({ hasText: srcName }).first()
      .locator('.tree-container-assets-details');
    await expect(srcAssets.first()).toBeVisible({ timeout: 10000 });

    const dstWrapper = adminPage.locator('.tree-container-details').filter({ hasText: dstName }).first();
    const dstDropZone = dstWrapper.locator('#assets-items');
    expect(await dstDropZone.count()).toBeGreaterThanOrEqual(1);

    await deleteDirectory(adminPage, srcName);
    await deleteDirectory(adminPage, dstName);
  });
});
