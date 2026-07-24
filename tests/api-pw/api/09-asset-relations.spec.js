

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
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('relations') });
  directoryId = folder.id;
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

test.describe('Asset ↔ folder assignment (via upload)', () => {
  test('assigns an asset to a folder on upload and the asset is then readable', async ({ api }) => {
    const up = await assetHelper.upload(api, { filePath: testData.files.image, directoryId });
    expect(up.status).toBe(STATUS.CREATED);
    expect(up.id).toBeTruthy();

    const show = await assetHelper.get(api, up.id);
    expect(show.status).toBe(STATUS.OK);
    expect(show.body.data.asset.id).toBe(up.id);
  });

  test('assignment to a non-existent folder is rejected', async ({ api }) => {
    const up = await assetHelper.upload(api, { filePath: testData.files.image, directoryId: testData.nonExistentId });
    expect([STATUS.UNPROCESSABLE_ENTITY, STATUS.NOT_FOUND, STATUS.FORBIDDEN]).toContain(up.status);
  });
});

test.describe('Linked resources (read-only)', () => {
  test('returns 404 for a non-existent linked resource', async ({ api }) => {
    const res = await api.get(ENDPOINTS.linkedResources.get(testData.nonExistentId));
    expect(res.status).toBe(STATUS.NOT_FOUND);
    expect(res.body.success).toBe(false);
  });

  test('requires authentication → 401 for anonymous', async ({ anonApi }) => {
    const res = await anonApi.get(ENDPOINTS.linkedResources.get(1));
    expect(res.status).toBe(STATUS.UNAUTHORIZED);
  });

  test('fetches an existing linked resource → 200', async ({ api }) => {
    const id = process.env.LINKED_RESOURCE_ID;
    test.skip(!id, 'set LINKED_RESOURCE_ID to a seeded mapping id to run this case');

    const res = await api.get(ENDPOINTS.linkedResources.get(id));
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(String(res.body.data.id)).toBe(String(id));
  });
});
