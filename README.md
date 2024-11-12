# UnoPim Digital Asset Management (DAM)

**UnoPim DAM** is a flexible, open-source Digital Asset Management (DAM) system built on Laravel. Designed to help businesses store, organize, and manage digital assets—such as images, videos, documents, and more—it enables seamless asset management across teams. Key features include:

- **Asset Grid View:** Browse and manage assets in a visual, grid-based interface for quick access and organization.
- **Directory Structure:** Organize assets in a structured, tree-based directory view, making it easy to locate and categorize files within directories.
- **Linked Resources:** Link related resources directly from the asset edit page, streamlining navigation and enhancing asset relevance.
- **Comprehensive Asset Operations:** Upload, preview, rename, delete, and re-upload assets; customize asset downloads to fit team requirements.
- **Metadata and Tagging:** Add tags, custom properties, and embedded meta-information to assets, improving searchability and organization.
- **Collaboration and Resource Linking:** Multi-user reply comments and linked resource views facilitate team collaboration.
- **Asset Assignment and Export/Import:** Assign assets to products and categories, with CSV/XLSX export/import support for efficient data management.

**Ideal for organizations aiming to centralize digital content and improve cross-team collaboration, UnoPim DAM simplifies asset management throughout the digital content lifecycle.**

---

## Requirements
- **UnoPim**: v0.1.5

## Installation (Without Composer)

To install UnoPim DAM manually:

1. **Download and Setup the Extension**  
   - Download and unzip the extension.
   - Rename the folder to `DAM` and move it to the `packages/Webkul` directory within the project's root.

2. **Register the Package Provider**  
   - In the `config/app.php` file, add the following provider class under the `providers` key:

     ```php
     Webkul\DAM\Providers\DAMServiceProvider::class,
     ```

3. **Update Autoload Configuration**  
   - In the `composer.json` file, register the DAM directory under the `autoload` `psr-4` section:

     ```json
     "Webkul\\DAM\\": "packages/Webkul/DAM/src"
     ```

4. **Run Installation Commands**  
   - Execute the following commands to complete the installation:

     ```bash
     composer dump-autoload
     php artisan optimize:clear
     php artisan migrate
     php artisan vendor:publish --provider=Webkul\\DAM\\Providers\\DAMServiceProvider
     php artisan db:seed --class=Webkul\\DAM\\Database\\Seeders\\DirectoryTableSeeder
     ```

5. **Enable Queue Operations**  
   - To execute directory operations, start the queue using:

     ```bash
     php artisan queue:work
     ```
