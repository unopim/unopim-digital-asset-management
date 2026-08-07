# DAM demo asset library

60 assets across 18 directories, seeded by `DamDemoDataSeeder`. Everything here is
fictional demo content for the **Meridian** brand — no real company, product or
document is represented.

`DamDemoDataSeeder::seedAssets()` walks `Root/` with `File::allFiles()`, so **only**
asset files belong under `Root/`. This README deliberately sits one level above it.

## Contents

| Type | Count | Provenance |
|---|---|---|
| Photography (`webp`, `jpg`) | 24 | Copied from `packages/Webkul/Installer/src/Resources/assets/images/demo/catalog`, renamed to describe what each frame actually shows; the `jpg` variants transcoded with ImageMagick |
| Logos (`png`, `svg`) | 4 | Hand-authored SVG wordmark plus two single-ink outline variants, rasterised with ImageMagick |
| Video (`mp4`, `mov`, `webm`) | 8 | `ffmpeg` Ken Burns pan/zoom over the same catalog photography, 6 s at 1280×720, lower-third title and a quiet sine bed |
| Audio (`mp3`, `wav`, `ogg`) | 9 | `ffmpeg`-composed chord pads; the five podcast episodes carry ID3v2.3 tags and an embedded APIC cover frame |
| Documents (`pdf`) | 9 | Rendered with `barryvdh/laravel-dompdf` |
| Documents (`docx`) | 3 | OOXML assembled with `ZipArchive` |
| Documents (`xlsx`, `csv`) | 3 | `phpoffice/phpspreadsheet` and `fputcsv` |

## Constraints worth knowing before regenerating

- **Photo filenames must describe the frame.** An earlier pass assigned names from the
  catalog's numbering without looking, and produced `outdoor-water-bottle.webp` on a
  photo of a camp stove. Anything built on this data — search, tagging, metadata demos —
  is only as good as that correspondence.
- **Mono logo variants are their own SVG sources.** Rasterising the primary mark and
  colorizing it flattens the white chevron into the white square, leaving a blank blob.
- **PDFs must enable font subsetting** (`->setOption('isFontSubsettingEnabled', true)`).
  The package default is off, and an unsubsetted DejaVu pair adds ~800 KB to every file —
  875 KB per PDF versus 48 KB with subsetting on.
- **`[Content_Types].xml` must be the first entry in a hand-built `.docx`.** Otherwise
  libmagic reports `application/zip` and the seeder stores the wrong `mime_type`.
- **The logo embedded in a PDF must be a transparent PNG, not JPEG.** JPEG shifts the
  flat brand green just enough to leave a visible rectangle against the header bar.
- **SVG must avoid `#7c3aec`.** `AssetHelper::isPlaceholderImage()` screens for that
  colour plus a known path signature and would reject the asset.

Regenerating is a manual, one-off operation — the generator scripts are not shipped.
Adding or removing a file here requires updating both `DemoAssetLibraryTest` (manifest
counts) and `SeedDamDemoDataTest` (seeded row counts).
