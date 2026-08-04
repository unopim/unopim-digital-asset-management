

const path = require('path');
const { randomBytes } = require('crypto');

const ASSETS_DIR = path.resolve(__dirname, '../../e2e-pw/assets');

function uniqueSuffix() {
  return `${Date.now().toString(36)}${randomBytes(4).toString('hex')}`;
}

function randomInt(min, max) {
  const range = max - min + 1;
  return min + (randomBytes(4).readUInt32BE(0) % range);
}

const testData = {
  uniqueSuffix,
  randomInt,

  ASSETS_DIR,

  files: {
    image: path.join(ASSETS_DIR, 'floral.jpg'),
    png:   path.join(ASSETS_DIR, 'dotted.png'),
    video: path.join(ASSETS_DIR, 'sample.mp4'),
    audio: path.join(ASSETS_DIR, 'sample.mp3'),
    wav:   path.join(ASSETS_DIR, 'sample.wav'),
    pdf:   path.join(ASSETS_DIR, 'sample.pdf'),
    text:  path.join(ASSETS_DIR, 'sample.txt'),
  },

  assetName(ext = 'png') {
    return `api-asset-${uniqueSuffix()}.${ext}`;
  },

  folderName(prefix = 'api-folder') {
    return `${prefix}-${uniqueSuffix()}`;
  },

  tagName(prefix = 'api-tag') {
    return `${prefix}-${uniqueSuffix()}`;
  },

  commentText() {
    return `Automated comment ${uniqueSuffix()}`;
  },

  property(overrides = {}) {
    return {
      name:     `Author ${uniqueSuffix()}`,
      type:     'text',
      language: 'en_US',
      value:    `value-${uniqueSuffix()}`,
      ...overrides,
    };
  },

  nonExistentId: 999999999,

  edge: {
    empty:       '',
    whitespace:  '   ',
    special:     '<>&"\'`/\\%#?@!$^*()[]{}|;:,~',
    unicode:     'ñç—🚀–日本語-Ωμ',
    sqlish:      "Robert'); DROP TABLE dam_assets;--",
    long(len = 256) { return 'A'.repeat(len); },
  },

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
