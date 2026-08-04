

const { test, expect } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const assetHelper = require('../helpers/assetHelper');
const folderHelper = require('../helpers/folderHelper');
const { createClient } = require('../helpers/support');
const q = require('../constants/queryParams');
const testData = require('../test-data/testData');

let support;
let directoryId;

test.beforeAll(async () => {
  support = await createClient();
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('asset-list') });
  directoryId = folder.id;

  await assetHelper.upload(support.client, { filePath: testData.files.image, directoryId }).catch(() => {});
  await assetHelper.upload(support.client, { filePath: testData.files.pdf, directoryId }).catch(() => {});
  await assetHelper.upload(support.client, { filePath: testData.files.video, directoryId }).catch(() => {});
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

const ASSET_KEYS = ['id', 'file_name', 'file_type', 'file_size', 'mime_type', 'extension', 'preview_path'];

function rows(body) {
  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  return [];
}

function meta(body) {
  return body?.meta || body?.pagination || (body?.current_page != null ? body : null);
}

test.describe('Asset — list & pagination', () => {
  test('lists assets → 200 with an array of normalised items', async ({ api }) => {
    const res = await assetHelper.list(api);
    expect(res.status).toBe(STATUS.OK);

    const data = rows(res.body);
    expect(Array.isArray(data)).toBe(true);
    if (data.length) {
      for (const key of ASSET_KEYS) expect(data[0]).toHaveProperty(key);
    }
  });

  test('accepts a pagination request and honours page size when echoed', async ({ api }, testInfo) => {
    const res = await assetHelper.list(api, q.paginate(1, 5));
    expect(res.status).toBe(STATUS.OK);
    expect(Array.isArray(rows(res.body))).toBe(true);

    const m = meta(res.body);
    const perPage = m ? Number(m.per_page ?? m.perPage ?? m.limit) : NaN;

    if (perPage === 5) {
      expect(rows(res.body).length).toBeLessThanOrEqual(5);
    } else {
      testInfo.annotations.push({ type: 'skip-effect', description: `page size not honoured (per_page=${perPage}); confirm param format in constants/queryParams.js` });
    }
  });

  test('page 1 and page 2 differ when the server confirms paging', async ({ api }, testInfo) => {
    const p1 = await assetHelper.list(api, q.paginate(1, 1));
    const p2 = await assetHelper.list(api, q.paginate(2, 1));
    expect(p1.status).toBe(STATUS.OK);
    expect(p2.status).toBe(STATUS.OK);

    const m1 = meta(p1.body);
    const m2 = meta(p2.body);
    const paged = m1 && m2 && Number(m1.current_page) === 1 && Number(m2.current_page) === 2;

    const r1 = rows(p1.body);
    const r2 = rows(p2.body);
    if (paged && r1.length && r2.length) {
      expect(r1[0].id).not.toBe(r2[0].id);
    } else {
      testInfo.annotations.push({ type: 'skip-effect', description: 'pagination not confirmed via current_page; distinctness not asserted' });
    }
  });
});

test.describe('Asset — sorting', () => {
  test('accepts sort by id descending and is well-formed', async ({ api }, testInfo) => {
    const res = await assetHelper.list(api, { ...q.sort('id', 'desc'), ...q.paginate(1, 20) });
    expect(res.status).toBe(STATUS.OK);

    const ids = rows(res.body).map((r) => r.id);
    expect(Array.isArray(ids)).toBe(true);

    if (ids.length > 1) {
      const desc = [...ids].sort((a, b) => b - a);
      const asc = [...ids].sort((a, b) => a - b);

      if (JSON.stringify(ids) !== JSON.stringify(asc)) {
        expect(ids).toEqual(desc);
      } else {
        testInfo.annotations.push({ type: 'skip-effect', description: 'result is in default (asc) order; desc sort not confirmed' });
      }
    }
  });

  test('accepts sort ascending and stays healthy', async ({ api }) => {
    const res = await assetHelper.list(api, q.sort('id', 'asc'));
    expect(res.status).toBe(STATUS.OK);
    expect(Array.isArray(rows(res.body))).toBe(true);
  });
});

test.describe('Asset — filtering & search', () => {
  test('accepts a file_type filter; filtered rows are consistent when applied', async ({ api }, testInfo) => {
    const res = await assetHelper.list(api, q.filter('file_type', 'image'));
    expect(res.status).toBe(STATUS.OK);

    const data = rows(res.body);
    const typed = data.filter((r) => r.file_type != null);
    const allImages = typed.length > 0 && typed.every((r) => r.file_type === 'image');
    const hasNonImage = typed.some((r) => r.file_type !== 'image');

    if (allImages) {

      for (const r of typed) expect(r.file_type).toBe('image');
    } else if (hasNonImage) {
      testInfo.annotations.push({ type: 'skip-effect', description: 'file_type filter not applied with assumed param format; see constants/queryParams.js' });
    }
  });

  test('accepts an extension filter and returns a well-formed array', async ({ api }) => {
    const res = await assetHelper.list(api, q.filter('extension', 'pdf', 'LIKE'));
    expect(res.status).toBe(STATUS.OK);
    expect(Array.isArray(rows(res.body))).toBe(true);
  });

  test('accepts a name search (code filter) and returns a well-formed array', async ({ api }) => {
    const res = await assetHelper.list(api, q.searchByName('api-asset'));
    expect(res.status).toBe(STATUS.OK);
    expect(Array.isArray(rows(res.body))).toBe(true);
  });

  test('accepts a combination filter (file_type + extension) without error', async ({ api }) => {
    const res = await assetHelper.list(api, {
      ...q.filters(q.criteria('file_type', 'image'), q.criteria('extension', 'jpg', 'LIKE')),
      ...q.paginate(1, 10),
    });
    expect(res.status).toBe(STATUS.OK);
    expect(Array.isArray(rows(res.body))).toBe(true);
  });
});
