const { test, expect } = require('../utils/fixtures');
const { navigateTo, generateUid, ensureAssetExists } = require('../utils/helpers');

async function navigateToCommentsTab(page) {
  await navigateTo(page, 'dam');
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(2000);

  const firstCard = page.locator('.image-card').first();
  await firstCard.waitFor({ state: 'visible', timeout: 20000 });
  await firstCard.hover();
  await page.waitForTimeout(500);
  await firstCard.locator('.icon-edit').first().click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

  const commentsTab = page.locator('#app').getByText('Comments').first();
  await commentsTab.click();
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

test.describe('DAM Asset Comments', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('Comments tab loads', async ({ adminPage }) => {
    await navigateToCommentsTab(adminPage);

    await expect(
      adminPage.locator('#app').getByText(/Add Comment|No Comments Yet|Post Comment/).first()
    ).toBeVisible({ timeout: 15000 });
  });

  test('Post Comment button is visible', async ({ adminPage }) => {
    await navigateToCommentsTab(adminPage);
    await expect(
      adminPage.locator('#app').getByRole('button', { name: /Post Comment/i }).first()
    ).toBeVisible({ timeout: 15000 });
  });

  test('Comment textarea has correct placeholder', async ({ adminPage }) => {
    await navigateToCommentsTab(adminPage);
    await expect(
      adminPage.locator('#app textarea').first()
    ).toBeVisible({ timeout: 15000 });

    await expect(
      adminPage.locator('#app').getByPlaceholder('Add Comment').first()
    ).toBeVisible();
  });

  test('Post a comment successfully', async ({ adminPage }) => {
    const uid = generateUid();
    const commentText = `Test comment ${uid}`;

    await navigateToCommentsTab(adminPage);

    const commentInput = adminPage.locator('#app textarea').first();
    await commentInput.fill(commentText);

    await adminPage.locator('#app').getByRole('button', { name: /Post Comment/i }).first().click();
    await adminPage.waitForTimeout(2000);

    await expect(
      adminPage.locator('#app').getByText(commentText).first()
    ).toBeVisible({ timeout: 20000 });
  });

  test('comment tab badge grows by exactly one after SPA tab navigation', async ({ adminPage }) => {
    const uid = generateUid();
    const commentText = `Badge comment ${uid}`;

    await navigateToCommentsTab(adminPage);

    const editUrl = adminPage.url().split('?')[0];

    await adminPage.evaluate(async (url) => {
      for (const suffix of ['?properties', '?comments', '', '?comments']) {
        window.unopim.visit(url + suffix);
        await new Promise(resolve => setTimeout(resolve, 2500));
      }
    }, editUrl);

    const interceptors = await adminPage.evaluate(
      () => window.axios.interceptors.response.handlers.filter(Boolean).length
    );
    expect(interceptors).toBe(1);

    const badge = adminPage.locator('[data-tab-badge="comments"]').first();

    const before = Number((await badge.textContent().catch(() => '0'))?.trim() || 0);

    await adminPage.locator('#app textarea').first().fill(commentText);
    await adminPage.locator('#app').getByRole('button', { name: /Post Comment/i }).first().click();

    await expect(adminPage.locator('#app').getByText(commentText).first())
      .toBeVisible({ timeout: 20000 });

    await expect(badge).toHaveText(String(before + 1), { timeout: 10000 });
  });
});
