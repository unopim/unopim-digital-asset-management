# UnoPim DAM — Playwright API Test Suite

End-to-end **API** automation for the UnoPim Digital Asset Management REST API
(`/api/v1/rest/*`). It is a sibling of the UI suite in `tests/e2e-pw` and follows
the same conventions (CommonJS, single-worker by default, `global-setup` auth,
fixtures + helpers, JSDoc).

## What it covers

| Area | Spec | Endpoints |
|------|------|-----------|
| Auth & access control | `api/01-auth-access-control.spec.js` | OAuth token mint/refresh/reuse, 401, invalid/malformed token |
| Asset CRUD | `api/02-asset-crud.spec.js` | upload, show, edit, update, destroy |
| Asset list/search/filter | `api/03-asset-list-search-filter.spec.js` | index, pagination, sort, filter, search |
| File operations | `api/04-asset-files.spec.js` | upload (image/pdf/video/audio), download (signed URL), reupload, invalid/empty/large file |
| Folder management | `api/05-directory.spec.js` | create, get, tree/list, rename, delete, nested folders |
| Tags | `api/06-tags.spec.js` | attach, fetch-by-id, detach (asset-scoped) |
| Metadata (properties) | `api/07-properties-metadata.spec.js` | create, fetch, update, delete |
| Comments | `api/08-comments.spec.js` | create, fetch, update, delete |
| Asset relations | `api/09-asset-relations.spec.js` | folder assignment via upload, linked-resource lookup |
| Permission validation | `api/10-permissions.spec.js` | directory-scoped 403 (env-gated) |

Each endpoint has **positive**, **negative** (missing fields, invalid id, bad
auth, 404), and **edge** (empty/null, special chars, unicode, min/max length,
duplicate, boundary) cases. Status codes asserted: `200, 201, 202, 400, 401,
403, 404, 422` (and `500` is explicitly guarded against on large uploads).

## Project structure

```
tests/api-pw/
├── api/                 # spec files (one per resource area)
├── config/              # env.js — env-driven config + .env loader
├── constants/           # endpoints.js, statusCodes.js, queryParams.js
├── fixtures/            # fixtures.js — api / anonApi / token / uid fixtures
├── helpers/             # asset, folder, tag, property, comment, support helpers
├── test-data/           # testData.js — dynamic data factory + file fixtures
├── utils/               # apiHelper.js (HTTP client), authHelper.js (OAuth)
├── global-setup.js      # mints + verifies the bearer token once
├── playwright.config.js # HTML + JSON + JUnit reporters
├── package.json
├── .env.example
└── README.md
```

Binary upload fixtures are shared with the UI suite (`tests/e2e-pw/assets`) to
avoid duplicating sample files; synthetic files (empty/large/invalid-type) are
generated in memory by `test-data/testData.js`.

## Prerequisites

1. A running UnoPim instance with the DAM package installed and reachable.
2. A **Passport password-grant client** (the DAM API uses `auth:api`):
   ```bash
   php artisan passport:client --password
   ```
   Note the printed **client id** and **secret**.
3. Node 18+.

## Setup

```bash
cd tests/api-pw
npm install
cp .env.example .env     # then fill in the values below
```

Edit `.env`:

```ini
BASE_URL=http://127.0.0.1:8000
OAUTH_TOKEN_URL=/oauth/token
OAUTH_CLIENT_ID=<password client id>
OAUTH_CLIENT_SECRET=<password client secret>
ADMIN_USERNAME=admin@example.com
ADMIN_PASSWORD=admin123
API_LOCALE=en_US
```

> Already have a token? Set `API_TOKEN=<bearer>` instead and the OAuth flow is
> skipped entirely. Real environment variables always override `.env`.

## Authentication model

- `global-setup.js` runs once, mints a bearer token via the OAuth2 **password
  grant** (or uses `API_TOKEN`), verifies it against the assets endpoint, and
  caches it to `.state/api-auth.json`.
- The `token` / `api` fixtures read that cache, so the whole run authenticates
  **once**. `authHelper.js` also exposes `refreshAccessToken()` (refresh-token
  grant) and `fetchAccessToken()` for ad-hoc use.
- `anonApi` and `invalidTokenApi` fixtures drive the 401 / access-control cases.

## Run

```bash
npx playwright test            # run everything
npx playwright show-report     # open the HTML report
```

Useful variants:

```bash
npx playwright test api/02-asset-crud.spec.js        # one file
npx playwright test -g "upload"                      # by title
npm run test:auth                                    # auth suite only
WORKERS=4 npx playwright test                        # parallel (php-fpm targets only)
```

> Keep `workers=1` (the default) against `php artisan serve`, which is
> single-threaded — same constraint as the e2e suite.

## Reporting

`playwright.config.js` enables three reporters out of the box:

- **HTML** → `playwright-report/` (`npx playwright show-report`)
- **JSON** → `test-results/results.json`
- **JUnit** → `test-results/results.xml` (for CI)

Every API call is auto-attached to its test as JSON containing the **request
payload**, **response payload**, **status**, and **response time** (responses
slower than `SLOW_RESPONSE_MS` are flagged). Secrets (passwords, tokens) are
masked in attachments.

## Directory-scoped permission (403) tests

Reproducing a *denied* directory needs a permission-scoped user + token, which
the REST API cannot provision for itself. `api/10-permissions.spec.js` runs
those checks when you supply, against a real dataset:

```ini
SCOPED_API_TOKEN=<token for a custom-role user with limited grants>
DENIED_ASSET_ID=<asset id that user must NOT access>
DENIED_DIR_ID=<directory id that user must NOT access>
```

They skip cleanly when unset. The DB-level equivalents already live in the PHP
Pest suite under `tests/Feature/Api/*`.

## Notes on API scope

The suite targets exactly the endpoints the DAM package exposes
(`src/Routes/V1/asset-routes.php`):

- **Tags** are asset-scoped: attach/detach a tag by name to/from an asset, and
  fetch a single tag by its id. There is no "list an asset's tags" or standalone
  tag-CRUD endpoint, so "Create/List/Delete Tag" map onto attach / fetch-by-id /
  detach. Attach & detach return **201**; a duplicate attach or a detach of an
  unattached tag returns **404**. Set `TAG_ID` to exercise the positive fetch.
- **Metadata** is modelled by the **asset properties** API.
- **Folder assignment** happens at **upload** time via `directory_id`; there is
  no separate assign/detach endpoint.
- **Linked resources** are **read-only** over REST (mappings are created by the
  import/UI layer); the positive fetch runs only when `LINKED_RESOURCE_ID` is set.
- **Bulk asset delete** is not exposed by the REST API (single delete only).

The list endpoint's filter/sort/pagination query convention is centralised in
`constants/queryParams.js`; if your AdminApi version expects different keys,
adjust that one file and all list/search/filter/sort specs follow.
