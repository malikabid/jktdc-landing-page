# End-to-end tests

Playwright suite covering the public site, the admin panel, and the security
regressions we do not want back. Playwright is MIT-licensed and free.

## Setup

```bash
cd site/tests
npm install
npx playwright install chromium
```

## Running

Both environments use self-signed certificates; the config already ignores TLS
errors.

```bash
npm run test:local      # https://kashmirtourismofficial.test
npm run test:staging    # https://staging.kashmirtourismofficial.com
npm test                # whatever BASE_URL points at (defaults to local)
```

Useful variations:

```bash
npx playwright test e2e/security.spec.js        # one file
npx playwright test -g "upload"                 # by name
npm run test:headed                             # watch it drive the browser
npm run test:ui                                 # interactive runner
npm run report                                  # open the last HTML report
```

## Admin credentials

Tests that need a login read them from the environment, so nothing secret is
committed. Without them, the admin and upload suites **skip** and the public,
security and smoke suites still run:

```bash
ADMIN_USER=admin ADMIN_PASS=yourpassword npm run test:staging
```

Local dev uses the seeded `admin` account. Staging and production have
different credentials — ask whoever owns the environment.

## What each file covers

| File | Needs login | Covers |
|---|---|---|
| `smoke.spec.js` | no | health endpoint, API index, public pages load, admin login page. Run this first after a deploy. |
| `security.spec.js` | no | `/admin/info` is gone, no phpinfo anywhere, `.env`/config unreadable, every admin API returns 401 unauthenticated, upload directories refuse to execute `.php` and expose no directory index. |
| `public-notifications.spec.js` | no | public feed shape matches the legacy JSON, publish/expiry windowing, priority ordering, no admin fields leak, page renders, attachment links resolve, and the static-JSON fallback works when the API is down. |
| `admin-notifications.spec.js` | yes | full CRUD over the API, validation rejections, drafts/expired staying off the public feed, stats, plus list/create/edit pages rendering and a create round-trip through the UI. |
| `admin-uploads.spec.js` | yes | uploads at 0.5/3/9 MB, oversized rejected with a size-specific message, blank-label filename fallback, delete round-trip, same-second collision safety, `.php` and disguised-PHP rejection, and the persistent error dialog. |

## Notes and gotchas

- **Tests run serially** (`workers: 1`). They share server-side state — the same
  notifications and upload directory — so parallel runs interfere.
- **They create and delete real data** in whatever environment you point them
  at. Everything is cleaned up in `afterEach`, and test records are titled
  `E2E ...` so strays are easy to spot. Do not point them at production.
- **Some failures are environment differences, not bugs.** A checkout without
  the upload-hardening merge will fail the `/admin/info` and
  `pub/tenders` / `pub/events` tests. That is the suite doing its job.
- The local Apache config allows directory indexes while the hosts do not, so
  the directory-listing assertions can fail locally and pass on the servers.
- Failures capture a trace, screenshot and video under `test-results/`. Open a
  trace with `npx playwright show-trace test-results/<dir>/trace.zip`.

## Deployment

`tests/`, `package.json`, `playwright.config.js` and the report directories are
excluded from the FTP deploy in both workflow files, so none of this reaches the
web server. If you add files here, keep them inside `tests/`.
