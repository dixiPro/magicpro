// languagePatch.js — патч подсветки HTML под MPro конструкции
export async function patchHtmlTokenizer(monaco) {
  const htmlLang = monaco.languages.getLanguages().find((l) => l.id === 'html');
  if (!htmlLang || typeof htmlLang.loader !== 'function') return;

  const htmlMod = await htmlLang.loader();
  const t = htmlMod.language.tokenizer;

  t.mproblock = [
    [/%\}/, { token: 'mproblock', next: '@pop' }],
    [/\}\}/, { token: 'mproblock', next: '@pop' }],
    [/./, 'mproblock'],
  ];
  t.mproexpr = [
    [/\}\}/, { token: 'mproblock', next: '@pop' }],
    [/./, 'mproblock'],
  ];
  t.mprocurly = [
    [/\}/, { token: 'mprocurly', next: '@pop' }],
    [/./, 'mprocurly'],
  ];
  t.mprocomment = [
    [/\*\}/, { token: 'mprocomment', next: '@pop' }],
    [/./, 'mprocomment'],
  ];

  const root = t.root;
  for (let i = root.length - 1; i >= 0; i--) {
    const r = root[i][1];
    if (r && r.next && (r.next === '@mproblock' || r.next === '@mproexpr' || r.next === '@mprocurly' || r.next === '@mprocomment')) {
      root.splice(i, 1);
    }
  }

  root.unshift(
    [/\s*\{%/, { token: 'mproblock', next: '@mproblock' }],
    [/\s*\{\{/, { token: 'mproblock', next: '@mproblock' }],
    [/\s*\{\[[^\s\]]+\]/, 'mprocurly'],
    [/\s*\{\*/, { token: 'mprocomment', next: '@mprocomment' }]
  );

  monaco.languages.setMonarchTokensProvider('html', htmlMod.language);
}
