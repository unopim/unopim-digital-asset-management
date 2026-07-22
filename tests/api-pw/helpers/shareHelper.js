const { ENDPOINTS } = require('../constants/endpoints');

function withOptions(body, { name, expiryDays, noExpiry } = {}) {
  if (name !== undefined) body.name = name;
  if (expiryDays !== undefined) body.expiry_days = expiryDays;
  if (noExpiry !== undefined) body.no_expiry = noExpiry;
  return body;
}

const shareHelper = {
  list(api, params = {}) {
    return api.get(ENDPOINTS.shares.index(), { params });
  },

  createForAsset(api, { assetId, ...opts } = {}) {
    return api.post(
      ENDPOINTS.shares.store(),
      withOptions({ share_type: 'asset', asset_id: assetId }, opts)
    );
  },

  createForDirectory(api, { directoryId, ...opts } = {}) {
    return api.post(
      ENDPOINTS.shares.store(),
      withOptions({ share_type: 'directory', directory_id: directoryId }, opts)
    );
  },

  update(api, id, { name, expiryDays, noExpiry } = {}) {
    const body = {};
    if (name !== undefined) body.name = name;
    if (expiryDays !== undefined) body.expiry_days = expiryDays;
    if (noExpiry !== undefined) body.no_expiry = noExpiry;
    return api.put(ENDPOINTS.shares.update(id), body);
  },

  async createForAssetOrThrow(api, opts) {
    const res = await shareHelper.createForAsset(api, opts);
    const id = res.body?.data?.id ?? null;
    if (res.status !== 201 || !id) {
      throw new Error(`Share create failed: ${res.status} ${JSON.stringify(res.body)}`);
    }
    res.id = id;
    return res;
  },

  revoke(api, id) {
    return api.post(ENDPOINTS.shares.revoke(id));
  },

  reauthorize(api, id) {
    return api.post(ENDPOINTS.shares.reauthorize(id));
  },

  destroy(api, id) {
    return api.delete(ENDPOINTS.shares.destroy(id));
  },
};

module.exports = shareHelper;
