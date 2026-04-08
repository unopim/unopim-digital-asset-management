# CHANGELOG for unopim-digital-asset-management

## v1.1.x

### v1.1.2

> Compatible with **UnoPim v2.0.0**

#### Bug Fixes

- **Asset Media Export** — Resolved issue with assets media not exporting with product exports. The `Exporter::copyMedia()` method now correctly handles asset field media using
`Storage::writeStream()`/`readStream()` when the source file exists. ([6e8c7c6](https://github.com/vipinkutthi-webkul/unopim-digital-asset-management/commit/6e8c7c65f290093ef4e9ce11aa060f6557eb4d25))

- **Image Saving (Intervention Image v3)** — Fixed issue with saving the image. Migrated `AssetController` and `FileController` from the deprecated `Image::make()` facade to Intervention Image v3 API
(`ImageManager` + `Driver`). Added `encodeImageByExtension()` for format-aware encoding (PNG, WebP, GIF, BMP, TIFF, AVIF, JPEG) and replaced constraint-based resize callbacks with `cover()`.
([5903c3e](https://github.com/unopim/unopim-digital-asset-management/commit/5903c3e71c6999ab4e5740586b15b5d0356628a8))

- **Route Required Parameters** — Fixed the issue with the required parameters in asset views. Replaced direct `${id}` interpolation with `:id` placeholder and `.replace(':id', id)` pattern across
`asset/edit`, `asset/field`, `datagrid/gallery`, and `datagrid/tree` Blade views to prevent Laravel route generation failures.
([31ca5ca](https://github.com/unopim/unopim-digital-asset-management/commit/31ca5ca8ded29af018b891f64e144b7adff860fe))

## **Version 1.1.1**
- Fixed issues with Product and Category imports
- Enabled updating the **Name** field in Asset Properties
- Updated translation message for asset deletion

## **Version 1.1.0** - AWS S3 Storage Support & REST API Support

## Features

- **AWS S3 Integration**:
  - Added AWS S3 feature to the Digital Asset Management (DAM) system. You can now configure AWS S3 credentials and upload images directly to AWS S3.
  - Created a new command for migrating local/public assets to the existing AWS S3 cloud, allowing you to seamlessly transfer your files.

- **Comment and Link History**:
  - Added history generation for comments and linked properties, enabling tracking of changes over time for better collaboration and versioning.

- **Support for New File Types**:
  - Added input support for SVG files. Now, when downloading an SVG file, you’ll only see the available options in the type dropdown (JPG, PNG, JPEG).
  - Included support for uploading and viewing `.mp4` and `.step` files, expanding the range of supported file types in the DAM system.

- **API Support:**:
  - API endpoints for managing directories (create, update, delete, list)
  - API endpoints for managing assets (upload, update metadata, delete, list, retrieve)

## **Version 1.0.0** - Initial Release

### 🎉 Initial Features
- **Asset Gallery Grid View**: Visual, grid-based interface with filters and a search box for easy browsing and management of assets. Supports mass actions like bulk delete.

- **Directory Structure**: Organize assets in a structured directory tree. Allows right-click actions for file upload, adding/renaming/deleting directories, copying directory structures, and downloading directories as ZIP files.

- **Resource Editing**: Add tags and custom properties (e.g., copyright, source, author) to assets, with the option to add comments and link related resources.

- **Linked Resources**: Direct linking of related resources from the asset edit page for enhanced navigation and relevance.

- **Comprehensive Asset Operations**: Upload, preview, rename, delete, re-upload, and download assets across various file types (images, CSV, XLSX, PDF, audio, video).

- **Metadata and Tagging**: Add embedded metadata, tags, and custom properties to improve searchability and organization.
- **Collaboration**: Multi-user comments and resource linking for team collaboration on assets.
- **Asset Detail Export in Product CSV**: Export asset details as part of product CSV export for streamlined data management.
