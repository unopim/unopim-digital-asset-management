# UnoPim DAM — Playwright API Test Suite

End-to-end **API** automation for the UnoPim Digital Asset Management REST API
(`/api/v1/rest/*`). It is a sibling of the UI suite in `tests/e2e-pw` and follows
the same conventions (CommonJS, single-worker by default, `global-setup` auth,
fixtures + helpers, JSDoc).

**97 tests across 10 specs.** With the credentials and seed data described under
[Environment variables](#environment-variables) provided, the suite runs with
**zero skips**. Every endpoint is covered with **positive**, **negative**
(missing fields, invalid id, bad auth, 404/422) and **edge** (empty/null,
special chars, unicode, min/max length, duplicate, large/forbidden file) cases.
Status codes asserted: `200, 201, 202, 400, 401, 403, 404, 422` (and `500` is
explicitly guarded against).

## What it covers

| # | Area | Spec | Tests |
|---|------|------|-------|
| 01 | Auth & access control | `api/01-auth-access-control.spec.js` | 7 |
| 02 | Asset CRUD | `api/02-asset-crud.spec.js` | 13 |
| 03 | Asset list / search / filter | `api/03-asset-list-search-filter.spec.js` | 9 |
| 04 | File operations | `api/04-asset-files.spec.js` | 13 |
| 05 | Folder (directory) management | `api/05-directory.spec.js` | 13 |
| 06 | Tags | `api/06-tags.spec.js` | 10 |
| 07 | Metadata (properties) | `api/07-properties-metadata.spec.js` | 12 |
| 08 | Comments | `api/08-comments.spec.js` | 10 |
| 09 | Asset relations | `api/09-asset-relations.spec.js` | 5 |
| 10 | Permission validation (403) | `api/10-permissions.spec.js` | 5 |

## Test coverage

<details>
<summary><b>01 — Authentication &amp; access control</b> (7)</summary>

- authorized user can reach a protected endpoint
- anonymous request is rejected with 401
- invalid bearer token is rejected with 401
- malformed Authorization header is rejected
- a valid token is reusable across multiple requests
- _Token lifecycle:_ mints a token and it authenticates the DAM API
- _Token lifecycle:_ refreshes the access token when a refresh_token is issued
</details>

<details>
<summary><b>02 — Asset CRUD</b> (13)</summary>

- **create:** uploads an image asset → 201 and persists
- **create:** rejects upload without `directory_id` → 422
- **create:** rejects upload to a non-existent directory
- **read:** shows an existing asset → 200 with schema
- **read:** returns 404 for a non-existent asset
- **read:** returns an edit payload → 200
- **read:** edit on a non-existent asset → 404
- **update:** renames an asset → 200 and persists
- **update:** update on a non-existent asset → 404
- **update:** accepts special characters in the file name
- **delete:** deletes an asset → 200 and is then gone
- **delete:** delete on a non-existent asset → 404
- **delete:** double delete returns 404 on the second call (idempotency boundary)
</details>

<details>
<summary><b>03 — Asset list / search / filter</b> (9)</summary>

- **list:** lists assets → 200 with an array of normalised items
- **list:** accepts a pagination request and honours page size when echoed
- **list:** page 1 and page 2 differ when the server confirms paging
- **sort:** accepts sort by id descending and is well-formed
- **sort:** accepts sort ascending and stays healthy
- **filter:** accepts a `file_type` filter; filtered rows are consistent
- **filter:** accepts an `extension` filter and returns a well-formed array
- **filter:** accepts a name search (`code` → file_name) and returns a well-formed array
- **filter:** accepts a combination filter (`file_type` + `extension`)
</details>

<details>
<summary><b>04 — File operations</b> (13)</summary>

- **types:** uploads an image / pdf / video / audio file → 201 (4 cases)
- **validation:** empty file upload
- **validation:** invalid file type (`.exe`)
- **validation:** large file upload (never 500)
- **validation:** upload with no `files` field → 422
- **download:** returns a signed `download_url` that streams the file
- **download:** download of a non-existent asset → 404
- **download:** signed download route rejects a tampered signature → 403
- **reupload:** replaces an existing asset binary → 201
- **reupload:** reupload to a non-existent asset is rejected
</details>

<details>
<summary><b>05 — Folder (directory) management</b> (13)</summary>

- **create:** creates a root folder → 201 and persists
- **create:** rejects creation without a name → 422
- **create:** rejects an empty name → 422
- **create:** accepts special characters in the folder name
- **read:** returns the directory tree → 200
- **read:** returns a single folder by id → 200
- **read:** returns 404 for a non-existent folder
- **update:** renames a folder → 200 and persists
- **update:** rename of a non-existent folder → 404
- **delete:** deletes a folder → 202 (queued)
- **delete:** delete of a non-existent folder → 404
- **nested:** creates a child under a parent and links them
- **nested:** rejects a child under a non-existent parent
</details>

<details>
<summary><b>06 — Tags</b> (10)</summary>

- **attach:** attaches a tag to an asset → 201
- **attach:** re-attaching the same tag → 404 (already exists)
- **attach:** rejects attaching without required fields → 422
- **attach:** rejects attaching to a non-existent asset → 422
- **attach:** rejects a tag longer than 100 chars → 422
- **attach:** accepts a tag with special characters
- **fetch:** returns 404 for a non-existent tag id
- **fetch:** fetches a tag by id → 200 _(needs `TAG_ID`)_
- **detach:** detaches an attached tag → 201, detached state observable
- **detach:** rejects detach without required fields → 422
</details>

<details>
<summary><b>07 — Metadata (properties)</b> (12)</summary>

- **create:** creates a property → 200 and persists
- **create:** rejects creation with missing fields → 422
- **create:** rejects a name shorter than 3 chars → 422
- **create:** rejects an unknown locale → 400
- **create:** rejects a duplicate name for the same asset+locale → 422
- **fetch:** fetches a property by id → 200
- **fetch:** returns 404 for a non-existent property
- **update:** updates a property → 200 and persists
- **update:** rejects update with missing required fields → 422
- **update:** update on a non-existent property → 404
- **delete:** deletes a property → 200 and is then gone
- **delete:** delete on a non-existent property → 404
</details>

<details>
<summary><b>08 — Comments</b> (10)</summary>

- **create:** creates a comment → 201 and is retrievable
- **create:** rejects creation with missing fields → 422
- **create:** rejects a comment for a non-existent asset
- **create:** accepts special characters and unicode in the body
- **read:** fetches a comment by id → 200
- **read:** returns 404 for a non-existent comment
- **update:** updates a comment → 200 and persists
- **update:** update on a non-existent comment → 404
- **delete:** deletes a comment → 200 and is then gone
- **delete:** delete on a non-existent comment → 404
</details>

<details>
<summary><b>09 — Asset relations</b> (5)</summary>

- assigns an asset to a folder on upload and the asset is then readable
- assignment to a non-existent folder is rejected
- returns 404 for a non-existent linked resource
- linked resource requires authentication → 401 for anonymous
- fetches an existing linked resource → 200 _(needs `LINKED_RESOURCE_ID`)_
</details>

<details>
<summary><b>10 — Permission validation (directory-scoped 403)</b> (5)</summary>

_All need `SCOPED_API_TOKEN` (+ `DENIED_ASSET_ID` / `DENIED_DIR_ID`)._

- forbids showing an asset in a denied directory → 403
- forbids updating an asset in a denied directory → 403
- forbids deleting an asset in a denied directory → 403
- forbids fetching a denied directory → 403
- scoped user only lists assets from granted directories
</details>

## Project structure

```
tests/api-pw/
├── api/                 # spec files (one per resource area)
├── config/              # env.js — env-driven config + .env loader
├── constants/           # endpoints.js, statusCodes.js, queryParams.js
├── fixtures/            # fixtures.js — api / anonApi / invalidTokenApi / token / uid
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
2. **API credentials for an admin user.** The DAM API is guarded by Passport
   (`auth:api`) *and* UnoPim's API-scope middleware, which reads the calling
   admin's **API Key** permission. A bare Passport client is **not** enough:
   without a matching `api_keys` row every authenticated request returns **500**,
   and a client not owned by the admin fails token mint with `invalid_client`.

   Create credentials the canonical way, in the UnoPim admin:

   > **Configuration → Integrations → API Keys → Create**
   > - assign it to an admin user,
   > - set **Permission Type = All** (so the token clears the scope check),
   > - **Save**, then **Generate** to reveal the **client id** and **secret**.

   (CLI alternative: `php artisan unopim:passport:client` creates the admin-owned
   password-grant client, but you must **still** create an API Key integration for
   that admin — otherwise authenticated requests 500.)
3. Node 18+.

## Setup

```bash
cd tests/api-pw
npm install
cp .env.example .env     # then fill in the values below
```

## Environment variables

| Variable | Required | Purpose |
|----------|----------|---------|
| `BASE_URL` | yes | UnoPim instance under test (default `http://127.0.0.1:8000`). |
| `OAUTH_TOKEN_URL` | yes | Passport token endpoint (default `/oauth/token`). |
| `OAUTH_CLIENT_ID` / `OAUTH_CLIENT_SECRET` | yes¹ | Password-grant client credentials. |
| `ADMIN_USERNAME` / `ADMIN_PASSWORD` | yes¹ | Admin the token is minted for. |
| `API_LOCALE` | no | Locale for locale-aware endpoints (default `en_US`). |
| `API_TOKEN` | no | Pre-minted bearer token; when set the OAuth flow is skipped **and the token-lifecycle tests in 01 self-skip**. Prefer client creds. |
| `TAG_ID` | no² | A known tag id → runs the positive tag fetch (06). |
| `LINKED_RESOURCE_ID` | no² | A known asset↔resource mapping id → runs the positive linked-resource fetch (09). |
| `SCOPED_API_TOKEN` | no² | Token for a `custom`-role admin with limited directory grants → runs the 403 tests (10). |
| `DENIED_ASSET_ID` / `DENIED_DIR_ID` | no² | An asset / directory that the scoped user must **not** access (10). |
| `WORKERS` | no | Worker count (keep `1` against `php artisan serve`). |
| `SLOW_RESPONSE_MS` | no | Flag responses slower than this in report attachments (default `2000`). |

¹ Either supply `OAUTH_CLIENT_ID`/`OAUTH_CLIENT_SECRET` + `ADMIN_USERNAME`/`ADMIN_PASSWORD`, **or** a pre-minted `API_TOKEN`.
² Optional for a default run — the dependent tests self-skip when unset. **Provide all of them for a zero-skip run** (CI seeds them automatically — see [CI](#ci)).

Minimal `.env`:

```ini
BASE_URL=http://127.0.0.1:8000
OAUTH_TOKEN_URL=/oauth/token
OAUTH_CLIENT_ID=<password client id>
OAUTH_CLIENT_SECRET=<password client secret>
ADMIN_USERNAME=admin@example.com
ADMIN_PASSWORD=admin123
API_LOCALE=en_US

# Optional — set all of these for a zero-skip run:
# TAG_ID=
# LINKED_RESOURCE_ID=
# SCOPED_API_TOKEN=
# DENIED_ASSET_ID=
# DENIED_DIR_ID=
```

> Real environment variables always override `.env`.

## Authentication model

- `global-setup.js` runs once, mints a bearer token via the OAuth2 **password
  grant** (or uses `API_TOKEN` when provided), verifies it against the assets
  endpoint, and caches it to `.state/api-auth.json`.
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
npm run test:assets                                  # asset CRUD + list + files
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

## CI

The suite runs in `.github/workflows/playwright_test.yml` (shard 1, after the
e2e suites). The workflow provisions everything needed for a **zero-skip** run:

- creates an admin-owned password-grant client **and** its `api_keys` row
  (`permission_type=all`) so authenticated requests clear the scope middleware;
- exports `OAUTH_CLIENT_ID`/`OAUTH_CLIENT_SECRET` (not a pre-minted `API_TOKEN`),
  so the password-grant lifecycle tests in 01 run;
- seeds a tag, a linked-resource mapping, and a scoped custom-role user with a
  denied directory + asset, exporting `TAG_ID`, `LINKED_RESOURCE_ID`,
  `SCOPED_API_TOKEN`, `DENIED_DIR_ID`, `DENIED_ASSET_ID`;
- stops the background queue worker before the suite so the async
  `DeleteDirectory` job can't mutate the nested-set tree while folders are being
  created (which otherwise corrupts `_lft/_rgt` and 500s folder creates).

## Notes on API scope

The suite targets exactly the endpoints the DAM package exposes
(`src/Routes/V1/asset-routes.php`):

- **Tags** are asset-scoped: attach/detach a tag by name to/from an asset, and
  fetch a single tag by its id. There is no "list an asset's tags" or standalone
  tag-CRUD endpoint, so "Create/List/Delete Tag" map onto attach / fetch-by-id /
  detach. Attach & detach return **201**; a duplicate attach or a detach of an
  unattached tag returns **404**.
- **Metadata** is modelled by the **asset properties** API.
- **Folder assignment** happens at **upload** time via `directory_id`; there is
  no separate assign/detach endpoint.
- **Linked resources** are **read-only** over REST (mappings are created by the
  import/UI layer).
- **Bulk asset delete** is not exposed by the REST API (single delete only).

The list endpoint's query convention is centralised in `constants/queryParams.js`.
Filters use a single JSON-encoded `filters` parameter (not bracketed keys), e.g.
`filters={"file_type":[{"operator":"=","value":"image"}]}`; pagination is flat
`limit` + `page`. Filterable columns: `file_type`, `mime_type`, `extension`,
`file_size`, `file_name`, `code` (name search), `created_at`, `updated_at`.
