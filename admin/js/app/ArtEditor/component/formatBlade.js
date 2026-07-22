// utils/formatBlade.js
//
// Formats a Blade template. Strategy:
//   1. Mask blocks prettier must not touch (@php, {{-- --}}, @extends(...),
//      @props(...)) with inline placeholders, remembering the original text.
//   2. Run prettier's HTML formatter to normalize the markup.
//   3. Re-indent Blade directives with a stack-based indenter (prettier's HTML
//      parser is unaware of Blade block directives, so it flattens them).
//   4. Restore the masked blocks, re-indenting multi-line @php to the depth of
//      its placeholder.
//
// Contract: `formatBlade(code, textIndent = 2)` resolves to the formatted
// string. If prettier fails on the input, the ORIGINAL code is returned
// unchanged and the error is surfaced via document.showToast.
import { formatPhp } from './formatPhp.js';

export async function formatBlade(code, textIndent = 2) {
  const phpBlocks = [];
  const commentBlocks = [];
  const dirBlocks = [];

  // Placeholders are HTML comments on their own conceptual token so prettier
  // keeps them intact and on a single line.
  const mark = (kind, i) => `<!--___${kind}____${i}-->`;

  let masked = code;

  // @php ... @endphp  (whole block, possibly multi-line or inline)
  masked = masked.replace(/@php\b([\s\S]*?)@endphp/g, (match) => {
    const i = phpBlocks.length;
    phpBlocks.push(match);
    return mark('PHP', i);
  });

  // {{-- blade comment --}}
  masked = masked.replace(/{{--[\s\S]*?--}}/g, (match) => {
    const i = commentBlocks.length;
    commentBlocks.push(match);
    return mark('COMMENT', i);
  });

  // Any non-block directive with an argument list — @include, @each, @extends,
  // @props, @yield, @component(inline), ... Prettier's HTML parser treats these
  // as plain text and collapses multi-line argument arrays (and glues the next
  // line onto them). Mask each as a single token so it survives untouched, then
  // pretty-print its arguments with the PHP formatter.
  masked = maskArgDirectives(masked, (match) => {
    const i = dirBlocks.length;
    dirBlocks.push(match);
    return mark('DIR', i);
  });

  // Format extracted blocks in parallel (PHP bodies + directive arg lists).
  const [formattedPhp, formattedDir] = await Promise.all([
    Promise.all(phpBlocks.map((b) => formatPhpBlock(b, textIndent))),
    Promise.all(dirBlocks.map((b) => formatArgDirective(b, textIndent))),
  ]);

  // Run prettier over the masked markup. On failure, leave the original code
  // untouched (this mirrors the editor's "don't destroy my file" contract).
  let formatted;
  try {
    formatted = await prettier.format(masked, {
      parser: 'html',
      plugins: [prettierPlugins.html],
      tabWidth: textIndent,
      printWidth: 160,
    });
  } catch (error) {
    showPrettierError(error);
    return code;
  }

  // Re-indent Blade directives that prettier flattened.
  formatted = reindentBlade(formatted, textIndent);

  // Restore masked blocks. Multi-line blocks keep the indent of their
  // placeholder line so nested content lines up with its surroundings.
  formatted = restoreIndented(formatted, 'PHP', formattedPhp);
  formatted = restoreIndented(formatted, 'DIR', formattedDir);
  formatted = restore(formatted, 'COMMENT', commentBlocks);

  // Collapse runs of 2+ blank lines into a single blank line, drop trailing
  // whitespace, and end with exactly one newline.
  formatted = formatted
    .replace(/[ \t]+$/gm, '')
    .replace(/\n{3,}/g, '\n\n')
    .replace(/\s+$/, '');

  return formatted + '\n';
}

// --------------------------------------------------------------------------
// Masking / formatting of directives that carry an argument list
// --------------------------------------------------------------------------

