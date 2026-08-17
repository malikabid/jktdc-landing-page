// @ts-check

/**
 * Shared helpers. Admin credentials come from the environment so nothing
 * secret is committed:
 *
 *   ADMIN_USER=admin ADMIN_PASS=... npx playwright test
 *
 * Tests that need a login call requireAdmin(test) and skip cleanly when the
 * credentials are absent, so the public and security suites still run anywhere.
 */

const ADMIN_USER = process.env.ADMIN_USER || '';
const ADMIN_PASS = process.env.ADMIN_PASS || '';

const hasAdminCredentials = Boolean(ADMIN_USER && ADMIN_PASS);

/** Skip the current test/suite when admin credentials were not supplied. */
function requireAdmin(testOrInfo) {
  testOrInfo.skip(
    !hasAdminCredentials,
    'Set ADMIN_USER and ADMIN_PASS to run admin-authenticated tests.'
  );
}

/** Log in through the API and return the bearer token. */
async function getToken(request) {
  const res = await request.post('/admin/api/auth/login', {
    data: { username: ADMIN_USER, password: ADMIN_PASS },
  });
  if (!res.ok()) {
    throw new Error(`Login failed (HTTP ${res.status()}): ${await res.text()}`);
  }
  const body = await res.json();
  if (!body.token) throw new Error('Login response contained no token');
  return body.token;
}

/** Put a valid session into localStorage so admin pages render. */
async function loginViaBrowser(page, request) {
  const token = await getToken(request);
  const me = await request.get('/admin/api/auth/me', {
    headers: { Authorization: `Bearer ${token}` },
  });
  const user = (await me.json()).user;

  // The layout reads localStorage on load, so seed it before navigating.
  await page.goto('/admin/');
  await page.evaluate(
    ([t, u]) => {
      localStorage.setItem('dotk_admin_token', t);
      localStorage.setItem('dotk_admin_user', JSON.stringify(u));
    },
    [token, user]
  );
  return { token, user };
}

/**
 * Build a syntactically valid PDF of an approximate size, in memory.
 * Used to exercise the upload size limits without committing binaries.
 */
function makePdf(sizeBytes) {
  const head = Buffer.from(
    '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n' +
      '2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\n'
  );
  const tail = Buffer.from('trailer<</Root 1 0 R>>\n%%EOF\n');
  const padLength = Math.max(0, sizeBytes - head.length - tail.length - 2);
  const pad = Buffer.concat([
    Buffer.from('%'),
    Buffer.alloc(padLength, 0x41),
    Buffer.from('\n'),
  ]);
  return Buffer.concat([head, pad, tail]);
}

/** A file that is PHP source regardless of what it claims to be. */
function makePhpPayload() {
  return Buffer.from('<?php echo "PWNED"; ?>');
}

/** Create a notification via the API and return its id. */
async function createNotification(request, token, overrides = {}) {
  const res = await request.post('/admin/api/notifications', {
    headers: { Authorization: `Bearer ${token}` },
    data: {
      title: 'E2E test notification',
      description: 'Created by the automated test suite. Safe to delete.',
      category: 'Official',
      priority: 'low',
      publish_date: new Date().toISOString().split('T')[0],
      status: 'draft',
      ...overrides,
    },
  });
  if (!res.ok()) {
    throw new Error(`Create failed (HTTP ${res.status()}): ${await res.text()}`);
  }
  return (await res.json()).notification.id;
}

/** Best-effort cleanup. */
async function deleteNotification(request, token, id) {
  if (!id) return;
  await request.delete(`/admin/api/notifications/${id}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
}

module.exports = {
  ADMIN_USER,
  ADMIN_PASS,
  hasAdminCredentials,
  requireAdmin,
  getToken,
  loginViaBrowser,
  makePdf,
  makePhpPayload,
  createNotification,
  deleteNotification,
};
