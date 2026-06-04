/**
 * File operations — upload (multiple types), download (signed-URL flow),
 * reupload/replace, and file-validation edge cases (invalid type, empty file,
 * large file).
 *
 * Where the server's acceptance policy for a given file isn't guaranteed by the
 * routes/tests we read, assertions accept the documented set of valid outcomes
 * rather than over-specifying a behaviour that may be config-dependent.
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
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('asset-files') });
  directoryId = folder.id;
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

test.describe('Upload — file types', () => {
  for (const kind of ['image', 'pdf', 'video', 'audio']) {
    test(`uploads a ${kind} file → 201`, async ({ api }) => {
      const res = await assetHelper.upload(api, { filePath: testData.files[kind], directoryId });
      expect(res.status).toBe(STATUS.CREATED);
      expect(res.body.success).toBe(true);
      expect(res.body.files?.[0]?.id).toBeTruthy();
    });
  }
});

test.describe('Upload — validation & edge cases', () => {
  test('empty file upload', async ({ api }) => {
    const res = await assetHelper.upload(api, { file: testData.syntheticFile.empty(), directoryId });
    // Either accepted as a 0-byte asset or rejected by validation — both valid.
    expect([STATUS.CREATED, STATUS.UNPROCESSABLE_ENTITY, STATUS.BAD_REQUEST]).toContain(res.status);
  });

  test('invalid file type (.exe)', async ({ api }) => {
    const res = await assetHelper.upload(api, { file: testData.syntheticFile.invalidType(), directoryId });
    expect([STATUS.CREATED, STATUS.UNPROCESSABLE_ENTITY, STATUS.BAD_REQUEST]).toContain(res.status);
  });

  test('large file upload', async ({ api }) => {
    const res = await assetHelper.upload(api, { file: testData.syntheticFile.large('large.bin', 12), directoryId });
    // Accept, or reject with a payload/validation status — never a 500.
    expect(res.status).not.toBe(STATUS.SERVER_ERROR);
    expect([STATUS.CREATED, STATUS.UNPROCESSABLE_ENTITY, STATUS.BAD_REQUEST, 413]).toContain(res.status);
  });

  test('upload with no files field → 422', async ({ api }) => {
    const res = await api.postMultipart(ENDPOINTS.assets.upload(), { directory_id: String(directoryId) });
    expect([STATUS.UNPROCESSABLE_ENTITY, STATUS.BAD_REQUEST]).toContain(res.status);
  });
});

test.describe('Download (signed-URL flow)', () => {
  test('returns a signed download_url that streams the file', async ({ api, anonApi }) => {
    const up = await assetHelper.uploadOrThrow(api, { filePath: testData.files.image, directoryId });

    const dl = await assetHelper.download(api, up.id);
    expect(dl.status).toBe(STATUS.OK);
    expect(dl.body.success).toBe(true);
    expect(dl.body.data.download_url).toContain('signature=');

    // The signed URL is public (signed middleware) — fetch it without a token.
    const streamed = await anonApi.get(dl.body.data.download_url);
    expect(streamed.status).toBe(STATUS.OK);
  });

  test('download of a non-existent asset → 404', async ({ api }) => {
    const res = await assetHelper.download(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
    expect(res.body.success).toBe(false);
  });

  test('signed download route rejects a tampered signature → 403', async ({ anonApi }) => {
    const res = await anonApi.get(`${ENDPOINTS.assets.signedDownload(1)}?expires=9999999999&signature=deadbeef`);
    expect(res.status).toBe(STATUS.FORBIDDEN);
  });
});

test.describe('Reupload (replace file)', () => {
  test('replaces an existing asset binary → 201', async ({ api }) => {
    const up = await assetHelper.uploadOrThrow(api, { filePath: testData.files.image, directoryId });

    const res = await assetHelper.reupload(api, { filePath: testData.files.png, assetId: up.id });
    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.success).toBe(true);
  });

  test('reupload to a non-existent asset is rejected', async ({ api }) => {
    const res = await assetHelper.reupload(api, { filePath: testData.files.png, assetId: testData.nonExistentId });
    expect([STATUS.NOT_FOUND, STATUS.UNPROCESSABLE_ENTITY, STATUS.BAD_REQUEST]).toContain(res.status);
  });
});
