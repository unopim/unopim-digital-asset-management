/**
 * Tag helpers. DAM tags are asset-scoped:
 *   - POST   /tags        attach a tag (by name) to an asset      → 201
 *   - DELETE /tags        detach a tag (by name) from an asset    → 201
 *   - GET    /tags/{id}   fetch a single tag by its TAG id        → 200
 *
 * There is no "list an asset's tags" endpoint, so attachment state is verified
 * via the API's own idempotency signals: re-attaching an attached tag returns
 * 404 (already exists); re-detaching a detached tag returns 404 (not found).
 */

const { ENDPOINTS } = require('../constants/endpoints');

const tagHelper = {
  /** Fetch a single tag by its tag id. */
  getById(api, tagId) {
    return api.get(ENDPOINTS.tags.get(tagId));
  },

  /** Attach a tag (by name) to an asset. */
  add(api, { tag, assetId }) {
    return api.post(ENDPOINTS.tags.add(), { tag, asset_id: assetId });
  },

  /** Detach a tag (by name) from an asset. Sends the body on DELETE. */
  remove(api, { tag, assetId }) {
    return api.delete(ENDPOINTS.tags.remove(), { tag, asset_id: assetId });
  },
};

module.exports = tagHelper;
