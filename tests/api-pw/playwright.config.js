const { defineConfig } = require('@playwright/test');
const path = require('path');

require('./config/env');

const isCI = !!process.env.CI;

const workerCount = Number(process.env.WORKERS || 1);

module.exports = defineConfig({
  testDir: './api',

  fullyParallel: false,

  forbidOnly: isCI,

  retries: isCI ? 1 : 0,

  workers: workerCount,

  reporter: [
    ['list'],
    ['html', { outputFolder: 'playwright-report', open: isCI ? 'never' : 'on-failure' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/results.xml' }],
  ],

  timeout: 60_000,

  expect: { timeout: 15_000 },

  globalSetup: require.resolve('./global-setup.js'),

  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',

    extraHTTPHeaders: {
      Accept: 'application/json',
    },

    trace: 'on-first-retry',
  },

  projects: [
    {
      name: 'api',
      testMatch: /.*\.spec\.js/,
    },
  ],

  outputDir: path.resolve(__dirname, 'test-results/artifacts'),
});
