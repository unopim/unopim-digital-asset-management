const { test, expect } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');
const assetHelper = require('../helpers/assetHelper');
const folderHelper = require('../helpers/folderHelper');
const { createClient } = require('../helpers/support');
const testData = require('../test-data/testData');

let support;
let directoryId;
let assetId;

test.beforeAll(async () => {
  support = await createClient();
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('metadata') });
  directoryId = folder.id;
  const up = await assetHelper.uploadOrThrow(support.client, { filePath: testData.files.image, directoryId });
  assetId = up.id;
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

test.describe('Asset — metadata', () => {
  test('returns embedded metadata for an asset → 200', async ({ api }) => {
    const res = await assetHelper.metadata(api, assetId);

    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.data.asset_id).toBe(assetId);
    expect(res.body.data).toHaveProperty('file_name');
    expect(res.body.data).toHaveProperty('meta_data');
    expect(res.body.data.meta_data && typeof res.body.data.meta_data).toBe('object');
  });

  test('returns 404 for an unknown asset id', async ({ api }) => {
    const res = await assetHelper.metadata(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
    expect(res.body.success).toBe(false);
  });

  test('rejects unauthenticated requests → 401', async ({ anonApi }) => {
    const res = await anonApi.get(ENDPOINTS.assets.metadata(assetId));
    expect(res.status).toBe(STATUS.UNAUTHORIZED);
  });
});
