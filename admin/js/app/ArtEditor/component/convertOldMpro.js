// utils/formatBlade.js

export function convertOldMpro(code) {
  const meta = {};
  let match = code.match(/\{% *setMeta\('title', *'([^']+)'/);
  meta.title = match ? match[1] : null;

  match = code.match(/\{% *setMeta\('htmlDescr', *'([^']+)'/);
  meta.description = match ? match[1].trim() : null;

  match = code.match(/\{% *setMeta\('keywords', *'([^']+)'/);
  meta.keywords = match ? match[1].trim() : null;

  match = code.match(/\{% *setMeta\('h1', *'([^']+)'/);
  meta.h1 = match ? match[1].trim() : null;

  console.log(meta);

  const res = ` @extends('magic::main',[
  'title' => '${meta.title}',
  'description' => '${meta.description}',
  'keywords' => '${meta.keywords}'
])
  

<h1>${meta.h1}</h1>

`;

  return res + code;
}
