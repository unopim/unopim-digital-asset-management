/**
 * Asset Management — create (upload), read (show/edit), update and delete,
 * with positive, negative and edge-case coverage and data-persistence checks.
 *
 * A shared parent directory is provisioned once per file; each test uploads its
 * own asset so cases stay isolated and order-independent.
 */

const { test, expect } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');
const assetHelper = require('../helpers/assetHelper');
const folderHelper = require('../helpers/folderHelper');
const { createClient } = require('../helpers/support');
const testData = require('../test-data/testData');

let support;
let directoryId;

test.beforeAll(async () => {
  support = await createClient();
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('asset-crud') });
  directoryId = folder.id;
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

/** Upload a fresh asset and return its id (precondition for read/update/delete). */
async function seedAsset(api) {
  const res = await assetHelper.uploadOrThrow(api, { filePath: testData.files.image, directoryId });
  return res.id;
}

test.describe('Asset — create (upload)', () => {
  test('uploads an image asset → 201 and persists', async ({ api }) => {
    const res = await assetHelper.upload(api, { filePath: testData.files.image, directoryId });

    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.success).toBe(true);
    expect(res.body.files?.[0]?.id).toBeTruthy();

    // Data persistence: the freshly uploaded asset is retrievable.
    const show = await assetHelper.get(api, res.body.files[0].id);
    expect(show.status).toBe(STATUS.OK);
    expect(show.body.data.asset.id).toBe(res.body.files[0].id);
  });

  test('rejects upload without directory_id → 422', async ({ api }) => {
    const res = await api.postMultipart(ENDPOINTS.assets.upload(), {
      'files[]': assetHelper.filePart(testData.files.image),
    });
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
    expect(res.body.success ?? false).toBe(false);
  });

  test('rejects upload to a non-existent directory', async ({ api }) => {
    const res = await assetHelper.upload(api, { filePath: testData.files.image, directoryId: testData.nonExistentId });
    expect([STATUS.UNPROCESSABLE_ENTITY, STATUS.NOT_FOUND, STATUS.FORBIDDEN]).toContain(res.status);
  });
});

test.describe('Asset — read', () => {
  test('shows an existing asset → 200 with schema', async ({ api }) => {
    const id = await seedAsset(api);
    const res = await assetHelper.get(api, id);

    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.data.asset).toMatchObject({ id });
    expect(res.body.data.asset).toHaveProperty('file_name');
  });

  test('returns 404 for a non-existent asset', async ({ api }) => {
    const res = await assetHelper.get(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
    expect(res.body.success).toBe(false);
  });

  test('returns an edit payload → 200', async ({ api }) => {
    const id = await seedAsset(api);
    const res = await assetHelper.edit(api, id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.data.asset.id).toBe(id);
  });

  test('edit on a non-existent asset → 404', async ({ api }) => {
    const res = await assetHelper.edit(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Asset — update', () => {
  test('renames an asset → 200 and persists', async ({ api }) => {
    const id = await seedAsset(api);
    const newName = testData.assetName('png');

    const res = await assetHelper.update(api, id, { file_name: newName });
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);

    const show = await assetHelper.get(api, id);
    expect(show.body.data.asset.file_name).toBe(newName);
  });

  test('update on a non-existent asset → 404', async ({ api }) => {
    const res = await assetHelper.update(api, testData.nonExistentId, { file_name: 'x.png' });
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });

  test('accepts special characters in the file name', async ({ api }) => {
    const id = await seedAsset(api);
    const name = `edge ${testData.edge.unicode}.png`;
    const res = await assetHelper.update(api, id, { file_name: name });
    expect([STATUS.OK, STATUS.UNPROCESSABLE_ENTITY]).toContain(res.status);
  });
});

test.describe('Asset — delete', () => {
  test('deletes an asset → 200 and is then gone', async ({ api }) => {
    const id = await seedAsset(api);

    const res = await assetHelper.remove(api, id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);

    const show = await assetHelper.get(api, id);
    expect(show.status).toBe(STATUS.NOT_FOUND);
  });

  test('delete on a non-existent asset → 404', async ({ api }) => {
    const res = await assetHelper.remove(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });

  test('double delete returns 404 on the second call (idempotency boundary)', async ({ api }) => {
    const id = await seedAsset(api);
    const first = await assetHelper.remove(api, id);
    expect(first.status).toBe(STATUS.OK);
    const second = await assetHelper.remove(api, id);
    expect(second.status).toBe(STATUS.NOT_FOUND);
  });
});
