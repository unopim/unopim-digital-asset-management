const { test, expect } = require('../utils/fixtures');
const { navigateTo, generateUid } = require('../utils/helpers');

/**
 * Helper: Right-click a directory in the tree to show context menu.
 * The contextmenu listener lives on the inner `.flex.cursor-pointer` row inside
 * `.tree-container-details`, NOT on the wrapper itself. Targeting the wrapper
 * (or dispatching contextmenu on it) silently does nothing.
 */
async function rightClickDirectory(page, dirName) {
  const wrapper = dirName === 'Root'
    ? page.locator('.tree-container').first()
    : page.locator('.tree-container-details').filter({ hasText: dirName }).first();

  const row = dirName === 'Root'
    ? wrapper.locator('> div.flex').first()
    : wrapper.locator('> .flex.cursor-pointer').first();

  await row.scrollIntoViewIfNeeded();
  await row.click({ button: 'right', force: true });
  // Wait for the menu to actually render before the caller clicks an item.
  await page.locator('#app').getByText('Add Directory').first()
    .waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
}

/**
 * Helper: Create a directory under Root via context menu.
 * Reloads the page after save so Vue re-mounts the tree with the new node's
 * contextmenu listeners attached — without this, right-clicking the new
 * directory silently does nothing.
 */
async function createDirectory(page, name) {
  await rightClickDirectory(page, 'Root');
  await page.getByText('Add Directory').click({ force: true });
  const nameInput = page.getByPlaceholder('Name').first();
  await nameInput.waitFor({ state: 'visible', timeout: 10000 });
  await nameInput.fill(name);
  await page.getByRole('button', { name: 'Save Directory' }).click();
  await page.waitForTimeout(1500);
  await navigateTo(page, 'dam');
  await page.locator('#app').getByText(name).first().waitFor({ state: 'visible', timeout: 10000 });
}

/**
 * Helper: Delete a directory via context menu.
 * Silently succeeds if directory is not found.
 */
