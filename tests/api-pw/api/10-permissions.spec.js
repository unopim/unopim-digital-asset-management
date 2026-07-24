

const { test, expect } = require('../fixtures/fixtures');
const { ApiClient } = require('../utils/apiHelper');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');

const scopedToken = process.env.SCOPED_API_TOKEN;
const deniedAssetId = process.env.DENIED_ASSET_ID;
const deniedDirId = process.env.DENIED_DIR_ID;

test.describe('Directory-scoped access control (403)', () => {
  test.skip(() => !scopedToken, 'set SCOPED_API_TOKEN (+ DENIED_* ids) to exercise 403 permission checks');

  test.skip('forbids showing an asset in a denied directory → 403', async ({ request }, testInfo) => {
    test.skip(!deniedAssetId, 'set DENIED_ASSET_ID');
    const scoped = new ApiClient(request, { token: scopedToken, testInfo });
    const res = await scoped.get(ENDPOINTS.assets.show(deniedAssetId));
    expect(res.status).toBe(STATUS.FORBIDDEN);
  });

  test.skip('forbids updating an asset in a denied directory → 403', async ({ request }, testInfo) => {
    test.skip(!deniedAssetId, 'set DENIED_ASSET_ID');
    const scoped = new ApiClient(request, { token: scopedToken, testInfo });
    const res = await scoped.put(ENDPOINTS.assets.update(deniedAssetId), { file_name: 'x.png' });
    expect(res.status).toBe(STATUS.FORBIDDEN);
  });

  test.skip('forbids deleting an asset in a denied directory → 403', async ({ request }, testInfo) => {
    test.skip(!deniedAssetId, 'set DENIED_ASSET_ID');
    const scoped = new ApiClient(request, { token: scopedToken, testInfo });
    const res = await scoped.delete(ENDPOINTS.assets.destroy(deniedAssetId));
    expect(res.status).toBe(STATUS.FORBIDDEN);
  });

  test('forbids fetching a denied directory → 403', async ({ request }, testInfo) => {
    test.skip(!deniedDirId, 'set DENIED_DIR_ID');
    const scoped = new ApiClient(request, { token: scopedToken, testInfo });
    const res = await scoped.get(ENDPOINTS.directories.get(deniedDirId));
    expect(res.status).toBe(STATUS.FORBIDDEN);
  });

  test('scoped user only lists assets from granted directories', async ({ request }, testInfo) => {
    const scoped = new ApiClient(request, { token: scopedToken, testInfo });
    const res = await scoped.get(ENDPOINTS.assets.index());
    expect(res.status).toBe(STATUS.OK);

    if (deniedAssetId) {
      const ids = (Array.isArray(res.body) ? res.body : res.body?.data || []).map((r) => String(r.id));
      expect(ids).not.toContain(String(deniedAssetId));
    }
  });
});
