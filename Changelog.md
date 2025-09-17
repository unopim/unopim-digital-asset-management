# Changelog


## **Version 1.1.0** - AWS S3 Integration & Enhanced File Support

## Features

- **AWS S3 Integration**:
  - Added AWS S3 feature to the Digital Asset Management (DAM) system. You can now configure AWS S3 credentials and upload images directly to AWS S3.
  - Created a new command for migrating local/public assets to the existing AWS S3 cloud, allowing you to seamlessly transfer your files.
  - Introduced a visibility option in the AWS Connector Module to choose between public or private URL access for assets stored in AWS S3.

- **Comment and Link History**:
  - Added history generation for comments and linked properties, enabling tracking of changes over time for better collaboration and versioning.

- **Support for New File Types**:
  - Added input support for SVG files. Now, when downloading an SVG file, you’ll only see the available options in the type dropdown (JPG, PNG, JPEG).
  - Included support for uploading and viewing `.mp4` and `.step` files, expanding the range of supported file types in the DAM system.

## Files

- **S3 Writeability Check**:

- Resolved an issue where Models/Directory::isWritable() always returned false for AWS S3 disks configured via Flysystem.
- Updated the logic to use a file creation attempt as a fallback check for writeability, improving compatibility with remote storage drivers.

## **Version 1.0.0** - Initial Release  

### Features
- **Comprehensive File and Directory Management**:  
  - Added functionality to add, remove, rename, move, and copy directories.
  - Enabled drag-and-drop file uploads.
  - Added ZIP file downloads for directories and ability to duplicate directory structures.

- **Advanced Asset Operations**:  
  - Introduced upload, preview, delete, rename, re-upload, and custom download options for assets.

- **Metadata and Tagging**:  
  - Added support for tagging, custom properties, and embedded meta-information to enhance asset organization, searchability, and retrieval.

- **Collaboration and Resource Linking**:  
  - Enabled multi-user reply comments for assets.
  - Added linked resource views for assets associated with categories and products.

- **Asset Assignment via Export**:  
  - Implemented CSV/XLSX export to assign assets to products and categories efficiently.

- **Change History Tracking**:  
  - Added full change history tracking for assets, enabling users to view all modifications made over time.