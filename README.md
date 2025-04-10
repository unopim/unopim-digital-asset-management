# UnoPim Digital Asset Management (DAM)

UnoPim DAM is a flexible, open-source Digital Asset Management (DAM) system built on Laravel. It enables businesses to store, organize, and manage digital assets such as images, videos, documents, and more. The system is designed for seamless cross-team asset management. Key features include:

## Requirements
- **UnoPim**: v0.1.6

## ✨ Features

- **Asset Gallery Grid View**  
  Browse and manage assets through a visual gallery grid, featuring filters and a search box for quick file access. Supports efficient organization with options for mass actions, including bulk delete.

- **Directory (Folder) Structure**  
  Organize assets in a clear directory tree structure. Right-click to upload files, add directories, rename, delete, copy, and download folders as ZIP files. Files can be dragged and dropped between directories, with support for unlimited subdirectory creation.

- **Resource Editing**  
  Add tags to assets for enhanced filtering in the gallery view, and apply custom properties such as copyright, source, author, and more. Users can also add comments and view linked resources associated with assets.

- **Linked Resources**  
  Directly link related resources from the asset edit page, allowing for easier navigation and improved resource relevance.

- **Comprehensive Asset Operations**  
  Perform key asset actions such as uploading, previewing, renaming, deleting, re-uploading, and downloading to meet team needs. Supports a wide range of file types, including images, CSV, XLSX, PDF, audio, and video files.

- **Metadata and Tagging**  
  Enhance asset searchability and organization by adding tags, custom properties, and embedded metadata.

- **Collaboration and Resource Linking**  
  Enable multi-user comments and resource linking for effective team collaboration on assets.

- **Asset Detail Export in Product CSV**  
  Export asset details as part of the product CSV export job, enabling smooth data transfer and management by including asset information directly in product CSV files.

## Installation with Composer

- Run the following command:
   ```bash
   composer require unopim/dam
   ```

- Run the command to execute migrations and clear the cache:
   ```bash
   php artisan dam-package:install;
   php artisan optimize:clear;
   ```

- Start the queue to execute actions, such as job operations, by running the following command:
    ```bash
      php artisan queue:work
    ```
- If the `queue:work` command is configured to run via a process manager like Supervisor, restart the Supervisor (or related) service after module installation to apply changes:
     ```bash
     sudo service supervisor restart
     ```

## Installation without Composer

To manually install UnoPim DAM:

1. **Download and Setup the Extension**  
   - Download and unzip the extension.
   - Rename the folder to `DAM` and place it in the `packages/Webkul` directory within the project's root.

2. **Register the Package Provider**  
   - Add the following provider class to `config/app.php` under the `providers` key:
     ```php
     Webkul\DAM\Providers\DAMServiceProvider::class,
     ```

3. **Update Autoload Configuration**  
   - Register the DAM directory in `composer.json` under `autoload` `psr-4`:
     ```json
     "Webkul\\DAM\\": "packages/Webkul/DAM/src"
     ```

4. **Run Installation Commands**  
   - Execute these commands to complete the installation:
     ```bash
     composer dump-autoload
     php artisan optimize:clear
     php artisan migrate
     php artisan vendor:publish --provider=Webkul\\DAM\\Providers\\DAMServiceProvider
     php artisan db:seed --class=Webkul\\DAM\\Database\\Seeders\\DirectoryTableSeeder
     ```

5. **Enable Queue Operations**  
   - Start the queue to execute actions, such as job operations, by running the following command:
     ```bash
       php artisan queue:work
     ```
   - If the `queue:work` command is configured to run via a process manager like Supervisor, restart the Supervisor (or related) service after module installation to apply changes:
     ```bash
     sudo service supervisor restart
     ```
