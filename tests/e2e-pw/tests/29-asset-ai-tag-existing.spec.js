const { test, expect } = require('../utils/fixtures');
const { navigateTo, ensureAssetExists, closeApShell } = require('../utils/helpers');

const AI_TAG_ROUTE = /\/admin\/dam\/assets\/mass-ai-tag/;

async function stubAiTagRequest(page, requests) {
  await page.route(AI_TAG_ROUTE, async (route) => {
    const body = route.request().postDataJSON();
    requests.push(body);

    await route.fulfill({
      json: {
        success: true,
        queued: body.indices.length,
        skipped: 0,
        message: `${body.indices.length} asset(s) queued for AI tagging.`,
      },
    });
  });
}

async function openFirstImageEdit(page) {
  await navigateTo(page, 'dam');
  const firstCard = page.locator('.image-card').first();
  await firstCard.waitFor({ state: 'visible', timeout: 20000 });
  await closeApShell(page);
  await firstCard.hover();
  await firstCard.locator('.icon-edit').first().click({ force: true });
  await page.waitForURL(/admin\/dam\/assets\/edit\/\d+/, { timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

test.describe('DAM AI tagging for existing assets', () => {
  test.beforeEach(async ({ adminPage }) => {
    await ensureAssetExists(adminPage);
  });

  test('Generate AI Tags on the edit page queues the asset, shows the new tags and then hides the button', async ({ adminPage, uid }) => {
    const requests = [];
    const aiTag = `ai-existing-${uid}`;
    let tagged = false;

    await stubAiTagRequest(adminPage, requests);

    await adminPage.route(/\/admin\/dam\/assets\/edit\/\d+/, async (route) => {
      const response = await route.fetch();
      const body = (await response.text()).replace('aiTaggingPending: true', 'aiTaggingPending: false');

      await route.fulfill({ response, body });
    });

    await adminPage.route(/\/admin\/dam\/assets\/show\/\d+/, async (route) => {
      const response = await route.fetch();
      const json = await response.json();

      if (tagged) {
        json.tags = [...(json.tags ?? []), { id: 2147480001, name: aiTag }];
      }

      json.aiTaggingPending = true;
      tagged = true;

      await route.fulfill({ response, json });
    });

    await openFirstImageEdit(adminPage);

    const button = adminPage.getByRole('button', { name: 'Generate AI Tags' });
    test.skip(! (await button.isVisible().catch(() => false)), 'AI tagging is not available on this instance.');

    await button.click();

    await expect(adminPage.getByText(/queued for AI tagging/).first()).toBeVisible({ timeout: 10000 });
    await expect(adminPage.getByRole('button', { name: 'Generating AI tags…' })).toBeDisabled();

    expect(requests).toHaveLength(1);
    expect(requests[0].indices).toHaveLength(1);

    await expect(adminPage.locator('#tags').getByText(aiTag)).toBeVisible({ timeout: 20000 });
    await expect(adminPage.getByRole('button', { name: /Generate AI Tags|Generating AI tags/ })).toHaveCount(0, { timeout: 20000 });
  });

  test('Generate AI Tags mass action queues every selected asset from the grid', async ({ adminPage }) => {
    const requests = [];
    await stubAiTagRequest(adminPage, requests);

    await navigateTo(adminPage, 'dam');
    await adminPage.locator('.image-card').first().waitFor({ state: 'visible', timeout: 20000 });
    await closeApShell(adminPage);

    const checkboxes = adminPage.locator('label[for^="mass_action_select_record_"]');
    test.skip((await checkboxes.count()) < 2, 'Needs at least two assets in the grid.');

    await checkboxes.nth(0).click();
    await checkboxes.nth(1).click();

    await adminPage.getByRole('button', { name: 'Select Action' }).first().click();

    const action = adminPage.getByText('Generate AI Tags', { exact: true }).first();
    test.skip(! (await action.isVisible().catch(() => false)), 'AI tagging is not available on this instance.');

    await action.click();
    await adminPage.getByRole('button', { name: /Agree/ }).first().click();

    await expect(adminPage.getByText('2 asset(s) queued for AI tagging.').first()).toBeVisible({ timeout: 10000 });

    expect(requests).toHaveLength(1);
    expect(requests[0].indices).toHaveLength(2);
  });
});
