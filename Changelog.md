# CHANGELOG for unopim-digital-asset-management

## **Version 2.1.1** — Demo Data Seed & API Improvements

### Features

- **`dam:demo-data` command** — New Artisan command that seeds sample directories and assets (Accessories, Audio and Video, Clothes, Documents) so DAM can be explored without uploading real files. Idempotent by default; re-run with `--force` to wipe and re-seed. The `--force` flag prompts for explicit Y/N confirmation (defaults to No) before deleting existing assets.

- **Demo data step in `dam-package:install`** — The install command now offers an optional demo data seed as its final step, after migrations and asset publishing complete.

- **Asset REST API filters** — The asset API data source now exposes filters for `file_type`, `mime_type`, `extension`, `file_size`, `file_name`, `code`, `created_at`, and `updated_at`, with range operators on size and date fields.

### Improvements

- **Hardened DAM API directory authorization** — Asset access-control resolution now fails closed: directory lookups run through a direct query, and any error during the permission check denies access instead of leaking it.

### Bug Fixes

- **Namespace casing in `FileStorer.php`** — Corrected the namespace from `Webkul\DAM\FileSystem` to `Webkul\DAM\Filesystem` to match the directory casing and fix autoloading on case-sensitive filesystems.

## **Version 2.1.0** — New Features, Improvements, and UnoPim v2.1.0 Compatibility

### Features & Enhancements

- **Real thumbnails for PDFs and videos** — Queued jobs generate actual first-page (PDF) and first-frame (video) thumbnails on upload. `dam:backfill-thumbnails` command backfills existing assets.

- **Inline preview on the asset edit page** — Media renders inline (image with zoom/pan/rotate, custom video player, custom audio player, PDF.js) without a separate modal.

- **Click card to open preview** — Clicking anywhere on a gallery card opens the asset preview (gated by `dam.asset.view`). Hover action buttons still work independently.

- **Asset preview image zoom** — `v-zoomable-image` component adds zoom, fit, 1:1, rotate, pan, and double-click toggle to the inline image preview and public share viewer.

- **Full-path breadcrumb** — Listing and edit pages show the full ancestor chain; each segment deep-links to that directory.

- **Unified shareable links** — Share assets and directories via token-based links with optional expiry, revoke, view/download counters, and a public viewer (`/share/{token}`). Includes reauthorization, custom naming, and ZIP download for directory shares. A **DAM → Shared Links** management datagrid lists all shares with inline per-row actions.

- **Role-based DAM directory permissions** — Admin roles can be granted access to specific directories. Permissions are enforced across all web and REST API endpoints, the directory tree, and the asset datagrid.

- **Inherit-children permission toggle** — Enabling inherit-children on a directory grant automatically extends access to all descendants; explicit child grants are preserved when the toggle is removed.

- **Auto-grant subdirectory permissions** — New subdirectories are automatically granted to the creator's role; the tree and context menu reflect the change immediately.

- **Directory search** — Substring search above the directory tree returns ACL-visible matches with full ancestor breadcrumbs. Available in the main tree and the asset picker.

- **Folder upload** — Upload entire folder trees via drag-and-drop or the **Upload Folder** context-menu item. A progress panel tracks each transfer.

- **Filterable and sortable asset property columns** — Properties marked `is_filterable` appear as dynamic filter columns in the asset datagrid.

- **Metadata tab ACL gating** — The embedded metadata tab is permission-gated via `dam.asset.meta_data.view`.

- **Image editor** — Expanded with crop overlay, adjust/filter/transform accordion, background editor (color / upload / AI fill), and filter presets. ACL-gated on all endpoints.

- **Custom video and audio players** — Native controls replaced with custom play/pause, scrub, volume, speed, and context-menu block in the preview.

- **Embedded audio cover art** — ID3 cover art used as asset thumbnail and preview background.

- **Download as ZIP** — Assets can be downloaded as a ZIP container.

- **Directory filter — server-side descendant expansion** — The `directory_id` filter expands to its full descendant set server-side, avoiding oversized URLs on large libraries.

- **Scrollable directory tree** — Tree scrolls horizontally and vertically on both the main page and the asset picker modal.

- **Lazy in-tree asset listing** — Asset rows are hidden by default; set `DAM_TREE_SHOW_ASSETS=true` to enable lazy-loading per directory.

- **Per-directory asset count** — Each tree node shows a recursive asset count.

- **Tree-busy interaction lock** — Uploads, moves, and mass actions lock conflicting UI interactions and show a progress overlay.

- **Upload — size limits and cancel support** — Server-side size limits surfaced in error messages; cancel button aborts large in-flight uploads.

- **Same-name upload — overwrite + cache** — Re-uploading a same-name file overwrites the existing asset and clears thumbnail/metadata caches.

- **Local asset streaming** — Switched to `response()->file()` for range-request support on large media files.

- **S3 enhancements** — Picker thumbnails and downloads redirect to S3 URLs; `dam:move-to-s3` migrates cover-art images alongside audio assets.

- **Public directory share — infinite scroll** — Infinite scroll on public directory share pages.

- **Directory tree performance** — Asset eager-loading skipped by default in the tree; asset picker opts in via `with_assets=1`.

- **Per-directory busy spinners** — Move, delete, and copy show a spinner only on the affected node.

- **Upload progress indicator** — Upload/re-upload buttons show a spinner and are disabled while in progress.

- **Persistent asset metadata** — Extracted metadata stored in `meta_data` column and surfaced in a dedicated Metadata tab with an API endpoint.

- **AWS S3 — URL resolution from disk** — S3 URLs resolved via visibility-aware disk logic instead of hard-coded path concatenation.

- **Prev/next navigation scoped to current directory** — Prev/next on the asset edit page stays within the asset's directory; position counter reflects the directory scope.

- **OS metadata files blocked from upload** — `.DS_Store`, `Thumbs.db`, and similar files are rejected at the upload boundary.

- **Thumbnail rendering for non-image files** — Correct fallback placeholder shown for documents, audio, and video in the gallery.


### Fixed

- **Asset datagrid filename search** — Fixed switch fall-through in `AssetDataGrid` that forced exact-match on filename; partial names and names without extensions now work.

- **Datagrid filter persistence** — Filter state is correctly restored after navigating between assets.

- **Accessible-ids reactivity after directory creation** — Tree's ACL id list updates immediately when a new directory is created.

- **Asset picker thumbnails** — Picker thumbnails go through the central thumbnail route for correct S3 and disk-aware behaviour.

- **Directory tree context menu** — Removed duplicate dark class; added view-only placeholder for ancestors the admin can see but not act on.

- **Asset grid — locked rows during upload** — Grid interactions blocked while an upload is in flight.

- **Picker — filter-applied indicator** — Ignores implicit `directory_id` and `directory_asset_id` filters set by clicking the tree.

- **Directory tree flicker on upload** — Tree rebuilds exclusively from server response; no premature local-state push.

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
