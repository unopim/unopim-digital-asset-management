const fs = require('fs');
const path = require('path');
const { ENDPOINTS } = require('../constants/endpoints');
const { STATUS } = require('../constants/statusCodes');

function filePart(filePath, mimeType) {
  const name = path.basename(filePath);
  return {
    name,
    mimeType: mimeType || mimeFromExt(name),
    buffer: fs.readFileSync(filePath),
  };
}

function mimeFromExt(name) {
  const ext = path.extname(name).toLowerCase();
  return {
    '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.png': 'image/png',
    '.mp4': 'video/mp4', '.mp3': 'audio/mpeg', '.wav': 'audio/wav',
    '.pdf': 'application/pdf', '.txt': 'text/plain',
  }[ext] || 'application/octet-stream';
}

const assetHelper = {
  filePart,

  async upload(api, { filePath, file, directoryId } = {}) {
    const part = file || filePart(filePath);
    const multipart = { 'files[]': part };
    if (directoryId !== undefined && directoryId !== null) {
      multipart.directory_id = String(directoryId);
    }

    const res = await api.postMultipart(ENDPOINTS.assets.upload(), multipart);
    res.id = res.body?.files?.[0]?.id ?? null;
    return res;
  },

  async uploadOrThrow(api, opts) {
    const res = await assetHelper.upload(api, opts);
    if (res.status !== STATUS.CREATED || !res.id) {
      throw new Error(`assetHelper: upload failed → ${res.status} ${JSON.stringify(res.body)}`);
    }
    return res;
  },

  reupload(api, { filePath, file, assetId } = {}) {
    const part = file || filePart(filePath);
    return api.postMultipart(ENDPOINTS.assets.reupload(), {
      file: part,
      asset_id: String(assetId),
    });
  },

  list(api, params) {
    return api.get(ENDPOINTS.assets.index(), { params });
  },

  get(api, id) {
    return api.get(ENDPOINTS.assets.show(id));
  },

  edit(api, id) {
    return api.put(ENDPOINTS.assets.edit(id));
  },

  update(api, id, data) {
    return api.put(ENDPOINTS.assets.update(id), data);
  },

  remove(api, id) {
    return api.delete(ENDPOINTS.assets.destroy(id));
  },

  download(api, id) {
    return api.get(ENDPOINTS.assets.download(id));
  },

  metadata(api, id) {
    return api.get(ENDPOINTS.assets.metadata(id));
  },
};

module.exports = assetHelper;
