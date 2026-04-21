# CHANGELOG for unopim-digital-asset-management

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
