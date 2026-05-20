# CHANGELOG for unopim-digital-asset-management

## Version 2.2.0 - Features & Enhancements

### Features & Enhancements

- **Real thumbnails for PDFs and videos** — DAM grid now shows the actual
  first page of a PDF and a real frame from a video instead of the generic
  placeholder. Queued `GeneratePdfThumbnail` (`pdftoppm`) and
  `GenerateVideoThumbnail` (`ffmpeg`) jobs run on upload / re-upload and
  cache to `thumbnails/{path}.jpg`. Lazy sync fallback covers pre-existing
  assets. New `dam:backfill-thumbnails` artisan command backfills the rest.

- **Eye-icon preview on the asset grid** — Each tile now exposes a preview
  eye on hover (gated by `dam.asset.view`). Opens a self-contained fullscreen
  modal with native `<img>` (zoom / pan / rotate), `<video controls>`,
  `<audio controls>`, or `<iframe>` for PDFs. Escape / backdrop closes.

- **Inline preview on the asset edit page** — Removed the eye button. The
  preview card now renders the media inline (image, custom video player,
  custom audio player, or PDF.js) on mount, picked by `file_type` /
  extension. Keyboard shortcuts now work without opening the old modal.

- **Full-path breadcrumb on the DAM listing and edit pages** — Listing page
  shows the full ancestor chain (e.g. `Root / National Delivery / 2026 / 05`);
  edit page shows the path with the file name highlighted, with each
  ancestor deep-linking to `dam/assets?directory_id={id}`. Built from the
  tree's in-memory state — no extra HTTP call.

- **Sidebar padding on the asset edit page** — Tags, Details, and Directory
  Path accordions sit inside `p-4` on the right sidebar card.

- **Unified shareable links** — Replaces the old asset-only signed
  URL share with a unified system for assets *and* directories. New
  `dam_shares` table stores each link with a 40-char token, owner, optional
  expiry (1 day / 7 days / 30 days / 1 year / never), revoke flag, and view
  and download counters. A new public viewer page at `/share/{token}` (no
  admin auth, rate limited) shows a clean preview (image / video / audio /
  PDF) with a Download button and file metadata. Directory shares render a
  thumbnail gallery scoped to direct children only — visitors can preview
  and download individual files but cannot traverse into subdirectories.
  Expired and revoked links land on dedicated 410 pages. A new
  **DAM → Shared Links** management page (DataGrid) lists all shares with
  type, target, creator, status, views, downloads, and inline revoke. The
  share modal (inline on asset edit, opened via the directory tree
  context-menu) lists active links for the target with copy and revoke, and
  uses Unopim's core `VMultiselect` dropdown for expiry selection.

- **Asset preview image zoom** — New `v-zoomable-image` component adds
  zoom in/out, fit, 1:1, rotate left/right, and reset to the inline image
  preview on the asset edit page and the public share viewer. Mouse wheel
  zooms, click-drag pans when zoomed, double-click toggles 1:1 / fit. The
  toolbar renders as a row below the image rather than overlaying it.

- **Role-based DAM directory permissions** — Admin roles can be granted
  access to specific directories via a new directory-grants tab on the role
  create and edit pages. Every DAM endpoint, the directory tree, and the
  asset datagrid honour the grants. Super-admin and API guard retain full access.

- **Directory permissions enforced on all REST API endpoints** — API requests
  authenticated via Passport now go through the same role-based directory ACL
  as web sessions. `DirectoryPermissionService` resolves the API guard user so
  `bypass()`, `canView()`, `canAccess()`, and `directlyGrantedIds()` all work
  for token-authenticated calls. The asset listing is filtered to directories
  granted to the caller's role. Individual asset, directory, comment, tag, and
  property endpoints return 403 when the target asset or directory falls outside
  the caller's grants.

- **Directory search** — Substring search input above the directory tree
  returns ACL-visible directories matching the query. Each result shows the
  directory name plus its full ancestor breadcrumb so identically-named
  folders at different levels are distinguishable. Clicking a result expands
  the tree to that directory, scrolls it into view, highlights it, and loads
  its assets. Infinite scroll with a sticky `X of Y matches` counter and a 300 ms
  debounce. The same search and reveal flow is available in the asset picker
  modal.

