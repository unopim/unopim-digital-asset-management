

const { ENDPOINTS } = require('../constants/endpoints');
const { STATUS } = require('../constants/statusCodes');
const testData = require('../test-data/testData');

const folderHelper = {

  async createFolder(api, { name = testData.folderName(), parentId = null } = {}) {
    const payload = { name };
    if (parentId !== null && parentId !== undefined) payload.parent_id = parentId;

    const res = await api.post(ENDPOINTS.directories.store(), payload);
    res.id = res.body?.data?.id ?? null;
    res.sentName = name;
    return res;
  },

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
