const { test, expect } = require('../utils/fixtures');
const { navigateTo } = require('../utils/helpers');

test.describe('DAM Page Navigation & Rendering', () => {

  test('DAM page loads successfully', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage).toHaveTitle(/DAM/);
  });

  test('DAM page shows title and description', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.locator('#app').getByText('DAM').first()).toBeVisible();
    await expect(
      adminPage.getByText('Tool can help you organise, store, and manage all your media asset in one place')
    ).toBeVisible();
  });

  test('DAM page shows directory panel', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.locator('#app').getByText('Directory', { exact: true }).first()).toBeVisible();
    await expect(adminPage.getByText('Root').first()).toBeVisible();
  });

  test('DAM page shows Upload button', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.getByText('Upload')).toBeVisible();
  });

  test('DAM page shows asset grid with results', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    // Wait for the datagrid to load
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.getByText(/Results/)).toBeVisible({ timeout: 30000 });
  });

  test('DAM sidebar link is visible', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.getByRole('link', { name: /DAM/ })).toBeVisible();
  });

  test('DAM page shows search input', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.getByPlaceholder('Search').first()).toBeVisible({ timeout: 30000 });
  });

  test('DAM page shows Filter button', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.getByText('Filter', { exact: true })).toBeVisible({ timeout: 30000 });
  });

  test('DAM page shows Per Page control', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.getByText('Per Page')).toBeVisible({ timeout: 30000 });
  });

  test('DAM page shows Select All option', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dam');
    await expect(adminPage.getByText('Select All')).toBeVisible({ timeout: 30000 });
  });
});