- **Scrollable directory tree** — Tree container scrolls horizontally on
  deep paths and vertically on long lists, on both the main DAM page and the
  asset picker modal, so the page / modal no longer grows unbounded with
  large libraries.

- **Directory filter — server-side descendant expansion** — The asset
  datagrid's `directory_id` filter expands a single selected directory id to
  its full descendant set server-side, so the client only sends one id.
  Previously the tree serialised every descendant id into the URL, which
  blew Apache's `LimitRequestLine` on installs with hundreds of directories.

- **Asset preview modal** — New overlay opens images, videos, audio, and
  documents with file metadata, dark-mode styling, and per-media-type
  controls.

- **Custom video and audio players** — Native browser controls replaced with
  custom controls in the preview modal. Video adds play/pause, scrub, volume,
  and a context-menu block to discourage right-click downloads. Audio gets a
  custom seek bar and a redesigned modal layout with disc-spin animation.

- **Embedded audio cover art** — Cover art embedded in audio files (ID3) is
  now extracted and used as the asset thumbnail and preview-modal background.

- **Image editor** — Expanded with a crop drag overlay using natural canvas
  dimensions, adjust / filter / transform controls in an accordion tools
  panel, a background editor with color / file-upload / AI fill
  modes, and image filter presets (sepia, grayscale, etc.). Dark-mode icon
  tinting applied throughout. Translated into every shipped locale.

- **Image editor ACL gating** — Every `ImageEditController` endpoint (resize,
  adjust, filters, transform, background color/upload/AI, normal-bg) now
  routes through the `AssetAccessControl` trait so role-restricted admins
  cannot edit assets they lack permission for.

- **Download as ZIP** — Non-archive assets can now be downloaded as a ZIP
  container alongside the existing single-file download path.

- **Lazy in-tree asset listing** — Asset rows under each directory in the
  tree are hidden by default. Set `DAM_TREE_SHOW_ASSETS=true` in `.env` to
  enable (defaults to `false`); the new `config/dam.php` exposes it as
  `dam.tree.show_assets`. Applies to both the main DAM tree and the asset
  picker. When disabled, the tree renders folder nodes only, cutting payload
  and DOM size for large libraries. When enabled, assets lazy-load on
  directory expand and refresh per-folder after upload / move / delete.

- **Per-directory asset count** — Each directory row in the tree shows a
  recursive total asset count.

- **Tree-busy interaction lock** — The upload button, asset datagrid, and
  asset edit toolbar lock during in-flight tree mutations (move, delete,
  copy) and during ongoing uploads, preventing conflicting actions. A
  full-screen progress overlay accompanies tree moves and datagrid mass
  actions.

- **Asset action spinners** — Rename and delete actions on assets now show
  inline spinners while the request is in flight.

- **Upload — size limits and cancel support** — Lifted hard upload size
  limits from the Vue uploader, surfaced server-side limits in error
  messages, and added a cancel button to abort large in-flight uploads.
  Multi-file batches now complete faster.

- **Same-name upload — overwrite + cache** — Uploading an asset with a name
  that already exists in the target directory now overwrites the existing
  file (instead of erroring or duplicating). Thumbnail / metadata caches
  are cleared on asset delete so stale data is not served after the file
  is gone.

- **Storage failure surfacing** — Storage write failures now bubble to the
  user instead of returning a half-written asset.

- **Local asset streaming** — Local-disk asset streaming switched to
  `response()->file()` so the browser can range-request large media files
  without buffering the full payload in PHP.

- **S3 — picker thumbnails and downloads** — Asset picker thumbnails and
  download endpoints now redirect to the S3 URL when the asset lives on S3,
  removing the proxying overhead.

- **S3 — audio cover art migration** — The `dam:move-to-s3` command now
  migrates extracted audio cover-art images alongside their parent audio
  assets.

- **Translation hardening** — Preview-modal strings, video player controls,
  download-as-ZIP labels, image-processing error, image editor / background
  editor / filter labels, and rename-modal copy are now routed through
  `trans()` with proper translations shipped for all 33 locales.

