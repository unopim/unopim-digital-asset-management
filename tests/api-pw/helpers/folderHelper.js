/**
 * Folder (directory) helpers — reusable CRUD wrappers over the DAM directory
 * API. Specs use these for both the assertions under test and for arranging
 * fixtures (e.g. "given a folder, upload an asset into it").
 *
 * Every method returns the raw {@link ApiClient} result so callers can assert
 * on status/body; convenience accessors (`.id`) are exposed where useful.
 */

const { ENDPOINTS } = require('../constants/endpoints');
const { STATUS } = require('../constants/statusCodes');
const testData = require('../test-data/testData');

const folderHelper = {
  /**
   * Create a directory. Returns the API result with `.id` of the new folder.
   *
   * @param {import('../utils/apiHelper').ApiClient} api
   * @param {{name?:string, parentId?:number|null}} [opts]
   */
  async createFolder(api, { name = testData.folderName(), parentId = null } = {}) {
    const payload = { name };
    if (parentId !== null && parentId !== undefined) payload.parent_id = parentId;

    const res = await api.post(ENDPOINTS.directories.store(), payload);
    res.id = res.body?.data?.id ?? null;
    res.sentName = name;
    return res;
  },

  /**
   * Create a folder and fail loudly if it could not be provisioned — use this
   * when the folder is a precondition, not the thing under test.
   */
  async createFolderOrThrow(api, opts = {}) {
    const res = await folderHelper.createFolder(api, opts);
    if (res.status !== STATUS.CREATED || !res.id) {
      throw new Error(`folderHelper: could not create folder → ${res.status} ${JSON.stringify(res.body)}`);
    }
    return res;
  },

  getFolder(api, id) {
    return api.get(ENDPOINTS.directories.get(id));
  },

  listFolders(api, params) {
    return api.get(ENDPOINTS.directories.index(), { params });
  },

  updateFolder(api, id, data) {
    return api.put(ENDPOINTS.directories.update(id), data);
  },

  deleteFolder(api, id) {
    return api.delete(ENDPOINTS.directories.delete(id));
  },
};

module.exports = folderHelper;
