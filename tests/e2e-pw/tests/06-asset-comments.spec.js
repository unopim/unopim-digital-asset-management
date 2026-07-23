const { test, expect } = require('../utils/fixtures');
const { navigateTo, generateUid, ensureAssetExists } = require('../utils/helpers');

/**
 * Helper: Navigate to the Comments tab of the first asset.
 * Uses hover + edit icon pattern from the gallery view.
 */
async function navigateToCommentsTab(page) {
  await navigateTo(page, 'dam');
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(2000);

  // Hover over first image card and click edit
  const firstCard = page.locator('.image-card').first();
  await firstCard.waitFor({ state: 'visible', timeout: 20000 });
  await firstCard.hover();
  await page.waitForTimeout(500);
  await firstCard.locator('.icon-edit').first().click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

  // Click Comments tab
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
    // Wait for the comments panel to finish rendering after the SPA tab switch.
    // A point-in-time isVisible() check can race the async component mount, so
    // assert on any of the panel's texts with an auto-waiting expectation.
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
    // Placeholder should be "Add Comment"
    await expect(
      adminPage.locator('#app').getByPlaceholder('Add Comment').first()
    ).toBeVisible();
  });

  test('Post a comment successfully', async ({ adminPage }) => {
    const uid = generateUid();
    const commentText = `Test comment ${uid}`;

    await navigateToCommentsTab(adminPage);

    // Fill the comment textarea
    const commentInput = adminPage.locator('#app textarea').first();
    await commentInput.fill(commentText);

    // Click Post Comment
    await adminPage.locator('#app').getByRole('button', { name: /Post Comment/i }).first().click();
    await adminPage.waitForTimeout(2000);

    // Verify the comment text appears on the page (more reliable than toast)
    await expect(
      adminPage.locator('#app').getByText(commentText).first()
    ).toBeVisible({ timeout: 20000 });
  });
});
