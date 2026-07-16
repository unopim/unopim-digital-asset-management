const { test, expect } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');
const shareHelper = require('../helpers/shareHelper');
const assetHelper = require('../helpers/assetHelper');
const folderHelper = require('../helpers/folderHelper');
const { createClient } = require('../helpers/support');
const testData = require('../test-data/testData');

let support;
let directoryId;
let assetId;

test.beforeAll(async () => {
  support = await createClient();
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('shares') });
  directoryId = folder.id;
  const up = await assetHelper.uploadOrThrow(support.client, { filePath: testData.files.image, directoryId });
  assetId = up.id;
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

test.describe('Share — create', () => {
  test('creates a share link for an asset → 201', async ({ api }) => {
    const res = await shareHelper.createForAsset(api, { assetId, name: 'API asset share' });

    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.success).toBe(true);
    expect(res.body.data).toMatchObject({ share_type: 'asset', target_id: assetId, status: 'active' });
    expect(res.body.data.token).toBeTruthy();
    expect(res.body.data.public_url).toContain(res.body.data.token);
  });

  test('creates a share link for a directory → 201', async ({ api }) => {
    const res = await shareHelper.createForDirectory(api, { directoryId, name: 'API dir share' });

    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.success).toBe(true);
    expect(res.body.data).toMatchObject({ share_type: 'directory', target_id: directoryId, status: 'active' });
  });

  test('honours no_expiry (link never expires) → 201', async ({ api }) => {
    const res = await shareHelper.createForAsset(api, { assetId, noExpiry: true });

    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.data.expires_at).toBeNull();
  });

  test('rejects missing share_type / target_id → 422', async ({ api }) => {
    const res = await api.post(ENDPOINTS.shares.store(), {});
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('rejects an invalid share_type → 422', async ({ api }) => {
    const res = await api.post(ENDPOINTS.shares.store(), { share_type: 'bogus', asset_id: assetId });
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('returns 404 when the asset target does not exist', async ({ api }) => {
    const res = await shareHelper.createForAsset(api, { assetId: testData.nonExistentId });
    expect(res.status).toBe(STATUS.NOT_FOUND);
    expect(res.body.success).toBe(false);
  });
});

test.describe('Share — list', () => {
  test('lists share links including a freshly created one → 200', async ({ api }) => {
    const created = await shareHelper.createForAssetOrThrow(api, { assetId, name: 'listed share' });

    const res = await shareHelper.list(api);
    expect(res.status).toBe(STATUS.OK);
    expect(Array.isArray(res.body.data)).toBe(true);
    expect(res.body.data.some((s) => s.id === created.id)).toBe(true);
  });
});

test.describe('Share — update', () => {
  test('updates name and expiry; only sent fields change', async ({ api }) => {
    const created = await shareHelper.createForAssetOrThrow(api, { assetId, name: 'before', expiryDays: 7 });

    const renamed = await shareHelper.update(api, created.id, { name: 'after' });
    expect(renamed.status).toBe(STATUS.OK);
    expect(renamed.body.success).toBe(true);
    expect(renamed.body.data.name).toBe('after');

    const reExpiry = await shareHelper.update(api, created.id, { expiryDays: 30 });
    expect(reExpiry.status).toBe(STATUS.OK);
    expect(reExpiry.body.data.name).toBe('after');
    expect(reExpiry.body.data.expires_at).toBeTruthy();
  });

  test('updating an unknown share id → 404', async ({ api }) => {
    const res = await shareHelper.update(api, testData.nonExistentId, { name: 'x' });
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Share — lifecycle (revoke → reauthorize → delete)', () => {
  test('revokes an active link, is idempotent, then reauthorizes it', async ({ api }) => {
    const created = await shareHelper.createForAssetOrThrow(api, { assetId });

    const revoked = await shareHelper.revoke(api, created.id);
    expect(revoked.status).toBe(STATUS.OK);
    expect(revoked.body.success).toBe(true);

    const again = await shareHelper.revoke(api, created.id);
    expect(again.status).toBe(STATUS.OK);
    expect(again.body.success).toBe(false);

    const reauth = await shareHelper.reauthorize(api, created.id);
    expect(reauth.status).toBe(STATUS.OK);
    expect(reauth.body.success).toBe(true);
    expect(reauth.body.data.status).toBe('active');
  });

  test('reauthorizing an active (non-revoked) link is a no-op', async ({ api }) => {
    const created = await shareHelper.createForAssetOrThrow(api, { assetId });

    const res = await shareHelper.reauthorize(api, created.id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(false);
  });

  test('deletes a link; deleting it again → 404', async ({ api }) => {
    const created = await shareHelper.createForAssetOrThrow(api, { assetId });

    const del = await shareHelper.destroy(api, created.id);
    expect(del.status).toBe(STATUS.OK);
    expect(del.body.success).toBe(true);

    const again = await shareHelper.destroy(api, created.id);
    expect(again.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Share — not found', () => {
  test('revoke / reauthorize / delete of an unknown id → 404', async ({ api }) => {
    const id = testData.nonExistentId;
    expect((await shareHelper.revoke(api, id)).status).toBe(STATUS.NOT_FOUND);
    expect((await shareHelper.reauthorize(api, id)).status).toBe(STATUS.NOT_FOUND);
    expect((await shareHelper.destroy(api, id)).status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Share — access control', () => {
  test('unauthenticated requests are rejected → 401', async ({ anonApi }) => {
    const list = await anonApi.get(ENDPOINTS.shares.index());
    expect(list.status).toBe(STATUS.UNAUTHORIZED);

    const create = await anonApi.post(ENDPOINTS.shares.store(), { share_type: 'asset', asset_id: assetId });
    expect(create.status).toBe(STATUS.UNAUTHORIZED);
  });
});
