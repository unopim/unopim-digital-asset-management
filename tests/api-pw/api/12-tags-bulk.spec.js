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
let assetIds;

test.beforeAll(async () => {
  support = await createClient();
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('tags-bulk') });
  directoryId = folder.id;

  assetIds = [];
  for (let i = 0; i < 3; i++) {
    const up = await assetHelper.uploadOrThrow(support.client, { filePath: testData.files.image, directoryId });
    assetIds.push(up.id);
  }
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

test.describe('Tag — bulk assign', () => {
  test('assigns tags to many assets at once → 200 with count', async ({ api }) => {
    const tag = testData.tagName('bulk');
    const res = await tagHelper.bulkAssign(api, { tags: [tag], assetIds });

    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.count).toBe(assetIds.length);

    const reattach = await tagHelper.add(api, { tag, assetId: assetIds[0] });
    expect(reattach.status).toBe(STATUS.NOT_FOUND);
  });

  test('creates new tag names on demand and dedupes case-insensitively', async ({ api }) => {
    const tag = testData.tagName('Case');
    const res = await tagHelper.bulkAssign(api, { tags: [tag, tag.toUpperCase()], assetIds: [assetIds[0]] });

    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.count).toBe(1);
  });

  test('rejects missing asset_ids / tags → 422', async ({ api }) => {
    expect((await api.post(ENDPOINTS.tags.bulkAssign(), {})).status).toBe(STATUS.UNPROCESSABLE_ENTITY);
    expect((await api.post(ENDPOINTS.tags.bulkAssign(), { tags: ['x'] })).status).toBe(STATUS.UNPROCESSABLE_ENTITY);
    expect((await api.post(ENDPOINTS.tags.bulkAssign(), { asset_ids: assetIds })).status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('skips non-existent asset ids instead of rejecting → 200', async ({ api }) => {
    const tag = testData.tagName('mixed');
    const res = await tagHelper.bulkAssign(api, { tags: [tag], assetIds: [assetIds[0], testData.nonExistentId] });

    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.count).toBe(1);
  });
});

test.describe('Tag — list', () => {
  test('lists the tag vocabulary including a freshly created tag → 200', async ({ api }) => {
    const tag = testData.tagName('listed');
    await tagHelper.bulkAssign(api, { tags: [tag], assetIds: [assetIds[0]] });

    const res = await tagHelper.list(api, { query: tag });
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(Array.isArray(res.body.data)).toBe(true);
    expect(res.body.data.some((t) => t.name === tag)).toBe(true);
  });
});

test.describe('Tag — delete from vocabulary', () => {
  test('deletes a tag globally; deleting it again → 404', async ({ api }) => {
    const tag = testData.tagName('vocab');
    await tagHelper.add(api, { tag, assetId: assetIds[0] });

    const asset = await assetHelper.get(api, assetIds[0]);
    const attached = (asset.body.data.tags || []).find((t) => t.name === tag);
    expect(attached, 'tag should be attached and discoverable via GET /assets/{id}').toBeTruthy();

    const del = await tagHelper.destroy(api, attached.id);
    expect(del.status).toBe(STATUS.OK);
    expect(del.body.success).toBe(true);

    const again = await tagHelper.destroy(api, attached.id);
    expect(again.status).toBe(STATUS.NOT_FOUND);
  });

  test('returns 404 for an unknown tag id', async ({ api }) => {
    const res = await tagHelper.destroy(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Tag bulk — access control', () => {
  test('unauthenticated requests are rejected → 401', async ({ anonApi }) => {
    const assign = await anonApi.post(ENDPOINTS.tags.bulkAssign(), { tags: ['x'], asset_ids: assetIds });
    expect(assign.status).toBe(STATUS.UNAUTHORIZED);
  });
});
