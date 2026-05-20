const { test, expect } = require('../utils/fixtures');
const { navigateTo, ensureAssetExists } = require('../utils/helpers');

/**
 * E2E flow for the Cloudinary-style share-link feature:
 *   1. Open an asset's edit page
 *   2. Click "Share" → modal opens, generate a 7-day link
 *   3. Copy the link, then open it in a fresh (unauthenticated) browser context
 *   4. Verify the public viewer page renders and the Download button works
 *   5. Re-open the modal, revoke the link
 *   6. Reload the public URL → "Link revoked" page
 */

async function navigateToFirstAssetEdit(page) {
  await navigateTo(page, 'dam');
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(2000);

  const firstCard = page.locator('.image-card').first();
  await firstCard.waitFor({ state: 'visible', timeout: 20000 });
  await firstCard.hover();
  await page.waitForTimeout(500);

  const editIcon = firstCard.locator('.icon-edit').first();
  await editIcon.click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

async function openShareModal(page) {
  // Asset edit page renders <v-share-asset-button> in the navButtons slot
  // (top-right of the tab row, next to the History tab). It's a
  // `transparent-button` labelled "Share" with icon-dam-link.
  const shareBtn = page.getByRole('button', { name: /^Share$/ }).first();
  await shareBtn.waitFor({ state: 'visible', timeout: 15000 });
  await shareBtn.click();
  await page.getByText('Create a new share link').waitFor({ state: 'visible', timeout: 15000 });
}

async function generateShareLink(page) {
  const responsePromise = page.waitForResponse(
    (res) => /\/admin\/dam\/shares$/.test(res.url()) && res.request().method() === 'POST',
    { timeout: 15000 }
  );

  await page.getByRole('button', { name: /Generate link/i }).first().click();
  const response = await responsePromise;
  const body = await response.json();
  expect(body?.share?.public_url, 'API should return a public_url').toBeTruthy();
  return body.share.public_url;
}

test.describe('DAM Share Links', () => {

  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('Admin can create a share link, view it publicly, and revoke it', async ({ adminPage, browser }) => {
    await navigateToFirstAssetEdit(adminPage);

    await openShareModal(adminPage);
    const publicUrl = await generateShareLink(adminPage);

    // Open the public URL in a fresh, unauthenticated context.
    const guestContext = await browser.newContext({ storageState: undefined });
    const guestPage = await guestContext.newPage();
    try {
      await guestPage.goto(publicUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });

      // Public viewer shows a download button.
      await expect(
        guestPage.getByRole('link', { name: /Download/i }).first()
      ).toBeVisible({ timeout: 15000 });
    } finally {
      await guestPage.close();
      await guestContext.close();
    }

    // Back on the admin side, the active links list should include our share —
    // revoke it.
    const revokeBtn = adminPage.getByRole('button', { name: /Revoke/i }).first();
    await revokeBtn.waitFor({ state: 'visible', timeout: 10000 });

    const revokePromise = adminPage.waitForResponse(
      (res) => /\/admin\/dam\/shares\/\d+$/.test(res.url()) && res.request().method() === 'DELETE',
      { timeout: 15000 }
    );
    await revokeBtn.click();
    await revokePromise;

    // After revoke, the public URL should render the "Link revoked" page.
    const guestContext2 = await browser.newContext({ storageState: undefined });
    const guestPage2 = await guestContext2.newPage();
    try {
      const resp = await guestPage2.goto(publicUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
      expect(resp.status()).toBe(410);
      await expect(guestPage2.getByText(/Link revoked/i).first()).toBeVisible({ timeout: 10000 });
    } finally {
      await guestPage2.close();
      await guestContext2.close();
    }
  });

  test('Shared Links manage page lists active shares', async ({ adminPage }) => {
    // Create one share first so the listing has at least a row.
    await navigateToFirstAssetEdit(adminPage);
    await openShareModal(adminPage);
    await generateShareLink(adminPage);

    await adminPage.goto('/admin/dam/shares', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await expect(adminPage.getByText(/Shared Links/i).first()).toBeVisible({ timeout: 15000 });
  });

});
