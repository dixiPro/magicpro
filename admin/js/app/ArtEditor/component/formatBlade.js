// utils/formatBlade.js

export async function formatBlade(code, textIdend = 2) {
    try {
        // форматируем
        const formatted = await prettier.format(code, {
            parser: "html",
            plugins: [prettierPlugins.html],
            tabWidth: textIdend,
            printWidth: 160,
        });
        // return formatted;
        return afterPrettier(formatted, textIdend);
    } catch (e) {
        console.error("Ошибка при форматировании Blade:", e);
        throw new Error("Ошибка при форматировании Blade");
    }
}

function afterPrettier(code, textIdend = 2) {
    const lines = code.split("\n");
    let indent = 0;
    const result = [];

    for (let some of lines) {
        // разбиваем строку с несколькими директивами на несколько строк
        let splitLines = splitBladeDirectivesWithIndent(some);

        for (let rawLine of splitLines) {
            let directiveType = getBladeDirectiveType(rawLine);

            if (directiveType === 0) {
                result.push(" ".repeat(indent) + rawLine);
            }

            if (directiveType === 1) {
                result.push(" ".repeat(indent) + rawLine);
                indent = ++textIdend;
                console.log("indent increased to", indent);
            }

            if (directiveType === -1) {
                indent = Math.max(indent - textIdend, 0);
                result.push(" ".repeat(indent) + rawLine);
                console.log("indent decreased to", indent);
            }
        }
    }

    return result.join("\n");
}

function splitBladeDirectivesWithIndent(line) {
    // 1️⃣ Определяем начальный отступ (количество пробелов или табов перед текстом)
    const indentMatch = line.match(/^(\s*)/); // ищет пробелы/табы в начале строки
    const indent = indentMatch ? indentMatch[1] : ""; // если нашли — сохраняем

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

function getBladeDirectiveType(line) {
    // если строка содержит открывающую директиву, возвращаем 1
    // если строка содержит закрывающую директиву, возвращаем -1
    // иначе возвращаем 0
    const openingDirectives = [
        "@if",
        "@elseif",
        "@else",
        "@foreach",
        "@forelse",
        "@for",
        "@while",
        "@section",
        "@switch",
        "@case",
        "@php",
        "@livewire",
        "@component",
        "@slot",
        "@props",
        "@error",
        "@once",
        "@push",
        "@stack",
        "@verbatim",
        "@can",
        "@cannot",
        "@auth",
        "@guest",
    ];

    const closingDirectives = [
        "@endif",
        "@endfor",
        "@endforeach",
        "@endforelse",
        "@endwhile",
        "@endsection",
        "@endswitch",
        "@endcase",
        "@endphp",
        "@endlivewire",
        "@endcomponent",
        "@endslot",
        "@enderror",
        "@endonce",
        "@endpush",
        "@endstack",
        "@endverbatim",
        "@endcan",
        "@endcannot",
        "@endauth",
        "@endguest",
    ];

    const trimmed = line.trim();

    const isOpening = openingDirectives.some((d) =>
        new RegExp(`^${d}(\\s|\\(|$)`).test(trimmed),
    );

    if (isOpening) return 1;

    const isClosing = closingDirectives.some((d) =>
        new RegExp(`^${d}(\\s|$)`).test(trimmed),
    );

    if (isClosing) return -1;

    return 0;
}
