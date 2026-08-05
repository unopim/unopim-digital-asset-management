const { test, expect } = require('../utils/fixtures');
const { navigateTo, ensureAssetExists } = require('../utils/helpers');

const ACTIVE_SHARES_URL = /\/admin\/dam\/shared-links\/active\/(asset|directory)\/\d+$/;
const STORE_SHARE_URL = /\/admin\/dam\/shared-links$/;
const REVOKE_SHARE_URL = /\/admin\/dam\/shared-links\/\d+\/revoke$/;
const REAUTHORIZE_SHARE_URL = /\/admin\/dam\/shared-links\/\d+\/reauthorize$/;

function shareModal(page) {
  return page.locator('div[data-unsaved-ignore]').filter({ hasText: /Share asset/i }).first();
}

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
  await expect(shareBtn).toBeEnabled({ timeout: 15000 });

  const activeSharesPromise = page.waitForResponse(
    (res) => ACTIVE_SHARES_URL.test(res.url()) && res.request().method() === 'GET',
    { timeout: 20000 }
  );

  await shareBtn.click();
  await activeSharesPromise;

  const modal = shareModal(page);
  await modal.waitFor({ state: 'visible', timeout: 15000 });
  await modal.getByText(/Loading…/).first().waitFor({ state: 'hidden', timeout: 15000 }).catch(() => {});

  return modal;
}

async function generateShareLink(page) {
  const modal = shareModal(page);

  const reauthorizeBtn = modal.getByRole('button', { name: /^Reauthorize$/ }).first();

  if (await reauthorizeBtn.isVisible().catch(() => false)) {
    const reauthorizePromise = page.waitForResponse(
      (res) => REAUTHORIZE_SHARE_URL.test(res.url()) && res.request().method() === 'PATCH',
      { timeout: 20000 }
    );

    await reauthorizeBtn.click();

    const reauthorizeResponse = await reauthorizePromise;
    expect(reauthorizeResponse.status(), 'Reauthorize should succeed').toBe(200);

    const reauthorizeBody = await reauthorizeResponse.json();
    expect(reauthorizeBody?.share?.public_url, 'API should return a public_url').toBeTruthy();

    return reauthorizeBody.share.public_url;
  }

  const urlInput = modal.locator('input[readonly]').first();

  if (await urlInput.isVisible().catch(() => false)) {
    const url = await urlInput.inputValue();
    if (url) return url;
  }

  const createBtn = modal.getByRole('button', { name: /Generate link/i }).first();
  await createBtn.waitFor({ state: 'visible', timeout: 15000 });
  await expect(createBtn).toBeEnabled({ timeout: 15000 });

  const responsePromise = page.waitForResponse(
    (res) => STORE_SHARE_URL.test(res.url()) && res.request().method() === 'POST',
    { timeout: 20000 }
  );

  await createBtn.click();

  const response = await responsePromise;
  expect(response.status(), 'Share creation should succeed').toBe(200);

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

    const modal = await openShareModal(adminPage);
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

    const advancedLabel = modal.locator('label.cursor-pointer').filter({ hasText: 'Advanced' }).first();
    await advancedLabel.waitFor({ state: 'visible', timeout: 15000 });
    await advancedLabel.click();

    const revokeBtn = modal.getByRole('button', { name: /^Revoke$/ }).first();
    await revokeBtn.waitFor({ state: 'visible', timeout: 15000 });
    await expect(revokeBtn).toBeEnabled({ timeout: 15000 });

    const revokePromise = adminPage.waitForResponse(
      (res) => REVOKE_SHARE_URL.test(res.url()) && res.request().method() === 'PATCH',
      { timeout: 20000 }
    );

    await revokeBtn.click();

    const revokeResponse = await revokePromise;
    expect(revokeResponse.status(), 'Revoke should succeed').toBe(200);
    expect((await revokeResponse.json())?.success, 'Revoke should report success').toBeTruthy();

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
