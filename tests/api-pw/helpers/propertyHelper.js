/**
 * Property (metadata) helpers. DAM "metadata" is modelled as asset properties:
 * a localized name/type/value triple attached to an asset. `add` targets an
 * asset id; get/update/delete target a property id.
 */

const { ENDPOINTS } = require('../constants/endpoints');
const { STATUS } = require('../constants/statusCodes');
const testData = require('../test-data/testData');

const propertyHelper = {
  /** Create a property (metadata entry) on an asset. */
  add(api, assetId, data = testData.property()) {
    return api.post(ENDPOINTS.properties.add(assetId), data);
  },

  /** Create and throw unless it succeeded — for preconditions. */
  async addOrThrow(api, assetId, data) {
    const res = await propertyHelper.add(api, assetId, data);
    if (res.status !== STATUS.OK || res.body?.success !== true) {
      throw new Error(`propertyHelper: add failed → ${res.status} ${JSON.stringify(res.body)}`);
    }
    return res;
  },

  get(api, id) {
    return api.get(ENDPOINTS.properties.get(id));
  },

  update(api, id, data) {
    return api.patch(ENDPOINTS.properties.update(id), data);
  },

  delete(api, id) {
    return api.delete(ENDPOINTS.properties.delete(id));
  },
};

module.exports = propertyHelper;
