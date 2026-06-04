/**
 * Comment helpers — reusable wrappers over the DAM asset-comment API.
 * Comments belong to an asset (`dam_asset_id`).
 */

const { ENDPOINTS } = require('../constants/endpoints');

const commentHelper = {
  /** Create a comment on an asset. Returns the result with `.id` of the comment. */
  async create(api, { comments, assetId }) {
    const res = await api.post(ENDPOINTS.comments.store(), { comments, dam_asset_id: assetId });
    res.id = res.body?.comment?.id ?? null;
    return res;
  },

  get(api, id) {
    return api.get(ENDPOINTS.comments.get(id));
  },

  update(api, id, comments) {
    return api.put(ENDPOINTS.comments.update(id), { comments });
  },

  delete(api, id) {
    return api.delete(ENDPOINTS.comments.delete(id));
  },
};

module.exports = commentHelper;
