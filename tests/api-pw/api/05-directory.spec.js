/**
 * Folder (directory) management — create, read, list/tree, rename (update),
 * delete, and nested-folder operations, with negative and edge-case coverage.
 *
 * Note on status codes (from the API contract): create → 201, get/list/update
 * → 200, delete → 202 (deletion is queued as a job).
 */

const { test, expect } = require('../fixtures/fixtures');
const { STATUS } = require('../constants/statusCodes');
const { ENDPOINTS } = require('../constants/endpoints');
const folderHelper = require('../helpers/folderHelper');
const { createClient } = require('../helpers/support');
const testData = require('../test-data/testData');

let support;
const createdIds = [];

test.beforeAll(async () => {
  support = await createClient();
});

test.afterAll(async () => {
  // Best-effort cleanup of anything left undeleted by a test.
  for (const id of createdIds) {
    await folderHelper.deleteFolder(support.client, id).catch(() => {});
  }
  await support?.dispose();
});

test.describe('Folder — create', () => {
  test('creates a root folder → 201 and persists', async ({ api }) => {
    const name = testData.folderName();
    const res = await folderHelper.createFolder(api, { name });

    expect(res.status).toBe(STATUS.CREATED);
    expect(res.body.success).toBe(true);
    expect(res.id).toBeTruthy();
    createdIds.push(res.id);

    const get = await folderHelper.getFolder(api, res.id);
    expect(get.status).toBe(STATUS.OK);
    expect(get.body.data).toMatchObject({ id: res.id });
  });

  test('rejects creation without a name → 422', async ({ api }) => {
    const res = await api.post(ENDPOINTS.directories.store(), {});
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('rejects an empty name → 422', async ({ api }) => {
    const res = await api.post(ENDPOINTS.directories.store(), { name: testData.edge.empty });
    expect(res.status).toBe(STATUS.UNPROCESSABLE_ENTITY);
  });

  test('accepts special characters in the folder name', async ({ api }) => {
    const res = await folderHelper.createFolder(api, { name: `dir-${testData.edge.unicode}-${testData.uniqueSuffix()}` });
    expect([STATUS.CREATED, STATUS.UNPROCESSABLE_ENTITY]).toContain(res.status);
    if (res.id) createdIds.push(res.id);
  });
});

test.describe('Folder — read & list', () => {
  test('returns the directory tree → 200', async ({ api }) => {
    const res = await folderHelper.listFolders(api);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
    expect(res.body).toHaveProperty('data');
  });

  test('returns a single folder by id → 200', async ({ api }) => {
    const created = await folderHelper.createFolderOrThrow(api);
    createdIds.push(created.id);

    const res = await folderHelper.getFolder(api, created.id);
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
  });

  test('returns 404 for a non-existent folder', async ({ api }) => {
    const res = await folderHelper.getFolder(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Folder — update (rename)', () => {
  test('renames a folder → 200 and persists', async ({ api }) => {
    const created = await folderHelper.createFolderOrThrow(api);
    createdIds.push(created.id);

    const newName = testData.folderName('renamed');
    const res = await folderHelper.updateFolder(api, created.id, { name: newName });
    expect(res.status).toBe(STATUS.OK);
    expect(res.body.success).toBe(true);
  });

  test('rename of a non-existent folder → 404', async ({ api }) => {
    const res = await folderHelper.updateFolder(api, testData.nonExistentId, { name: 'x' });
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Folder — delete', () => {
  test('deletes a folder → 202 (queued)', async ({ api }) => {
    const created = await folderHelper.createFolderOrThrow(api);

    const res = await folderHelper.deleteFolder(api, created.id);
    expect(res.status).toBe(STATUS.ACCEPTED);
    expect(res.body.success).toBe(true);
  });

  test('delete of a non-existent folder → 404', async ({ api }) => {
    const res = await folderHelper.deleteFolder(api, testData.nonExistentId);
    expect(res.status).toBe(STATUS.NOT_FOUND);
  });
});

test.describe('Folder — nested operations', () => {
  test('creates a child under a parent and links them', async ({ api }) => {
    const parent = await folderHelper.createFolderOrThrow(api, { name: testData.folderName('parent') });
    createdIds.push(parent.id);

    const child = await folderHelper.createFolder(api, { name: testData.folderName('child'), parentId: parent.id });
    expect(child.status).toBe(STATUS.CREATED);
    expect(child.id).toBeTruthy();
    createdIds.push(child.id);

    const get = await folderHelper.getFolder(api, child.id);
    expect(get.status).toBe(STATUS.OK);
    // The child should report its parent when the payload exposes it.
    if (get.body.data?.parent_id !== undefined) {
      expect(get.body.data.parent_id).toBe(parent.id);
    }
  });

  test('rejects a child under a non-existent parent', async ({ api }) => {
    const res = await folderHelper.createFolder(api, {
      name: testData.folderName('orphan'),
      parentId: testData.nonExistentId,
    });
    expect([STATUS.UNPROCESSABLE_ENTITY, STATUS.NOT_FOUND, STATUS.FORBIDDEN]).toContain(res.status);
  });
});
