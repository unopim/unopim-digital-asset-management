const { defineConfig, devices } = require('@playwright/test');
const path = require('path');
const os = require('os');

const isCI = !!process.env.CI;
const STORAGE_STATE = path.resolve(__dirname, '.state/admin-auth.json');

/* Single worker: artisan serve is single-threaded, can't handle concurrent requests */
const workerCount = 1;

module.exports = defineConfig({
  testDir: './tests',

  fullyParallel: false,

  forbidOnly: isCI,

  retries: isCI ? 1 : 0,

  workers: workerCount,

  reporter: isCI
    ? [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]]
    : [['html', { outputFolder: 'playwright-report', open: 'on-failure' }]],

  timeout: 120_000,

  expect: { timeout: 15_000 },

  globalSetup: require.resolve('./global-setup.js'),

  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',

    /* Reuse authenticated session across all tests */
    storageState: STORAGE_STATE,

    trace: 'on-first-retry',

    screenshot: 'only-on-failure',

    video: 'off',

    actionTimeout: 15_000,

    navigationTimeout: 30_000,

    reducedMotion: 'reduce',

    locale: 'en-US',

    viewport: { width: 1280, height: 720 },
  },

  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        headless: true,
        launchOptions: {
          args: [
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--no-sandbox',
          ],
        },
      },
    },
  ],
});
