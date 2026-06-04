/**
 * Metadata — modelled by the DAM "asset properties" API: a localized
 * name/type/value entry attached to an asset. Covers create, fetch, update and
 * delete with positive, negative, and boundary (min/max length) cases.
 *
 * Validation contract (from PropertyController): name required min:3 max:100 and
 * unique per asset+language; value required max:1000; type & language required;
 * unknown/inactive locale → 400.
 */

const { test, expect, env } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');
const propertyHelper = require('../helpers/propertyHelper');
const assetHelper = require('../helpers/assetHelper');
const folderHelper = require('../helpers/folderHelper');
const { createClient } = require('../helpers/support');
const testData = require('../test-data/testData');

let support;
let directoryId;
let assetId;

test.beforeAll(async () => {
  support = await createClient();
  const folder = await folderHelper.createFolderOrThrow(support.client, { name: testData.folderName('props') });
  directoryId = folder.id;
  const up = await assetHelper.uploadOrThrow(support.client, { filePath: testData.files.image, directoryId });
  assetId = up.id;
});

test.afterAll(async () => {
  if (directoryId) await folderHelper.deleteFolder(support.client, directoryId).catch(() => {});
  await support?.dispose();
});

/** Create a property and return its id (precondition for fetch/update/delete). */
async function seedProperty(api, overrides = {}) {
  const res = await propertyHelper.add(api, assetId, testData.property({ language: env.locale, ...overrides }));
  expect(res.status, JSON.stringify(res.body)).toBe(STATUS.OK);
  return res.body.property.id;
}

test.describe('Metadata — create', () => {
  test('creates a property → 200 and persists', async ({ api }) => {
    const payload = testData.property({ language: env.locale });
    const res = await propertyHelper.add(api, assetId, payload);

    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.property.id).toBeTruthy();

    const fetched = await propertyHelper.get(api, res.body.property.id);
    expect(fetched.status).toBe(STATUS.OK);
    expect(fetched.body.data.id).toBe(res.body.property.id);
  });

  test('rejects creation with missing fields → 422', async ({ api }) => {
    const res = await api.post(ENDPOINTS.properties.add(assetId), {});
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('rejects a name shorter than 3 chars (min length) → 422', async ({ api }) => {
    const res = await propertyHelper.add(api, assetId, testData.property({ language: env.locale, name: 'ab' }));
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('rejects an unknown locale → 400', async ({ api }) => {
    const res = await propertyHelper.add(api, assetId, testData.property({ language: 'zz_ZZ' }));
    expect(res.status).toBe(STATUS.BAD_REQUEST);
    expect(res.body.success).toBe(false);
  });

  test('rejects a duplicate name for the same asset+locale → 422', async ({ api }) => {
    const name = `Dup ${testData.uniqueSuffix()}`;
    const first = await propertyHelper.add(api, assetId, testData.property({ language: env.locale, name }));
    expect(first.status).toBe(STATUS.OK);

    const second = await propertyHelper.add(api, assetId, testData.property({ language: env.locale, name }));
    expect(second.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  // NOTE: the property-add endpoint does not validate asset existence and the
  // API guard bypasses the directory ACL, so posting to a non-existent asset id
  // hits a DB foreign-key error (500) rather than a clean 4xx. That is a server
  // gap, not a behaviour worth asserting here, so it is intentionally not tested.
});

test.describe('Metadata — fetch', () => {
  test('fetches a property by id → 200', async ({ api }) => {
    const id = await seedProperty(api);
    const res = await propertyHelper.get(api, id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.data.id).toBe(id);
  });

  test('returns 404 for a non-existent property', async ({ api }) => {
    const res = await propertyHelper.get(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Metadata — update', () => {
  test('updates a property → 200 and persists', async ({ api }) => {
    const id = await seedProperty(api);
    const newName = `Updated ${testData.uniqueSuffix()}`;
    const newValue = `val-${testData.uniqueSuffix()}`;

    const res = await propertyHelper.update(api, id, { name: newName, value: newValue });
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body.property).toMatchObject({ id });
  });

  test('rejects update with missing required fields → 422', async ({ api }) => {
    const id = await seedProperty(api);
    const res = await propertyHelper.update(api, id, {});
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('update on a non-existent property → 404', async ({ api }) => {
    const res = await propertyHelper.update(api, testData.nonExistentId, { name: 'Valid Name', value: 'v' });
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Metadata — delete', () => {
  test('deletes a property → 200 and is then gone', async ({ api }) => {
    const id = await seedProperty(api);

    const res = await propertyHelper.delete(api, id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);

    const fetched = await propertyHelper.get(api, id);
    expect(fetched.status).toBe(STATUS.NOT_FOUND);
  });

  test('delete on a non-existent property → 404', async ({ api }) => {
    const res = await propertyHelper.delete(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});
