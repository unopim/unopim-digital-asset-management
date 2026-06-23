/**
 * Single source of truth for every DAM REST API route.
 *
 * Paths mirror `src/Routes/V1/asset-routes.php` exactly and are prefixed with
 * the group mount point `api/v1/rest` (see `DAMServiceProvider` + `api.php`).
 * Functions take ids/segments so specs never hand-build URLs.
 */

/** Mount prefix shared by every DAM REST route. */
const API_PREFIX = '/api/v1/rest';

const ENDPOINTS = {
  /** OAuth2 token endpoint (not under the DAM prefix — Passport-owned). */
  oauthToken: '/oauth/token',

  assets: {
    index:          () => `${API_PREFIX}/assets`,
    show:           (id) => `${API_PREFIX}/assets/${id}`,
    edit:           (id) => `${API_PREFIX}/assets/edit/${id}`,
    update:         (id) => `${API_PREFIX}/assets/${id}`,
    upload:         () => `${API_PREFIX}/assets`,
    reupload:       () => `${API_PREFIX}/assets/reupload`,
    destroy:        (id) => `${API_PREFIX}/assets/${id}`,
    download:       (id) => `${API_PREFIX}/assets/download/${id}`,
    signedDownload: (id) => `${API_PREFIX}/assets/signUrlDownload/${id}`,
  },

  directories: {
    index:  () => `${API_PREFIX}/directories`,
    get:    (id) => `${API_PREFIX}/directories/${id}`,
    store:  () => `${API_PREFIX}/directories`,
    update: (id) => `${API_PREFIX}/directories/${id}`,
    delete: (id) => `${API_PREFIX}/directories/${id}`,
  },

  comments: {
    get:    (id) => `${API_PREFIX}/comments/${id}`,
    store:  () => `${API_PREFIX}/comments`,
    update: (id) => `${API_PREFIX}/comments/${id}`,
    delete: (id) => `${API_PREFIX}/comments/${id}`,
  },

  /**
   * Tags are asset-scoped: `get(tagId)` fetches a single tag by its id;
   * `add`/`remove` attach/detach a tag (by name) to/from an asset. There is no
   * "list an asset's tags" or standalone tag-CRUD endpoint in the REST API.
   */
  tags: {
    get:    (tagId) => `${API_PREFIX}/tags/${tagId}`,
    add:    () => `${API_PREFIX}/tags`,
    remove: () => `${API_PREFIX}/tags`,
  },

  /**
   * Properties are the DAM "metadata" surface (key/value/locale per asset).
   * `add(assetId)` creates against an asset; get/update/delete take a property id.
   */
  properties: {
    get:    (id) => `${API_PREFIX}/properties/${id}`,
    add:    (assetId) => `${API_PREFIX}/properties/${assetId}`,
    update: (id) => `${API_PREFIX}/properties/${id}`,
    delete: (id) => `${API_PREFIX}/properties/${id}`,
  },

  linkedResources: {
    get: (id) => `${API_PREFIX}/linked-resource/${id}`,
  },
};

module.exports = { ENDPOINTS, API_PREFIX };
