/**
 * This will track all the images and fonts for publishing.
 *
 * `eager: true` is required: a lazy import.meta.glob() whose result is never used
 * gets tree-shaken away by Vite/Rollup, so the images never get emitted into the
 * build manifest and unopim_asset('images/grid/*.svg', 'dam') fails to resolve.
 * Eager imports are static, so every image/font is emitted with a manifest entry.
 */
import.meta.glob(["../images/**", "../fonts/**"], { eager: true });

 