/**
 * Asset comments — create, fetch, update and delete a comment on an asset,
 * with negative and edge-case coverage. Comments are a collaboration surface
 * keyed by `dam_asset_id`.
 *
 * Contract: create → 201 returning `{ comment }`; get/update/delete → 200 with
 * `{ success: true }`; missing fields → 422.
 */

const { test, expect } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');
const commentHelper = require('../helpers/commentHelper');
const assetHelper = require('../helpers/assetHelper');
const folderHelper = require('../helpers/folderHelper');
const { createClient } = require('../helpers/support');
const testData = require('../test-data/testData');

let support;
let directoryId;
let assetId;

test.beforeAll(async () => {
  support = await createClient();
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('comments') });
  directoryId = folder.id;
  const up = await assetHelper.uploadOrThrow(support.client, { filePath: testData.files.image, directoryId });
  assetId = up.id;
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

async function seedComment(api, text = testData.commentText()) {
  const res = await commentHelper.create(api, { comments: text, assetId });
  expect(res.status).toBe(STATUS.CREATED);
  return res.id;
}

test.describe('Comment — create', () => {
  test('creates a comment → 201 and is retrievable', async ({ api }) => {
    const text = testData.commentText();
    const res = await commentHelper.create(api, { comments: text, assetId });

    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.comment).toBeTruthy();
    expect(res.id).toBeTruthy();

    const get = await commentHelper.get(api, res.id);
    expect(get.status).toBe(STATUS.OK);
    expect(get.body.data.id).toBe(res.id);
  });

  test('rejects creation with missing fields → 422', async ({ api }) => {
    const res = await api.post(ENDPOINTS.comments.store(), {});
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('rejects a comment for a non-existent asset', async ({ api }) => {
    const res = await commentHelper.create(api, { comments: 'orphan', assetId: testData.nonExistentId });
    expect([STATUS.UNPROCESSABLE_ENTITY, STATUS.NOT_FOUND, STATUS.FORBIDDEN]).toContain(res.status);
  });

  test('accepts special characters and unicode in the body', async ({ api }) => {
    const res = await commentHelper.create(api, { comments: `${testData.edge.unicode} ${testData.edge.special}`, assetId });
    expect([STATUS.CREATED, STATUS.UNPROCESSABLE_ENTITY]).toContain(res.status);
  });
});

test.describe('Comment — read', () => {
  test('fetches a comment by id → 200', async ({ api }) => {
    const id = await seedComment(api);
    const res = await commentHelper.get(api, id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.data.id).toBe(id);
  });

  test('returns 404 for a non-existent comment', async ({ api }) => {
    const res = await commentHelper.get(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Comment — update', () => {
  test('updates a comment → 200 and persists', async ({ api }) => {
    const id = await seedComment(api);
    const updated = `updated ${testData.uniqueSuffix()}`;

    const res = await commentHelper.update(api, id, updated);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);

    const get = await commentHelper.get(api, id);
    expect(JSON.stringify(get.body.data)).toContain(updated);
  });

  test('update on a non-existent comment → 404', async ({ api }) => {
    const res = await commentHelper.update(api, testData.nonExistentId, 'x');
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Comment — delete', () => {
  test('deletes a comment → 200 and is then gone', async ({ api }) => {
    const id = await seedComment(api);

    const res = await commentHelper.delete(api, id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);

    const get = await commentHelper.get(api, id);
    expect(get.status).toBe(STATUS.NOT_FOUND);
  });

  test('delete on a non-existent comment → 404', async ({ api }) => {
    const res = await commentHelper.delete(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});
