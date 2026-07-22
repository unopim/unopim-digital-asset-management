const API_PREFIX = '/api/v1/rest';

const ENDPOINTS = {
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
    metadata:       (id) => `${API_PREFIX}/assets/${id}/metadata`,
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

  tags: {
    list:       () => `${API_PREFIX}/tags`,
    get:        (tagId) => `${API_PREFIX}/tags/${tagId}`,
    add:        () => `${API_PREFIX}/tags`,
    remove:     () => `${API_PREFIX}/tags`,
    bulkAssign: () => `${API_PREFIX}/tags/bulk`,
    destroy:    (tagId) => `${API_PREFIX}/tags/${tagId}`,
  },

  properties: {
    get:    (id) => `${API_PREFIX}/properties/${id}`,
    add:    (assetId) => `${API_PREFIX}/properties/${assetId}`,
    update: (id) => `${API_PREFIX}/properties/${id}`,
    delete: (id) => `${API_PREFIX}/properties/${id}`,
  },

  linkedResources: {
    get: (id) => `${API_PREFIX}/linked-resource/${id}`,
  },

  shares: {
    index:       () => `${API_PREFIX}/shares`,
    store:       () => `${API_PREFIX}/shares`,
    update:      (id) => `${API_PREFIX}/shares/${id}`,
    revoke:      (id) => `${API_PREFIX}/shares/${id}/revoke`,
    reauthorize: (id) => `${API_PREFIX}/shares/${id}/reauthorize`,
    destroy:     (id) => `${API_PREFIX}/shares/${id}`,
  },
};

module.exports = { ENDPOINTS, API_PREFIX };
