/**
 * Centralised, dynamic test data for the DAM API suite.
 *
 * Everything that must be unique per run is generated from a timestamp +
 * random suffix so re-runs never collide and parallel data stays isolated.
 * Static binary fixtures are shared with the e2e suite (`tests/e2e-pw/assets`)
 * to avoid duplicating sample files; synthetic files (empty/large/bad-type)
 * are generated in-memory at request time.
 */

const path = require('path');
const { randomBytes } = require('crypto');

/** Sample assets reused from the e2e suite — single source of binaries. */
const ASSETS_DIR = path.resolve(__dirname, '../../e2e-pw/assets');

/** Monotonic-ish unique suffix: base36 timestamp + 4 random bytes. */
function uniqueSuffix() {
  return `${Date.now().toString(36)}${randomBytes(4).toString('hex')}`;
}

/** Random integer in [min, max]. Avoids Math.random bias for small ranges. */
function randomInt(min, max) {
  const range = max - min + 1;
  return min + (randomBytes(4).readUInt32BE(0) % range);
}

const testData = {
  uniqueSuffix,
  randomInt,

  ASSETS_DIR,

  /** Absolute paths to the shared binary fixtures. */
  files: {
    image: path.join(ASSETS_DIR, 'floral.jpg'),
    png:   path.join(ASSETS_DIR, 'dotted.png'),
    video: path.join(ASSETS_DIR, 'sample.mp4'),
    audio: path.join(ASSETS_DIR, 'sample.mp3'),
    wav:   path.join(ASSETS_DIR, 'sample.wav'),
    pdf:   path.join(ASSETS_DIR, 'sample.pdf'),
    text:  path.join(ASSETS_DIR, 'sample.txt'),
  },

  /** A unique, valid asset/file name (e.g. "api-asset-l9x2…f1.png"). */
  assetName(ext = 'png') {
    return `api-asset-${uniqueSuffix()}.${ext}`;
  },

  /** A unique directory/folder name. */
  folderName(prefix = 'api-folder') {
    return `${prefix}-${uniqueSuffix()}`;
  },

  /** A unique tag name. */
  tagName(prefix = 'api-tag') {
    return `${prefix}-${uniqueSuffix()}`;
  },

  /** A unique comment body. */
  commentText() {
    return `Automated comment ${uniqueSuffix()}`;
  },

  /** A property (metadata) payload with a unique value. */
  property(overrides = {}) {
    return {
      name:     `Author ${uniqueSuffix()}`,
      type:     'text',
      language: 'en_US',
      value:    `value-${uniqueSuffix()}`,
      ...overrides,
    };
  },

  /** An id guaranteed not to exist — drives 404 negative cases. */
  nonExistentId: 999999999,

  /** Edge-case string inputs reused across negative/boundary tests. */
  edge: {
    empty:       '',
    whitespace:  '   ',
    special:     '<>&"\'`/\\%#?@!$^*()[]{}|;:,~',
    unicode:     'ñç—🚀–日本語-Ωμ',
    sqlish:      "Robert'); DROP TABLE dam_assets;--",
    long(len = 256) { return 'A'.repeat(len); },
  },

  /** In-memory synthetic files for upload edge cases. */
  syntheticFile: {
    empty(name = 'empty.png') {
      return { name, mimeType: 'image/png', buffer: Buffer.alloc(0) };
    },
    large(name = 'large.bin', megabytes = 15) {
      return { name, mimeType: 'application/octet-stream', buffer: Buffer.alloc(megabytes * 1024 * 1024, 1) };
    },
    invalidType(name = 'malware.exe') {
      return { name, mimeType: 'application/x-msdownload', buffer: Buffer.from('MZ executable stub') };
    },
    text(name = 'note.txt', content = 'hello dam') {
      return { name, mimeType: 'text/plain', buffer: Buffer.from(content) };
    },
  },
};

module.exports = testData;
