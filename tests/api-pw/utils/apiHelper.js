

const env = require('../config/env');
const { authHeaders } = require('./authHelper');

class ApiClient {

  constructor(requestContext, { token = null, testInfo = null } = {}) {
    this.request = requestContext;
    this.token = token;
    this.testInfo = testInfo;
  }

  _headers(extra = {}) {
    const base = this.token ? authHeaders(this.token) : { Accept: 'application/json' };
    return { ...base, ...extra };
  }

  async _send(method, url, options = {}) {
    const headers = this._headers(options.headers);
    const started = Date.now();

    const response = await this.request.fetch(url, {
      method,
      ...options,
      headers,
    });

    const timeMs = Date.now() - started;
    const text = await response.text();

    let body;
    try {
      body = text ? JSON.parse(text) : null;
    } catch (_) {
      body = text;
    }

    const result = {
      ok:         response.ok(),
      status:     response.status(),
      statusText: response.statusText(),
      headers:    response.headers(),
      body,
      timeMs,
      method,
      url,
    };

    await this._attach(result, options);
    return result;
  }

  async _attach(result, options) {
    if (!this.testInfo) return;

    const requestPayload = options.data ?? options.form ?? options.multipart ?? null;
    const slow = result.timeMs > env.slowResponseMs ? ' ⚠ SLOW' : '';

    const summary = [
      `${result.method} ${result.url}`,
      `→ ${result.status} ${result.statusText} in ${result.timeMs}ms${slow}`,
    ].join('\n');

    await this.testInfo.attach(`api · ${result.method} ${shortPath(result.url)}`, {
      contentType: 'application/json',
      body: JSON.stringify(
        {
          request:  safeJson(maskSecrets(requestPayload)),
          response: safeJson(result.body),
          status:   result.status,
          timeMs:   result.timeMs,
          summary,
        },
        null,
        2
      ),
    });
  }

  get(url, { params, headers } = {}) {
    return this._send('GET', withQuery(url, params), { headers });
  }

  post(url, data = {}, { headers } = {}) {
    return this._send('POST', url, { data, headers });
  }

  postMultipart(url, multipart, { headers } = {}) {
    return this._send('POST', url, { multipart, headers });
  }

  put(url, data = {}, { headers } = {}) {
    return this._send('PUT', url, { data, headers });
  }

  patch(url, data = {}, { headers } = {}) {
    return this._send('PATCH', url, { data, headers });
  }

  delete(url, data = undefined, { headers } = {}) {
    return this._send('DELETE', url, data === undefined ? { headers } : { data, headers });
  }
}

function withQuery(url, params) {
  if (!params || Object.keys(params).length === 0) return url;
  const qs = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === null || value === undefined) continue;
    qs.append(key, String(value));
  }
  const sep = url.includes('?') ? '&' : '?';
  return `${url}${sep}${qs.toString()}`;
}

function shortPath(url) {
  try {
    return new URL(url, env.baseURL).pathname;
  } catch (_) {
    return url;
  }
}

function maskSecrets(payload) {
  if (!payload || typeof payload !== 'object') return payload;
  const clone = { ...payload };
  for (const key of ['password', 'client_secret', 'refresh_token', 'access_token']) {
    if (key in clone) clone[key] = '***';
  }
  return clone;
}

function safeJson(value) {
  if (value && typeof value === 'object') {
    const out = {};
    for (const [k, v] of Object.entries(value)) {
      out[k] = Buffer.isBuffer(v?.buffer) || Buffer.isBuffer(v)
        ? `<binary ${v?.name || ''}>`
        : v;
    }
    return out;
  }
  return value;
}

module.exports = { ApiClient };
