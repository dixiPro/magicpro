// ---------- ✅ функция регистрации кастомного режима Blade ----------
export function registerBladeMode() {
    if (ace.require("ace/mode/blade")) return;

    ace.define(
        "ace/mode/blade",
        [
            "require",
            "exports",
            "module",
            "ace/lib/oop",
            "ace/mode/html",
            "ace/mode/html_highlight_rules",
        ],
        function (require, exports) {
            const oop = require("ace/lib/oop");
            const HtmlMode = require("ace/mode/html").Mode;
            const HtmlHighlightRules =
                require("ace/mode/html_highlight_rules").HtmlHighlightRules;

            const BladeHighlightRules = function () {
                this.$rules = new HtmlHighlightRules().getRules();

                for (const key in this.$rules) {
                    this.$rules[key].unshift(
                        {
                            token: "variable.language.blade",
                            regex: "\\{\\{.*?\\}\\}",
                        },
                        {
                            token: "keyword.control.blade",
                            regex: "@[a-zA-Z_]\\w*",
                        }
                    );
                }
            };
            oop.inherits(BladeHighlightRules, HtmlHighlightRules);

            const Mode = function () {
                HtmlMode.call(this);
                this.HighlightRules = BladeHighlightRules;
                this.$id = "ace/mode/blade";

                // 👇 ключевой момент — сообщаем Ace, что этот режим “html-подобный”
                this.$highlightRules = new BladeHighlightRules();
                this.createModeDelegates({
                    "html-": HtmlMode,
                });
            };

            oop.inherits(Mode, HtmlMode);

            // 🔹 Emmet, подсветка и автодополнение будут видеть его как html
            Mode.prototype.$id = "ace/mode/html";
            Mode.prototype.getCompletions = HtmlMode.prototype.getCompletions;
            Mode.prototype.blockComment = { start: "{{--", end: "--}}" };
            Mode.prototype.lineCommentStart = null;

            exports.Mode = Mode;
        }
    );
}
