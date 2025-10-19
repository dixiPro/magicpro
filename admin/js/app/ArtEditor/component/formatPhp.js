// utils/formatBlade.js

export async function formatPhp(code, textIdend = 2) {
    try {
        // форматируем
        const formatted = await prettier.format(code, {
            parser: "php",
            plugins: prettierPlugins,
            tabWidth: textIdend,
        });
        return formatted;
    } catch (e) {
        console.error("Ошибка при форматировании Php:", e);
        throw new Error("Ошибка при форматировании Php");
    }
}
