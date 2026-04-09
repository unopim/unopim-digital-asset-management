const { chromium } = require('@playwright/test');
const { login } = require('./utils/login');
const fs = require('fs');
const path = require('path');

const STORAGE_PATH = path.resolve(__dirname, '.state/admin-auth.json');

module.exports = async function globalSetup(config) {
  fs.mkdirSync(path.dirname(STORAGE_PATH), { recursive: true });

  const baseURL = config?.projects?.[0]?.use?.baseURL || process.env.BASE_URL || 'http://127.0.0.1:8000';

  const browser = await chromium.launch();
  const context = await browser.newContext({ baseURL });
  const page = await context.newPage();

  await login(page, config);

  await context.storageState({ path: STORAGE_PATH });
  await browser.close();
};