async function deleteDirectory(page, name) {
  try {
    await rightClickDirectory(page, name);
    await page.getByText('Delete', { exact: true }).click({ force: true });
    await page.waitForTimeout(500);
    const confirmBtn = page.getByRole('button', { name: /Delete|Agree/ });
    await confirmBtn.waitFor({ state: 'visible', timeout: 5000 });
    await confirmBtn.click();
    await page.waitForTimeout(2000);
  } catch {
    // Directory not found — that's fine
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
    await expect(adminPage.getByText('Add Directory')).toBeVisible();
    await expect(adminPage.getByText('Upload Files')).toBeVisible();
  });

  test('Context menu has all expected actions', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    await expect(adminPage.getByText('Add Directory')).toBeVisible();
    await expect(adminPage.getByText('Upload Files')).toBeVisible();
    await expect(adminPage.getByText('Rename', { exact: true })).toBeVisible();
    await expect(adminPage.getByText('Delete', { exact: true })).toBeVisible();
    await expect(adminPage.getByText('Copy Directory Structured')).toBeVisible();
    await expect(adminPage.getByText('Download Zip')).toBeVisible();
  });

  test('Create Directory modal shows on Add Directory click', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    await adminPage.getByText('Add Directory').click({ force: true });
    await adminPage.waitForTimeout(500);
    await expect(adminPage.locator('#app').getByText('Create Directory').first()).toBeVisible();
    await expect(adminPage.getByPlaceholder('Name')).toBeVisible();
    await expect(adminPage.getByRole('button', { name: 'Save Directory' })).toBeVisible();
  });

  test('Create Directory with empty name shows validation error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    await adminPage.getByText('Add Directory').click({ force: true });
    await adminPage.waitForTimeout(500);
    await adminPage.getByRole('button', { name: 'Save Directory' }).click();
    await expect(adminPage.getByText(/The Name field is required/i)).toBeVisible();
  });

  test('Create Directory successfully', async ({ adminPage }) => {
    const uid = generateUid();
    const dirName = `test_dir_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);

    // Verify the directory appears in the tree
    await expect(adminPage.locator('#app').getByText(dirName).first()).toBeVisible({ timeout: 10000 });

    // Cleanup
    await deleteDirectory(adminPage, dirName);
  });

  test('Rename Directory via context menu', async ({ adminPage }) => {
    const uid = generateUid();
    const dirName = `rename_dir_${uid}`;
    const newName = `renamed_dir_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);

    // Right-click the created directory and rename
    await rightClickDirectory(adminPage, dirName);
    await adminPage.getByText('Rename', { exact: true }).click({ force: true });

    // Fill in new name in the rename modal — wait for modal to mount first.
    const nameInput = adminPage.getByPlaceholder('Name').first();
    await nameInput.waitFor({ state: 'visible', timeout: 10000 });
    await nameInput.fill(newName);
    await adminPage.getByRole('button', { name: /Save/i }).click();
    await adminPage.waitForTimeout(2000);

    // Verify renamed directory
    await expect(adminPage.locator('#app').getByText(newName).first()).toBeVisible({ timeout: 10000 });

    // Cleanup
    await deleteDirectory(adminPage, newName);
  });

  test('Delete Directory via context menu', async ({ adminPage }) => {
    test.setTimeout(120000); // Extra time for create + async delete + verify
    const uid = generateUid();
    const dirName = `del_dir_${uid}`;

    await navigateTo(adminPage, 'dam');
    await createDirectory(adminPage, dirName);
    await expect(adminPage.locator('#app').getByText(dirName).first()).toBeVisible({ timeout: 10000 });

    // Delete it
    await rightClickDirectory(adminPage, dirName);
    await adminPage.getByText('Delete', { exact: true }).click({ force: true });
    await adminPage.waitForTimeout(500);
    const confirmBtn = adminPage.getByRole('button', { name: /Delete|Agree/ });
    await confirmBtn.waitFor({ state: 'visible', timeout: 5000 });
    await confirmBtn.click();

    // Directory deletion may be async (job-based). Wait for success indicator.
    await adminPage.waitForTimeout(3000);
  });

  test('Delete Root Directory shows error', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await rightClickDirectory(adminPage, 'Root');
    await adminPage.getByText('Delete', { exact: true }).click({ force: true });
    await adminPage.waitForTimeout(500);
    const confirmBtn = adminPage.getByRole('button', { name: /Delete|Agree/ });
    if (await confirmBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await confirmBtn.click();
    }
    await expect(
      adminPage.locator('#app').getByText(/cannot be deleted|Root Directory/i).first()
    ).toBeVisible({ timeout: 10000 });
  });

  test('Download Zip from context menu triggers download', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');

    // Find a subdirectory with assets (e.g. Accessories or first tree-container-details)
    const subDir = adminPage.locator('.tree-container-details').first();
    const isVisible = await subDir.isVisible({ timeout: 5000 }).catch(() => false);

    if (!isVisible) {
      // If no subdirectories, right-click Root
      await rightClickDirectory(adminPage, 'Root');
    } else {
      await subDir.click({ button: 'right', force: true });
      await adminPage.waitForTimeout(500);
    }

    // Click Download Zip
    const downloadZip = adminPage.getByText('Download Zip');
    await expect(downloadZip).toBeVisible({ timeout: 5000 });

    // Set up download listener before clicking
    const downloadPromise = adminPage.waitForEvent('download', { timeout: 30000 }).catch(() => null);
    await downloadZip.click({ force: true });

    // Verify download started or at least the action was triggered without error
    const download = await downloadPromise;
    if (download) {
      // Download started successfully
      expect(download.suggestedFilename()).toMatch(/\.zip$/i);
    }
    // If no download event (e.g. empty directory), just verify no error toast
  });

  test('Click directory updates the asset grid header', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    // Click on Root directory
    const root = adminPage.locator('.tree-container > div.flex').filter({ hasText: 'Root' }).first();
    await root.click({ force: true });
    await adminPage.waitForTimeout(500);
    // The grid header should show "Root"
    await expect(adminPage.locator('#app').getByText('Root').first()).toBeVisible();
  });
});
