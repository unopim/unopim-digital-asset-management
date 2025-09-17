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


## 📦 DAM Asset Migration to AWS S3

This Laravel Artisan command is used to **migrate DAM asset files** from the **local private disk** to **AWS S3 storage**. It supports migrating all files or only newly uploaded ones, and optionally deleting local files after successful migration.

### 🛠 Command Signature

```bash
php artisan unopim:dam:move-asset-to-s3
```

### 🔐 Authentication

To execute this command, valid admin credentials are required:

-   You'll be prompted to enter your **email** and **password**.
-   Access is granted only if the credentials match an admin user.

### 🧭 Options Prompts

During execution, the command will prompt you to choose:

-   **Migrate only new files?**
    If `yes`, only assets not already present on S3 will be migrated.

-   **Delete files from local disk after uploading?**
    If `yes`, local files will be deleted after successful transfer to S3.

### 📋 Example Workflow

```bash
php artisan unopim:dam:move-asset-to-s3
```

Prompts:

```text
Enter your Email: admin@example.com
Enter your Password: ********
Want to migrate only new uploaded files from your local to s3 (yes/no): yes
Want to delete files from local once uploaded to s3? (yes/no): no
```

### ✅ Features

-   Authenticated access
-   Batch processing (in chunks of 1000 records)
-   Progress bar display
-   Logging of all migrated files and skipped entries
-   Supports:

    -   Full migration
    -   Incremental (new only) migration
    -   Optional local deletion

### 📁 Storage Disks Used

Make sure the following disks are correctly configured in `config/filesystems.php`:

```php
'disks' => [
    'private' => [
        'driver' => 'local',
        'root' => storage_path('app/private'),
    ],

    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
],
```

### 📌 Note

-   Ensure AWS credentials and bucket permissions are properly set up.
-   It's recommended to test the command on a staging environment before running in production.
-   Logs are recorded in `storage/logs/laravel.log`.
