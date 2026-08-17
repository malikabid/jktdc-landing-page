// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Both the local dev site and staging use self-signed certificates, so TLS
 * errors are ignored. Point at an environment with BASE_URL, e.g.
 *
 *   BASE_URL=https://staging.kashmirtourismofficial.com npx playwright test
 */
const BASE_URL = process.env.BASE_URL || 'https://kashmirtourismofficial.test';

module.exports = defineConfig({
  testDir: './e2e',
  // Tests share server-side state (notifications, uploads), so run serially.
  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: [
    ['list'],
    ['html', { open: 'never', outputFolder: 'playwright-report' }],
  ],
  timeout: 60_000,
  expect: { timeout: 10_000 },
  use: {
    baseURL: BASE_URL,
    ignoreHTTPSErrors: true,
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
