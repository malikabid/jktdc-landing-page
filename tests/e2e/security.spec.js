// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Security regressions. These encode bugs that were live in production and
 * must never come back. They need no credentials.
 */

test.describe('Secrets are not exposed', () => {
  test('/admin/info is gone (it leaked JWT_SECRET and DB_PASSWORD)', async ({ request }) => {
    const res = await request.get('/admin/info');
    expect(res.status(), '/admin/info must not be served').toBe(404);

    const body = await res.text();
    expect(body).not.toContain('JWT_SECRET');
    expect(body).not.toContain('DB_PASSWORD');
  });

  test('no endpoint serves a phpinfo() dump', async ({ request }) => {
    for (const path of ['/admin/info', '/admin/phpinfo.php', '/info.php', '/phpinfo.php']) {
      const res = await request.get(path);
      if (res.status() === 200) {
        const body = await res.text();
        expect(body, `${path} exposes phpinfo`).not.toContain('PHP Version');
      }
    }
  });

  test('environment and config files are not readable', async ({ request }) => {
    for (const path of ['/admin/.env', '/admin/composer.json', '/admin/phinx.php']) {
      const res = await request.get(path);
      expect([403, 404], `${path} returned ${res.status()}`).toContain(res.status());
    }
  });
});

test.describe('Admin APIs reject unauthenticated access', () => {
  const protectedEndpoints = [
    '/admin/api/notifications',
    '/admin/api/notifications/stats',
    '/admin/api/tenders',
    '/admin/api/events',
    '/admin/api/users',
  ];

  for (const endpoint of protectedEndpoints) {
    test(`${endpoint} returns 401 without a token`, async ({ request }) => {
      const res = await request.get(endpoint);
      expect(res.status()).toBe(401);
    });
  }

  test('a forged/garbage bearer token is rejected', async ({ request }) => {
    const res = await request.get('/admin/api/notifications', {
      headers: { Authorization: 'Bearer not.a.real.token' },
    });
    expect(res.status()).toBe(401);
  });

  test('write endpoints reject unauthenticated requests', async ({ request }) => {
    const post = await request.post('/admin/api/notifications', {
      data: { title: 'should not be created' },
    });
    expect(post.status()).toBe(401);

    const del = await request.delete('/admin/api/notifications/1');
    expect(del.status()).toBe(401);
  });
});

test.describe('Upload directories cannot execute code', () => {
  // The vhost maps every *.php under the docroot to PHP-FPM with no path
  // scoping, so each upload directory needs its own .htaccess guard.
  const uploadDirs = ['/pub/notifications', '/pub/tenders', '/pub/events'];

  for (const dir of uploadDirs) {
    test(`${dir} refuses to execute .php`, async ({ request }) => {
      const res = await request.get(`${dir}/definitely-not-here.php`);
      // 403 = guard is active. 404 would mean the guard is missing and only
      // the absent file saved us, so it is not an acceptable pass.
      expect(res.status(), `${dir} is missing its .htaccess guard`).toBe(403);
    });

    test(`${dir} does not allow directory listing`, async ({ request }) => {
      const res = await request.get(`${dir}/`);
      if (res.status() === 200) {
        const body = await res.text();
        expect(body, `${dir} exposes a directory index`).not.toContain('Index of');
      }
    });
  }
});
