

const { request } = require('@playwright/test');
const env = require('./config/env');
const { ENDPOINTS } = require('./constants/endpoints');
const { fetchAccessToken, saveToken } = require('./utils/authHelper');

module.exports = async function globalSetup() {
  const ctx = await request.newContext({ baseURL: env.baseURL });

  try {

    const tokenPayload = await fetchAccessToken(ctx);

    const probe = await ctx.get(ENDPOINTS.assets.index(), {
      headers: {
        Authorization: `Bearer ${tokenPayload.access_token}`,
        Accept: 'application/json',
      },
    });

    if (probe.status() === 401) {
      throw new Error(
        'global-setup: minted token was rejected (401) by the DAM API. ' +
        'Verify the Passport client and admin credentials.'
      );
    }

    saveToken(tokenPayload);

    console.log(`global-setup: token acquired and verified against ${env.baseURL}`);
  } finally {
    await ctx.dispose();
  }
};
