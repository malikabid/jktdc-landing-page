// @ts-check
const { test, expect } = require('@playwright/test');
const {
  requireAdmin,
  getToken,
  loginViaBrowser,
  createNotification,
  deleteNotification,
} = require('./helpers');

/**
 * Admin CRUD for notifications, over the API and through the UI.
 * Requires ADMIN_USER / ADMIN_PASS.
 */

test.beforeEach(({}, testInfo) => requireAdmin(testInfo));

test.describe('Notifications API', () => {
  let token;
  const created = [];

  test.beforeAll(async ({ playwright, baseURL }) => {
    if (!process.env.ADMIN_USER || !process.env.ADMIN_PASS) return;
    const request = await playwright.request.newContext({ baseURL, ignoreHTTPSErrors: true });
    token = await getToken(request);
    await request.dispose();
  });

  test.afterEach(async ({ request }) => {
    while (created.length) {
      await deleteNotification(request, token, created.pop());
    }
  });

  test('lists notifications with the expected envelope', async ({ request }) => {
    const res = await request.get('/admin/api/notifications', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);

    const body = await res.json();
    expect(body).toHaveProperty('notifications');
    expect(body).toHaveProperty('total');
    expect(body).toHaveProperty('categories');
    expect(body).toHaveProperty('priorities');
    expect(Array.isArray(body.notifications)).toBe(true);
  });

  test('creates, reads, updates and deletes a notification', async ({ request }) => {
    const auth = { Authorization: `Bearer ${token}` };

    const id = await createNotification(request, token, { title: 'E2E lifecycle test' });
    created.push(id);

    const show = await request.get(`/admin/api/notifications/${id}`, { headers: auth });
    expect(show.status()).toBe(200);
    expect((await show.json()).notification.title).toBe('E2E lifecycle test');

    const update = await request.put(`/admin/api/notifications/${id}`, {
      headers: auth,
      data: { title: 'E2E lifecycle test (edited)', status: 'published' },
    });
    expect(update.status()).toBe(200);

    const after = await (await request.get(`/admin/api/notifications/${id}`, { headers: auth })).json();
    expect(after.notification.title).toBe('E2E lifecycle test (edited)');
    expect(after.notification.status).toBe('published');

    const del = await request.delete(`/admin/api/notifications/${id}`, { headers: auth });
    expect(del.status()).toBe(200);
    created.pop();

    const gone = await request.get(`/admin/api/notifications/${id}`, { headers: auth });
    expect(gone.status()).toBe(404);
  });

  test('rejects invalid input', async ({ request }) => {
    const auth = { Authorization: `Bearer ${token}` };
    const today = new Date().toISOString().split('T')[0];

    const missingTitle = await request.post('/admin/api/notifications', {
      headers: auth,
      data: { description: 'x', publish_date: today, category: 'Official' },
    });
    expect(missingTitle.status()).toBe(400);

    const badPriority = await request.post('/admin/api/notifications', {
      headers: auth,
      data: { title: 'x', description: 'x', publish_date: today, category: 'Official', priority: 'URGENT' },
    });
    expect(badPriority.status()).toBe(400);
    expect((await badPriority.json()).error).toMatch(/priority/i);

    const badDates = await request.post('/admin/api/notifications', {
      headers: auth,
      data: {
        title: 'x', description: 'x', category: 'Official',
        publish_date: '2026-08-01', expiry_date: '2026-07-01',
      },
    });
    expect(badDates.status()).toBe(400);
    expect((await badDates.json()).error).toMatch(/expiry/i);
  });

  test('draft and expired notifications stay off the public feed', async ({ request }) => {
    const auth = { Authorization: `Bearer ${token}` };

    const draftId = await createNotification(request, token, {
      title: 'E2E draft must stay hidden', status: 'draft',
    });
    created.push(draftId);

    const expiredId = await createNotification(request, token, {
      title: 'E2E expired must stay hidden',
      status: 'published',
      publish_date: '2020-01-01',
      expiry_date: '2020-12-31',
    });
    created.push(expiredId);

    const publicFeed = await (await request.get('/admin/api/public/notifications')).json();
    const ids = publicFeed.map((n) => n.id);
    expect(ids).not.toContain(draftId);
    expect(ids).not.toContain(expiredId);

    // ...and a published, in-window one does appear
    const liveId = await createNotification(request, token, {
      title: 'E2E live must be visible', status: 'published',
    });
    created.push(liveId);

    const refreshed = await (await request.get('/admin/api/public/notifications')).json();
    expect(refreshed.map((n) => n.id)).toContain(liveId);
  });

  test('reports statistics', async ({ request }) => {
    const res = await request.get('/admin/api/notifications/stats', {
      headers: { Authorization: `Bearer ${token}` },
    });
    expect(res.status()).toBe(200);
    const stats = await res.json();
    for (const key of ['total', 'published', 'draft', 'archived', 'live']) {
      expect(stats, `stats missing ${key}`).toHaveProperty(key);
      expect(typeof stats[key]).toBe('number');
    }
  });
});

