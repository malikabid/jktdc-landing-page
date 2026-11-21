// @ts-check
const { test, expect } = require('@playwright/test');
const {
  requireAdmin,
  getToken,
  loginViaBrowser,
  makePdf,
  makePhpPayload,
  createNotification,
  deleteNotification,
} = require('./helpers');

/**
 * Document uploads. Covers the reported bug (multi-MB uploads failing), the
 * error reporting that made it hard to diagnose, and the upload security
 * regressions that must never come back.
 *
 * Requires ADMIN_USER / ADMIN_PASS.
 */

test.beforeEach(({}, testInfo) => requireAdmin(testInfo));

function pdfFile(name, sizeBytes) {
  return { name, mimeType: 'application/pdf', buffer: makePdf(sizeBytes) };
}

test.describe('Notification document upload', () => {
  let token;
  let notificationId;

  test.beforeEach(async ({ request }) => {
    token = await getToken(request);
    notificationId = await createNotification(request, token, {
      title: `E2E upload target ${Date.now()}`,
    });
  });

  test.afterEach(async ({ request }) => {
    await deleteNotification(request, token, notificationId);
  });

  const auth = () => ({ Authorization: `Bearer ${token}` });

  test('accepts a small PDF', async ({ request }) => {
    const res = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: { label: 'Small doc', document: pdfFile('small.pdf', 500 * 1024) },
    });
    expect(res.status()).toBe(201);
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(body.document.file_path).toMatch(/^\/pub\/notifications\/notification_\d+_/);
  });

  // The reported bug: anything over the old 2MB limit failed.
  test('accepts a 3MB PDF (the originally reported failure)', async ({ request }) => {
    const res = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: { label: '3MB doc', document: pdfFile('three-mb.pdf', 3 * 1024 * 1024) },
    });
    expect(res.status(), await res.text()).toBe(201);
    expect((await res.json()).success).toBe(true);
  });

  test('accepts a 9MB PDF, the upper end of the advertised 10MB limit', async ({ request }) => {
    const res = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: { label: '9MB doc', document: pdfFile('nine-mb.pdf', 9 * 1024 * 1024) },
    });
    expect(res.status(), await res.text()).toBe(201);
    expect((await res.json()).success).toBe(true);
  });

  test('rejects an oversized upload with a message that names the size', async ({ request }) => {
    const res = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: { label: 'huge', document: pdfFile('huge.pdf', 40 * 1024 * 1024) },
    });
    expect(res.status()).toBe(413);

    const error = (await res.json()).error || '';
    // Must be actionable, not the old generic "File upload failed"
    expect(error).toMatch(/large|size|exceed/i);
    expect(error).not.toBe('File upload failed');
  });

  test('falls back to the filename when no label is given', async ({ request }) => {
    const res = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: { label: '', document: pdfFile('unlabelled.pdf', 200 * 1024) },
    });
    expect(res.status()).toBe(201);
    // A blank label previously stored a nameless document
    expect((await res.json()).document.name).toBe('unlabelled.pdf');
  });

  test('uploaded documents appear on the notification and can be deleted', async ({ request }) => {
    const upload = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: { label: 'Attachment', document: pdfFile('attach.pdf', 300 * 1024) },
    });
    const docId = (await upload.json()).document.id;

    const show = await (
      await request.get(`/admin/api/notifications/${notificationId}`, { headers: auth() })
    ).json();
    expect(show.notification.documents.map((d) => d.id)).toContain(docId);

    // The stored file is actually reachable
    const filePath = show.notification.documents.find((d) => d.id === docId).file_path;
    expect((await request.get(filePath)).status()).toBe(200);

    const del = await request.delete(
      `/admin/api/notifications/${notificationId}/documents/${docId}`,
      { headers: auth() }
    );
    expect(del.status()).toBe(200);

    const after = await (
      await request.get(`/admin/api/notifications/${notificationId}`, { headers: auth() })
    ).json();
    expect(after.notification.documents.map((d) => d.id)).not.toContain(docId);
  });

  test('two uploads in the same second do not collide', async ({ request }) => {
    const [a, b] = await Promise.all([
      request.post(`/admin/api/notifications/${notificationId}/documents`, {
        headers: auth(),
        multipart: { label: 'first', document: pdfFile('a.pdf', 120 * 1024) },
      }),
      request.post(`/admin/api/notifications/${notificationId}/documents`, {
        headers: auth(),
        multipart: { label: 'second', document: pdfFile('b.pdf', 120 * 1024) },
      }),
    ]);

    expect(a.status()).toBe(201);
    expect(b.status()).toBe(201);
    const pathA = (await a.json()).document.file_path;
    const pathB = (await b.json()).document.file_path;
    expect(pathA, 'same-second uploads overwrote each other').not.toBe(pathB);

    // Both files must still exist - a collision used to delete one of them
    expect((await request.get(pathA)).status()).toBe(200);
    expect((await request.get(pathB)).status()).toBe(200);
  });
});

