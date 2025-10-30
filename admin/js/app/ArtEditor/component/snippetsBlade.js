/**
 * Включает пользовательские Blade/HTML снипеты и горячую клавишу Alt+7
 * для их вызова через встроенное автодополнение Ace.
 */
export function snippetsBlade(editor) {
  const snippetArr = [
    { tabTrigger: '', name: '_@auth', content: '@auth\n\t$0\n@endauth' },
    { tabTrigger: '', name: '_@can', content: '@can(${1:ability})\n\t$0\n@endcan' },
    { tabTrigger: '', name: '_@cannot', content: '@cannot(${1:ability})\n\t$0\n@endcannot' },
    { tabTrigger: '', name: '_@csrf', content: '@csrf' },
    { tabTrigger: '', name: '_@dd', content: '@dd(${1:var})' },
    { tabTrigger: '', name: '_@dump', content: '@dump(${1:var})' },
    { tabTrigger: '', name: '_@else', content: '@else\n\t$0' },
    { tabTrigger: '', name: '_@elseif', content: '@elseif ($${1:user}->${2:active})' },
    { tabTrigger: '', name: '_@empty', content: '@empty ($${1:arr})\n\t$0\n@endempty' },
    { tabTrigger: '', name: '_@endauth', content: '@endauth' },
    { tabTrigger: '', name: '_@endcan', content: '@endcan' },
    { tabTrigger: '', name: '_@endcannot', content: '@endcannot' },
    { tabTrigger: '', name: '_@endempty', content: '@endempty' },
    { tabTrigger: '', name: '_@endforeach', content: '@endforeach' },
    { tabTrigger: '', name: '_@endif', content: '@endif' },
    { tabTrigger: '', name: '_@endfor', content: '@endfor' },
    { tabTrigger: '', name: '_@endguest', content: '@endguest' },
    { tabTrigger: '', name: '_@endisset', content: '@endisset' },
    { tabTrigger: '', name: '_@endonce', content: '@endonce' },
    { tabTrigger: '', name: '_@endprepend', content: '@endprepend' },
    { tabTrigger: '', name: '_@endphp', content: '@endphp' },
    { tabTrigger: '', name: '_@endpush', content: '@endpush' },
    { tabTrigger: '', name: '_@endsection', content: '@endsection' },
    { tabTrigger: '', name: '_@endswitch', content: '@endswitch' },
    { tabTrigger: '', name: '_@endwhile', content: '@endwhile' },
    { tabTrigger: '', name: '_@env', content: '@env(${1:environment})\n\t$0\n@endenv' },
    { tabTrigger: '', name: '_@error', content: '@error(\'${1:field}\')\n\t<div class="text-danger">{{ $message }}</div>\n@enderror' },
    { tabTrigger: '', name: '_@extends', content: "@extends('${1:layout}')" },

    { tabTrigger: '', name: '_@for', content: '@for ($${1:i} = 0; $${1:i} < ${2:10}; $${1:i}++)\n\t$0\n@endfor' },
    { tabTrigger: '', name: '_@foreach', content: '@foreach ($${1:items} as $${2:item})\n\t$0\n@endforeach' },

    { tabTrigger: '', name: '_@foreach index', content: '@foreach ($${1:items} as $${2:ndex} => $${3:item})\n\t$0\n@endforeach' },
    { tabTrigger: '', name: '_@guest', content: '@guest\n\t$0\n@endguest' },

    { tabTrigger: '', name: '_@if', content: '@if ($${1:var} > 0)\n\t$0\n@endif' },

    { tabTrigger: '', name: '_@include', content: "@include('magic::${1:view}', [${2:data}])" },
    { tabTrigger: '', name: '_@includeFirst', content: "@includeFirst(['magic::${1:view1}', 'magic::${2:view2}'])" },
    { tabTrigger: '', name: '_@includeIf', content: "@includeIf('magic::${1:view}', [${2:data}])" },
    { tabTrigger: '', name: '_@includeWhen', content: "@includeWhen(${1:condition}, 'magic::${2:view}', [${3:data}])" },
    { tabTrigger: '', name: '_@includeUnless', content: "@includeUnless(${1:condition}, 'magic::${2:view}', [${3:data}])" },

    { tabTrigger: '', name: '_@isset', content: '@isset ($${1:var})\n\t$0\n@endisset' },
    { tabTrigger: '', name: '_@json', content: '@json(${1:data})' },
    { tabTrigger: '', name: '_@method', content: "@method('${1:PUT}')" },
    { tabTrigger: '', name: '_@once', content: '@once\n\t$0\n@endonce' },
    { tabTrigger: '', name: '_@php', content: '@php\n\t$0\n@endphp' },
    { tabTrigger: '', name: '_@prepend', content: "@prepend('${1:stack}')\n\t$0\n@endprepend" },
    { tabTrigger: '', name: '_@production', content: '@production\n\t$0\n@endproduction' },
    { tabTrigger: '', name: '_@props', content: "@props(['${1:variable}' => ${2:default}])" },
    { tabTrigger: '', name: '_@push', content: "@push('${1:stack}')\n\t$0\n@endpush" },
    { tabTrigger: '', name: '_@section (block)', content: "@section('${1:name}')\n\t$0\n@endsection" },
    { tabTrigger: '', name: '_@section (inline)', content: "@section('${1:name}', '${2:content}')" },
    { tabTrigger: '', name: '_@stack', content: "@stack('${1:stack}')" },
    { tabTrigger: '', name: '_@switch', content: '@switch ($${1:value})\n\t@case($${2:key})\n\t\t$0\n\t@break\n@endswitch' },
    { tabTrigger: '', name: '_@unless', content: '@unless ($${1:cond})\n\t$0\n@endunless' },
    { tabTrigger: '', name: '_@verbatim', content: '@verbatim\n\t$0\n@endverbatim' },
    { tabTrigger: '', name: '_@while', content: '@while ($${1:flag})\n\t$0\n@endwhile' },
    { tabTrigger: '', name: '_@yield', content: "@yield('${1:section}')" },

    { tabTrigger: '', name: '_<x-magic', content: '<x-magic::${1:anonymus} :data="$${1:value}"></x-magic::${1:anonymus}>)' },
  ];
  const allKeys = snippetArr.map((e) => e.tabTrigger.replace('@', ''));

  const snippetManager = ace.require('ace/snippets').snippetManager;
  snippetManager.register(snippetArr, 'html');

  const Autocomplete = ace.require('ace/autocomplete').Autocomplete;

  // сохраняем оригинальные методы
  const oldShowPopup = Autocomplete.prototype.showPopup;
  const oldUpdateCompletions = Autocomplete.prototype.updateCompletions;

  // фильтрация
  function filterPopup(popup) {
    if (popup && Array.isArray(popup.data)) {
      popup.data = popup.data.filter((item) => !allKeys.includes(item.value));
      popup.setData(popup.data);
    }
  }

  // патчим showPopup
  Autocomplete.prototype.showPopup = function (editor) {
    oldShowPopup.call(this, editor);
    filterPopup(this.popup);
  };

  // патчим updateCompletions (срабатывает при каждом новом вводе символа)
  Autocomplete.prototype.updateCompletions = function (keepPopupPosition) {
    oldUpdateCompletions.call(this, keepPopupPosition);
    filterPopup(this.popup);
  };

  // 3️⃣ Привязка к Alt-/
  editor.commands.addCommand({
    name: 'toggleLineMarkersCommand',
    bindKey: { win: 'Alt-/', mac: 'Alt-/' },
    exec: toggleLineMarkers,
    readOnly: false,
  });
}

