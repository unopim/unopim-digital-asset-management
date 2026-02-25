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
