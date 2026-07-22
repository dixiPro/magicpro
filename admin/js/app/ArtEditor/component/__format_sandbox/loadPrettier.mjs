// Loads the browser prettier standalone bundles into Node as globals,
// exactly like templateAdmin.blade.php does with <script> tags.
// This lets us run formatBlade.js (which relies on the global `prettier`
// and `prettierPlugins`) outside the browser.
import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Walk up until we find the Laravel root (the dir that contains `public`),
// then point at the prettier bundles served to the browser.
function findPrettierDir(start) {
  let dir = start;
  for (let i = 0; i < 15; i++) {
    const candidate = path.join(dir, 'public', 'vendor', 'dixipro', 'magicpro', 'prettier');
    if (fs.existsSync(candidate)) return candidate;
    const parent = path.dirname(dir);
    if (parent === dir) break;
    dir = parent;
  }
  throw new Error('Could not locate public/vendor/.../prettier starting from ' + start);
}

const PRETTIER_DIR = findPrettierDir(__dirname);

const BUNDLES = [
  'standalone.js',
  'plugin-html.js',
  'plugin-php.js',
  'postcss.js',
  'babel.js',
  'estree.js',
];

export function loadPrettier() {
  // Run each UMD bundle in a context where `module`/`exports` are absent so
  // it takes the browser branch and attaches to globalThis.
  const sandbox = { globalThis: null, self: null, window: null, console };
  sandbox.globalThis = sandbox;
  sandbox.self = sandbox;
  sandbox.window = sandbox;
  vm.createContext(sandbox);

  for (const file of BUNDLES) {
    const full = path.join(PRETTIER_DIR, file);
    if (!fs.existsSync(full)) {
      throw new Error(`Prettier bundle not found: ${full}`);
    }
    const src = fs.readFileSync(full, 'utf8');
    vm.runInContext(src, sandbox, { filename: file });
  }

  if (!sandbox.prettier || typeof sandbox.prettier.format !== 'function') {
    throw new Error('prettier global was not set by standalone.js');
  }
  if (!sandbox.prettierPlugins) {
    throw new Error('prettierPlugins global was not set by plugins');
  }

  // Expose to the current Node global scope for the module under test.
  globalThis.prettier = sandbox.prettier;
  globalThis.prettierPlugins = sandbox.prettierPlugins;

  return { prettier: sandbox.prettier, prettierPlugins: sandbox.prettierPlugins };
}