// 1️⃣ Функция: выделяет строку или блок строк и возвращает их диапазон
function smartSelectLines(editor) {
  let startRow, endRow;

  if (editor.getSelectionRange().isEmpty()) {
    const pos = editor.getCursorPosition();
    editor.selection.selectLine(pos.row);
    startRow = endRow = pos.row;
  } else {
    const sel = editor.getSelectionRange();
    const Range = ace.require('ace/range').Range;

    startRow = sel.start.row;
    endRow = sel.end.row;

    const fullRange = new Range(startRow, 0, endRow, editor.session.getLine(endRow).length);

    editor.selection.setRange(fullRange);
  }

  return { startRow, endRow };
}

// 🔧 функция обработки одной строки
function toggleCommentLine(line) {
  // убираем Blade-комментарий, если он есть
  if (/{{--.*--}}/.test(line)) {
    return line.replace(/{{--\s*|\s*--}}/g, '');
  }

  // иначе добавляем
  return `{{-- ${line} --}}`;
}

// 2️⃣ Функция: добавляет или снимает обёртку {-- ... --}} для выбранных строк
function toggleLineMarkers(editor) {
  const { startRow, endRow } = smartSelectLines(editor);
  const session = editor.session;
  const Range = ace.require('ace/range').Range;

  for (let row = startRow; row <= endRow; row++) {
    const line = session.getLine(row);
    const updated = toggleCommentLine(line);
    const length = line.length;
    // просто и понятно: заменить всю строку от начала до конца
    session.replace(new Range(row, 0, row, length), updated);
  }
}
