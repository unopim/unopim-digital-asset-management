/**
 * Thin, reusable HTTP client over Playwright's APIRequestContext.
 *
 * Responsibilities:
 *   - attach the bearer token + JSON Accept header to every request,
 *   - measure response time,
 *   - parse the JSON body once and return a normalised result object,
 *   - automatically attach the request payload, response payload and timing
 *     to the Playwright report (HTML/JSON/JUnit) for every call.
 *
 * Specs and resource helpers talk to this class — never to `request` directly —
 * so auth, timing and reporting stay consistent everywhere.
 */

const env = require('../config/env');
const { authHeaders } = require('./authHelper');

/**
 * @typedef {Object} ApiResult
 * @property {boolean} ok            True for 2xx responses.
 * @property {number}  status        HTTP status code.
 * @property {string}  statusText    HTTP status text.
 * @property {*}       body          Parsed JSON body (or raw text fallback).
 * @property {Object}  headers       Response headers.
 * @property {number}  timeMs        Round-trip time in milliseconds.
 * @property {string}  method        HTTP method used.
 * @property {string}  url           Request URL.
 */

class ApiClient {
  /**
   * @param {import('@playwright/test').APIRequestContext} requestContext
   * @param {Object} [options]
   * @param {string} [options.token]    Bearer token (omit for anonymous calls).
   * @param {import('@playwright/test').TestInfo} [options.testInfo] Enables auto-attach.
   */
  constructor(requestContext, { token = null, testInfo = null } = {}) {
    this.request = requestContext;
    this.token = token;
    this.testInfo = testInfo;
  }

  /** Build headers, merging auth (when a token is present) with per-call overrides. */
  _headers(extra = {}) {
    const base = this.token ? authHeaders(this.token) : { Accept: 'application/json' };
    return { ...base, ...extra };
  }

  /**
   * Core request executor. All verb helpers funnel through here so timing,
   * parsing and reporting happen in exactly one place.
   *
   * @param {string} method
   * @param {string} url
   * @param {import('@playwright/test').APIRequestContextOptions} [options]
   * @returns {Promise<ApiResult>}
   */
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
      body = text; // non-JSON (e.g. a streamed file or HTML error page)
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

  /**
   * Attach request/response/timing to the active test for rich reporting.
   * No-op when constructed without a TestInfo (e.g. inside global-setup).
   */
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

  /* ----------------------------- Verb helpers ----------------------------- */

  /** GET request. `params` are appended as a query string. */
  get(url, { params, headers } = {}) {
    return this._send('GET', withQuery(url, params), { headers });
  }

  /** POST a JSON body. */
  post(url, data = {}, { headers } = {}) {
    return this._send('POST', url, { data, headers });
  }

  /** POST a multipart/form-data body (file uploads). */
  postMultipart(url, multipart, { headers } = {}) {
    return this._send('POST', url, { multipart, headers });
  }

  /** PUT a JSON body. */
  put(url, data = {}, { headers } = {}) {
    return this._send('PUT', url, { data, headers });
  }

  /** PATCH a JSON body. */
  patch(url, data = {}, { headers } = {}) {
    return this._send('PATCH', url, { data, headers });
  }

  /** DELETE, optionally with a JSON body (some DAM routes read the body). */
  delete(url, data = undefined, { headers } = {}) {
    return this._send('DELETE', url, data === undefined ? { headers } : { data, headers });
  }
}

/* ------------------------------- internals -------------------------------- */

/** Append `params` to `url` as a query string, skipping null/undefined values. */
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

/** Shorten a URL to its path for compact attachment titles. */
function shortPath(url) {
  try {
    return new URL(url, env.baseURL).pathname;
  } catch (_) {
    return url;
  }
}

/** Never leak credentials into the report. */
function maskSecrets(payload) {
  if (!payload || typeof payload !== 'object') return payload;
  const clone = { ...payload };
  for (const key of ['password', 'client_secret', 'refresh_token', 'access_token']) {
    if (key in clone) clone[key] = '***';
  }
  return clone;
}

/** Multipart payloads contain Buffers/streams — describe them, don't serialise. */
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
