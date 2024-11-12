# UnoPim Digital Asset Management (DAM)

UnoPim DAM is a flexible, open-source Digital Asset Management (DAM) system built on Laravel. It enables businesses to store, organize, and manage digital assets such as images, videos, documents, and more. The system is designed for seamless cross-team asset management. Key features include:

## Requirements
- **UnoPim**: v0.1.5

## ✨ Features

- **Asset Grid View**  
  Browse and manage assets through a visual, grid-based interface, ensuring quick access and efficient organization.

- **Directory Structure**  
  Organize assets within a clear, structured tree of directories, making it easy to categorize files.

- **Linked Resources**  
  Link related resources directly from the asset edit page for easier navigation and enhanced asset relevance.

- **Comprehensive Asset Operations**  
  Upload, preview, rename, delete, re-upload, and download assets based on team needs.

- **Metadata and Tagging**  
  Add tags, custom properties, and embedded meta-information to improve asset searchability and organization.

- **Collaboration and Resource Linking**  
  Multi-user reply comments and linked resources allow teams to collaborate effectively on assets.

- **Asset Assignment and Export/Import**  
  Assign assets to products and categories, with CSV/XLSX export for smooth data transfer and management.


## Installation with composer

- Run the following command
```
composer require unopim/dam
```

* Run the command to execute migrations and clear the cache.

```bash
php artisan dam-package:install;
php artisan optimize:clear;
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
   - Start the queue to execute directory operations:

     ```bash
     php artisan queue:work
     ```
