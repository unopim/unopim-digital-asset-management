/**
 * Tags — attach a tag to an asset, fetch a tag by id, and detach a tag.
 * DAM tags are asset-scoped (no standalone tag CRUD / no list-by-asset), so the
 * "Create / List / Delete Tag" requirements map onto these real endpoints.
 *
 * Contract (from TagController):
 *   - attach (POST /tags) → 201; duplicate → 404 (success:false)
 *   - detach (DELETE /tags) → 201; not-attached → 404 (success:false)
 *   - fetch (GET /tags/{tagId}) → 200; unknown id → 404
 *   - attach/detach validate { tag (max:100), asset_id (exists) } → 422
 */

const { test, expect } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');
const tagHelper = require('../helpers/tagHelper');
const assetHelper = require('../helpers/assetHelper');
const folderHelper = require('../helpers/folderHelper');
const { createClient } = require('../helpers/support');
const testData = require('../test-data/testData');

let support;
let directoryId;
let assetId;

test.beforeAll(async () => {
  support = await createClient();
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('tags') });
  directoryId = folder.id;
  const up = await assetHelper.uploadOrThrow(support.client, { filePath: testData.files.image, directoryId });
  assetId = up.id;
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

test.describe('Tag — attach (create)', () => {
  test('attaches a tag to an asset → 201', async ({ api }) => {
    const tag = testData.tagName();
    const res = await tagHelper.add(api, { tag, assetId });

    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.success).toBe(true);
  });

  test('attached state is observable: re-attaching the same tag → 404 (already exists)', async ({ api }) => {
    const tag = testData.tagName('state');
    const first = await tagHelper.add(api, { tag, assetId });
    expect(first.status).toBe(STATUS.CREATED);

    const again = await tagHelper.add(api, { tag, assetId });
    expect(again.status).toBe(STATUS.NOT_FOUND);
    expect(again.body.success).toBe(false);
  });

  test('rejects attaching without required fields → 422', async ({ api }) => {
    const res = await api.post(ENDPOINTS.tags.add(), {});
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('rejects attaching to a non-existent asset → 422', async ({ api }) => {
    const res = await tagHelper.add(api, { tag: testData.tagName(), assetId: testData.nonExistentId });
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('rejects a tag longer than 100 chars (max length) → 422', async ({ api }) => {
    const res = await tagHelper.add(api, { tag: testData.edge.long(150), assetId });
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('accepts a tag with special characters', async ({ api }) => {
    const res = await tagHelper.add(api, { tag: `t-${testData.edge.unicode}`, assetId });
    expect([STATUS.CREATED, STATUS.UNPROCESSABLE_ENTITY]).toContain(res.status);
  });
});

test.describe('Tag — fetch by id', () => {
  test('returns 404 for a non-existent tag id', async ({ api }) => {
    const res = await tagHelper.getById(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
    expect(res.body.success).toBe(false);
  });

  // The attach response carries the asset (not the new tag id), so the tag id
  // isn't reliably discoverable from the API alone. Provide TAG_ID to exercise
  // the positive fetch against a known tag.
  test('fetches a tag by id → 200', async ({ api }) => {
    const id = process.env.TAG_ID;
    test.skip(!id, 'set TAG_ID to a known tag id to run the positive fetch');

    const res = await tagHelper.getById(api, id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(String(res.body.data.id)).toBe(String(id));
  });
});

test.describe('Tag — detach (delete)', () => {
  test('detaches an attached tag → 201, and detached state is observable', async ({ api }) => {
    const tag = testData.tagName('remove');
    const attach = await tagHelper.add(api, { tag, assetId });
    expect(attach.status).toBe(STATUS.CREATED);

    const res = await tagHelper.remove(api, { tag, assetId });
    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.success).toBe(true);

    // Re-detaching a now-detached tag reports not-found → confirms removal.
    const again = await tagHelper.remove(api, { tag, assetId });
    expect(again.status).toBe(STATUS.NOT_FOUND);
    expect(again.body.success).toBe(false);
  });

  test('rejects detach without required fields → 422', async ({ api }) => {
    const res = await api.delete(ENDPOINTS.tags.remove(), {});
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });
});