- **Asset card thumbnail sizing** — Gallery thumbnails render at a larger
  max-height, making images legible without opening the preview modal.

- **Gallery datagrid mass-action markup** — Mass-action buttons sit inline
  with the selection counter instead of wrapping on narrow viewports.

### Fixed

- **"Directory no longer accessible" flash on first load and back navigation** —
  Reveal requests that arrived before the tree finished loading `formattedItems`
  fired a spurious not-found flash. The tree now queues such requests until the
  AJAX resolves, and deep-link / breadcrumb reveals pass `silent: true` so they
  never flash. The explicit search path keeps its original behaviour.

- Asset picker thumbnails
  Picker thumbnails now always go through the central thumbnail route, so
  signed S3 URLs and disk-aware streaming behave like the main DAM grid.

- Directory tree context menu — duplicate dark class
  Removed a duplicate `dark:text-white` class on the context menu and added a
  view-only placeholder for ancestors the admin can see but not act on.

- Asset grid — locked rows during upload
  Asset grid row interactions are blocked while an upload is in flight, so a
  user can't open or edit a mid-upload asset and end up referencing a stale
  file id.

- Picker — filter-applied indicator counted directory selection
  The picker toolbar's "filters applied" indicator now ignores the implicit
  `directory_id` and `directory_asset_id` filters set by clicking the tree.

## Version 2.1.0 - Features & Enhancements

### Features & Enhancements

- **Directory tree performance** — Asset eager-loading is skipped by default in the
  directory tree; the asset picker opts in via `with_assets=1`, reducing the initial
  payload for large libraries.

- **Per-directory busy spinners** — Move, delete, and copy operations now show a spinner
  only on the affected tree node instead of a full-screen overlay, giving precise visual
  feedback without blocking the rest of the UI.

- **Upload progress indicator** — Upload and re-upload buttons are disabled and display a
  spinner while a transfer is in progress, preventing duplicate submissions.

- **Persistent asset metadata** — Extracted metadata is stored in a new `meta_data` column
  on `dam_assets` and surfaced in a dedicated Metadata tab in the asset editor, with a
  new API endpoint for programmatic access.

- **AWS S3 — URL resolution from disk** — Asset URLs for S3-hosted files are now resolved
  from the configured disk using visibility-aware logic (public URL vs. temporary signed
  URL), replacing the previous hard-coded path concatenation.

### Fixed

- Directory tree flicker on upload
Removed premature local-state push of uploaded assets that caused duplicate entries in
the tree. The tree now rebuilds exclusively from the server response.

- meta_data double-encoding
Fixed a regression where the `meta_data` JSON field was serialised twice on asset store,
producing a string-wrapped JSON value instead of a plain object.

## Version 2.0.2 - Bug Fix

### Fixed

- DB prefix — Asset datagrid query
Fixed unprefixed `dam_directories` and `dam_asset_directory` references in raw `MIN()` expressions that broke the asset datagrid when `DB_PREFIX` was set.

## Version 2.0.1 - Bug Fix Release
Compatible with UnoPim v2.0.0

### Fixed

- S3 — Single asset move
Fixed an issue where moving a single asset on S3 orphaned the object.

