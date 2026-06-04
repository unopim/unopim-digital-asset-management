const { defineConfig } = require('@playwright/test');
const path = require('path');

/* Load tests/api-pw/.env into process.env before reading any variable below. */
require('./config/env');

const isCI = !!process.env.CI;

/*
 * Single worker by default: a local `php artisan serve` is single-threaded and
 * cannot handle concurrent requests — matching the e2e suite's rationale. Pure
 * API tests against a multi-process target (php-fpm) can safely raise this via
 * the WORKERS env var.
 */
const workerCount = Number(process.env.WORKERS || 1);

module.exports = defineConfig({
  testDir: './api',

  /* API calls are independent; keep them serial only because of artisan serve. */
  fullyParallel: false,

  forbidOnly: isCI,

  retries: isCI ? 1 : 0,

  workers: workerCount,

  /* HTML + JSON + JUnit so the run is consumable by humans and CI alike. */
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

    /* Send/Accept JSON by default; helpers add the bearer token per request. */
    extraHTTPHeaders: {
      Accept: 'application/json',
    },

    /* Keep request/response traces on first retry for debugging. */
    trace: 'on-first-retry',
  },

  /* API suite needs no browser project; the default request context is enough. */
  projects: [
    {
      name: 'api',
      testMatch: /.*\.spec\.js/,
    },
  ],

  outputDir: path.resolve(__dirname, 'test-results/artifacts'),
});
