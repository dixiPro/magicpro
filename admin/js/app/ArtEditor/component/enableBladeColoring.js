// enableBladeColoring.js
import ace from 'ace-builds/src-noconflict/ace';

export function enableBladeColoring() {
  const HtmlHR = ace.require('ace/mode/html_highlight_rules').HtmlHighlightRules;
  if (HtmlHR.__bladePatched) return;

  const bladeTokens = [
    { token: 'comment', regex: /\{\{\-\-[\s\S]*?\-\-\}\}/ },
    { token: 'variable', regex: /\{\{[\s\S]*?\}\}/ }, // {{ ... }}
    { token: 'entity.name.function', regex: /@[A-Za-z_]\w*/ }, // @if, @foreach...
  ];

  const origGetRules = HtmlHR.prototype.getRules;
  HtmlHR.prototype.getRules = function () {
    const rules = origGetRules.call(this);
    for (const k in rules) rules[k].unshift(...bladeTokens);
    return rules;
  };

  HtmlHR.__bladePatched = true;
}
