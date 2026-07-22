const { ENDPOINTS } = require('../constants/endpoints');

const tagHelper = {
  getById(api, tagId) {
    return api.get(ENDPOINTS.tags.get(tagId));
  },

  add(api, { tag, assetId }) {
    return api.post(ENDPOINTS.tags.add(), { tag, asset_id: assetId });
  },

  remove(api, { tag, assetId }) {
    return api.delete(ENDPOINTS.tags.remove(), { tag, asset_id: assetId });
  },

  list(api, params = {}) {
    return api.get(ENDPOINTS.tags.list(), { params });
  },

  bulkAssign(api, { tags, assetIds }) {
    return api.post(ENDPOINTS.tags.bulkAssign(), { tags, asset_ids: assetIds });
  },

  destroy(api, tagId) {
    return api.delete(ENDPOINTS.tags.destroy(tagId));
  },
};

module.exports = tagHelper;
