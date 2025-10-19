/**
 * Включает пользовательские Blade/HTML снипеты и горячую клавишу Alt+7
 * для их вызова через встроенное автодополнение Ace.
 */
export function snippetsBlade(editor) {
    const snippetManager = ace.require("ace/snippets").snippetManager;

    const mySnippets = [
        // Blade control structures
        { name: "@if", content: "@if (${1:condition})\n\t$0\n@endif" },
        { name: "@elseif", content: "@elseif (${1:condition})" },
        { name: "@else", content: "@else\n\t$0" },
        {
            name: "@foreach",
            content: "@foreach (${1:items} as ${2:item})\n\t$0\n@endforeach",
        },
        {
            name: "@for",
            content:
                "@for (${1:i} = 0; ${1:i} < ${2:n}; ${1:i}++)\n\t$0\n@endfor",
        },
        { name: "@while", content: "@while (${1:condition})\n\t$0\n@endwhile" },
        {
            name: "@switch",
            content:
                "@switch(${1:variable})\n\t@case(${2:value})\n\t\t$0\n\t@break\n@endswitch",
        },

        // Blade includes / layouts
        { name: "@include", content: "@include('${1:view}', [${2:data}])" },
        { name: "@extends", content: "@extends('${1:layout}')" },
        {
            name: "@section",
            content: "@section('${1:name}')\n\t$0\n@endsection",
        },
        { name: "@yield", content: "@yield('${1:section}')" },
        { name: "@csrf", content: "@csrf" },
        { name: "@method", content: "@method('${1:PUT}')" },
        {
            name: "@error",
            content:
                "@error('${1:field}')\n\t<div class=\"text-danger\">{{ \$message }}</div>\n@enderror",
        },

        // Laravel PHP helpers
        { name: "route", content: "{{ route('${1:name}', [${2:params}]) }}" },
        { name: "url", content: "{{ url('${1:path}') }}" },
        { name: "asset", content: "{{ asset('${1:file}') }}" },
        { name: "old", content: "{{ old('${1:field}', ${2:default}) }}" },
        { name: "dd", content: "<?php dd(${1:var}); ?>" },
        { name: "dump", content: "<?php dump(${1:var}); ?>" },
        { name: "auth", content: "{{ Auth::user()->${1:name} }}" },
        { name: "request", content: "{{ request('${1:key}') }}" },

        // Controller / PHP snippets
        {
            name: "function",
            content:
                "public function ${1:name}(${2:Request \$request})\n{\n\t$0\n}",
        },
        { name: "return", content: "return ${1:view}(${2:params});" },
        { name: "redirect", content: "return redirect()->route('${1:name}');" },
        {
            name: "validate",
            content:
                "$request->validate([\n\t'${1:field}' => '${2:required|string}',\n]);",
        },
    ];

    snippetManager.register(mySnippets, "html");

    editor.commands.addCommand({
        name: "showMySnippets",
        bindKey: { win: "Shift-_", mac: "Shift-_" },
        exec(ed) {
            console.log("Showing custom snippets...");
            const snippetManager = ace.require("ace/snippets").snippetManager;
            // регистрируем только если ещё не зарегистрированы
            if (
                !snippetManager.snippetMap.html?.some((s) => s.name === "hello")
            ) {
                snippetManager.register(mySnippets, "html");
            }
            // вызвать стандартное окно автодополнения
            ed.execCommand("startAutocomplete");
        },
    });
}