test.describe('Notifications admin UI', () => {
  test('list page renders notifications and stats', async ({ page, request }) => {
    await loginViaBrowser(page, request);
    await page.goto('/admin/notifications');

    await expect(page.locator('h1')).toContainText('Notifications Management');
    await expect(page.locator('#totalCount')).not.toHaveText('-');

    const rows = page.locator('#notificationsBody tr');
    await expect(rows.first()).toBeVisible();
    await expect(page.locator('#notificationsBody')).not.toContainText('Error loading');
  });

  test('create page renders its form with sane defaults', async ({ page, request }) => {
    await loginViaBrowser(page, request);
    await page.goto('/admin/notifications/create');

    await expect(page.locator('#title')).toBeVisible();
    await expect(page.locator('#description')).toBeVisible();
    // Publish date defaults to today
    const today = new Date().toISOString().split('T')[0];
    await expect(page.locator('#publish_date')).toHaveValue(today);
    await expect(page.locator('#status')).toHaveValue('draft');
  });

  test('edit page loads existing values into the form', async ({ page, request }) => {
    const token = await getToken(request);
    const id = await createNotification(request, token, {
      title: 'E2E edit form population',
      description: 'Populated by the test suite.',
      priority: 'high',
      status: 'published',
    });

    try {
      await loginViaBrowser(page, request);
      await page.goto(`/admin/notifications/${id}/edit`);

      // The form is hidden until the fetch resolves
      await expect(page.locator('#notificationForm')).toBeVisible();
      await expect(page.locator('#title')).toHaveValue('E2E edit form population');
      await expect(page.locator('#priority')).toHaveValue('high');
      await expect(page.locator('#status')).toHaveValue('published');
    } finally {
      await deleteNotification(request, token, id);
    }
  });

  test('creating a notification through the UI persists it', async ({ page, request }) => {
    const token = await getToken(request);
    const title = `E2E UI create ${Date.now()}`;
    let id;

    try {
      await loginViaBrowser(page, request);
      await page.goto('/admin/notifications/create');

      await page.fill('#title', title);
      await page.fill('#description', 'Created through the admin UI by the test suite.');
      await page.selectOption('#category', 'Official');
      await page.selectOption('#priority', 'low');
      await page.selectOption('#status', 'draft');
      await page.click('#submitBtn');

      await page.waitForURL('**/admin/notifications', { timeout: 20_000 });

      const list = await (
        await request.get('/admin/api/notifications', {
          headers: { Authorization: `Bearer ${token}` },
        })
      ).json();
      const found = list.notifications.find((n) => n.title === title);
      expect(found, 'notification created via UI was not persisted').toBeTruthy();
      id = found.id;
    } finally {
      await deleteNotification(request, token, id);
    }
  });
});
