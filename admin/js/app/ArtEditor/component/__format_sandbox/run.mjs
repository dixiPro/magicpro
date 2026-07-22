// Harness: load prettier globals, then formatBlade, then run fixtures.
// Usage:
//   node run.mjs            -> format every fixtures/*.blade.php, print result
//   node run.mjs <file>     -> format a single file
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { loadPrettier } from './loadPrettier.mjs';

loadPrettier();

// document.showToast is called on prettier errors in the browser; stub it.
globalThis.document = globalThis.document || {
  showToast: (msg) => console.error('[toast] ' + msg),
};

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const { formatBlade } = await import('../formatBlade.js');

const arg = process.argv[2];
const fixturesDir = path.join(__dirname, 'fixtures');

let files;
if (arg) {
  files = [path.resolve(arg)];
} else {
  files = fs
    .readdirSync(fixturesDir)
    .filter((f) => f.endsWith('.blade.php'))
    .map((f) => path.join(fixturesDir, f));
}

for (const file of files) {
  const src = fs.readFileSync(file, 'utf8');
  const out = await formatBlade(src, 2);
  console.log('\n============================================================');
  console.log('FILE: ' + path.basename(file));
  console.log('============================================================');
  console.log(out);
}
