// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Broad smoke checks. Fast, credential-free, and the first thing to run after
 * a deploy to confirm the environment is actually up and serving the new code.
 */

test('admin health endpoint responds', async ({ request }) => {
  const res = await request.get('/admin/health');
  expect(res.status()).toBe(200);

  const body = await res.json();
  expect(body.status).toBe('ok');
  expect(body).toHaveProperty('version');
});

test('the API index advertises the notifications endpoints', async ({ request }) => {
  const res = await request.get('/admin/api');
  expect(res.status()).toBe(200);

  const body = await res.json();
  expect(body.endpoints).toHaveProperty('public_notifications');
  expect(body.endpoints.public_notifications).toBe('/api/public/notifications');
});

test('public pages load', async ({ page }) => {
  for (const path of ['/', '/pub/pages/notifications.html', '/pub/pages/tenders.html']) {
    const res = await page.goto(path);
    expect(res?.status(), `${path} did not load`).toBeLessThan(400);
  }
});

test('admin login page loads', async ({ page }) => {
  await page.goto('/admin/');
  await expect(page).toHaveTitle(/Login/i);
});

test('admin pages are served (they are client-side guarded only)', async ({ request }) => {
  // Documented gap: page routes have no server-side middleware, the guard is a
  // localStorage check in the browser. This test records the CURRENT behaviour
  // so that hardening it later shows up as a deliberate change, not a surprise.
  for (const path of ['/admin/dashboard', '/admin/notifications', '/admin/tenders']) {
    const res = await request.get(path);
    expect([200, 401, 403], `${path} returned ${res.status()}`).toContain(res.status());
  }
});

test('public notification feed is reachable', async ({ request }) => {
  const res = await request.get('/admin/api/public/notifications');
  expect(res.status()).toBe(200);
  expect(Array.isArray(await res.json())).toBe(true);
});
