// bladeLanguagePatch.js — подсветка Blade поверх HTML

// 1) Токенайзер
export async function patchBladeTokenizer(monaco) {
    const htmlLang = monaco.languages
        .getLanguages()
        .find((l) => l.id === "html");
    if (!htmlLang || typeof htmlLang.loader !== "function") return;

    const htmlMod = await htmlLang.loader();
    const t = htmlMod.language.tokenizer;

    // Новые Blade-правила
    t.root.unshift(
        [/\s*\{\{\-\-[\s\S]*?\-\-\}\}\s*/, "mprocomment"], // комментарий {{-- --}}
        [/\s*\{\{\!\![\s\S]*?\!\!\}\}\s*/, "keyword"], //{{!! !!}}
        [/\s*@[a-zA-Z_]\w*.*\)/, "keyword"],
        [/\s*@[a-zA-Z_]\w*/, "keyword"],
        [/\{\{ [\s\S]*?\}\}/, "keyword"]
    );

    monaco.languages.setMonarchTokensProvider("html", htmlMod.language);
}
// 2) Функция для получения цветовой схемы
export function getBladeTheme(base = "vs") {
    return {
        base,
        inherit: true,
        rules: [
            { token: "mproexpr", foreground: "0000ff", fontStyle: "bold" },
            { token: "mprocomment", foreground: "008000", fontStyle: "italic" },
            { token: "keyword", foreground: "6608f8", fontStyle: "bolder" },
        ],
        colors: {},
    };
}