// Directives whose parentheses control indentation (block open/middle) must NOT
// be masked here — the re-indenter needs to see them. Everything else that
// takes arguments is safe to mask.
const BLOCK_ARG_DIRECTIVES = new Set([
  'if', 'elseif', 'unless', 'isset', 'empty', 'foreach', 'forelse', 'for',
  'while', 'switch', 'case', 'section', 'push', 'pushonce', 'prepend', 'stack',
  'once', 'error', 'can', 'elsecan', 'cannot', 'elsecannot', 'canany', 'env',
  'production', 'auth', 'guest', 'mproauth', 'hasSection', 'sectionMissing',
  'component', 'slot', 'fragment', 'livewire',
]);

// Scan for `@name(...args...)` with balanced parentheses and hand each match to
// `replacer`, unless the directive is a block directive we must keep visible.
function maskArgDirectives(text, replacer) {
  let out = '';
  let i = 0;
  const re = /@(\w+)\s*\(/g;
  let m;
  while ((m = re.exec(text)) !== null) {
    const name = m[1];
    const start = m.index;
    const parenOpen = m.index + m[0].length - 1;
    const close = matchBalanced(text, parenOpen);
    if (close === -1) continue; // unbalanced — leave as-is

    if (BLOCK_ARG_DIRECTIVES.has(name)) {
      re.lastIndex = close + 1;
      continue;
    }

    const match = text.slice(start, close + 1);
    out += text.slice(i, start) + replacer(match);
    i = close + 1;
    re.lastIndex = close + 1;
  }
  out += text.slice(i);
  return out;
}

// Index of the `)` that closes the `(` at `open`, respecting nesting, strings
// and escapes. -1 if unbalanced.
function matchBalanced(text, open) {
  let depth = 0;
  let quote = null;
  for (let i = open; i < text.length; i++) {
    const ch = text[i];
    if (quote) {
      if (ch === '\\') { i++; continue; }
      if (ch === quote) quote = null;
      continue;
    }
    if (ch === '"' || ch === "'" || ch === '`') { quote = ch; continue; }
    if (ch === '(') depth++;
    else if (ch === ')') {
      depth--;
      if (depth === 0) return i;
    }
  }
  return -1;
}

// Pretty-print a masked `@name(args)` directive: format the argument list with
// the PHP formatter (keeping single quotes) and reassemble the directive.
async function formatArgDirective(directive, textIndent) {
  const open = directive.indexOf('(');
  const name = directive.slice(0, open); // e.g. "@include"
  const args = directive.slice(open + 1, directive.lastIndexOf(')'));

  let phpOut;
  try {
    phpOut = await prettier.format(`<?php\n__d(${args});`, {
      parser: 'php',
      plugins: prettierPlugins,
      tabWidth: textIndent,
      singleQuote: true,
    });
  } catch {
    // If the args aren't valid PHP, keep the directive verbatim.
    return directive;
  }

  // Extract the formatted arg list from `__d( ... );`.
  const inner = phpOut
    .replace(/^<\?php\s*/, '')
    .replace(/\s+$/, '')
    .replace(/^__d\(/, '')
    .replace(/\);$/, '');

  return `${name}(${inner})`;
}

// --------------------------------------------------------------------------
// PHP block formatting
// --------------------------------------------------------------------------

async function formatPhpBlock(block, textIndent) {
  // Strip @php / @endphp, hand the body to the PHP formatter.
  const body = block.replace(/^@php\s*/, '').replace(/\s*@endphp$/, '');
  const formatted = await formatPhp(`<?php\n${body}`, textIndent);
  const inner = formatted
    .replace(/^<\?php\s*/, '')
    .replace(/\s+$/, '')
    .split('\n')
    .map((s) => ' '.repeat(textIndent) + s)
    .join('\n');
  return `@php\n${inner}\n@endphp`;
}

// Restore placeholders that expand to (possibly) multi-line blocks, shifting
// the whole block to the placeholder's own indentation so nested lines line up
// with their surrounding directives.
function restoreIndented(code, kind, blocks) {
  const lineRe = new RegExp(`^([ \\t]*)<!--___${kind}____(\\d+)-->[ \\t]*$`, 'gm');
  const inlineRe = new RegExp(`<!--___${kind}____(\\d+)-->`, 'g');
  return code
    .replace(lineRe, (_, indent, i) => indentLines(blocks[i], indent))
    .replace(inlineRe, (_, i) => blocks[i]); // inline fallback (mid-line)
}

function indentLines(text, indent) {
  return text
    .split('\n')
    .map((line) => (line === '' ? '' : indent + line))
    .join('\n');
}

function restore(code, kind, blocks) {
  const re = new RegExp(`<!--___${kind}____(\\d+)-->`, 'g');
  return code.replace(re, (_, i) => blocks[i]);
}

// --------------------------------------------------------------------------
// Prettier error reporting (unchanged behaviour, tidied)
// --------------------------------------------------------------------------

function showPrettierError(error) {
  const msg = String(error && error.message ? error.message : error)
    .split('\n')
    .map((s) => s.replace(/^\s+/, ''))
    .map((s) => s.replace(/For more info.*/, ''))
    .filter((s) => !s.includes('at'))
    .join('\n');
  if (typeof document !== 'undefined' && typeof document.showToast === 'function') {
    document.showToast(msg, 'error');
  }
}

// --------------------------------------------------------------------------
// Blade directive re-indentation
// --------------------------------------------------------------------------

// How each directive affects indentation.
//   open   : this line stays at the current level, following lines go deeper.
//   close  : this line and following lines come back one level.
//   middle : this line is drawn one level shallower, level is unchanged.
const OPEN = 'open';
const CLOSE = 'close';
const MIDDLE = 'middle';

const DIRECTIVES = {
  if: OPEN,
  elseif: MIDDLE,
  else: MIDDLE,
  endif: CLOSE,
  unless: OPEN,
  endunless: CLOSE,
  isset: OPEN,
  endisset: CLOSE,
  empty: OPEN,
  endempty: CLOSE, // note: @empty inside @forelse handled specially

  foreach: OPEN,
  endforeach: CLOSE,
  forelse: OPEN,
  endforelse: CLOSE,
  for: OPEN,
  endfor: CLOSE,
  while: OPEN,
  endwhile: CLOSE,

  switch: OPEN,
  endswitch: CLOSE,
  case: MIDDLE,
  default: MIDDLE,

  section: OPEN,
  endsection: CLOSE,
  show: CLOSE,
  stop: CLOSE,
  append: CLOSE,
  overwrite: CLOSE,
  php: OPEN,
  endphp: CLOSE,

  push: OPEN,
  endpush: CLOSE,
  pushonce: OPEN,
  endpushonce: CLOSE,
  prepend: OPEN,
  endprepend: CLOSE,
  stack: OPEN,
  endstack: CLOSE,

  component: OPEN,
  endcomponent: CLOSE,
  slot: OPEN,
  endslot: CLOSE,

  once: OPEN,
  endonce: CLOSE,
  verbatim: OPEN,
  endverbatim: CLOSE,
  error: OPEN,
  enderror: CLOSE,
  fragment: OPEN,
  endfragment: CLOSE,

  can: OPEN,
  elsecan: MIDDLE,
  endcan: CLOSE,
  cannot: OPEN,
  elsecannot: MIDDLE,
  endcannot: CLOSE,
  canany: OPEN,
  endcanany: CLOSE,

  auth: OPEN,
  endauth: CLOSE,
  guest: OPEN,
  endguest: CLOSE,
  mproauth: OPEN,
  endmproauth: CLOSE,

  hasSection: OPEN,
  endif_hassection: CLOSE, // rare; kept lenient
  sectionMissing: OPEN,

  production: OPEN,
  endproduction: CLOSE,
  env: OPEN,
  endenv: CLOSE,

  livewire: OPEN,
  endlivewire: CLOSE,
};

// Directives that never open a block regardless of the table (self-contained).
const NEVER_OPENS = new Set([
  'yield',
  'extends',
  'include',
  'includeif',
  'includewhen',
  'includeunless',
  'includefirst',
  'each',
  'csrf',
  'method',
  'props',
  'break',
  'continue',
  'json',
  'js',
  'dd',
  'dump',
  'vite',
  'inject',
  'lang',
  'use',
  'aware',
  'checked',
  'selected',
  'disabled',
  'readonly',
  'required',
  'class',
  'style',
]);

// Prettier lays out the HTML nesting but is blind to Blade block directives:
// everything inside @if/@section/@foreach keeps the HTML indent of the tag it
// sits in and is NOT pushed deeper for the Blade block. We therefore keep
// prettier's own indentation as the BASE and add the depth of the currently
// open Blade blocks on top of it. Final indent = htmlLevel + bladeDepth.
function reindentBlade(code, textIndent) {
  const lines = code.split('\n');
  let bladeDepth = 0;
  const out = [];

  for (const raw of lines) {
    // HTML indentation prettier assigned to this physical line (its base).
    const htmlLevel = Math.round(leadingSpaces(raw) / textIndent);

    for (const piece of splitDirectives(raw)) {
      const info = classify(piece);
      const content = info.text.replace(/^\s+/, '');

      if (content === '') {
        out.push('');
        continue;
      }

      // Closing/middle directives dedent themselves by one Blade level.
      const drawDepth = Math.max(0, bladeDepth + info.before);
      const indent = (htmlLevel + drawDepth) * textIndent;
      out.push(' '.repeat(indent) + content);

      // Adjust the open-block depth for subsequent lines.
      bladeDepth = Math.max(0, bladeDepth + info.after);
    }
  }

  return out.join('\n');
}

function leadingSpaces(line) {
  const m = line.match(/^[ \t]*/);
  // Treat a tab as one indent unit's worth of spaces is not needed here because
  // prettier emits spaces; count spaces directly.
  return m ? m[0].replace(/\t/g, ' ').length : 0;
}

// Prettier reflows Blade directives and surrounding text onto shared lines
// (`@endforeach @else`, `@else Some text`, `@if(...) @include(...) text`). The
// re-indenter needs every *structural* directive on its own line, with the text
// that follows it broken off. This tokenizer walks the line and emits a segment
// for each structural directive (with its balanced arg list) and a segment for
// the free text / inline expressions between them. Non-structural directives
// (@csrf, @method, includes, {{ }}) are NOT split away from their text so that
// prose like `Цена: {{ $p }} руб` stays on one line.
function splitDirectives(line) {
  if (line.trim() === '') return [''];

  const segments = [];
  let buffer = '';
  const flush = () => {
    if (buffer.trim() !== '') segments.push(buffer.trim());
    buffer = '';
  };

  const re = /@(\w+)/g;
  let lastIndex = 0;
  let m;
  while ((m = re.exec(line)) !== null) {
    const name = m[1];
    // Only structural directives force a line break.
    if (!isStructural(name)) continue;

    // Text before this directive belongs to the previous segment.
    buffer += line.slice(lastIndex, m.index);
    flush();

    // Consume the directive head plus its balanced () argument list, if any.
    let end = m.index + m[0].length;
    const afterName = line.slice(end).match(/^\s*\(/);
    if (afterName) {
      const parenOpen = line.indexOf('(', end);
      const parenClose = matchBalanced(line, parenOpen);
      if (parenClose !== -1) end = parenClose + 1;
    }
    segments.push(line.slice(m.index, end).trim());
    lastIndex = end;
    re.lastIndex = end;
  }
  buffer += line.slice(lastIndex);
  flush();

  return segments.length ? segments : [line];
}

// A directive is "structural" when it opens, closes, or divides a block — i.e.
// it appears in the indentation table and is not in NEVER_OPENS.
function isStructural(name) {
  if (NEVER_OPENS.has(name)) return false;
  return Object.prototype.hasOwnProperty.call(DIRECTIVES, name);
}

// Determine how a single logical line changes indentation.
// Returns { text, before, after }:
//   before -> delta applied to THIS line's own indent
//   after  -> delta applied to the level for subsequent lines
function classify(line) {
  const name = directiveName(line);
  if (!name) return { text: line, before: 0, after: 0 };

  const normalized = tidyDirective(line, name);

  if (NEVER_OPENS.has(name)) {
    return { text: normalized, before: 0, after: 0 };
  }

  // @empty is overloaded: `@empty` (no args) is the else-branch of @forelse
  // (a MIDDLE), while `@empty($var) ... @endempty` is its own block (OPEN).
  if (name === 'empty' && matchParens(normalized, 'empty') === null) {
    return { text: normalized, before: -1, after: 0 };
  }

  const kind = DIRECTIVES[name];
  if (!kind) {
    return { text: normalized, before: 0, after: 0 };
  }

  // Inline self-closed block on one line, e.g. `@php ... @endphp`,
  // `@section('a','b')`, `@error('x') ... @enderror` — no level change.
  if (kind === OPEN && isInlineClosed(normalized, name)) {
    return { text: normalized, before: 0, after: 0 };
  }

  if (kind === OPEN) return { text: normalized, before: 0, after: 1 };
  if (kind === CLOSE) return { text: normalized, before: -1, after: -1 };
  // MIDDLE (else / elseif / case / default / empty-in-forelse):
  return { text: normalized, before: -1, after: 0 };
}

function directiveName(line) {
  const m = line.match(/^\s*@(\w+)/);
  return m ? m[1] : null;
}

// Remove the space between a directive and its opening paren: `@if (x)` -> `@if(x)`.
function tidyDirective(line, name) {
  return line.replace(new RegExp(`@${name}\\s+\\(`), `@${name}(`);
}

// A block-opening directive is "inline closed" when its matching @endX (or a
// second argument, for @section) appears on the same line.
function isInlineClosed(line, name) {
  // @section('title', 'value') is a one-liner (two args) -> not a block.
  if (name === 'section') {
    const expr = matchParens(line, 'section');
    if (expr !== null && splitArgs(expr).length >= 2) return true;
  }
  // @php ... @endphp, @error(...) ... @enderror on one line.
  const closer = pairFor(name);
  if (closer && new RegExp(`@${closer}\\b`).test(line)) return true;
  return false;
}

// The closing directive name for a given opener (only where a one-line form
// is realistic).
function pairFor(name) {
  const pairs = {
    php: 'endphp',
    error: 'enderror',
    section: 'endsection',
    once: 'endonce',
    verbatim: 'endverbatim',
    push: 'endpush',
  };
  return pairs[name] || null;
}

// Return the text inside the first (...) following @name, respecting nested
// parentheses. Null if none.
function matchParens(line, name) {
  const start = line.search(new RegExp(`@${name}\\s*\\(`));
  if (start === -1) return null;
  const open = line.indexOf('(', start);
  let depth = 0;
  for (let i = open; i < line.length; i++) {
    const ch = line[i];
    if (ch === '(') depth++;
    else if (ch === ')') {
      depth--;
      if (depth === 0) return line.slice(open + 1, i);
    }
  }
  return null;
}

// Split a directive argument list on top-level commas (ignores commas inside
// nested (), [], {} and quotes).
function splitArgs(expr) {
  const args = [];
  let depth = 0;
  let quote = null;
  let cur = '';
  for (let i = 0; i < expr.length; i++) {
    const ch = expr[i];
    if (quote) {
      if (ch === quote && expr[i - 1] !== '\\') quote = null;
      cur += ch;
      continue;
    }
    if (ch === '"' || ch === "'") {
      quote = ch;
      cur += ch;
      continue;
    }
    if (ch === '(' || ch === '[' || ch === '{') {
      depth++;
      cur += ch;
      continue;
    }
    if (ch === ')' || ch === ']' || ch === '}') {
      depth--;
      cur += ch;
      continue;
    }
    if (ch === ',' && depth === 0) {
      args.push(cur.trim());
      cur = '';
      continue;
    }
    cur += ch;
  }
  if (cur.trim() !== '') args.push(cur.trim());
  return args;
}
