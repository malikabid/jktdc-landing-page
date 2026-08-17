// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * The public notifications feed and page.
 *
 * These run without credentials, so they work against any environment.
 */

test.describe('Public notifications API', () => {
  test('returns a JSON array in the legacy notifications.json shape', async ({ request }) => {
    const res = await request.get('/admin/api/public/notifications');
    expect(res.status()).toBe(200);
    expect(res.headers()['content-type']).toContain('application/json');

    const body = await res.json();
    expect(Array.isArray(body)).toBe(true);

    // The frontend renderer and the JSON fallback both depend on this shape.
    for (const n of body) {
      expect(n).toHaveProperty('id');
      expect(n).toHaveProperty('title');
      expect(n).toHaveProperty('description');
      expect(n).toHaveProperty('priority');
      expect(n).toHaveProperty('publishDate');
      expect(n).toHaveProperty('category');
      expect(n).toHaveProperty('documents');
      expect(Array.isArray(n.documents)).toBe(true);
      // fileUrl/fileName may be null, but the keys must exist
      expect(Object.keys(n)).toEqual(expect.arrayContaining(['fileUrl', 'fileName', 'isActive']));
    }
  });

  test('only exposes notifications inside their publish/expiry window', async ({ request }) => {
    const body = await (await request.get('/admin/api/public/notifications')).json();
    const today = new Date().toISOString().split('T')[0];

    for (const n of body) {
      expect(n.publishDate <= today,
        `notification ${n.id} publishes in the future (${n.publishDate})`).toBe(true);
      if (n.expiryDate) {
        expect(n.expiryDate >= today,
          `notification ${n.id} expired on ${n.expiryDate}`).toBe(true);
      }
    }
  });

  test('is sorted by priority, most urgent first', async ({ request }) => {
    const body = await (await request.get('/admin/api/public/notifications')).json();
    const rank = { critical: 0, high: 1, medium: 2, low: 3 };
    const ranks = body.map((n) => rank[n.priority]);
    expect(ranks).toEqual([...ranks].sort((a, b) => a - b));
  });

  test('never leaks admin-only fields', async ({ request }) => {
    const body = await (await request.get('/admin/api/public/notifications')).json();
    for (const n of body) {
      for (const leaked of ['created_by', 'updated_by', 'status', 'created_at']) {
        expect(n, `public payload leaked ${leaked}`).not.toHaveProperty(leaked);
      }
    }
  });
});

test.describe('Public notifications page', () => {
  test('renders notifications fetched from the API', async ({ page, request }) => {
    const apiItems = await (await request.get('/admin/api/public/notifications')).json();

    await page.goto('/pub/pages/notifications.html');
    const container = page.locator('#all-notifications');
    await expect(container).toBeVisible();

    if (apiItems.length === 0) {
      await expect(container).toContainText(/no notifications/i);
      return;
    }

    const items = container.locator('.notification-item');
    await expect(items).toHaveCount(apiItems.length);
    // The first API item's title should appear on the page
    await expect(container).toContainText(apiItems[0].title.trim().slice(0, 30));
  });

  test('shows a download link for notifications that have a file', async ({ page, request }) => {
    const apiItems = await (await request.get('/admin/api/public/notifications')).json();
    const withFile = apiItems.filter((n) => n.fileUrl);
    test.skip(withFile.length === 0, 'No published notification currently has an attachment');

    await page.goto('/pub/pages/notifications.html');
    const links = page.locator('#all-notifications a.download-btn');
    await expect(links).toHaveCount(withFile.length);
    await expect(links.first()).toHaveAttribute('href', withFile[0].fileUrl);
  });

  test('attachment links actually resolve', async ({ request }) => {
    const apiItems = await (await request.get('/admin/api/public/notifications')).json();
    const withFile = apiItems.filter((n) => n.fileUrl);
    test.skip(withFile.length === 0, 'No published notification currently has an attachment');

    for (const n of withFile) {
      const res = await request.get(n.fileUrl);
      expect(res.status(), `${n.fileUrl} is broken`).toBe(200);
    }
  });

  test('falls back to the static JSON when the API is unavailable', async ({ page }) => {
    // Force the API to fail so the fallback path is exercised
    await page.route('**/admin/api/public/notifications', (route) =>
      route.fulfill({ status: 500, body: 'boom' })
    );

    await page.goto('/pub/pages/notifications.html');
    const container = page.locator('#all-notifications');
    await expect(container).toBeVisible();

    // The fallback fetch is async - wait for it to render rather than sampling
    // immediately, otherwise this races the page's own initialisation.
    await expect(container.locator('.notification-item').first()).toBeVisible();

    await expect
      .poll(
        () => page.evaluate(() => window.notificationsManager?.notifications?.length ?? 0),
        { message: 'fallback produced no notifications' }
      )
      .toBeGreaterThan(0);
  });
});