test.describe('Upload security', () => {
  let token;
  let notificationId;

  test.beforeEach(async ({ request }) => {
    token = await getToken(request);
    notificationId = await createNotification(request, token, {
      title: `E2E upload security ${Date.now()}`,
    });
  });

  test.afterEach(async ({ request }) => {
    await deleteNotification(request, token, notificationId);
  });

  const auth = () => ({ Authorization: `Bearer ${token}` });

  test('rejects a .php file sent with a spoofed PDF content type', async ({ request }) => {
    const res = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: {
        label: 'evil',
        // Claims to be a PDF; the extension is what matters
        document: { name: 'evil.php', mimeType: 'application/pdf', buffer: makePhpPayload() },
      },
    });
    expect(res.status()).toBe(400);
    expect((await res.json()).error).toMatch(/only pdf and word/i);
  });

  test('rejects PHP source disguised with a .pdf extension', async ({ request }) => {
    const res = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: {
        label: 'disguised',
        document: { name: 'evil.pdf', mimeType: 'application/pdf', buffer: makePhpPayload() },
      },
    });
    // Extension passes, but the content check must catch it
    expect(res.status()).toBe(400);
    expect((await res.json()).error).toMatch(/content does not match/i);
  });

  test('rejects other executable extensions', async ({ request }) => {
    for (const name of ['shell.phtml', 'script.sh', 'page.html', 'app.js']) {
      const res = await request.post(`/admin/api/notifications/${notificationId}/documents`, {
        headers: auth(),
        multipart: {
          label: name,
          document: { name, mimeType: 'application/pdf', buffer: makePhpPayload() },
        },
      });
      expect(res.status(), `${name} was not rejected`).toBe(400);
    }
  });

  test('a rejected upload leaves no file behind', async ({ request }) => {
    await request.post(`/admin/api/notifications/${notificationId}/documents`, {
      headers: auth(),
      multipart: {
        label: 'disguised',
        document: { name: 'evil.pdf', mimeType: 'application/pdf', buffer: makePhpPayload() },
      },
    });

    const show = await (
      await request.get(`/admin/api/notifications/${notificationId}`, { headers: auth() })
    ).json();
    expect(show.notification.documents, 'rejected upload created a document row').toHaveLength(0);
  });
});

test.describe('Upload error reporting in the UI', () => {
  test('shows a persistent dialog that does not auto-dismiss', async ({ page, request }) => {
    const token = await getToken(request);
    const id = await createNotification(request, token, {
      title: `E2E upload error dialog ${Date.now()}`,
    });

    try {
      await loginViaBrowser(page, request);
      await page.goto(`/admin/notifications/${id}/edit`);
      await expect(page.locator('#notificationForm')).toBeVisible();

      // Make the upload endpoint fail with a specific, recognisable reason
      await page.route(`**/admin/api/notifications/${id}/documents`, (route) =>
        route.fulfill({
          status: 413,
          contentType: 'application/json',
          body: JSON.stringify({ error: 'Upload too large: the request is 40 MB.' }),
        })
      );

      await page.setInputFiles('.doc-file', {
        name: 'too-big.pdf',
        mimeType: 'application/pdf',
        buffer: makePdf(50 * 1024),
      });
      await page.click('#submitBtn');

      const dialog = page.locator('#errorModal');
      await expect(dialog).toBeVisible();
      await expect(dialog).toContainText(/upload too large/i);

      // The old toast auto-hid after 5s; this must still be readable after that
      await page.waitForTimeout(6500);
      await expect(dialog, 'error dialog auto-dismissed').toBeVisible();

      // ...and closes only when asked
      await page.locator('.error-modal__close').click();
      await expect(dialog).toBeHidden();
    } finally {
      await deleteNotification(request, token, id);
    }
  });

  test('client-side validation rejects a disallowed file type', async ({ page, request }) => {
    const token = await getToken(request);
    const id = await createNotification(request, token, {
      title: `E2E client validation ${Date.now()}`,
    });

    try {
      await loginViaBrowser(page, request);
      await page.goto(`/admin/notifications/${id}/edit`);
      await expect(page.locator('#notificationForm')).toBeVisible();

      await page.setInputFiles('.doc-file', {
        name: 'notes.txt',
        mimeType: 'text/plain',
        buffer: Buffer.from('not a document'),
      });
      await page.click('#submitBtn');

      const dialog = page.locator('#errorModal');
      await expect(dialog).toBeVisible();
      await expect(dialog).toContainText(/not an accepted file type/i);
    } finally {
      await deleteNotification(request, token, id);
    }
  });
});
