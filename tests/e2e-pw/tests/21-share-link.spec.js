const { test, expect } = require('../utils/fixtures');
const { navigateTo, ensureAssetExists } = require('../utils/helpers');

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

  const shareBtn = page.locator('button.transparent-button').filter({ hasText: /Share/ }).first();
  await shareBtn.waitFor({ state: 'visible', timeout: 15000 });
  await shareBtn.click();

  await page.getByText(/Share asset/i).first().waitFor({ state: 'visible', timeout: 15000 });
}

async function generateShareLink(page) {

  await page.getByText('Loading…').first().waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {});

  const urlInput = page.locator('input[readonly]').first();
  const alreadyHasShare = await urlInput.isVisible({ timeout: 8000 }).catch(() => false);
  if (alreadyHasShare) {
    const url = await urlInput.inputValue();
    if (url) return url;
  }

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

    const guestContext = await browser.newContext({ storageState: undefined });
    const guestPage = await guestContext.newPage();
    try {
      await guestPage.goto(publicUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });

      await expect(
        guestPage.getByRole('link', { name: /Download/i }).first()
      ).toBeVisible({ timeout: 15000 });
    } finally {
      await guestPage.close();
      await guestContext.close();
    }

    const advancedLabel = adminPage.locator('label.cursor-pointer').filter({ hasText: 'Advanced' }).first();
    await advancedLabel.waitFor({ state: 'visible', timeout: 15000 });
    await advancedLabel.click();

    const revokeBtn = adminPage.getByRole('button', { name: /Revoke/i }).first();
    await revokeBtn.waitFor({ state: 'visible', timeout: 10000 });

    const revokePromise = adminPage.waitForResponse(
      (res) => /\/admin\/dam\/shares\/\d+\/revoke$/.test(res.url()) && res.request().method() === 'PATCH',
      { timeout: 15000 }
    );
    await revokeBtn.click();
    await revokePromise;

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

    await navigateToFirstAssetEdit(adminPage);
    await openShareModal(adminPage);
    await generateShareLink(adminPage);

    await adminPage.goto('/admin/dam/shared-links', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await expect(adminPage.getByText(/Shared Links/i).first()).toBeVisible({ timeout: 15000 });
  });

});