- S3 — Directory rename
Fixed directory move by relocating files individually (S3 doesn't support directory operations).

- S3 — Asset metadata
Fixed metadata extraction by using temporary local copies and storage-level values.

- Missing EXIF extension
Prevented fatal errors when EXIF extension is not installed.

- PostgreSQL — SQL compatibility
Fixed MySQL-specific queries causing errors on PostgreSQL.

- Dark mode — Root Category label
Fixed text visibility in dark mode.

## **Version 2.0.0** - UnoPim v2.0.0 Compatibility

> Compatible with **UnoPim v2.0.0**
>
> **Breaking changes:** drops support for UnoPim v1.x. Requires **PHP 8.3+**, **Laravel 12**, and **Intervention Image v3**. The `composer.json` now declares these constraints explicitly, so upgrading an UnoPim 1.x site to DAM v2.0.0 will be refused by the Composer resolver.

This is the first DAM release since **v1.0.0**. It consolidates every change shipped on the `v1.1.x` line (which was never tagged) together with the UnoPim v2.0.0 compatibility work, and is the recommended upgrade path for every existing DAM install.

### Features & Enhancements

- **UnoPim 2.0 compatibility** — Migrated internals to work against UnoPim v2.0.0 (Laravel 12, PHP 8.3+, Intervention Image v3). DAM v2.x no longer supports UnoPim v1.x.

- **Composer dependency constraints** — `composer.json` now declares `php: ^8.3` and `intervention/image: ^3.0`, preventing accidental installation on incompatible UnoPim 1.x environments.

- **AWS S3 integration** — Added native AWS S3 support to the Digital Asset Management system. You can now configure AWS S3 credentials and upload assets directly to S3.

- **S3 migration command** — New `php artisan unopim:dam:move-asset-to-s3` Artisan command for migrating existing local/public assets to S3. Supports migrating only new files and optionally deleting local copies after a successful transfer.

- **REST API support** — Full REST API under `api/v1/rest/` for managing DAM resources:
  - `directories` — create, update, delete, list
  - `assets` — upload, update metadata, delete, list, retrieve, download

- **Comment & link history** — Added history tracking for comments and linked resources so changes over time are auditable for collaboration and versioning.

- **New file type support** — Added input support for SVG files (with JPG/PNG/JPEG download options in the type dropdown), and upload/preview support for `.mp4` and `.step` files.

- **File size validation on upload** — Added maximum file size validation across the admin asset upload flow and the REST `POST /api/v1/rest/assets` endpoint, with a new `file-too-large` translation key so users get a clear error when they exceed the limit. ([10106e0](https://github.com/unopim/unopim-digital-asset-management/commit/10106e0c99e163cbacf4ccc34c16c3e31acde898))

- **Editable Asset Property name** — The **Name** field in Asset Properties can now be updated after creation.

- **Full 33-locale translation coverage** — DAM now ships translations for every UnoPim-supported locale. Added 21 previously missing locales: `ca_ES`, `da_DK`, `en_AU`, `en_GB`, `en_NZ`, `es_VE`, `fi_FI`, `hr_HR`, `it_IT`, `ko_KR`, `no_NO`, `pl_PL`, `pt_BR`, `pt_PT`, `ro_RO`, `sv_SE`, `tl_PH`, `tr_TR`, `uk_UA`, `vi_VN`, `zh_TW`. Existing locales were updated with newly introduced keys.

### Bug Fixes

- **Asset Media Export** — Resolved issue with asset media not exporting with product exports. The `Exporter::copyMedia()` method now correctly handles asset field media using `Storage::writeStream()`/`readStream()` when the source file exists. ([6e8c7c6](https://github.com/unopim/unopim-digital-asset-management/commit/6e8c7c65f290093ef4e9ce11aa060f6557eb4d25))

- **Image Saving (Intervention Image v3)** — Fixed issue with saving images. Migrated `AssetController` and `FileController` from the deprecated `Image::make()` facade to the Intervention Image v3 API (`ImageManager` + `Driver`). Added `encodeImageByExtension()` for format-aware encoding (PNG, WebP, GIF, BMP, TIFF, AVIF, JPEG) and replaced constraint-based resize callbacks with `cover()`. ([5903c3e](https://github.com/unopim/unopim-digital-asset-management/commit/5903c3e71c6999ab4e5740586b15b5d0356628a8))

- **Route Required Parameters** — Fixed an issue with required parameters in asset views. Replaced direct `${id}` interpolation with the `:id` placeholder and `.replace(':id', id)` pattern across `asset/edit`, `asset/field`, `datagrid/gallery`, and `datagrid/tree` Blade views to prevent Laravel route generation failures. ([31ca5ca](https://github.com/unopim/unopim-digital-asset-management/commit/31ca5ca8ded29af018b891f64e144b7adff860fe))

- **API property update via Postman** — Fixed a regression where property name updates via the REST API returned translation errors. ([800d6fc](https://github.com/unopim/unopim-digital-asset-management/commit/800d6fc))

- **Multiple asset import** — Resolved an issue where importing multiple assets in a single job could fail partway through. ([2e5dd51](https://github.com/unopim/unopim-digital-asset-management/commit/2e5dd51))

- **Incomplete metadata info** — Fixed metadata extraction so the embedded meta info block is no longer truncated for certain file types; metadata loading was moved into `Traits/Directory` for consistency. ([f065e2a](https://github.com/unopim/unopim-digital-asset-management/commit/f065e2a))

- **Product & Category imports** — Fixed issues with product and category imports when linking DAM assets via the importer pipeline.

- **Asset deletion translation** — Updated the translation message shown after asset deletion so it reads naturally across locales.

- **Dark-mode & datagrid UI polish** — Hidden a phantom filter indicator in the asset gallery toolbar, fixed dark-mode highlight on the directory tree, and corrected the upload icon rendering. ([232cc21](https://github.com/unopim/unopim-digital-asset-management/commit/232cc21))

- **Incorrect DAM icons** — Replaced wrong icons used across the DAM admin panel. ([7f8be20](https://github.com/unopim/unopim-digital-asset-management/commit/7f8be20))

### PostgreSQL Compatibility

- **Path collation migration** — Added the `update_path_collation_in_dam_assets_table` migration that applies `utf8mb4_bin` on MySQL and is safely skipped on PostgreSQL. The migration is fully reversible. ([6bc8658](https://github.com/unopim/unopim-digital-asset-management/commit/6bc8658))

- **Directory seeder sequence fix** — Removed hardcoded IDs from `DirectoryTableSeeder` that were causing PostgreSQL sequence conflicts during installation and tests. ([107d855](https://github.com/unopim/unopim-digital-asset-management/commit/107d855), [bfa0ad8](https://github.com/unopim/unopim-digital-asset-management/commit/bfa0ad8), [37d78d5](https://github.com/unopim/unopim-digital-asset-management/commit/37d78d5))

### Installer

- **Asset publishing on install** — Fixed `php artisan dam-package:install` so publishable assets (build manifest, CSS, JS, fonts, SVGs) are actually copied to `public/themes/default/assets/` during installation instead of being skipped. ([ee5902a](https://github.com/unopim/unopim-digital-asset-management/commit/ee5902a))

### Tests & CI/CD

- **CI/CD workflows** — Added GitHub Actions workflows for linting, Pest (MySQL and PostgreSQL matrices), Playwright E2E, and translation coverage checks, running DAM against a real UnoPim install. ([03ce34a](https://github.com/unopim/unopim-digital-asset-management/commit/03ce34a), [efbfccf](https://github.com/unopim/unopim-digital-asset-management/commit/efbfccf), [9e1ce30](https://github.com/unopim/unopim-digital-asset-management/commit/9e1ce30))

- **Pest unit & feature tests** — Added unit coverage for models, helpers, repositories, jobs, and request classes, plus feature tests for action requests, asset pickers, comments, properties, and tags. ([5934baf](https://github.com/unopim/unopim-digital-asset-management/commit/5934baf))

- **Playwright E2E suite** — Added end-to-end coverage for DAM navigation, directory management, asset upload, asset edit, asset properties, comments, and datagrid operations. ([0a30529](https://github.com/unopim/unopim-digital-asset-management/commit/0a30529))

## **Version 1.0.0** - Initial Release

_Released 2 December 2024._

### 🎉 Initial Features
- **Asset Gallery Grid View**: Visual, grid-based interface with filters and a search box for easy browsing and management of assets. Supports mass actions like bulk delete.

- **Directory Structure**: Organize assets in a structured directory tree. Allows right-click actions for file upload, adding/renaming/deleting directories, copying directory structures, and downloading directories as ZIP files.

- **Resource Editing**: Add tags and custom properties (e.g., copyright, source, author) to assets, with the option to add comments and link related resources.

- **Linked Resources**: Direct linking of related resources from the asset edit page for enhanced navigation and relevance.

- **Comprehensive Asset Operations**: Upload, preview, rename, delete, re-upload, and download assets across various file types (images, CSV, XLSX, PDF, audio, video).

- **Metadata and Tagging**: Add embedded metadata, tags, and custom properties to improve searchability and organization.
- **Collaboration**: Multi-user comments and resource linking for team collaboration on assets.
- **Asset Detail Export in Product CSV**: Export asset details as part of product CSV export for streamlined data management.
