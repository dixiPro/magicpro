# formatBlade test sandbox

Runs `../formatBlade.js` outside the browser so the Blade formatter can be
checked without loading the admin UI.

`formatBlade.js` depends on the global `prettier` / `prettierPlugins` that the
admin template loads via `<script>` tags. `loadPrettier.mjs` loads the very same
standalone bundles from `public/vendor/dixipro/magicpro/prettier/*.js` into Node
and exposes them as globals, so the module under test runs unmodified.

## Run

```bash
# from this directory
node run.mjs                       # format every fixtures/*.blade.php
node run.mjs fixtures/01-basic.blade.php   # format one file
node run.mjs ../../../some.blade.php       # format an arbitrary file
```

## Files

- `loadPrettier.mjs` — loads the browser prettier bundles as Node globals.
- `run.mjs` — harness: loads globals, imports `formatBlade`, prints results.
- `fixtures/` — sample Blade templates covering the tricky cases:
  - `01-basic` — @extends/@section/@if/@foreach/@else nesting.
  - `02-php-and-comments` — @php blocks (block + inline + nested) and {{-- --}}.
  - `03-switch-forelse` — @switch/@case/@default and @forelse/@empty.
  - `04-components-props` — @props/@component/@slot/@once/@push.
  - `05-invalid-unclosed` — malformed input must not crash or over-indent.
  - `06-multidirective` — several @directives on one physical line.
  - `07-include-array` — @include with a multi-line array arg (must not
    collapse, must not glue the following {{ }} line).
  - `08-tricky-parens` — balanced-paren / quoted `)` edge cases in arg lists.
  - `09-else-text` — text after @else must break onto its own line.
  - `10-prose-inline` — prose with inline {{ }} must stay on one line while
    structural directives around it are broken out.
