// utils/formatBlade.js
import { formatPhp } from './formatPhp.js';

export async function formatBlade(code, textIdend = 2) {
  let phpBlocks = [];

  // находим заменяем PHP
  let formatted = code.replace(/@php([\s\S]*?)@endphp/g, (match, code) => {
    const index = phpBlocks.length;
    phpBlocks.push(match); // сохраняем весь блок
    return `<!--___PHP____${index}-->`; // подставляем маркер
  });

  let extendsBlocks = [];
  formatted = formatted.replace(/@extends\s*\(\s*['"][^)]+?\[[\s\S]*?\]\s*\)/g, (match) => {
    const index = extendsBlocks.length;
    extendsBlocks.push(match);
    return `<!--___EXTENDS____${index}-->`;
  });

  let propsBlocks = [];
  // находим заменяем props
  formatted = formatted.replace(/@props\s*\(\s*\[[\s\S]*?\]\s*\)/g, (match) => {
    const index = propsBlocks.length;
    propsBlocks.push(match);
    return `<!--___PROPS____${index}-->`;
  });

  let commentBlocks = [];
  formatted = formatted.replace(/{{--([\s\S]*?)--}}/g, (match, comment) => {
    const index = commentBlocks.length;
    commentBlocks.push(match); // сохраняем весь блок
    return `<!--___COMMENT____${index}-->`; // подставляем маркер
  });

  // форматируем пхп
  if (phpBlocks.length > 0) {
    phpBlocks = await Promise.all(phpBlocks.map((b) => formatPhpLocal(b, textIdend)));
  }
  // if (propsBlocks.length > 0) {
  //   propsBlocks = await Promise.all(propsBlocks.map((b) => formatProps(b, textIdend)));
  // }

  try {
    formatted = await prettier.format(formatted, {
      parser: 'html',
      plugins: [prettierPlugins.html],
      tabWidth: textIdend,
      printWidth: 160,
    });
  } catch (error) {
    // debugger;
    const msg = error.message
      .split('\n')
      .map((s) => s.replace(/^\s+/, ''))
      .map((s) => s.replace(/For more info.*/, ''))
      .filter((s) => !s.includes('at'))
      .join('\n');
    document.showToast(msg, 'error');
  }
  // форматируем

  formatted = afterPrettier(formatted, textIdend);

  formatted = formatted.replace(/<!--___PHP____(\d+)-->/g, (_, i) => phpBlocks[i]);

  formatted = formatted.replace(/<!--___PROPS____(\d+)-->/g, (_, i) => propsBlocks[i]);

  // восстанавливаем Blade-комментарии из commentBlocks
  formatted = formatted.replace(/<!--___COMMENT____(\d+)-->/g, (_, i) => commentBlocks[i]);

  formatted = formatted.replace(/<!--___EXTENDS____(\d+)-->/g, (_, i) => extendsBlocks[i]);

  return formatted;
}

async function formatPhpLocal(line, textIdend) {
  // убираем @php и @endphp
  line = line.replace(/@php\s*/g, '').replace(/\s*@endphp/g, '');
  // добавляем <?php
  let code = `<?php\n${line}`;
  // вызываем внешний форматтер

  let formatted = await formatPhp(code, textIdend);
  // убираем <?php
  formatted = formatted.replace(/^<\?php\s*/, '').trim();
  formatted = formatted
    .split('\n')
    .map((s) => ' '.repeat(textIdend) + s)
    .join('\n');
  formatted = `@php\n${formatted}\n@endphp`;
  return formatted;
}

async function formatProps(line) {
  // убираем @php и @endphp
  line = line.replace(/@/, '');
  // добавляем <?php
  let code = `<?php\n${line}`;
  // вызываем внешний форматтер
  let formatted = await formatPhp(code, 4);
  // убираем <?php
  formatted = formatted.replace(/^<\?php\s*/, '');
  // возвращаем с оберткой
  return `@${formatted.trim()}\n`;
}

function afterPrettier(code, textIdend = 2) {
  const lines = code.split('\n');
  let indent = 0;
  const result = [];

  for (let some of lines) {
    // разбиваем строку с несколькими директивами на несколько строк
    let splitLines = splitBladeDirectivesWithIndent(some);

    for (let rawLine of splitLines) {
      let directiveAttr = getBladeDirectiveAttr(rawLine);

      // пробелы в  начале
      if (rawLine.includes('<!--___PHP____')) {
        rawLine = rawLine.replace(/^\s+/, '');
        result.push(rawLine);
      } else {
        indent = indent + directiveAttr.ident;
        result.push(' '.repeat(indent * textIdend) + directiveAttr.line);
        indent = indent + directiveAttr.after;
      }
    }
  }

  return result.join('\n');
}

function splitBladeDirectivesWithIndent(line) {
  // 1️⃣ Определяем начальный отступ (количество пробелов или табов перед текстом)
  const indentMatch = line.match(/^(\s*)/); // ищет пробелы/табы в начале строки
  const indent = indentMatch ? indentMatch[1] : ''; // если нашли — сохраняем

  // 2️⃣ Разбиваем строку по всем @директивам, кроме первой
  // Регулярка `(?=@\w+)` — это "разделить перед каждым символом '@', за которым идёт слово"
  const parts = line
    .split(/(?=@\w+)/)
    .map((s) => s.trim()) // убираем лишние пробелы вокруг
    .filter(Boolean); // выкидываем пустые элементы

  // 3️⃣ Если в строке только одна директива — возвращаем строку без изменений
  if (parts.length <= 1) return [line];

  // 4️⃣ Собираем обратно: каждую директиву помещаем на отдельной строке
  return parts.map((p) => indent + p);
}

function getBladeDirectiveAttr(line) {
  const directives = {
    if: { ident: 0, after: 1 },
    elseif: { ident: -1, after: 1 },
    else: { ident: -1, after: 1 },
    endif: { ident: -1, after: 0 },

    section: { ident: 0, after: 1, extended: { ident: 0, after: 0 } },
    endsection: { ident: -1, after: 0 },

    foreach: { ident: 0, after: 1 },
    endforeach: { ident: -1, after: 0 },

    forelse: { ident: 0, after: 1 },
    endforelse: { ident: -1, after: 0 },

    for: { ident: 0, after: 1 },
    endfor: { ident: -1, after: 0 },

    yield: { ident: 1, after: -1 },

    while: { ident: 0, after: 1 },
    endwhile: { ident: -1, after: 0 },

    section: { ident: 0, after: 1 },
    endsection: { ident: -1, after: 0 },

    switch: { ident: 0, after: 1 },
    case: { ident: -1, after: 1 },
    endswitch: { ident: -1, after: 0 },
    endcase: { ident: -1, after: 0 },

    php: { ident: 0, after: 1 },
    endphp: { ident: -1, after: 0 },

    livewire: { ident: 0, after: 1 },
    endlivewire: { ident: -1, after: 0 },

    component: { ident: 0, after: 1 },
    endcomponent: { ident: -1, after: 0 },

    slot: { ident: 0, after: 1 },
    endslot: { ident: -1, after: 0 },

    props: { ident: 0, after: 0 },

    error: { ident: 0, after: 1 },
    enderror: { ident: -1, after: 0 },

    once: { ident: 0, after: 1 },
    endonce: { ident: -1, after: 0 },

    push: { ident: 0, after: 1 },
    endpush: { ident: -1, after: 0 },

    stack: { ident: 0, after: 1 },
    endstack: { ident: -1, after: 0 },

    verbatim: { ident: 0, after: 1 },
    endverbatim: { ident: -1, after: 0 },

    can: { ident: 0, after: 1 },
    endcan: { ident: -1, after: 0 },

    cannot: { ident: 0, after: 1 },
    endcannot: { ident: -1, after: 0 },

    auth: { ident: 0, after: 1 },
    endauth: { ident: -1, after: 0 },

    guest: { ident: 0, after: 1 },
    endguest: { ident: -1, after: 0 },
    mproauth: { ident: 0, after: 1 },
    endmproauth: { ident: -1, after: 0 },
  };

  let directive = (line.match(/@\w+/) || [null])[0];
  if (!directive) {
    return { ident: 0, after: 0, line: line };
  }
  directive = directive.replace('@', '');
  if (!directives[directive]) {
    return { ident: 0, after: 0, line: line };
  }

  // убираем пробелы после директивы и перед скобкой
  let tmpLine = line.replace(new RegExp(`${directive}\\s*\\(`, 'g'), `${directive}(`);

  let dirAttr = directives[directive];
  dirAttr.line = tmpLine;

  // директива на имеет расширений
  if (!dirAttr.extended) {
    return dirAttr;
  }

  // вернуть выражение в скобках после  directive из строки tmpLine
  const expression = (tmpLine.match(new RegExp(`${directive}\\(([^)]*)\\)`)) || [null, ''])[1];
  // разбиваем
  const dirSplit = expression.split(',');
  // два
  if (dirSplit.length > 1) {
    dirAttr = directives[directive].extended;
    dirAttr.line = tmpLine;
    return res;
  }

  return dirAttr;
}
