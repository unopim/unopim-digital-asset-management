/**
 * Asset helpers — reusable wrappers over the DAM asset API, including the
 * multipart upload machinery. Specs use these to arrange assets and to drive
 * the upload/download/reupload assertions.
 */

const fs = require('fs');
const path = require('path');
const { ENDPOINTS } = require('../constants/endpoints');
const { STATUS } = require('../constants/statusCodes');

/**
 * Build a Playwright multipart file part from a path on disk.
 *
 * @param {string} filePath
 * @param {string} [mimeType] Override; inferred from extension when omitted.
 */
function filePart(filePath, mimeType) {
  const name = path.basename(filePath);
  return {
    name,
    mimeType: mimeType || mimeFromExt(name),
    buffer: fs.readFileSync(filePath),
  };
}

/** Minimal extension → MIME map for the fixtures we ship. */
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

  /**
   * Upload one asset into a directory.
   *
   * @param {import('../utils/apiHelper').ApiClient} api
   * @param {Object} opts
   * @param {string} [opts.filePath]   Path to a file on disk.
   * @param {Object} [opts.file]       Pre-built multipart part ({name,mimeType,buffer}).
   * @param {number} opts.directoryId  Target directory id (required by the API).
   */
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

  /** Upload and throw unless it succeeded — for preconditions. */
  async uploadOrThrow(api, opts) {
    const res = await assetHelper.upload(api, opts);
    if (res.status !== STATUS.CREATED || !res.id) {
      throw new Error(`assetHelper: upload failed → ${res.status} ${JSON.stringify(res.body)}`);
    }
    return res;
  },

  /** Replace the binary of an existing asset (reupload). */
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
};

module.exports = assetHelper;
