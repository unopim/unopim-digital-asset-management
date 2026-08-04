

const { request } = require('@playwright/test');
const env = require('../config/env');
const { ApiClient } = require('../utils/apiHelper');
const { loadToken, fetchAccessToken } = require('../utils/authHelper');

async function createClient() {
  const ctx = await request.newContext({ baseURL: env.baseURL });
  const cached = loadToken();
  const token = cached?.access_token || (await fetchAccessToken(ctx)).access_token;
  const client = new ApiClient(ctx, { token });
  return { client, dispose: () => ctx.dispose() };
}

module.exports = { createClient };
