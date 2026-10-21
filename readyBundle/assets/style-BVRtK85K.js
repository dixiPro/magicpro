import{a as Et}from"./php-DWCa8Mut.js";import{r as Lt,x as me,y as fe,z as be,w as pt,B as ve,o as k,b as we,D as xe,l as z,W as U,k as P,p as E,m as q,F as _t,j as Wt,U as st,f as Zt,q as Gt,T as Qt,V as ye,g as It}from"./ToastConfirm-BNIfcHfI.js";var ht={exports:{}},Bt;function $e(){return Bt||(Bt=1,(function(t,n){ace.define("ace/mode/css_highlight_rules",["require","exports","module","ace/lib/oop","ace/lib/lang","ace/mode/text_highlight_rules"],function(e,o,s){var i=e("../lib/oop");e("../lib/lang");var a=e("./text_highlight_rules").TextHighlightRules,l=o.supportType="align-content|align-items|align-self|all|animation|animation-delay|animation-direction|animation-duration|animation-fill-mode|animation-iteration-count|animation-name|animation-play-state|animation-timing-function|backface-visibility|background|background-attachment|background-blend-mode|background-clip|background-color|background-image|background-origin|background-position|background-repeat|background-size|border|border-bottom|border-bottom-color|border-bottom-left-radius|border-bottom-right-radius|border-bottom-style|border-bottom-width|border-collapse|border-color|border-image|border-image-outset|border-image-repeat|border-image-slice|border-image-source|border-image-width|border-left|border-left-color|border-left-style|border-left-width|border-radius|border-right|border-right-color|border-right-style|border-right-width|border-spacing|border-style|border-top|border-top-color|border-top-left-radius|border-top-right-radius|border-top-style|border-top-width|border-width|bottom|box-shadow|box-sizing|caption-side|clear|clip|color|column-count|column-fill|column-gap|column-rule|column-rule-color|column-rule-style|column-rule-width|column-span|column-width|columns|content|counter-increment|counter-reset|cursor|direction|display|empty-cells|filter|flex|flex-basis|flex-direction|flex-flow|flex-grow|flex-shrink|flex-wrap|float|font|font-family|font-size|font-size-adjust|font-stretch|font-style|font-variant|font-weight|hanging-punctuation|height|justify-content|left|letter-spacing|line-height|list-style|list-style-image|list-style-position|list-style-type|margin|margin-bottom|margin-left|margin-right|margin-top|max-height|max-width|max-zoom|min-height|min-width|min-zoom|nav-down|nav-index|nav-left|nav-right|nav-up|opacity|order|outline|outline-color|outline-offset|outline-style|outline-width|overflow|overflow-x|overflow-y|padding|padding-bottom|padding-left|padding-right|padding-top|page-break-after|page-break-before|page-break-inside|perspective|perspective-origin|position|quotes|resize|right|tab-size|table-layout|text-align|text-align-last|text-decoration|text-decoration-color|text-decoration-line|text-decoration-style|text-indent|text-justify|text-overflow|text-shadow|text-transform|top|transform|transform-origin|transform-style|transition|transition-delay|transition-duration|transition-property|transition-timing-function|unicode-bidi|user-select|user-zoom|vertical-align|visibility|white-space|width|word-break|word-spacing|word-wrap|z-index",r=o.supportFunction="rgb|rgba|url|attr|counter|counters",c=o.supportConstant="absolute|after-edge|after|all-scroll|all|alphabetic|always|antialiased|armenian|auto|avoid-column|avoid-page|avoid|balance|baseline|before-edge|before|below|bidi-override|block-line-height|block|bold|bolder|border-box|both|bottom|box|break-all|break-word|capitalize|caps-height|caption|center|central|char|circle|cjk-ideographic|clone|close-quote|col-resize|collapse|column|consider-shifts|contain|content-box|cover|crosshair|cubic-bezier|dashed|decimal-leading-zero|decimal|default|disabled|disc|disregard-shifts|distribute-all-lines|distribute-letter|distribute-space|distribute|dotted|double|e-resize|ease-in|ease-in-out|ease-out|ease|ellipsis|end|exclude-ruby|flex-end|flex-start|fill|fixed|georgian|glyphs|grid-height|groove|hand|hanging|hebrew|help|hidden|hiragana-iroha|hiragana|horizontal|icon|ideograph-alpha|ideograph-numeric|ideograph-parenthesis|ideograph-space|ideographic|inactive|include-ruby|inherit|initial|inline-block|inline-box|inline-line-height|inline-table|inline|inset|inside|inter-ideograph|inter-word|invert|italic|justify|katakana-iroha|katakana|keep-all|last|left|lighter|line-edge|line-through|line|linear|list-item|local|loose|lower-alpha|lower-greek|lower-latin|lower-roman|lowercase|lr-tb|ltr|mathematical|max-height|max-size|medium|menu|message-box|middle|move|n-resize|ne-resize|newspaper|no-change|no-close-quote|no-drop|no-open-quote|no-repeat|none|normal|not-allowed|nowrap|nw-resize|oblique|open-quote|outset|outside|overline|padding-box|page|pointer|pre-line|pre-wrap|pre|preserve-3d|progress|relative|repeat-x|repeat-y|repeat|replaced|reset-size|ridge|right|round|row-resize|rtl|s-resize|scroll|se-resize|separate|slice|small-caps|small-caption|solid|space|square|start|static|status-bar|step-end|step-start|steps|stretch|strict|sub|super|sw-resize|table-caption|table-cell|table-column-group|table-column|table-footer-group|table-header-group|table-row-group|table-row|table|tb-rl|text-after-edge|text-before-edge|text-bottom|text-size|text-top|text|thick|thin|transparent|underline|upper-alpha|upper-latin|upper-roman|uppercase|use-script|vertical-ideographic|vertical-text|visible|w-resize|wait|whitespace|z-index|zero|zoom",h=o.supportConstantColor="aliceblue|antiquewhite|aqua|aquamarine|azure|beige|bisque|black|blanchedalmond|blue|blueviolet|brown|burlywood|cadetblue|chartreuse|chocolate|coral|cornflowerblue|cornsilk|crimson|cyan|darkblue|darkcyan|darkgoldenrod|darkgray|darkgreen|darkgrey|darkkhaki|darkmagenta|darkolivegreen|darkorange|darkorchid|darkred|darksalmon|darkseagreen|darkslateblue|darkslategray|darkslategrey|darkturquoise|darkviolet|deeppink|deepskyblue|dimgray|dimgrey|dodgerblue|firebrick|floralwhite|forestgreen|fuchsia|gainsboro|ghostwhite|gold|goldenrod|gray|green|greenyellow|grey|honeydew|hotpink|indianred|indigo|ivory|khaki|lavender|lavenderblush|lawngreen|lemonchiffon|lightblue|lightcoral|lightcyan|lightgoldenrodyellow|lightgray|lightgreen|lightgrey|lightpink|lightsalmon|lightseagreen|lightskyblue|lightslategray|lightslategrey|lightsteelblue|lightyellow|lime|limegreen|linen|magenta|maroon|mediumaquamarine|mediumblue|mediumorchid|mediumpurple|mediumseagreen|mediumslateblue|mediumspringgreen|mediumturquoise|mediumvioletred|midnightblue|mintcream|mistyrose|moccasin|navajowhite|navy|oldlace|olive|olivedrab|orange|orangered|orchid|palegoldenrod|palegreen|paleturquoise|palevioletred|papayawhip|peachpuff|peru|pink|plum|powderblue|purple|rebeccapurple|red|rosybrown|royalblue|saddlebrown|salmon|sandybrown|seagreen|seashell|sienna|silver|skyblue|slateblue|slategray|slategrey|snow|springgreen|steelblue|tan|teal|thistle|tomato|turquoise|violet|wheat|white|whitesmoke|yellow|yellowgreen",p=o.supportConstantFonts="arial|century|comic|courier|cursive|fantasy|garamond|georgia|helvetica|impact|lucida|symbol|system|tahoma|times|trebuchet|utopia|verdana|webdings|sans-serif|serif|monospace",u=o.numRe="\\-?(?:(?:[0-9]+(?:\\.[0-9]+)?)|(?:\\.[0-9]+))",d=o.pseudoElements="(\\:+)\\b(after|before|first-letter|first-line|moz-selection|selection)\\b",g=o.pseudoClasses="(:)\\b(active|checked|disabled|empty|enabled|first-child|first-of-type|focus|hover|indeterminate|invalid|last-child|last-of-type|link|not|nth-child|nth-last-child|nth-last-of-type|nth-of-type|only-child|only-of-type|required|root|target|valid|visited)\\b",m=function(){var f=this.createKeywordMapper({"support.function":r,"support.constant":c,"support.type":l,"support.constant.color":h,"support.constant.fonts":p},"text",!0);this.$rules={start:[{include:["strings","url","comments"]},{token:"paren.lparen",regex:"\\{",next:"ruleset"},{token:"paren.rparen",regex:"\\}"},{token:"string",regex:"@(?!viewport)",next:"media"},{token:"keyword",regex:"#[a-z0-9-_]+"},{token:"keyword",regex:"%"},{token:"variable",regex:"\\.[a-z0-9-_]+"},{token:"string",regex:":[a-z0-9-_]+"},{token:"constant.numeric",regex:u},{token:"constant",regex:"[a-z0-9-_]+"},{caseInsensitive:!0}],media:[{include:["strings","url","comments"]},{token:"paren.lparen",regex:"\\{",next:"start"},{token:"paren.rparen",regex:"\\}",next:"start"},{token:"string",regex:";",next:"start"},{token:"keyword",regex:"(?:media|supports|document|charset|import|namespace|media|supports|document|page|font|keyframes|viewport|counter-style|font-feature-values|swash|ornaments|annotation|stylistic|styleset|character-variant)"}],comments:[{token:"comment",regex:"\\/\\*",push:[{token:"comment",regex:"\\*\\/",next:"pop"},{defaultToken:"comment"}]}],ruleset:[{regex:"-(webkit|ms|moz|o)-",token:"text"},{token:"punctuation.operator",regex:"[:;]"},{token:"paren.rparen",regex:"\\}",next:"start"},{include:["strings","url","comments"]},{token:["constant.numeric","keyword"],regex:"("+u+")(ch|cm|deg|em|ex|fr|gd|grad|Hz|in|kHz|mm|ms|pc|pt|px|rad|rem|s|turn|vh|vmax|vmin|vm|vw|%)"},{token:"constant.numeric",regex:u},{token:"constant.numeric",regex:"#[a-f0-9]{6}"},{token:"constant.numeric",regex:"#[a-f0-9]{3}"},{token:["punctuation","entity.other.attribute-name.pseudo-element.css"],regex:d},{token:["punctuation","entity.other.attribute-name.pseudo-class.css"],regex:g},{include:"url"},{token:f,regex:"\\-?[a-zA-Z_][a-zA-Z0-9_\\-]*"},{token:"paren.lparen",regex:"\\{"},{caseInsensitive:!0}],url:[{token:"support.function",regex:"(?:url(:?-prefix)?|domain|regexp)\\(",push:[{token:"support.function",regex:"\\)",next:"pop"},{defaultToken:"string"}]}],strings:[{token:"string.start",regex:"'",push:[{token:"string.end",regex:"'|$",next:"pop"},{include:"escapes"},{token:"constant.language.escape",regex:/\\$/,consumeLineEnd:!0},{defaultToken:"string"}]},{token:"string.start",regex:'"',push:[{token:"string.end",regex:'"|$',next:"pop"},{include:"escapes"},{token:"constant.language.escape",regex:/\\$/,consumeLineEnd:!0},{defaultToken:"string"}]}],escapes:[{token:"constant.language.escape",regex:/\\([a-fA-F\d]{1,6}|[^a-fA-F\d])/}]},this.normalizeRules()};i.inherits(m,a),o.CssHighlightRules=m}),ace.define("ace/mode/matching_brace_outdent",["require","exports","module","ace/range"],function(e,o,s){var i=e("../range").Range,a=function(){};(function(){this.checkOutdent=function(l,r){return/^\s+$/.test(l)?/^\s*\}/.test(r):!1},this.autoOutdent=function(l,r){var c=l.getLine(r),h=c.match(/^(\s*\})/);if(!h)return 0;var p=h[1].length,u=l.findMatchingBracket({row:r,column:p});if(!u||u.row==r)return 0;var d=this.$getIndent(l.getLine(u.row));l.replace(new i(r,0,r,p-1),d)},this.$getIndent=function(l){return l.match(/^\s*/)[0]}}).call(a.prototype),o.MatchingBraceOutdent=a}),ace.define("ace/mode/css_completions",["require","exports","module"],function(e,o,s){var i={background:{"#$0":1},"background-color":{"#$0":1,transparent:1,fixed:1},"background-image":{"url('/$0')":1},"background-repeat":{repeat:1,"repeat-x":1,"repeat-y":1,"no-repeat":1,inherit:1},"background-position":{bottom:2,center:2,left:2,right:2,top:2,inherit:2},"background-attachment":{scroll:1,fixed:1},"background-size":{cover:1,contain:1},"background-clip":{"border-box":1,"padding-box":1,"content-box":1},"background-origin":{"border-box":1,"padding-box":1,"content-box":1},border:{"solid $0":1,"dashed $0":1,"dotted $0":1,"#$0":1},"border-color":{"#$0":1},"border-style":{solid:2,dashed:2,dotted:2,double:2,groove:2,hidden:2,inherit:2,inset:2,none:2,outset:2,ridged:2},"border-collapse":{collapse:1,separate:1},bottom:{px:1,em:1,"%":1},clear:{left:1,right:1,both:1,none:1},color:{"#$0":1,"rgb(#$00,0,0)":1},cursor:{default:1,pointer:1,move:1,text:1,wait:1,help:1,progress:1,"n-resize":1,"ne-resize":1,"e-resize":1,"se-resize":1,"s-resize":1,"sw-resize":1,"w-resize":1,"nw-resize":1},display:{none:1,block:1,inline:1,"inline-block":1,"table-cell":1},"empty-cells":{show:1,hide:1},float:{left:1,right:1,none:1},"font-family":{Arial:2,"Comic Sans MS":2,Consolas:2,"Courier New":2,Courier:2,Georgia:2,Monospace:2,"Sans-Serif":2,"Segoe UI":2,Tahoma:2,"Times New Roman":2,"Trebuchet MS":2,Verdana:1},"font-size":{px:1,em:1,"%":1},"font-weight":{bold:1,normal:1},"font-style":{italic:1,normal:1},"font-variant":{normal:1,"small-caps":1},height:{px:1,em:1,"%":1},left:{px:1,em:1,"%":1},"letter-spacing":{normal:1},"line-height":{normal:1},"list-style-type":{none:1,disc:1,circle:1,square:1,decimal:1,"decimal-leading-zero":1,"lower-roman":1,"upper-roman":1,"lower-greek":1,"lower-latin":1,"upper-latin":1,georgian:1,"lower-alpha":1,"upper-alpha":1},margin:{px:1,em:1,"%":1},"margin-right":{px:1,em:1,"%":1},"margin-left":{px:1,em:1,"%":1},"margin-top":{px:1,em:1,"%":1},"margin-bottom":{px:1,em:1,"%":1},"max-height":{px:1,em:1,"%":1},"max-width":{px:1,em:1,"%":1},"min-height":{px:1,em:1,"%":1},"min-width":{px:1,em:1,"%":1},overflow:{hidden:1,visible:1,auto:1,scroll:1},"overflow-x":{hidden:1,visible:1,auto:1,scroll:1},"overflow-y":{hidden:1,visible:1,auto:1,scroll:1},padding:{px:1,em:1,"%":1},"padding-top":{px:1,em:1,"%":1},"padding-right":{px:1,em:1,"%":1},"padding-bottom":{px:1,em:1,"%":1},"padding-left":{px:1,em:1,"%":1},"page-break-after":{auto:1,always:1,avoid:1,left:1,right:1},"page-break-before":{auto:1,always:1,avoid:1,left:1,right:1},position:{absolute:1,relative:1,fixed:1,static:1},right:{px:1,em:1,"%":1},"table-layout":{fixed:1,auto:1},"text-decoration":{none:1,underline:1,"line-through":1,blink:1},"text-align":{left:1,right:1,center:1,justify:1},"text-transform":{capitalize:1,uppercase:1,lowercase:1,none:1},top:{px:1,em:1,"%":1},"vertical-align":{top:1,bottom:1},visibility:{hidden:1,visible:1},"white-space":{nowrap:1,normal:1,pre:1,"pre-line":1,"pre-wrap":1},width:{px:1,em:1,"%":1},"word-spacing":{normal:1},filter:{"alpha(opacity=$0100)":1},"text-shadow":{"$02px 2px 2px #777":1},"text-overflow":{"ellipsis-word":1,clip:1,ellipsis:1},"-moz-border-radius":1,"-moz-border-radius-topright":1,"-moz-border-radius-bottomright":1,"-moz-border-radius-topleft":1,"-moz-border-radius-bottomleft":1,"-webkit-border-radius":1,"-webkit-border-top-right-radius":1,"-webkit-border-top-left-radius":1,"-webkit-border-bottom-right-radius":1,"-webkit-border-bottom-left-radius":1,"-moz-box-shadow":1,"-webkit-box-shadow":1,transform:{"rotate($00deg)":1,"skew($00deg)":1},"-moz-transform":{"rotate($00deg)":1,"skew($00deg)":1},"-webkit-transform":{"rotate($00deg)":1,"skew($00deg)":1}},a=function(){};(function(){this.completionsDefined=!1,this.defineCompletions=function(){if(document){var l=document.createElement("c").style;for(var r in l)if(typeof l[r]=="string"){var c=r.replace(/[A-Z]/g,function(h){return"-"+h.toLowerCase()});i.hasOwnProperty(c)||(i[c]=1)}}this.completionsDefined=!0},this.getCompletions=function(l,r,c,h){if(this.completionsDefined||this.defineCompletions(),l==="ruleset"||r.$mode.$id=="ace/mode/scss"){var p=r.getLine(c.row).substr(0,c.column),u=/\([^)]*$/.test(p);return u&&(p=p.substr(p.lastIndexOf("(")+1)),/:[^;]+$/.test(p)?this.getPropertyValueCompletions(l,r,c,h):this.getPropertyCompletions(l,r,c,h,u)}return[]},this.getPropertyCompletions=function(l,r,c,h,p){p=p||!1;var u=Object.keys(i);return u.map(function(d){return{caption:d,snippet:d+": $0"+(p?"":";"),meta:"property",score:1e6}})},this.getPropertyValueCompletions=function(l,r,c,h){var p=r.getLine(c.row).substr(0,c.column),u=(/([\w\-]+):[^:]*$/.exec(p)||{})[1];if(!u)return[];var d=[];return u in i&&typeof i[u]=="object"&&(d=Object.keys(i[u])),d.map(function(g){return{caption:g,snippet:g,meta:"property value",score:1e6}})}}).call(a.prototype),o.CssCompletions=a}),ace.define("ace/mode/behaviour/css",["require","exports","module","ace/lib/oop","ace/mode/behaviour","ace/mode/behaviour/cstyle","ace/token_iterator"],function(e,o,s){var i=e("../../lib/oop");e("../behaviour").Behaviour;var a=e("./cstyle").CstyleBehaviour,l=e("../../token_iterator").TokenIterator,r=function(){this.inherit(a),this.add("colon","insertion",function(c,h,p,u,d){if(d===":"&&p.selection.isEmpty()){var g=p.getCursorPosition(),m=new l(u,g.row,g.column),f=m.getCurrentToken();if(f&&f.value.match(/\s+/)&&(f=m.stepBackward()),f&&f.type==="support.type"){var b=u.doc.getLine(g.row),v=b.substring(g.column,g.column+1);if(v===":")return{text:"",selection:[1,1]};if(/^(\s+[^;]|\s*$)/.test(b.substring(g.column)))return{text:":;",selection:[1,1]}}}}),this.add("colon","deletion",function(c,h,p,u,d){var g=u.doc.getTextRange(d);if(!d.isMultiLine()&&g===":"){var m=p.getCursorPosition(),f=new l(u,m.row,m.column),b=f.getCurrentToken();if(b&&b.value.match(/\s+/)&&(b=f.stepBackward()),b&&b.type==="support.type"){var v=u.doc.getLine(d.start.row),$=v.substring(d.end.column,d.end.column+1);if($===";")return d.end.column++,d}}}),this.add("semicolon","insertion",function(c,h,p,u,d){if(d===";"&&p.selection.isEmpty()){var g=p.getCursorPosition(),m=u.doc.getLine(g.row),f=m.substring(g.column,g.column+1);if(f===";")return{text:"",selection:[1,1]}}}),this.add("!important","insertion",function(c,h,p,u,d){if(d==="!"&&p.selection.isEmpty()){var g=p.getCursorPosition(),m=u.doc.getLine(g.row);if(/^\s*(;|}|$)/.test(m.substring(g.column)))return{text:"!important",selection:[10,10]}}})};i.inherits(r,a),o.CssBehaviour=r}),ace.define("ace/mode/folding/cstyle",["require","exports","module","ace/lib/oop","ace/range","ace/mode/folding/fold_mode"],function(e,o,s){var i=e("../../lib/oop"),a=e("../../range").Range,l=e("./fold_mode").FoldMode,r=o.FoldMode=function(c){c&&(this.foldingStartMarker=new RegExp(this.foldingStartMarker.source.replace(/\|[^|]*?$/,"|"+c.start)),this.foldingStopMarker=new RegExp(this.foldingStopMarker.source.replace(/\|[^|]*?$/,"|"+c.end)))};i.inherits(r,l),(function(){this.foldingStartMarker=/([\{\[\(])[^\}\]\)]*$|^\s*(\/\*)/,this.foldingStopMarker=/^[^\[\{\(]*([\}\]\)])|^[\s\*]*(\*\/)/,this.singleLineBlockCommentRe=/^\s*(\/\*).*\*\/\s*$/,this.tripleStarBlockCommentRe=/^\s*(\/\*\*\*).*\*\/\s*$/,this.startRegionRe=/^\s*(\/\*|\/\/)#?region\b/,this._getFoldWidgetBase=this.getFoldWidget,this.getFoldWidget=function(c,h,p){var u=c.getLine(p);if(this.singleLineBlockCommentRe.test(u)&&!this.startRegionRe.test(u)&&!this.tripleStarBlockCommentRe.test(u))return"";var d=this._getFoldWidgetBase(c,h,p);return!d&&this.startRegionRe.test(u)?"start":d},this.getFoldWidgetRange=function(c,h,p,u){var d=c.getLine(p);if(this.startRegionRe.test(d))return this.getCommentRegionBlock(c,d,p);var f=d.match(this.foldingStartMarker);if(f){var g=f.index;if(f[1])return this.openingBracketBlock(c,f[1],p,g);var m=c.getCommentFoldRange(p,g+f[0].length,1);return m&&!m.isMultiLine()&&(u?m=this.getSectionRange(c,p):h!="all"&&(m=null)),m}if(h!=="markbegin"){var f=d.match(this.foldingStopMarker);if(f){var g=f.index+f[0].length;return f[1]?this.closingBracketBlock(c,f[1],p,g):c.getCommentFoldRange(p,g,-1)}}},this.getSectionRange=function(c,h){var p=c.getLine(h),u=p.search(/\S/),d=h,g=p.length;h=h+1;for(var m=h,f=c.getLength();++h<f;){p=c.getLine(h);var b=p.search(/\S/);if(b!==-1){if(u>b)break;var v=this.getFoldWidgetRange(c,"all",h);if(v){if(v.start.row<=d)break;if(v.isMultiLine())h=v.end.row;else if(u==b)break}m=h}}return new a(d,g,m,c.getLine(m).length)},this.getCommentRegionBlock=function(c,h,p){for(var u=h.search(/\s*$/),d=c.getLength(),g=p,m=/^\s*(?:\/\*|\/\/|--)#?(end)?region\b/,f=1;++p<d;){h=c.getLine(p);var b=m.exec(h);if(b&&(b[1]?f--:f++,!f))break}var v=p;if(v>g)return new a(g,u,v,h.length)}}).call(r.prototype)}),ace.define("ace/mode/css",["require","exports","module","ace/lib/oop","ace/mode/text","ace/mode/css_highlight_rules","ace/mode/matching_brace_outdent","ace/worker/worker_client","ace/mode/css_completions","ace/mode/behaviour/css","ace/mode/folding/cstyle"],function(e,o,s){var i=e("../lib/oop"),a=e("./text").Mode,l=e("./css_highlight_rules").CssHighlightRules,r=e("./matching_brace_outdent").MatchingBraceOutdent,c=e("../worker/worker_client").WorkerClient,h=e("./css_completions").CssCompletions,p=e("./behaviour/css").CssBehaviour,u=e("./folding/cstyle").FoldMode,d=function(){this.HighlightRules=l,this.$outdent=new r,this.$behaviour=new p,this.$completer=new h,this.foldingRules=new u};i.inherits(d,a),(function(){this.foldingRules="cStyle",this.blockComment={start:"/*",end:"*/"},this.getNextLineIndent=function(g,m,f){var b=this.$getIndent(m),v=this.getTokenizer().getLineTokens(m,g).tokens;if(v.length&&v[v.length-1].type=="comment")return b;var $=m.match(/^.*\{\s*$/);return $&&(b+=f),b},this.checkOutdent=function(g,m,f){return this.$outdent.checkOutdent(m,f)},this.autoOutdent=function(g,m,f){this.$outdent.autoOutdent(m,f)},this.getCompletions=function(g,m,f,b){return this.$completer.getCompletions(g,m,f,b)},this.createWorker=function(g){var m=new c(["ace"],"ace/mode/css_worker","Worker");return m.attachToDocument(g.getDocument()),m.on("annotate",function(f){g.setAnnotations(f.data)}),m.on("terminate",function(){g.clearAnnotations()}),m},this.$id="ace/mode/css",this.snippetFileId="ace/snippets/css"}).call(d.prototype),o.Mode=d}),(function(){ace.require(["ace/mode/css"],function(e){t&&(t.exports=e)})})()})(ht)),ht.exports}$e();var ut={exports:{}},Ot;function ke(){return Ot||(Ot=1,(function(t,n){ace.define("ace/snippets/css.snippets",["require","exports","module"],function(e,o,s){s.exports=`snippet .
	\${1} {
		\${2}
	}
snippet !
	 !important
snippet bdi:m+
	-moz-border-image: url(\${1}) \${2:0} \${3:0} \${4:0} \${5:0} \${6:stretch} \${7:stretch};
snippet bdi:m
	-moz-border-image: \${1};
snippet bdrz:m
	-moz-border-radius: \${1};
snippet bxsh:m+
	-moz-box-shadow: \${1:0} \${2:0} \${3:0} #\${4:000};
snippet bxsh:m
	-moz-box-shadow: \${1};
snippet bdi:w+
	-webkit-border-image: url(\${1}) \${2:0} \${3:0} \${4:0} \${5:0} \${6:stretch} \${7:stretch};
snippet bdi:w
	-webkit-border-image: \${1};
snippet bdrz:w
	-webkit-border-radius: \${1};
snippet bxsh:w+
	-webkit-box-shadow: \${1:0} \${2:0} \${3:0} #\${4:000};
snippet bxsh:w
	-webkit-box-shadow: \${1};
snippet @f
	@font-face {
		font-family: \${1};
		src: url(\${2});
	}
snippet @i
	@import url(\${1});
snippet @m
	@media \${1:print} {
		\${2}
	}
snippet bg+
	background: #\${1:FFF} url(\${2}) \${3:0} \${4:0} \${5:no-repeat};
snippet bga
	background-attachment: \${1};
snippet bga:f
	background-attachment: fixed;
snippet bga:s
	background-attachment: scroll;
snippet bgbk
	background-break: \${1};
snippet bgbk:bb
	background-break: bounding-box;
snippet bgbk:c
	background-break: continuous;
snippet bgbk:eb
	background-break: each-box;
snippet bgcp
	background-clip: \${1};
snippet bgcp:bb
	background-clip: border-box;
snippet bgcp:cb
	background-clip: content-box;
snippet bgcp:nc
	background-clip: no-clip;
snippet bgcp:pb
	background-clip: padding-box;
snippet bgc
	background-color: #\${1:FFF};
snippet bgc:t
	background-color: transparent;
snippet bgi
	background-image: url(\${1});
snippet bgi:n
	background-image: none;
snippet bgo
	background-origin: \${1};
snippet bgo:bb
	background-origin: border-box;
snippet bgo:cb
	background-origin: content-box;
snippet bgo:pb
	background-origin: padding-box;
snippet bgpx
	background-position-x: \${1};
snippet bgpy
	background-position-y: \${1};
snippet bgp
	background-position: \${1:0} \${2:0};
snippet bgr
	background-repeat: \${1};
snippet bgr:n
	background-repeat: no-repeat;
snippet bgr:x
	background-repeat: repeat-x;
snippet bgr:y
	background-repeat: repeat-y;
snippet bgr:r
	background-repeat: repeat;
snippet bgz
	background-size: \${1};
snippet bgz:a
	background-size: auto;
snippet bgz:ct
	background-size: contain;
snippet bgz:cv
	background-size: cover;
snippet bg
	background: \${1};
snippet bg:ie
	filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='\${1}',sizingMethod='\${2:crop}');
snippet bg:n
	background: none;
snippet bd+
	border: \${1:1px} \${2:solid} #\${3:000};
snippet bdb+
	border-bottom: \${1:1px} \${2:solid} #\${3:000};
snippet bdbc
	border-bottom-color: #\${1:000};
snippet bdbi
	border-bottom-image: url(\${1});
snippet bdbi:n
	border-bottom-image: none;
snippet bdbli
	border-bottom-left-image: url(\${1});
snippet bdbli:c
	border-bottom-left-image: continue;
snippet bdbli:n
	border-bottom-left-image: none;
snippet bdblrz
	border-bottom-left-radius: \${1};
snippet bdbri
	border-bottom-right-image: url(\${1});
snippet bdbri:c
	border-bottom-right-image: continue;
snippet bdbri:n
	border-bottom-right-image: none;
snippet bdbrrz
	border-bottom-right-radius: \${1};
snippet bdbs
	border-bottom-style: \${1};
snippet bdbs:n
	border-bottom-style: none;
snippet bdbw
	border-bottom-width: \${1};
snippet bdb
	border-bottom: \${1};
snippet bdb:n
	border-bottom: none;
snippet bdbk
	border-break: \${1};
snippet bdbk:c
	border-break: close;
snippet bdcl
	border-collapse: \${1};
snippet bdcl:c
	border-collapse: collapse;
snippet bdcl:s
	border-collapse: separate;
snippet bdc
	border-color: #\${1:000};
snippet bdci
	border-corner-image: url(\${1});
snippet bdci:c
	border-corner-image: continue;
snippet bdci:n
	border-corner-image: none;
snippet bdf
	border-fit: \${1};
snippet bdf:c
	border-fit: clip;
snippet bdf:of
	border-fit: overwrite;
snippet bdf:ow
	border-fit: overwrite;
snippet bdf:r
	border-fit: repeat;
snippet bdf:sc
	border-fit: scale;
snippet bdf:sp
	border-fit: space;
snippet bdf:st
	border-fit: stretch;
snippet bdi
	border-image: url(\${1}) \${2:0} \${3:0} \${4:0} \${5:0} \${6:stretch} \${7:stretch};
snippet bdi:n
	border-image: none;
snippet bdl+
	border-left: \${1:1px} \${2:solid} #\${3:000};
snippet bdlc
	border-left-color: #\${1:000};
snippet bdli
	border-left-image: url(\${1});
snippet bdli:n
	border-left-image: none;
snippet bdls
	border-left-style: \${1};
snippet bdls:n
	border-left-style: none;
snippet bdlw
	border-left-width: \${1};
snippet bdl
	border-left: \${1};
snippet bdl:n
	border-left: none;
snippet bdlt
	border-length: \${1};
snippet bdlt:a
	border-length: auto;
snippet bdrz
	border-radius: \${1};
snippet bdr+
	border-right: \${1:1px} \${2:solid} #\${3:000};
snippet bdrc
	border-right-color: #\${1:000};
snippet bdri
	border-right-image: url(\${1});
snippet bdri:n
	border-right-image: none;
snippet bdrs
	border-right-style: \${1};
snippet bdrs:n
	border-right-style: none;
snippet bdrw
	border-right-width: \${1};
snippet bdr
	border-right: \${1};
snippet bdr:n
	border-right: none;
snippet bdsp
	border-spacing: \${1};
snippet bds
	border-style: \${1};
snippet bds:ds
	border-style: dashed;
snippet bds:dtds
	border-style: dot-dash;
snippet bds:dtdtds
	border-style: dot-dot-dash;
snippet bds:dt
	border-style: dotted;
snippet bds:db
	border-style: double;
snippet bds:g
	border-style: groove;
snippet bds:h
	border-style: hidden;
snippet bds:i
	border-style: inset;
snippet bds:n
	border-style: none;
snippet bds:o
	border-style: outset;
snippet bds:r
	border-style: ridge;
snippet bds:s
	border-style: solid;
snippet bds:w
	border-style: wave;
snippet bdt+
	border-top: \${1:1px} \${2:solid} #\${3:000};
snippet bdtc
	border-top-color: #\${1:000};
snippet bdti
	border-top-image: url(\${1});
snippet bdti:n
	border-top-image: none;
snippet bdtli
	border-top-left-image: url(\${1});
snippet bdtli:c
	border-corner-image: continue;
snippet bdtli:n
	border-corner-image: none;
snippet bdtlrz
	border-top-left-radius: \${1};
snippet bdtri
	border-top-right-image: url(\${1});
snippet bdtri:c
	border-top-right-image: continue;
snippet bdtri:n
	border-top-right-image: none;
snippet bdtrrz
	border-top-right-radius: \${1};
snippet bdts
	border-top-style: \${1};
snippet bdts:n
	border-top-style: none;
snippet bdtw
	border-top-width: \${1};
snippet bdt
	border-top: \${1};
snippet bdt:n
	border-top: none;
snippet bdw
	border-width: \${1};
snippet bd
	border: \${1};
snippet bd:n
	border: none;
snippet b
	bottom: \${1};
snippet b:a
	bottom: auto;
snippet bxsh+
	box-shadow: \${1:0} \${2:0} \${3:0} #\${4:000};
snippet bxsh
	box-shadow: \${1};
snippet bxsh:n
	box-shadow: none;
snippet bxz
	box-sizing: \${1};
snippet bxz:bb
	box-sizing: border-box;
snippet bxz:cb
	box-sizing: content-box;
snippet cps
	caption-side: \${1};
snippet cps:b
	caption-side: bottom;
snippet cps:t
	caption-side: top;
snippet cl
	clear: \${1};
snippet cl:b
	clear: both;
snippet cl:l
	clear: left;
snippet cl:n
	clear: none;
snippet cl:r
	clear: right;
snippet cp
	clip: \${1};
snippet cp:a
	clip: auto;
snippet cp:r
	clip: rect(\${1:0} \${2:0} \${3:0} \${4:0});
snippet c
	color: #\${1:000};
snippet ct
	content: \${1};
snippet ct:a
	content: attr(\${1});
snippet ct:cq
	content: close-quote;
snippet ct:c
	content: counter(\${1});
snippet ct:cs
	content: counters(\${1});
snippet ct:ncq
	content: no-close-quote;
snippet ct:noq
	content: no-open-quote;
snippet ct:n
	content: normal;
snippet ct:oq
	content: open-quote;
snippet coi
	counter-increment: \${1};
snippet cor
	counter-reset: \${1};
snippet cur
	cursor: \${1};
snippet cur:a
	cursor: auto;
snippet cur:c
	cursor: crosshair;
snippet cur:d
	cursor: default;
snippet cur:ha
	cursor: hand;
snippet cur:he
	cursor: help;
snippet cur:m
	cursor: move;
snippet cur:p
	cursor: pointer;
snippet cur:t
	cursor: text;
snippet d
	display: \${1};
snippet d:mib
	display: -moz-inline-box;
snippet d:mis
	display: -moz-inline-stack;
snippet d:b
	display: block;
snippet d:cp
	display: compact;
snippet d:ib
	display: inline-block;
snippet d:itb
	display: inline-table;
snippet d:i
	display: inline;
snippet d:li
	display: list-item;
snippet d:n
	display: none;
snippet d:ri
	display: run-in;
snippet d:tbcp
	display: table-caption;
snippet d:tbc
	display: table-cell;
snippet d:tbclg
	display: table-column-group;
snippet d:tbcl
	display: table-column;
snippet d:tbfg
	display: table-footer-group;
snippet d:tbhg
	display: table-header-group;
snippet d:tbrg
	display: table-row-group;
snippet d:tbr
	display: table-row;
snippet d:tb
	display: table;
snippet ec
	empty-cells: \${1};
snippet ec:h
	empty-cells: hide;
snippet ec:s
	empty-cells: show;
snippet exp
	expression()
snippet fl
	float: \${1};
snippet fl:l
	float: left;
snippet fl:n
	float: none;
snippet fl:r
	float: right;
snippet f+
	font: \${1:1em} \${2:Arial},\${3:sans-serif};
snippet fef
	font-effect: \${1};
snippet fef:eb
	font-effect: emboss;
snippet fef:eg
	font-effect: engrave;
snippet fef:n
	font-effect: none;
snippet fef:o
	font-effect: outline;
snippet femp
	font-emphasize-position: \${1};
snippet femp:a
	font-emphasize-position: after;
snippet femp:b
	font-emphasize-position: before;
snippet fems
	font-emphasize-style: \${1};
snippet fems:ac
	font-emphasize-style: accent;
snippet fems:c
	font-emphasize-style: circle;
snippet fems:ds
	font-emphasize-style: disc;
snippet fems:dt
	font-emphasize-style: dot;
snippet fems:n
	font-emphasize-style: none;
snippet fem
	font-emphasize: \${1};
snippet ff
	font-family: \${1};
snippet ff:c
	font-family: \${1:'Monotype Corsiva','Comic Sans MS'},cursive;
snippet ff:f
	font-family: \${1:Capitals,Impact},fantasy;
snippet ff:m
	font-family: \${1:Monaco,'Courier New'},monospace;
snippet ff:ss
	font-family: \${1:Helvetica,Arial},sans-serif;
snippet ff:s
	font-family: \${1:Georgia,'Times New Roman'},serif;
snippet fza
	font-size-adjust: \${1};
snippet fza:n
	font-size-adjust: none;
snippet fz
	font-size: \${1};
snippet fsm
	font-smooth: \${1};
snippet fsm:aw
	font-smooth: always;
snippet fsm:a
	font-smooth: auto;
snippet fsm:n
	font-smooth: never;
snippet fst
	font-stretch: \${1};
snippet fst:c
	font-stretch: condensed;
snippet fst:e
	font-stretch: expanded;
snippet fst:ec
	font-stretch: extra-condensed;
snippet fst:ee
	font-stretch: extra-expanded;
snippet fst:n
	font-stretch: normal;
snippet fst:sc
	font-stretch: semi-condensed;
snippet fst:se
	font-stretch: semi-expanded;
snippet fst:uc
	font-stretch: ultra-condensed;
snippet fst:ue
	font-stretch: ultra-expanded;
snippet fs
	font-style: \${1};
snippet fs:i
	font-style: italic;
snippet fs:n
	font-style: normal;
snippet fs:o
	font-style: oblique;
snippet fv
	font-variant: \${1};
snippet fv:n
	font-variant: normal;
snippet fv:sc
	font-variant: small-caps;
snippet fw
	font-weight: \${1};
snippet fw:b
	font-weight: bold;
snippet fw:br
	font-weight: bolder;
snippet fw:lr
	font-weight: lighter;
snippet fw:n
	font-weight: normal;
snippet f
	font: \${1};
snippet h
	height: \${1};
snippet h:a
	height: auto;
snippet l
	left: \${1};
snippet l:a
	left: auto;
snippet lts
	letter-spacing: \${1};
snippet lh
	line-height: \${1};
snippet lisi
	list-style-image: url(\${1});
snippet lisi:n
	list-style-image: none;
snippet lisp
	list-style-position: \${1};
snippet lisp:i
	list-style-position: inside;
snippet lisp:o
	list-style-position: outside;
snippet list
	list-style-type: \${1};
snippet list:c
	list-style-type: circle;
snippet list:dclz
	list-style-type: decimal-leading-zero;
snippet list:dc
	list-style-type: decimal;
snippet list:d
	list-style-type: disc;
snippet list:lr
	list-style-type: lower-roman;
snippet list:n
	list-style-type: none;
snippet list:s
	list-style-type: square;
snippet list:ur
	list-style-type: upper-roman;
snippet lis
	list-style: \${1};
snippet lis:n
	list-style: none;
snippet mb
	margin-bottom: \${1};
snippet mb:a
	margin-bottom: auto;
snippet ml
	margin-left: \${1};
snippet ml:a
	margin-left: auto;
snippet mr
	margin-right: \${1};
snippet mr:a
	margin-right: auto;
snippet mt
	margin-top: \${1};
snippet mt:a
	margin-top: auto;
snippet m
	margin: \${1};
snippet m:4
	margin: \${1:0} \${2:0} \${3:0} \${4:0};
snippet m:3
	margin: \${1:0} \${2:0} \${3:0};
snippet m:2
	margin: \${1:0} \${2:0};
snippet m:0
	margin: 0;
snippet m:a
	margin: auto;
snippet mah
	max-height: \${1};
snippet mah:n
	max-height: none;
snippet maw
	max-width: \${1};
snippet maw:n
	max-width: none;
snippet mih
	min-height: \${1};
snippet miw
	min-width: \${1};
snippet op
	opacity: \${1};
snippet op:ie
	filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=\${1:100});
snippet op:ms
	-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=\${1:100})';
snippet orp
	orphans: \${1};
snippet o+
	outline: \${1:1px} \${2:solid} #\${3:000};
snippet oc
	outline-color: \${1:#000};
snippet oc:i
	outline-color: invert;
snippet oo
	outline-offset: \${1};
snippet os
	outline-style: \${1};
snippet ow
	outline-width: \${1};
snippet o
	outline: \${1};
snippet o:n
	outline: none;
snippet ovs
	overflow-style: \${1};
snippet ovs:a
	overflow-style: auto;
snippet ovs:mq
	overflow-style: marquee;
snippet ovs:mv
	overflow-style: move;
snippet ovs:p
	overflow-style: panner;
snippet ovs:s
	overflow-style: scrollbar;
snippet ovx
	overflow-x: \${1};
snippet ovx:a
	overflow-x: auto;
snippet ovx:h
	overflow-x: hidden;
snippet ovx:s
	overflow-x: scroll;
snippet ovx:v
	overflow-x: visible;
snippet ovy
	overflow-y: \${1};
snippet ovy:a
	overflow-y: auto;
snippet ovy:h
	overflow-y: hidden;
snippet ovy:s
	overflow-y: scroll;
snippet ovy:v
	overflow-y: visible;
snippet ov
	overflow: \${1};
snippet ov:a
	overflow: auto;
snippet ov:h
	overflow: hidden;
snippet ov:s
	overflow: scroll;
snippet ov:v
	overflow: visible;
snippet pb
	padding-bottom: \${1};
snippet pl
	padding-left: \${1};
snippet pr
	padding-right: \${1};
snippet pt
	padding-top: \${1};
snippet p
	padding: \${1};
snippet p:4
	padding: \${1:0} \${2:0} \${3:0} \${4:0};
snippet p:3
	padding: \${1:0} \${2:0} \${3:0};
snippet p:2
	padding: \${1:0} \${2:0};
snippet p:0
	padding: 0;
snippet pgba
	page-break-after: \${1};
snippet pgba:aw
	page-break-after: always;
snippet pgba:a
	page-break-after: auto;
snippet pgba:l
	page-break-after: left;
snippet pgba:r
	page-break-after: right;
snippet pgbb
	page-break-before: \${1};
snippet pgbb:aw
	page-break-before: always;
snippet pgbb:a
	page-break-before: auto;
snippet pgbb:l
	page-break-before: left;
snippet pgbb:r
	page-break-before: right;
snippet pgbi
	page-break-inside: \${1};
snippet pgbi:a
	page-break-inside: auto;
snippet pgbi:av
	page-break-inside: avoid;
snippet pos
	position: \${1};
snippet pos:a
	position: absolute;
snippet pos:f
	position: fixed;
snippet pos:r
	position: relative;
snippet pos:s
	position: static;
snippet q
	quotes: \${1};
snippet q:en
	quotes: '\\201C' '\\201D' '\\2018' '\\2019';
snippet q:n
	quotes: none;
snippet q:ru
	quotes: '\\00AB' '\\00BB' '\\201E' '\\201C';
snippet rz
	resize: \${1};
snippet rz:b
	resize: both;
snippet rz:h
	resize: horizontal;
snippet rz:n
	resize: none;
snippet rz:v
	resize: vertical;
snippet r
	right: \${1};
snippet r:a
	right: auto;
snippet tbl
	table-layout: \${1};
snippet tbl:a
	table-layout: auto;
snippet tbl:f
	table-layout: fixed;
snippet tal
	text-align-last: \${1};
snippet tal:a
	text-align-last: auto;
snippet tal:c
	text-align-last: center;
snippet tal:l
	text-align-last: left;
snippet tal:r
	text-align-last: right;
snippet ta
	text-align: \${1};
snippet ta:c
	text-align: center;
snippet ta:l
	text-align: left;
snippet ta:r
	text-align: right;
snippet td
	text-decoration: \${1};
snippet td:l
	text-decoration: line-through;
snippet td:n
	text-decoration: none;
snippet td:o
	text-decoration: overline;
snippet td:u
	text-decoration: underline;
snippet te
	text-emphasis: \${1};
snippet te:ac
	text-emphasis: accent;
snippet te:a
	text-emphasis: after;
snippet te:b
	text-emphasis: before;
snippet te:c
	text-emphasis: circle;
snippet te:ds
	text-emphasis: disc;
snippet te:dt
	text-emphasis: dot;
snippet te:n
	text-emphasis: none;
snippet th
	text-height: \${1};
snippet th:a
	text-height: auto;
snippet th:f
	text-height: font-size;
snippet th:m
	text-height: max-size;
snippet th:t
	text-height: text-size;
snippet ti
	text-indent: \${1};
snippet ti:-
	text-indent: -9999px;
snippet tj
	text-justify: \${1};
snippet tj:a
	text-justify: auto;
snippet tj:d
	text-justify: distribute;
snippet tj:ic
	text-justify: inter-cluster;
snippet tj:ii
	text-justify: inter-ideograph;
snippet tj:iw
	text-justify: inter-word;
snippet tj:k
	text-justify: kashida;
snippet tj:t
	text-justify: tibetan;
snippet to+
	text-outline: \${1:0} \${2:0} #\${3:000};
snippet to
	text-outline: \${1};
snippet to:n
	text-outline: none;
snippet tr
	text-replace: \${1};
snippet tr:n
	text-replace: none;
snippet tsh+
	text-shadow: \${1:0} \${2:0} \${3:0} #\${4:000};
snippet tsh
	text-shadow: \${1};
snippet tsh:n
	text-shadow: none;
snippet tt
	text-transform: \${1};
snippet tt:c
	text-transform: capitalize;
snippet tt:l
	text-transform: lowercase;
snippet tt:n
	text-transform: none;
snippet tt:u
	text-transform: uppercase;
snippet tw
	text-wrap: \${1};
snippet tw:no
	text-wrap: none;
snippet tw:n
	text-wrap: normal;
snippet tw:s
	text-wrap: suppress;
snippet tw:u
	text-wrap: unrestricted;
snippet t
	top: \${1};
snippet t:a
	top: auto;
snippet va
	vertical-align: \${1};
snippet va:bl
	vertical-align: baseline;
snippet va:b
	vertical-align: bottom;
snippet va:m
	vertical-align: middle;
snippet va:sub
	vertical-align: sub;
snippet va:sup
	vertical-align: super;
snippet va:tb
	vertical-align: text-bottom;
snippet va:tt
	vertical-align: text-top;
snippet va:t
	vertical-align: top;
snippet v
	visibility: \${1};
snippet v:c
	visibility: collapse;
snippet v:h
	visibility: hidden;
snippet v:v
	visibility: visible;
snippet whsc
	white-space-collapse: \${1};
snippet whsc:ba
	white-space-collapse: break-all;
snippet whsc:bs
	white-space-collapse: break-strict;
snippet whsc:k
	white-space-collapse: keep-all;
snippet whsc:l
	white-space-collapse: loose;
snippet whsc:n
	white-space-collapse: normal;
snippet whs
	white-space: \${1};
snippet whs:n
	white-space: normal;
snippet whs:nw
	white-space: nowrap;
snippet whs:pl
	white-space: pre-line;
snippet whs:pw
	white-space: pre-wrap;
snippet whs:p
	white-space: pre;
snippet wid
	widows: \${1};
snippet w
	width: \${1};
snippet w:a
	width: auto;
snippet wob
	word-break: \${1};
snippet wob:ba
	word-break: break-all;
snippet wob:bs
	word-break: break-strict;
snippet wob:k
	word-break: keep-all;
snippet wob:l
	word-break: loose;
snippet wob:n
	word-break: normal;
snippet wos
	word-spacing: \${1};
snippet wow
	word-wrap: \${1};
snippet wow:no
	word-wrap: none;
snippet wow:n
	word-wrap: normal;
snippet wow:s
	word-wrap: suppress;
snippet wow:u
	word-wrap: unrestricted;
snippet z
	z-index: \${1};
snippet z:a
	z-index: auto;
snippet zoo
	zoom: 1;
`}),ace.define("ace/snippets/css",["require","exports","module","ace/snippets/css.snippets"],function(e,o,s){o.snippetText=e("./css.snippets"),o.scope="css"}),(function(){ace.require(["ace/snippets/css"],function(e){t&&(t.exports=e)})})()})(ut)),ut.exports}ke();var dt={exports:{}},jt;function ze(){return jt||(jt=1,(function(t,n){ace.define("ace/mode/jsdoc_comment_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/text_highlight_rules"],function(e,o,s){var i=e("../lib/oop"),a=e("./text_highlight_rules").TextHighlightRules,l=function(){this.$rules={start:[{token:["comment.doc.tag","comment.doc.text","lparen.doc"],regex:"(@(?:param|member|typedef|property|namespace|var|const|callback))(\\s*)({)",push:[{token:"lparen.doc",regex:"{",push:[{include:"doc-syntax"},{token:"rparen.doc",regex:"}|(?=$)",next:"pop"}]},{token:["rparen.doc","text.doc","variable.parameter.doc","lparen.doc","variable.parameter.doc","rparen.doc"],regex:/(})(\s*)(?:([\w=:\/\.]+)|(?:(\[)([\w=:\/\.\-\'\" ]+)(\])))/,next:"pop"},{token:"rparen.doc",regex:"}|(?=$)",next:"pop"},{include:"doc-syntax"},{defaultToken:"text.doc"}]},{token:["comment.doc.tag","text.doc","lparen.doc"],regex:"(@(?:returns?|yields|type|this|suppress|public|protected|private|package|modifies|implements|external|exception|throws|enum|define|extends))(\\s*)({)",push:[{token:"lparen.doc",regex:"{",push:[{include:"doc-syntax"},{token:"rparen.doc",regex:"}|(?=$)",next:"pop"}]},{token:"rparen.doc",regex:"}|(?=$)",next:"pop"},{include:"doc-syntax"},{defaultToken:"text.doc"}]},{token:["comment.doc.tag","text.doc","variable.parameter.doc"],regex:'(@(?:alias|memberof|instance|module|name|lends|namespace|external|this|template|requires|param|implements|function|extends|typedef|mixes|constructor|var|memberof\\!|event|listens|exports|class|constructs|interface|emits|fires|throws|const|callback|borrows|augments))(\\s+)(\\w[\\w#.:/~"\\-]*)?'},{token:["comment.doc.tag","text.doc","variable.parameter.doc"],regex:"(@method)(\\s+)(\\w[\\w.\\(\\)]*)"},{token:"comment.doc.tag",regex:"@access\\s+(?:private|public|protected)"},{token:"comment.doc.tag",regex:"@kind\\s+(?:class|constant|event|external|file|function|member|mixin|module|namespace|typedef)"},{token:"comment.doc.tag",regex:"@\\w+(?=\\s|$)"},l.getTagRule(),{defaultToken:"comment.doc.body",caseInsensitive:!0}],"doc-syntax":[{token:"operator.doc",regex:/[|:]/},{token:"paren.doc",regex:/[\[\]]/}]},this.normalizeRules()};i.inherits(l,a),l.getTagRule=function(r){return{token:"comment.doc.tag.storage.type",regex:"\\b(?:TODO|FIXME|XXX|HACK)\\b"}},l.getStartRule=function(r){return{token:"comment.doc",regex:/\/\*\*(?!\/)/,next:r}},l.getEndRule=function(r){return{token:"comment.doc",regex:"\\*\\/",next:r}},o.JsDocCommentHighlightRules=l}),ace.define("ace/mode/javascript_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/jsdoc_comment_highlight_rules","ace/mode/text_highlight_rules"],function(e,o,s){var i=e("../lib/oop"),a=e("./jsdoc_comment_highlight_rules").JsDocCommentHighlightRules,l=e("./text_highlight_rules").TextHighlightRules,r="[a-zA-Z\\$_¡-￿][a-zA-Z\\d\\$_¡-￿]*",c=function(u){var d={"variable.language":"Array|Boolean|Date|Function|Iterator|Number|Object|RegExp|String|Proxy|Symbol|Namespace|QName|XML|XMLList|ArrayBuffer|Float32Array|Float64Array|Int16Array|Int32Array|Int8Array|Uint16Array|Uint32Array|Uint8Array|Uint8ClampedArray|Error|EvalError|InternalError|RangeError|ReferenceError|StopIteration|SyntaxError|TypeError|URIError|decodeURI|decodeURIComponent|encodeURI|encodeURIComponent|eval|isFinite|isNaN|parseFloat|parseInt|JSON|Math|this|arguments|prototype|window|document",keyword:"const|yield|import|get|set|async|await|break|case|catch|continue|default|delete|do|else|finally|for|if|in|of|instanceof|new|return|switch|throw|try|typeof|let|var|while|with|debugger|__parent__|__count__|escape|unescape|with|__proto__|class|enum|extends|super|export|implements|private|public|interface|package|protected|static|constructor","storage.type":"const|let|var|function","constant.language":"null|Infinity|NaN|undefined","support.function":"alert","constant.language.boolean":"true|false"},g=this.createKeywordMapper(d,"identifier"),m="case|do|else|finally|in|instanceof|return|throw|try|typeof|yield|void",f="\\\\(?:x[0-9a-fA-F]{2}|u[0-9a-fA-F]{4}|u{[0-9a-fA-F]{1,6}}|[0-2][0-7]{0,2}|3[0-7][0-7]?|[4-7][0-7]?|.)",b="(function)(\\s*)(\\*?)",v={token:["identifier","text","paren.lparen"],regex:"(\\b(?!"+Object.values(d).join("|")+"\\b)"+r+")(\\s*)(\\()"};this.$rules={no_regex:[a.getStartRule("doc-start"),p("no_regex"),v,{token:"string",regex:"'(?=.)",next:"qstring"},{token:"string",regex:'"(?=.)',next:"qqstring"},{token:"constant.numeric",regex:/0(?:[xX][0-9a-fA-F]+|[oO][0-7]+|[bB][01]+)\b/},{token:"constant.numeric",regex:/(?:\d\d*(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+\b)?/},{token:["entity.name.function","text","keyword.operator","text","storage.type","text","storage.type","text","paren.lparen"],regex:"("+r+")(\\s*)(=)(\\s*)"+b+"(\\s*)(\\()",next:"function_arguments"},{token:["storage.type","text","storage.type","text","text","entity.name.function","text","paren.lparen"],regex:"(function)(?:(?:(\\s*)(\\*)(\\s*))|(\\s+))("+r+")(\\s*)(\\()",next:"function_arguments"},{token:["entity.name.function","text","punctuation.operator","text","storage.type","text","storage.type","text","paren.lparen"],regex:"("+r+")(\\s*)(:)(\\s*)"+b+"(\\s*)(\\()",next:"function_arguments"},{token:["text","text","storage.type","text","storage.type","text","paren.lparen"],regex:"(:)(\\s*)"+b+"(\\s*)(\\()",next:"function_arguments"},{token:"keyword",regex:`from(?=\\s*('|"))`},{token:"keyword",regex:"(?:"+m+")\\b",next:"start"},{token:"support.constant",regex:/that\b/},{token:["storage.type","punctuation.operator","support.function.firebug"],regex:/(console)(\.)(warn|info|log|error|debug|time|trace|timeEnd|assert)\b/},{token:g,regex:r},{token:"punctuation.operator",regex:/[.](?![.])/,next:"property"},{token:"storage.type",regex:/=>/,next:"start"},{token:"keyword.operator",regex:/--|\+\+|\.{3}|===|==|=|!=|!==|<+=?|>+=?|!|&&|\|\||\?:|[!$%&*+\-~\/^]=?/,next:"start"},{token:"punctuation.operator",regex:/[?:,;.]/,next:"start"},{token:"paren.lparen",regex:/[\[({]/,next:"start"},{token:"paren.rparen",regex:/[\])}]/},{token:"comment",regex:/^#!.*$/}],property:[{token:"text",regex:"\\s+"},{token:"keyword.operator",regex:/=/},{token:["storage.type","text","storage.type","text","paren.lparen"],regex:b+"(\\s*)(\\()",next:"function_arguments"},{token:["storage.type","text","storage.type","text","text","entity.name.function","text","paren.lparen"],regex:"(function)(?:(?:(\\s*)(\\*)(\\s*))|(\\s+))(\\w+)(\\s*)(\\()",next:"function_arguments"},{token:"punctuation.operator",regex:/[.](?![.])/},{token:"support.function",regex:"prototype"},{token:"support.function",regex:/(s(?:h(?:ift|ow(?:Mod(?:elessDialog|alDialog)|Help))|croll(?:X|By(?:Pages|Lines)?|Y|To)?|t(?:op|rike)|i(?:n|zeToContent|debar|gnText)|ort|u(?:p|b(?:str(?:ing)?)?)|pli(?:ce|t)|e(?:nd|t(?:Re(?:sizable|questHeader)|M(?:i(?:nutes|lliseconds)|onth)|Seconds|Ho(?:tKeys|urs)|Year|Cursor|Time(?:out)?|Interval|ZOptions|Date|UTC(?:M(?:i(?:nutes|lliseconds)|onth)|Seconds|Hours|Date|FullYear)|FullYear|Active)|arch)|qrt|lice|avePreferences|mall)|h(?:ome|andleEvent)|navigate|c(?:har(?:CodeAt|At)|o(?:s|n(?:cat|textual|firm)|mpile)|eil|lear(?:Timeout|Interval)?|a(?:ptureEvents|ll)|reate(?:StyleSheet|Popup|EventObject))|t(?:o(?:GMTString|S(?:tring|ource)|U(?:TCString|pperCase)|Lo(?:caleString|werCase))|est|a(?:n|int(?:Enabled)?))|i(?:s(?:NaN|Finite)|ndexOf|talics)|d(?:isableExternalCapture|ump|etachEvent)|u(?:n(?:shift|taint|escape|watch)|pdateCommands)|j(?:oin|avaEnabled)|p(?:o(?:p|w)|ush|lugins.refresh|a(?:ddings|rse(?:Int|Float)?)|r(?:int|ompt|eference))|e(?:scape|nableExternalCapture|val|lementFromPoint|x(?:p|ec(?:Script|Command)?))|valueOf|UTC|queryCommand(?:State|Indeterm|Enabled|Value)|f(?:i(?:nd|lter|le(?:ModifiedDate|Size|CreatedDate|UpdatedDate)|xed)|o(?:nt(?:size|color)|rward|rEach)|loor|romCharCode)|watch|l(?:ink|o(?:ad|g)|astIndexOf)|a(?:sin|nchor|cos|t(?:tachEvent|ob|an(?:2)?)|pply|lert|b(?:s|ort))|r(?:ou(?:nd|teEvents)|e(?:size(?:By|To)|calc|turnValue|place|verse|l(?:oad|ease(?:Capture|Events)))|andom)|g(?:o|et(?:ResponseHeader|M(?:i(?:nutes|lliseconds)|onth)|Se(?:conds|lection)|Hours|Year|Time(?:zoneOffset)?|Da(?:y|te)|UTC(?:M(?:i(?:nutes|lliseconds)|onth)|Seconds|Hours|Da(?:y|te)|FullYear)|FullYear|A(?:ttention|llResponseHeaders)))|m(?:in|ove(?:B(?:y|elow)|To(?:Absolute)?|Above)|ergeAttributes|a(?:tch|rgins|x))|b(?:toa|ig|o(?:ld|rderWidths)|link|ack))\b(?=\()/},{token:"support.function.dom",regex:/(s(?:ub(?:stringData|mit)|plitText|e(?:t(?:NamedItem|Attribute(?:Node)?)|lect))|has(?:ChildNodes|Feature)|namedItem|c(?:l(?:ick|o(?:se|neNode))|reate(?:C(?:omment|DATASection|aption)|T(?:Head|extNode|Foot)|DocumentFragment|ProcessingInstruction|E(?:ntityReference|lement)|Attribute))|tabIndex|i(?:nsert(?:Row|Before|Cell|Data)|tem)|open|delete(?:Row|C(?:ell|aption)|T(?:Head|Foot)|Data)|focus|write(?:ln)?|a(?:dd|ppend(?:Child|Data))|re(?:set|place(?:Child|Data)|move(?:NamedItem|Child|Attribute(?:Node)?)?)|get(?:NamedItem|Element(?:sBy(?:Name|TagName|ClassName)|ById)|Attribute(?:Node)?)|blur)\b(?=\()/},{token:"support.constant",regex:/(s(?:ystemLanguage|cr(?:ipts|ollbars|een(?:X|Y|Top|Left))|t(?:yle(?:Sheets)?|atus(?:Text|bar)?)|ibling(?:Below|Above)|ource|uffixes|e(?:curity(?:Policy)?|l(?:ection|f)))|h(?:istory|ost(?:name)?|as(?:h|Focus))|y|X(?:MLDocument|SLDocument)|n(?:ext|ame(?:space(?:s|URI)|Prop))|M(?:IN_VALUE|AX_VALUE)|c(?:haracterSet|o(?:n(?:structor|trollers)|okieEnabled|lorDepth|mp(?:onents|lete))|urrent|puClass|l(?:i(?:p(?:boardData)?|entInformation)|osed|asses)|alle(?:e|r)|rypto)|t(?:o(?:olbar|p)|ext(?:Transform|Indent|Decoration|Align)|ags)|SQRT(?:1_2|2)|i(?:n(?:ner(?:Height|Width)|put)|ds|gnoreCase)|zIndex|o(?:scpu|n(?:readystatechange|Line)|uter(?:Height|Width)|p(?:sProfile|ener)|ffscreenBuffering)|NEGATIVE_INFINITY|d(?:i(?:splay|alog(?:Height|Top|Width|Left|Arguments)|rectories)|e(?:scription|fault(?:Status|Ch(?:ecked|arset)|View)))|u(?:ser(?:Profile|Language|Agent)|n(?:iqueID|defined)|pdateInterval)|_content|p(?:ixelDepth|ort|ersonalbar|kcs11|l(?:ugins|atform)|a(?:thname|dding(?:Right|Bottom|Top|Left)|rent(?:Window|Layer)?|ge(?:X(?:Offset)?|Y(?:Offset)?))|r(?:o(?:to(?:col|type)|duct(?:Sub)?|mpter)|e(?:vious|fix)))|e(?:n(?:coding|abledPlugin)|x(?:ternal|pando)|mbeds)|v(?:isibility|endor(?:Sub)?|Linkcolor)|URLUnencoded|P(?:I|OSITIVE_INFINITY)|f(?:ilename|o(?:nt(?:Size|Family|Weight)|rmName)|rame(?:s|Element)|gColor)|E|whiteSpace|l(?:i(?:stStyleType|n(?:eHeight|kColor))|o(?:ca(?:tion(?:bar)?|lName)|wsrc)|e(?:ngth|ft(?:Context)?)|a(?:st(?:M(?:odified|atch)|Index|Paren)|yer(?:s|X)|nguage))|a(?:pp(?:MinorVersion|Name|Co(?:deName|re)|Version)|vail(?:Height|Top|Width|Left)|ll|r(?:ity|guments)|Linkcolor|bove)|r(?:ight(?:Context)?|e(?:sponse(?:XML|Text)|adyState))|global|x|m(?:imeTypes|ultiline|enubar|argin(?:Right|Bottom|Top|Left))|L(?:N(?:10|2)|OG(?:10E|2E))|b(?:o(?:ttom|rder(?:Width|RightWidth|BottomWidth|Style|Color|TopWidth|LeftWidth))|ufferDepth|elow|ackground(?:Color|Image)))\b/},{token:"identifier",regex:r},{regex:"",token:"empty",next:"no_regex"}],start:[a.getStartRule("doc-start"),p("start"),{token:"string.regexp",regex:"\\/",next:"regex"},{token:"text",regex:"\\s+|^$",next:"start"},{token:"empty",regex:"",next:"no_regex"}],regex:[{token:"regexp.keyword.operator",regex:"\\\\(?:u[\\da-fA-F]{4}|x[\\da-fA-F]{2}|.)"},{token:"string.regexp",regex:"/[sxngimy]*",next:"no_regex"},{token:"invalid",regex:/\{\d+\b,?\d*\}[+*]|[+*$^?][+*]|[$^][?]|\?{3,}/},{token:"constant.language.escape",regex:/\(\?[:=!]|\)|\{\d+\b,?\d*\}|[+*]\?|[()$^+*?.]/},{token:"constant.language.delimiter",regex:/\|/},{token:"constant.language.escape",regex:/\[\^?/,next:"regex_character_class"},{token:"empty",regex:"$",next:"no_regex"},{defaultToken:"string.regexp"}],regex_character_class:[{token:"regexp.charclass.keyword.operator",regex:"\\\\(?:u[\\da-fA-F]{4}|x[\\da-fA-F]{2}|.)"},{token:"constant.language.escape",regex:"]",next:"regex"},{token:"constant.language.escape",regex:"-"},{token:"empty",regex:"$",next:"no_regex"},{defaultToken:"string.regexp.charachterclass"}],default_parameter:[{token:"string",regex:"'(?=.)",push:[{token:"string",regex:"'|$",next:"pop"},{include:"qstring"}]},{token:"string",regex:'"(?=.)',push:[{token:"string",regex:'"|$',next:"pop"},{include:"qqstring"}]},{token:"constant.language",regex:"null|Infinity|NaN|undefined"},{token:"constant.numeric",regex:/0(?:[xX][0-9a-fA-F]+|[oO][0-7]+|[bB][01]+)\b/},{token:"constant.numeric",regex:/(?:\d\d*(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+\b)?/},{token:"punctuation.operator",regex:",",next:"function_arguments"},{token:"text",regex:"\\s+"},{token:"punctuation.operator",regex:"$"},{token:"empty",regex:"",next:"no_regex"}],function_arguments:[p("function_arguments"),{token:"variable.parameter",regex:r},{token:"punctuation.operator",regex:","},{token:"text",regex:"\\s+"},{token:"punctuation.operator",regex:"$"},{token:"empty",regex:"",next:"no_regex"}],qqstring:[{token:"constant.language.escape",regex:f},{token:"string",regex:"\\\\$",consumeLineEnd:!0},{token:"string",regex:'"|$',next:"no_regex"},{defaultToken:"string"}],qstring:[{token:"constant.language.escape",regex:f},{token:"string",regex:"\\\\$",consumeLineEnd:!0},{token:"string",regex:"'|$",next:"no_regex"},{defaultToken:"string"}]},(!u||!u.noES6)&&(this.$rules.no_regex.unshift({regex:"[{}]",onMatch:function($,S,R){if(this.next=$=="{"?this.nextState:"",$=="{"&&R.length)R.unshift("start",S);else if($=="}"&&R.length&&(R.shift(),this.next=R.shift(),this.next.indexOf("string")!=-1||this.next.indexOf("jsx")!=-1))return"paren.quasi.end";return $=="{"?"paren.lparen":"paren.rparen"},nextState:"start"},{token:"string.quasi.start",regex:/`/,push:[{token:"constant.language.escape",regex:f},{token:"paren.quasi.start",regex:/\${/,push:"start"},{token:"string.quasi.end",regex:/`/,next:"pop"},{defaultToken:"string.quasi"}]},{token:["variable.parameter","text"],regex:"("+r+")(\\s*)(?=\\=>)"},{token:"paren.lparen",regex:"(\\()(?=[^\\(]+\\s*=>)",next:"function_arguments"},{token:"variable.language",regex:"(?:(?:(?:Weak)?(?:Set|Map))|Promise)\\b"}),this.$rules.function_arguments.unshift({token:"keyword.operator",regex:"=",next:"default_parameter"},{token:"keyword.operator",regex:"\\.{3}"}),this.$rules.property.unshift({token:"support.function",regex:"(findIndex|repeat|startsWith|endsWith|includes|isSafeInteger|trunc|cbrt|log2|log10|sign|then|catch|finally|resolve|reject|race|any|all|allSettled|keys|entries|isInteger)\\b(?=\\()"},{token:"constant.language",regex:"(?:MAX_SAFE_INTEGER|MIN_SAFE_INTEGER|EPSILON)\\b"}),(!u||u.jsx!=!1)&&h.call(this)),this.embedRules(a,"doc-",[a.getEndRule("no_regex")]),this.normalizeRules()};i.inherits(c,l);function h(){var u=r.replace("\\d","\\d\\-"),d={onMatch:function(m,f,b){var v=m.charAt(1)=="/"?2:1;return v==1?(f!=this.nextState?b.unshift(this.next,this.nextState,0):b.unshift(this.next),b[2]++):v==2&&f==this.nextState&&(b[1]--,(!b[1]||b[1]<0)&&(b.shift(),b.shift())),[{type:"meta.tag.punctuation."+(v==1?"":"end-")+"tag-open.xml",value:m.slice(0,v)},{type:"meta.tag.tag-name.xml",value:m.substr(v)}]},regex:"</?(?:"+u+"|(?=>))",next:"jsxAttributes",nextState:"jsx"};this.$rules.start.unshift(d);var g={regex:"{",token:"paren.quasi.start",push:"start"};this.$rules.jsx=[g,d,{include:"reference"},{defaultToken:"string.xml"}],this.$rules.jsxAttributes=[{token:"meta.tag.punctuation.tag-close.xml",regex:"/?>",onMatch:function(m,f,b){return f==b[0]&&b.shift(),m.length==2&&(b[0]==this.nextState&&b[1]--,(!b[1]||b[1]<0)&&b.splice(0,2)),this.next=b[0]||"start",[{type:this.token,value:m}]},nextState:"jsx"},g,p("jsxAttributes"),{token:"entity.other.attribute-name.xml",regex:u},{token:"keyword.operator.attribute-equals.xml",regex:"="},{token:"text.tag-whitespace.xml",regex:"\\s+"},{token:"string.attribute-value.xml",regex:"'",stateName:"jsx_attr_q",push:[{token:"string.attribute-value.xml",regex:"'",next:"pop"},{include:"reference"},{defaultToken:"string.attribute-value.xml"}]},{token:"string.attribute-value.xml",regex:'"',stateName:"jsx_attr_qq",push:[{token:"string.attribute-value.xml",regex:'"',next:"pop"},{include:"reference"},{defaultToken:"string.attribute-value.xml"}]},d],this.$rules.reference=[{token:"constant.language.escape.reference.xml",regex:"(?:&#[0-9]+;)|(?:&#x[0-9a-fA-F]+;)|(?:&[a-zA-Z0-9_:\\.-]+;)"}]}function p(u){return[{token:"comment",regex:/\/\*/,next:[a.getTagRule(),{token:"comment",regex:"\\*\\/",next:u||"pop"},{defaultToken:"comment",caseInsensitive:!0}]},{token:"comment",regex:"\\/\\/",next:[a.getTagRule(),{token:"comment",regex:"$|^",next:u||"pop"},{defaultToken:"comment",caseInsensitive:!0}]}]}o.JavaScriptHighlightRules=c}),ace.define("ace/mode/matching_brace_outdent",["require","exports","module","ace/range"],function(e,o,s){var i=e("../range").Range,a=function(){};(function(){this.checkOutdent=function(l,r){return/^\s+$/.test(l)?/^\s*\}/.test(r):!1},this.autoOutdent=function(l,r){var c=l.getLine(r),h=c.match(/^(\s*\})/);if(!h)return 0;var p=h[1].length,u=l.findMatchingBracket({row:r,column:p});if(!u||u.row==r)return 0;var d=this.$getIndent(l.getLine(u.row));l.replace(new i(r,0,r,p-1),d)},this.$getIndent=function(l){return l.match(/^\s*/)[0]}}).call(a.prototype),o.MatchingBraceOutdent=a}),ace.define("ace/mode/behaviour/xml",["require","exports","module","ace/lib/oop","ace/mode/behaviour","ace/token_iterator"],function(e,o,s){var i=e("../../lib/oop"),a=e("../behaviour").Behaviour,l=e("../../token_iterator").TokenIterator;function r(h,p){return h&&h.type.lastIndexOf(p+".xml")>-1}var c=function(){this.add("string_dquotes","insertion",function(h,p,u,d,g){if(g=='"'||g=="'"){var m=g,f=d.doc.getTextRange(u.getSelectionRange());if(f!==""&&f!=="'"&&f!='"'&&u.getWrapBehavioursEnabled())return{text:m+f+m,selection:!1};var b=u.getCursorPosition(),v=d.doc.getLine(b.row),$=v.substring(b.column,b.column+1),S=new l(d,b.row,b.column),R=S.getCurrentToken();if($==m&&(r(R,"attribute-value")||r(R,"string")))return{text:"",selection:[1,1]};if(R||(R=S.stepBackward()),!R)return;for(;r(R,"tag-whitespace")||r(R,"whitespace");)R=S.stepBackward();var B=!$||$.match(/\s/);if(r(R,"attribute-equals")&&(B||$==">")||r(R,"decl-attribute-equals")&&(B||$=="?"))return{text:m+m,selection:[1,1]}}}),this.add("string_dquotes","deletion",function(h,p,u,d,g){var m=d.doc.getTextRange(g);if(!g.isMultiLine()&&(m=='"'||m=="'")){var f=d.doc.getLine(g.start.row),b=f.substring(g.start.column+1,g.start.column+2);if(b==m)return g.end.column++,g}}),this.add("autoclosing","insertion",function(h,p,u,d,g){if(g==">"){var m=u.getSelectionRange().start,f=new l(d,m.row,m.column),b=f.getCurrentToken()||f.stepBackward();if(!b||!(r(b,"tag-name")||r(b,"tag-whitespace")||r(b,"attribute-name")||r(b,"attribute-equals")||r(b,"attribute-value"))||r(b,"reference.attribute-value"))return;if(r(b,"attribute-value")){var v=f.getCurrentTokenColumn()+b.value.length;if(m.column<v)return;if(m.column==v){var $=f.stepForward();if($&&r($,"attribute-value"))return;f.stepBackward()}}if(/^\s*>/.test(d.getLine(m.row).slice(m.column)))return;for(;!r(b,"tag-name");)if(b=f.stepBackward(),b.value=="<"){b=f.stepForward();break}var S=f.getCurrentTokenRow(),R=f.getCurrentTokenColumn();if(r(f.stepBackward(),"end-tag-open"))return;var B=b.value;return S==m.row&&(B=B.substring(0,m.column-R)),this.voidElements&&this.voidElements.hasOwnProperty(B.toLowerCase())?void 0:{text:"></"+B+">",selection:[1,1]}}}),this.add("autoindent","insertion",function(h,p,u,d,g){if(g==`
`){var m=u.getCursorPosition(),f=d.getLine(m.row),b=new l(d,m.row,m.column),v=b.getCurrentToken();if(r(v,"")&&v.type.indexOf("tag-close")!==-1){if(v.value=="/>")return;for(;v&&v.type.indexOf("tag-name")===-1;)v=b.stepBackward();if(!v)return;var $=v.value,S=b.getCurrentTokenRow();if(v=b.stepBackward(),!v||v.type.indexOf("end-tag")!==-1)return;if(this.voidElements&&!this.voidElements[$]||!this.voidElements){var R=d.getTokenAt(m.row,m.column+1),f=d.getLine(S),B=this.$getIndent(f),K=B+d.getTabString();return R&&R.value==="</"?{text:`
`+K+`
`+B,selection:[1,K.length,1,K.length]}:{text:`
`+K}}}}})};i.inherits(c,a),o.XmlBehaviour=c}),ace.define("ace/mode/behaviour/javascript",["require","exports","module","ace/lib/oop","ace/token_iterator","ace/mode/behaviour/cstyle","ace/mode/behaviour/xml"],function(e,o,s){var i=e("../../lib/oop"),a=e("../../token_iterator").TokenIterator,l=e("../behaviour/cstyle").CstyleBehaviour,r=e("../behaviour/xml").XmlBehaviour,c=function(){var h=new r({closeCurlyBraces:!0}).getBehaviours();this.addBehaviours(h),this.inherit(l),this.add("autoclosing-fragment","insertion",function(p,u,d,g,m){if(m==">"){var f=d.getSelectionRange().start,b=new a(g,f.row,f.column),v=b.getCurrentToken()||b.stepBackward();if(!v)return;if(v.value=="<")return{text:"></>",selection:[1,1]}}})};i.inherits(c,l),o.JavaScriptBehaviour=c}),ace.define("ace/mode/folding/xml",["require","exports","module","ace/lib/oop","ace/range","ace/mode/folding/fold_mode"],function(e,o,s){var i=e("../../lib/oop"),a=e("../../range").Range,l=e("./fold_mode").FoldMode,r=o.FoldMode=function(p,u){l.call(this),this.voidElements=p||{},this.optionalEndTags=i.mixin({},this.voidElements),u&&i.mixin(this.optionalEndTags,u)};i.inherits(r,l);var c=function(){this.tagName="",this.closing=!1,this.selfClosing=!1,this.start={row:0,column:0},this.end={row:0,column:0}};function h(p,u){return p&&p.type&&p.type.lastIndexOf(u+".xml")>-1}(function(){this.getFoldWidget=function(p,u,d){var g=this._getFirstTagInLine(p,d);return g?g.closing||!g.tagName&&g.selfClosing?u==="markbeginend"?"end":"":!g.tagName||g.selfClosing||this.voidElements.hasOwnProperty(g.tagName.toLowerCase())||this._findEndTagInLine(p,d,g.tagName,g.end.column)?"":"start":this.getCommentFoldWidget(p,d)},this.getCommentFoldWidget=function(p,u){return/comment/.test(p.getState(u))&&/<!-/.test(p.getLine(u))?"start":""},this._getFirstTagInLine=function(p,u){for(var d=p.getTokens(u),g=new c,m=0;m<d.length;m++){var f=d[m];if(h(f,"tag-open")){if(g.end.column=g.start.column+f.value.length,g.closing=h(f,"end-tag-open"),f=d[++m],!f)return null;if(g.tagName=f.value,f.value===""){if(f=d[++m],!f)return null;g.tagName=f.value}for(g.end.column+=f.value.length,m++;m<d.length;m++)if(f=d[m],g.end.column+=f.value.length,h(f,"tag-close")){g.selfClosing=f.value=="/>";break}return g}else if(h(f,"tag-close"))return g.selfClosing=f.value=="/>",g;g.start.column+=f.value.length}return null},this._findEndTagInLine=function(p,u,d,g){for(var m=p.getTokens(u),f=0,b=0;b<m.length;b++){var v=m[b];if(f+=v.value.length,!(f<g-1)&&h(v,"end-tag-open")&&(v=m[b+1],h(v,"tag-name")&&v.value===""&&(v=m[b+2]),v&&v.value==d))return!0}return!1},this.getFoldWidgetRange=function(p,u,d){var g=this._getFirstTagInLine(p,d);if(!g)return this.getCommentFoldWidget(p,d)&&p.getCommentFoldRange(d,p.getLine(d).length);var m=p.getMatchingTags({row:d,column:0});if(m)return new a(m.openTag.end.row,m.openTag.end.column,m.closeTag.start.row,m.closeTag.start.column)}}).call(r.prototype)}),ace.define("ace/mode/folding/cstyle",["require","exports","module","ace/lib/oop","ace/range","ace/mode/folding/fold_mode"],function(e,o,s){var i=e("../../lib/oop"),a=e("../../range").Range,l=e("./fold_mode").FoldMode,r=o.FoldMode=function(c){c&&(this.foldingStartMarker=new RegExp(this.foldingStartMarker.source.replace(/\|[^|]*?$/,"|"+c.start)),this.foldingStopMarker=new RegExp(this.foldingStopMarker.source.replace(/\|[^|]*?$/,"|"+c.end)))};i.inherits(r,l),(function(){this.foldingStartMarker=/([\{\[\(])[^\}\]\)]*$|^\s*(\/\*)/,this.foldingStopMarker=/^[^\[\{\(]*([\}\]\)])|^[\s\*]*(\*\/)/,this.singleLineBlockCommentRe=/^\s*(\/\*).*\*\/\s*$/,this.tripleStarBlockCommentRe=/^\s*(\/\*\*\*).*\*\/\s*$/,this.startRegionRe=/^\s*(\/\*|\/\/)#?region\b/,this._getFoldWidgetBase=this.getFoldWidget,this.getFoldWidget=function(c,h,p){var u=c.getLine(p);if(this.singleLineBlockCommentRe.test(u)&&!this.startRegionRe.test(u)&&!this.tripleStarBlockCommentRe.test(u))return"";var d=this._getFoldWidgetBase(c,h,p);return!d&&this.startRegionRe.test(u)?"start":d},this.getFoldWidgetRange=function(c,h,p,u){var d=c.getLine(p);if(this.startRegionRe.test(d))return this.getCommentRegionBlock(c,d,p);var f=d.match(this.foldingStartMarker);if(f){var g=f.index;if(f[1])return this.openingBracketBlock(c,f[1],p,g);var m=c.getCommentFoldRange(p,g+f[0].length,1);return m&&!m.isMultiLine()&&(u?m=this.getSectionRange(c,p):h!="all"&&(m=null)),m}if(h!=="markbegin"){var f=d.match(this.foldingStopMarker);if(f){var g=f.index+f[0].length;return f[1]?this.closingBracketBlock(c,f[1],p,g):c.getCommentFoldRange(p,g,-1)}}},this.getSectionRange=function(c,h){var p=c.getLine(h),u=p.search(/\S/),d=h,g=p.length;h=h+1;for(var m=h,f=c.getLength();++h<f;){p=c.getLine(h);var b=p.search(/\S/);if(b!==-1){if(u>b)break;var v=this.getFoldWidgetRange(c,"all",h);if(v){if(v.start.row<=d)break;if(v.isMultiLine())h=v.end.row;else if(u==b)break}m=h}}return new a(d,g,m,c.getLine(m).length)},this.getCommentRegionBlock=function(c,h,p){for(var u=h.search(/\s*$/),d=c.getLength(),g=p,m=/^\s*(?:\/\*|\/\/|--)#?(end)?region\b/,f=1;++p<d;){h=c.getLine(p);var b=m.exec(h);if(b&&(b[1]?f--:f++,!f))break}var v=p;if(v>g)return new a(g,u,v,h.length)}}).call(r.prototype)}),ace.define("ace/mode/folding/javascript",["require","exports","module","ace/lib/oop","ace/mode/folding/xml","ace/mode/folding/cstyle"],function(e,o,s){var i=e("../../lib/oop"),a=e("./xml").FoldMode,l=e("./cstyle").FoldMode,r=o.FoldMode=function(c){c&&(this.foldingStartMarker=new RegExp(this.foldingStartMarker.source.replace(/\|[^|]*?$/,"|"+c.start)),this.foldingStopMarker=new RegExp(this.foldingStopMarker.source.replace(/\|[^|]*?$/,"|"+c.end))),this.xmlFoldMode=new a};i.inherits(r,l),(function(){this.getFoldWidgetRangeBase=this.getFoldWidgetRange,this.getFoldWidgetBase=this.getFoldWidget,this.getFoldWidget=function(c,h,p){var u=this.getFoldWidgetBase(c,h,p);return u||this.xmlFoldMode.getFoldWidget(c,h,p)},this.getFoldWidgetRange=function(c,h,p,u){var d=this.getFoldWidgetRangeBase(c,h,p,u);return d||this.xmlFoldMode.getFoldWidgetRange(c,h,p)}}).call(r.prototype)}),ace.define("ace/mode/javascript",["require","exports","module","ace/lib/oop","ace/mode/text","ace/mode/javascript_highlight_rules","ace/mode/matching_brace_outdent","ace/worker/worker_client","ace/mode/behaviour/javascript","ace/mode/folding/javascript"],function(e,o,s){var i=e("../lib/oop"),a=e("./text").Mode,l=e("./javascript_highlight_rules").JavaScriptHighlightRules,r=e("./matching_brace_outdent").MatchingBraceOutdent,c=e("../worker/worker_client").WorkerClient,h=e("./behaviour/javascript").JavaScriptBehaviour,p=e("./folding/javascript").FoldMode,u=function(){this.HighlightRules=l,this.$outdent=new r,this.$behaviour=new h,this.foldingRules=new p};i.inherits(u,a),(function(){this.lineCommentStart="//",this.blockComment={start:"/*",end:"*/"},this.$quotes={'"':'"',"'":"'","`":"`"},this.$pairQuotesAfter={"`":/\w/},this.getNextLineIndent=function(d,g,m){var f=this.$getIndent(g),b=this.getTokenizer().getLineTokens(g,d),v=b.tokens,$=b.state;if(v.length&&v[v.length-1].type=="comment")return f;if(d=="start"||d=="no_regex"){var S=g.match(/^.*(?:\bcase\b.*:|[\{\(\[])\s*$/);S&&(f+=m)}else if(d=="doc-start"&&($=="start"||$=="no_regex"))return"";return f},this.checkOutdent=function(d,g,m){return this.$outdent.checkOutdent(g,m)},this.autoOutdent=function(d,g,m){this.$outdent.autoOutdent(g,m)},this.createWorker=function(d){var g=new c(["ace"],"ace/mode/javascript_worker","JavaScriptWorker");return g.attachToDocument(d.getDocument()),g.on("annotate",function(m){d.setAnnotations(m.data)}),g.on("terminate",function(){d.clearAnnotations()}),g},this.$id="ace/mode/javascript",this.snippetFileId="ace/snippets/javascript"}).call(u.prototype),o.Mode=u}),(function(){ace.require(["ace/mode/javascript"],function(e){t&&(t.exports=e)})})()})(dt)),dt.exports}ze();var gt={exports:{}},Ft;function Re(){return Ft||(Ft=1,(function(t,n){ace.define("ace/snippets/javascript.snippets",["require","exports","module"],function(e,o,s){s.exports=`# Prototype
snippet proto
	\${1:class_name}.prototype.\${2:method_name} = function(\${3:first_argument}) {
		\${4:// body...}
	};
# Function
snippet fun
	function \${1?:function_name}(\${2:argument}) {
		\${3:// body...}
	}
# Anonymous Function
regex /((=)\\s*|(:)\\s*|(\\()|\\b)/f/(\\))?/
snippet f
	function\${M1?: \${1:functionName}}($2) {
		\${0:$TM_SELECTED_TEXT}
	}\${M2?;}\${M3?,}\${M4?)}
# Immediate function
trigger \\(?f\\(
endTrigger \\)?
snippet f(
	(function(\${1}) {
		\${0:\${TM_SELECTED_TEXT:/* code */}}
	}(\${1}));
# if
snippet if
	if (\${1:true}) {
		\${0}
	}
# if ... else
snippet ife
	if (\${1:true}) {
		\${2}
	} else {
		\${0}
	}
# tertiary conditional
snippet ter
	\${1:/* condition */} ? \${2:a} : \${3:b}
# switch
snippet switch
	switch (\${1:expression}) {
		case '\${3:case}':
			\${4:// code}
			break;
		\${5}
		default:
			\${2:// code}
	}
# case
snippet case
	case '\${1:case}':
		\${2:// code}
		break;
	\${3}

# while (...) {...}
snippet wh
	while (\${1:/* condition */}) {
		\${0:/* code */}
	}
# try
snippet try
	try {
		\${0:/* code */}
	} catch (e) {}
# do...while
snippet do
	do {
		\${2:/* code */}
	} while (\${1:/* condition */});
# Object Method
snippet :f
regex /([,{[])|^\\s*/:f/
	\${1:method_name}: function(\${2:attribute}) {
		\${0}
	}\${3:,}
# setTimeout function
snippet setTimeout
regex /\\b/st|timeout|setTimeo?u?t?/
	setTimeout(function() {\${3:$TM_SELECTED_TEXT}}, \${1:10});
# Get Elements
snippet gett
	getElementsBy\${1:TagName}('\${2}')\${3}
# Get Element
snippet get
	getElementBy\${1:Id}('\${2}')\${3}
# console.log (Firebug)
snippet cl
	console.log(\${1});
# return
snippet ret
	return \${1:result}
# for (property in object ) { ... }
snippet fori
	for (var \${1:prop} in \${2:Things}) {
		\${0:$2[$1]}
	}
# hasOwnProperty
snippet has
	hasOwnProperty(\${1})
# docstring
snippet /**
	/**
	 * \${1:description}
	 *
	 */
snippet @par
regex /^\\s*\\*\\s*/@(para?m?)?/
	@param {\${1:type}} \${2:name} \${3:description}
snippet @ret
	@return {\${1:type}} \${2:description}
# JSON.parse
snippet jsonp
	JSON.parse(\${1:jstr});
# JSON.stringify
snippet jsons
	JSON.stringify(\${1:object});
# self-defining function
snippet sdf
	var \${1:function_name} = function(\${2:argument}) {
		\${3:// initial code ...}

		$1 = function($2) {
			\${4:// main code}
		};
	}
# singleton
snippet sing
	function \${1:Singleton} (\${2:argument}) {
		// the cached instance
		var instance;

		// rewrite the constructor
		$1 = function $1($2) {
			return instance;
		};
		
		// carry over the prototype properties
		$1.prototype = this;

		// the instance
		instance = new $1();

		// reset the constructor pointer
		instance.constructor = $1;

		\${3:// code ...}

		return instance;
	}
# class
snippet class
regex /^\\s*/clas{0,2}/
	var \${1:class} = function(\${20}) {
		$40$0
	};
	
	(function() {
		\${60:this.prop = ""}
	}).call(\${1:class}.prototype);
	
	exports.\${1:class} = \${1:class};
# 
snippet for-
	for (var \${1:i} = \${2:Things}.length; \${1:i}--; ) {
		\${0:\${2:Things}[\${1:i}];}
	}
# for (...) {...}
snippet for
	for (var \${1:i} = 0; $1 < \${2:Things}.length; $1++) {
		\${3:$2[$1]}$0
	}
# for (...) {...} (Improved Native For-Loop)
snippet forr
	for (var \${1:i} = \${2:Things}.length - 1; $1 >= 0; $1--) {
		\${3:$2[$1]}$0
	}


#modules
snippet def
	define(function(require, exports, module) {
	"use strict";
	var \${1/.*\\///} = require("\${1}");
	
	$TM_SELECTED_TEXT
	});
snippet req
guard ^\\s*
	var \${1/.*\\///} = require("\${1}");
	$0
snippet requ
guard ^\\s*
	var \${1/.*\\/(.)/\\u$1/} = require("\${1}").\${1/.*\\/(.)/\\u$1/};
	$0
`}),ace.define("ace/snippets/javascript",["require","exports","module","ace/snippets/javascript.snippets"],function(e,o,s){o.snippetText=e("./javascript.snippets"),o.scope="javascript"}),(function(){ace.require(["ace/snippets/javascript"],function(e){t&&(t.exports=e)})})()})(gt)),gt.exports}Re();const Me=["id"],pn={__name:"AceFileEditor",props:xe({fileExtention:{type:String,default:"html"},theme:{type:String,default:"monokai"},readOnly:{type:Boolean,default:!1},tabSize:{type:Number,default:4},wrap:{type:Boolean,default:!1},height:{type:Number,default:100}},{modelValue:{type:String,default:""},modelModifiers:{}}),emits:["update:modelValue"],setup(t){Et.config.set("basePath","/vendor/dixipro/magicpro/ace");const n=Lt(me()),e=t,o=fe(t,"modelValue"),s=Lt(null);let i=null;function a(){const r={js:"ace/mode/javascript",css:"ace/mode/css",html:"ace/mode/html"};return r[e.fileExtention]?r[e.fileExtention]:"ace/mode/text"}const l=r=>`ace/theme/${r}`;return be(()=>{a(),i=Et.edit(n.value,{value:o.value,mode:a(),theme:l(e.theme),readOnly:e.readOnly,wrap:e.wrap,tabSize:e.tabSize,useWorker:!1}),i.setOptions({enableBasicAutocompletion:!0,enableLiveAutocompletion:!0,enableEmmet:!0}),i.setFontSize(14),i.session.on("change",()=>{o.value=i.getValue()})}),pt(o,r=>{if(!i)return;const c=i.getValue();r!==c&&i.setValue(r)}),ve(()=>i?.destroy()),pt(()=>e.fileExtention,r=>{const c=a();i?.session.setMode(c)}),pt(()=>e.theme,r=>i?.setTheme(l(r))),(r,c)=>(k(),we("div",{ref_key:"el",ref:s,id:n.value,style:{width:"100%",height:"100%"}},null,8,Me))}};function Dt(t,n){var e=Object.keys(t);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(t);n&&(o=o.filter((function(s){return Object.getOwnPropertyDescriptor(t,s).enumerable}))),e.push.apply(e,o)}return e}function x(t){for(var n=1;n<arguments.length;n++){var e=arguments[n]!=null?arguments[n]:{};n%2?Dt(Object(e),!0).forEach((function(o){_(t,o,e[o])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(e)):Dt(Object(e)).forEach((function(o){Object.defineProperty(t,o,Object.getOwnPropertyDescriptor(e,o))}))}return t}function _(t,n,e){return n in t?Object.defineProperty(t,n,{value:e,enumerable:!0,configurable:!0,writable:!0}):t[n]=e,t}function Ae(t,n){if(t==null)return{};var e,o,s=(function(a,l){if(a==null)return{};var r,c,h={},p=Object.keys(a);for(c=0;c<p.length;c++)r=p[c],l.indexOf(r)>=0||(h[r]=a[r]);return h})(t,n);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(t);for(o=0;o<i.length;o++)e=i[o],n.indexOf(e)>=0||Object.prototype.propertyIsEnumerable.call(t,e)&&(s[e]=t[e])}return s}function X(t){return(function(n){if(Array.isArray(n))return mt(n)})(t)||(function(n){if(typeof Symbol<"u"&&n[Symbol.iterator]!=null||n["@@iterator"]!=null)return Array.from(n)})(t)||(function(n,e){if(n){if(typeof n=="string")return mt(n,e);var o=Object.prototype.toString.call(n).slice(8,-1);if(o==="Object"&&n.constructor&&(o=n.constructor.name),o==="Map"||o==="Set")return Array.from(n);if(o==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(o))return mt(n,e)}})(t)||(function(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)})()}function mt(t,n){(n==null||n>t.length)&&(n=t.length);for(var e=0,o=new Array(n);e<n;e++)o[e]=t[e];return o}var Ht,Se,tt,M=(Ht=function(t){(function(){var n={}.hasOwnProperty;function e(){for(var o=[],s=0;s<arguments.length;s++){var i=arguments[s];if(i){var a=typeof i;if(a==="string"||a==="number")o.push(i);else if(Array.isArray(i)){if(i.length){var l=e.apply(null,i);l&&o.push(l)}}else if(a==="object")if(i.toString===Object.prototype.toString)for(var r in i)n.call(i,r)&&i[r]&&o.push(r);else o.push(i.toString())}}return o.join(" ")}t.exports?(e.default=e,t.exports=e):window.classNames=e})()},Ht(tt={path:Se,exports:{},require:function(t,n){return(function(){throw new Error("Dynamic requires are not currently supported by @rollup/plugin-commonjs")})(n==null&&tt.path)}},tt.exports),tt.exports),I=function(t){return function(n,e){if(!n)return t;var o;typeof n=="string"?o=n:e=n;var s=t;return o&&(s+="__"+o),s+(e?Object.keys(e).reduce((function(i,a){var l=e[a];return l&&(i+=" "+(typeof l=="boolean"?s+"--"+a:s+"--"+a+"_"+l)),i}),""):"")}};function yt(t,n,e){var o,s,i,a,l;function r(){var h=Date.now()-a;h<n&&h>=0?o=setTimeout(r,n-h):(o=null,e||(l=t.apply(i,s),i=s=null))}n==null&&(n=100);var c=function(){i=this,s=arguments,a=Date.now();var h=e&&!o;return o||(o=setTimeout(r,n)),h&&(l=t.apply(i,s),i=s=null),l};return c.clear=function(){o&&(clearTimeout(o),o=null)},c.flush=function(){o&&(l=t.apply(i,s),i=s=null,clearTimeout(o),o=null)},c}yt.debounce=yt;var $t=yt,w=function(){return w=Object.assign||function(t){for(var n,e=1,o=arguments.length;e<o;e++)for(var s in n=arguments[e])Object.prototype.hasOwnProperty.call(n,s)&&(t[s]=n[s]);return t},w.apply(this,arguments)};function Kt(t,n){var e,o;return t&&n?(e=""+t+n[0].toUpperCase()+n.slice(1),o=t+"-"+n):(e=t||n,o=t||n),{name:e,classname:o}}function te(t){return/^blob:/.test(t)}function Pt(t){return te(t)||(function(n){return/^data:/.test(n)})(t)}function Y(t){return!!(t&&t.constructor&&t.call&&t.apply)}function A(t){return t===void 0}function rt(t){return typeof t=="object"&&t!==null}function kt(t,n,e){var o={};return rt(t)?(Object.keys(n).forEach((function(s){A(t[s])?o[s]=n[s]:rt(n[s])?rt(t[s])?o[s]=kt(t[s],n[s],e[s]):o[s]=t[s]?n[s]:e[s]:n[s]===!0||n[s]===!1?o[s]=!!t[s]:o[s]=t[s]})),o):t?n:e}function et(t){var n=Number(t);return Number.isNaN(n)?t:n}function Nt(t){return typeof(t=="number"||(function(n){return typeof n=="object"&&n!==null})(t)&&toString.call(t)=="[object Number]")&&!ee(t)}function ee(t){return t!=t}function ne(t,n){return Math.sqrt(Math.pow(t.x-n.x,2)+Math.pow(t.y-n.y,2))}var G=function(t,n){t===void 0&&(t={}),n===void 0&&(n={}),this.type="manipulateImage",this.move=t,this.scale=n},Ce=function(t,n){n===void 0&&(n={}),this.type="resize",this.directions=t,this.params=n},At=function(t){this.type="move",this.directions=t},Te=(function(){function t(n,e,o,s,i){this.type="drag",this.nativeEvent=n,this.position=o,this.previousPosition=s,this.element=e,this.anchor=i}return t.prototype.shift=function(){var n=this,e=n.element,o=n.anchor,s=n.position;if(e){var i=e.getBoundingClientRect(),a=i.left,l=i.top;return{left:s.left-a-o.left,top:s.top-l-o.top}}return{left:0,top:0}},t})(),St={name:"DraggableElement",props:{classname:{type:String}},beforeMount:function(){window.addEventListener("mouseup",this.onMouseUp,{passive:!1}),window.addEventListener("mousemove",this.onMouseMove,{passive:!1}),window.addEventListener("touchmove",this.onTouchMove,{passive:!1}),window.addEventListener("touchend",this.onTouchEnd,{passive:!1})},beforeUnmount:function(){window.removeEventListener("mouseup",this.onMouseUp),window.removeEventListener("mousemove",this.onMouseMove),window.removeEventListener("touchmove",this.onTouchMove),window.removeEventListener("touchend",this.onTouchEnd)},mounted:function(){if(!this.$refs.draggable)throw new Error('You should add ref "draggable" to your root element to use draggable mixin');this.touches=[],this.hovered=!1},methods:{onMouseOver:function(){this.hovered||(this.hovered=!0,this.$emit("enter"))},onMouseLeave:function(){this.hovered&&!this.touches.length&&(this.hovered=!1,this.$emit("leave"))},onTouchStart:function(t){t.cancelable&&!this.disabled&&t.touches.length===1&&(this.touches=X(t.touches),this.hovered||(this.$emit("enter"),this.hovered=!0),t.touches.length&&this.initAnchor(this.touches.reduce((function(n,e){return{clientX:n.clientX+e.clientX/t.touches.length,clientY:n.clientY+e.clientY/t.touches.length}}),{clientX:0,clientY:0})),t.preventDefault&&t.preventDefault(),t.stopPropagation())},onTouchEnd:function(){this.processEnd()},onTouchMove:function(t){this.touches.length&&(this.processMove(t,t.touches),t.preventDefault&&t.preventDefault(),t.stopPropagation&&t.stopPropagation())},onMouseDown:function(t){if(!this.disabled){var n={fake:!0,clientX:t.clientX,clientY:t.clientY};this.touches=[n],this.initAnchor(n),t.stopPropagation()}},onMouseMove:function(t){this.touches.length&&(this.processMove(t,[{fake:!0,clientX:t.clientX,clientY:t.clientY}]),t.preventDefault&&t.preventDefault())},onMouseUp:function(){this.processEnd()},initAnchor:function(t){var n=this.$refs.draggable.getBoundingClientRect(),e=n.left,o=n.right,s=n.bottom,i=n.top;this.anchor={left:t.clientX-e,top:t.clientY-i,bottom:s-t.clientY,right:o-t.clientX}},processMove:function(t,n){var e=X(n);if(this.touches.length){if(this.touches.length===1&&e.length===1){var o=this.$refs.draggable;this.$emit("drag",new Te(t,o,{left:e[0].clientX,top:e[0].clientY},{left:this.touches[0].clientX,top:this.touches[0].clientY},this.anchor))}this.touches=e}},processEnd:function(){this.touches.length&&this.$emit("drag-end"),this.hovered&&(this.$emit("leave"),this.hovered=!1),this.touches=[]}},emits:["drag","drag-end","leave","enter"]};St.render=function(t,n,e,o,s,i){return k(),z("div",{ref:"draggable",class:e.classname,onTouchstart:n[1]||(n[1]=function(){return i.onTouchStart&&i.onTouchStart.apply(i,arguments)}),onMousedown:n[2]||(n[2]=function(){return i.onMouseDown&&i.onMouseDown.apply(i,arguments)}),onMouseover:n[3]||(n[3]=function(){return i.onMouseOver&&i.onMouseOver.apply(i,arguments)}),onMouseleave:n[4]||(n[4]=function(){return i.onMouseLeave&&i.onMouseLeave.apply(i,arguments)})},[U(t.$slots,"default")],34)};var ft=I("vue-handler-wrapper"),ie={name:"HandlerWrapper",components:{DraggableElement:St},props:{horizontalPosition:{type:String},verticalPosition:{type:String},disabled:{type:Boolean,default:!1}},computed:{classes:function(){var t;if(this.horizontalPosition||this.verticalPosition){var n,e=Kt(this.horizontalPosition,this.verticalPosition);t=ft((_(n={},e.classname,!0),_(n,"disabled",this.disabled),n))}else t=ft({disabled:this.disabled});return{root:t,draggable:ft("draggable")}}},emits:["leave","enter","drag","drag-end"]};ie.render=function(t,n,e,o,s,i){var a=P("DraggableElement");return k(),z("div",{class:i.classes.root},[E(a,{class:i.classes.draggable,onDrag:n[1]||(n[1]=function(l){return t.$emit("drag",l)}),onDragEnd:n[2]||(n[2]=function(l){return t.$emit("drag-end")}),onLeave:n[3]||(n[3]=function(l){return t.$emit("leave")}),onEnter:n[4]||(n[4]=function(l){return t.$emit("enter")})},{default:q((function(){return[U(t.$slots,"default")]})),_:3},8,["class"])],2)};var Ee=I("vue-line-wrapper"),oe={name:"LineWrapper",components:{DraggableElement:St},props:{position:{type:String,required:!0},disabled:{type:Boolean,default:!1}},computed:{classname:function(){var t;return Ee((_(t={},this.position,!0),_(t,"disabled",this.disabled),t))}},emits:["leave","enter","drag","drag-end"]};oe.render=function(t,n,e,o,s,i){var a=P("DraggableElement");return k(),z(a,{class:i.classname,onDrag:n[1]||(n[1]=function(l){return t.$emit("drag",l)}),onDragEnd:n[2]||(n[2]=function(l){return t.$emit("drag-end")}),onLeave:n[3]||(n[3]=function(l){return t.$emit("leave")}),onEnter:n[4]||(n[4]=function(l){return t.$emit("enter")})},{default:q((function(){return[U(t.$slots,"default")]})),_:3},8,["class"])};var j=["left","right","top","bottom"],Le=["left","right"],_e=["top","bottom"],We=["left","top"],Ie=["fill-area","fit-area","stencil","none"],qt={left:0,top:0,width:0,height:0};function Xt(t,n,e){return!(e=e||["width","height","left","top"]).some((function(o){return t[o]!==n[o]}))}function F(t){return{left:t.left,top:t.top,right:t.left+t.width,bottom:t.top+t.height}}function V(t,n){return{left:t.left-n.left,top:t.top-n.top}}function C(t){return{left:t.left+t.width/2,top:t.top+t.height/2}}function Q(t,n){var e={left:0,top:0,right:0,bottom:0};return j.forEach((function(o){var s=n[o],i=F(t)[o];e[o]=s!==void 0&&i!==void 0?o==="left"||o==="top"?Math.max(0,s-i):Math.max(0,i-s):0})),e}function O(t,n){return{left:t.left-n.left,top:t.top-n.top,width:t.width+n.left+n.right,height:t.height+n.top+n.bottom}}function at(t){return{left:-t.left,top:-t.top}}function L(t,n){return w(w({},t),{left:t.left+n.left,top:t.top+n.top})}function W(t,n,e,o){if(n!==1){if(e){var s=C(t);return{width:t.width*n,height:t.height*n,left:t.left+t.width*(1-n)/2+(e.left-s.left)*(1-n),top:t.top+t.height*(1-n)/2+(e.top-s.top)*(1-n)}}return{width:t.width*n,height:t.height*n,left:t.left+t.width*(1-n)/2,top:t.top+t.height*(1-n)/2}}return t}function y(t){return t.width/t.height}function J(t,n){return Math.min(n.right!==void 0&&n.left!==void 0?(n.right-n.left)/t.width:1/0,n.bottom!==void 0&&n.top!==void 0?(n.bottom-n.top)/t.height:1/0)}function Z(t,n){var e={left:0,top:0},o=Q(t,n);return o.left&&o.left>0?e.left=o.left:o.right&&o.right>0&&(e.left=-o.right),o.top&&o.top>0?e.top=o.top:o.bottom&&o.bottom>0&&(e.top=-o.bottom),e}function bt(t,n){var e;return n.minimum&&t<n.minimum?e=n.minimum:n.maximum&&t>n.maximum&&(e=n.maximum),e}function re(t,n){var e=y(t),o=y(n);return n.width<1/0&&n.height<1/0?e>o?{width:n.width,height:n.width/e}:{width:n.height*e,height:n.height}:n.width<1/0?{width:n.width,height:n.width/e}:n.height<1/0?{width:n.height*e,height:n.height}:t}function se(t,n){var e=n*Math.PI/180;return{width:Math.abs(t.width*Math.cos(e))+Math.abs(t.height*Math.sin(e)),height:Math.abs(t.width*Math.sin(e))+Math.abs(t.height*Math.cos(e))}}function N(t,n){var e=n*Math.PI/180;return{left:t.left*Math.cos(e)-t.top*Math.sin(e),top:t.left*Math.sin(e)+t.top*Math.cos(e)}}function lt(t,n){var e=Q(T(t,n),n);return e.left+e.right+e.top+e.bottom?e.left+e.right>e.top+e.bottom?Math.min((t.width+e.left+e.right)/t.width,J(t,n)):Math.min((t.height+e.top+e.bottom)/t.height,J(t,n)):1}function T(t,n,e){e===void 0&&(e=!1);var o=Z(t,n);return L(t,e?at(o):o)}function zt(t){return{width:t.right!==void 0&&t.left!==void 0?t.right-t.left:1/0,height:t.bottom!==void 0&&t.top!==void 0?t.bottom-t.top:1/0}}function Be(t,n){return w(w({},t),{minWidth:Math.min(n.width,t.minWidth),minHeight:Math.min(n.height,t.minHeight),maxWidth:Math.min(n.width,t.maxWidth),maxHeight:Math.min(n.height,t.maxHeight)})}function ae(t,n,e){e===void 0&&(e=!0);var o={};return j.forEach((function(s){var i=t[s],a=n[s];i!==void 0&&a!==void 0?o[s]=s==="left"||s==="top"?e?Math.max(i,a):Math.min(i,a):e?Math.min(i,a):Math.max(i,a):a!==void 0?o[s]=a:i!==void 0&&(o[s]=i)})),o}function ct(t,n){return ae(t,n,!0)}function Ut(t){var n=t.size,e=t.aspectRatio,o=t.ignoreMinimum,s=t.sizeRestrictions;return!!((n.correctRatio||y(n)>=e.minimum&&y(n)<=e.maximum)&&n.height<=s.maxHeight&&n.width<=s.maxWidth&&n.width&&n.height&&(o||n.height>=s.minHeight&&n.width>=s.minWidth))}function Yt(t,n){return Math.pow(t.width-n.width,2)+Math.pow(t.height-n.height,2)}function H(t){var n=t.width,e=t.height,o=t.sizeRestrictions,s={minimum:t.aspectRatio&&t.aspectRatio.minimum||0,maximum:t.aspectRatio&&t.aspectRatio.maximum||1/0},i={width:Math.max(o.minWidth,Math.min(o.maxWidth,n)),height:Math.max(o.minHeight,Math.min(o.maxHeight,e))};function a(c,h){return h===void 0&&(h=!1),c.reduce((function(p,u){return Ut({size:u,aspectRatio:s,sizeRestrictions:o,ignoreMinimum:h})&&(!p||Yt(u,{width:n,height:e})<Yt(p,{width:n,height:e}))?u:p}),null)}var l=[];s&&[s.minimum,s.maximum].forEach((function(c){c&&l.push({width:i.width,height:i.width/c,correctRatio:!0},{width:i.height*c,height:i.height,correctRatio:!0})})),Ut({size:i,aspectRatio:s,sizeRestrictions:o})&&l.push(i);var r=a(l)||a(l,!0);return r&&{width:r.width,height:r.height}}function Rt(t){var n=t.event,e=t.coordinates,o=t.positionRestrictions,s=o===void 0?{}:o,i=L(e,n.directions);return L(i,Z(i,s))}function Oe(t){var n=t.coordinates,e=t.transform,o=t.imageSize,s=t.sizeRestrictions,i=t.positionRestrictions,a=t.aspectRatio,l=t.visibleArea,r=function(h,p){return Rt({coordinates:h,positionRestrictions:i,event:new At({left:p.left-h.left,top:p.top-h.top})})},c=w({},n);return(Array.isArray(e)?e:[e]).forEach((function(h){var p={};A((p=typeof h=="function"?h({coordinates:c,imageSize:o,visibleArea:l}):h).width)&&A(p.height)||(c=(function(u,d){var g=w(w(w({},u),H({width:d.width,height:d.height,sizeRestrictions:s,aspectRatio:a})),{left:0,top:0});return r(g,{left:u.left,top:u.top})})(c,w(w({},c),p))),A(p.left)&&A(p.top)||(c=r(c,w(w({},c),p)))})),c}function je(t){t.event;var n=t.getAreaRestrictions,e=t.boundaries,o=t.coordinates,s=t.visibleArea;t.aspectRatio;var i=t.stencilSize,a=t.sizeRestrictions,l=t.positionRestrictions;t.stencilReference;var r,c,h,p=w({},o),u=w({},s),d=w({},i);r=y(d),c=y(p),h===void 0&&(h=.001),(r===0||c===0?Math.abs(c-r)<h:Math.abs(c/r)<1+h&&Math.abs(c/r)>1-h)||(p=w(w({},p),H({sizeRestrictions:a,width:p.width,height:p.height,aspectRatio:{minimum:y(d),maximum:y(d)}})));var g=lt(u=W(u,p.width*e.width/(u.width*d.width)),n({visibleArea:u,type:"resize"}));return g!==1&&(u=W(u,g),p=W(p,g)),u=T(u=L(u,V(C(p),C(u))),n({visibleArea:u,type:"move"})),{coordinates:p=T(p,ct(F(u),l)),visibleArea:u}}function Fe(t){var n=t.event,e=t.getAreaRestrictions,o=t.boundaries,s=t.coordinates,i=t.visibleArea;t.aspectRatio,t.stencilSize,t.sizeRestrictions;var a=t.positionRestrictions;t.stencilReference;var l=w({},s),r=w({},i);if(s&&i&&n.type!=="manipulateImage"){var c={width:0,height:0};r.width,o.width,y(o)>y(l)?(c.height=.8*o.height,c.width=c.height*y(l)):(c.width=.8*o.width,c.height=c.width*y(l));var h=lt(r=W(r,l.width*o.width/(r.width*c.width)),e({visibleArea:r,type:"resize"}));r=W(r,h),h!==1&&(c.height/=h,c.width/=h),r=T(r=L(r,V(C(l),C(r))),e({visibleArea:r,type:"move"})),l=T(l,ct(F(r),a))}return{coordinates:l,visibleArea:r}}function De(t){var n=t.event,e=t.coordinates,o=t.visibleArea,s=t.getAreaRestrictions,i=w({},o),a=w({},e);if(n.type==="setCoordinates"){var l=Math.max(0,a.width-i.width),r=Math.max(0,a.height-i.height);l>r?i=W(i,Math.min(a.width/i.width,J(i,s({visibleArea:i,type:"resize"})))):r>l&&(i=W(i,Math.min(a.height/i.height,J(i,s({visibleArea:i,type:"resize"}))))),i=T(i=L(i,at(Z(a,F(i)))),s({visibleArea:i,type:"move"}))}return{visibleArea:i,coordinates:a}}function He(t){var n=t.imageSize,e=t.visibleArea,o=t.coordinates,s=e||n;return{left:(e?e.left:0)+s.width/2-o.width/2,top:(e?e.top:0)+s.height/2-o.height/2}}function Pe(t){var n=t.imageSize,e=t.visibleArea,o=t.aspectRatio,s=t.sizeRestrictions,i=e||n,a=Math.min(o.maximum||1/0,Math.max(o.minimum||0,y(i))),l=i.width<i.height?{width:.8*i.width,height:.8*i.width/a}:{height:.8*i.height,width:.8*i.height*a};return H(w(w({},l),{aspectRatio:o,sizeRestrictions:s}))}function Ne(t){var n,e,o=t.imageSize,s=t.visibleArea,i=t.boundaries,a=t.aspectRatio,l=t.sizeRestrictions,r=t.stencilSize,c=s||o;return y(c)>y(i)?e=(n=r.height*c.height/i.height)*y(r):n=(e=r.width*c.width/i.width)/y(r),H({width:e,height:n,aspectRatio:a,sizeRestrictions:l})}function qe(t){var n=t.getAreaRestrictions,e=t.coordinates,o=t.imageSize,s=y(t.boundaries);if(e){var i={height:Math.max(e.height,o.height),width:Math.max(e.width,o.width)},a=re({width:y(i)>s?i.width:i.height*s,height:y(i)>s?i.width/s:i.height},zt(n())),l={left:e.left+e.width/2-a.width/2,top:e.top+e.height/2-a.height/2,width:a.width,height:a.height},r=Q(e,F(w({left:0,top:0},o))),c={};return!r.left&&!r.right&&l.width<=o.width&&(c.left=0,c.right=o.width),!r.top&&!r.bottom&&l.height<=o.height&&(c.top=0,c.bottom=o.height),T(l,c)}var h=y(o);return a={height:h>s?o.height:o.width/s,width:h>s?o.height*s:o.width},{left:o.width/2-a.width/2,top:o.height/2-a.height/2,width:a.width,height:a.height}}function nt(t,n){return ae(t,F(n))}function Xe(t){var n=t.event,e=t.coordinates,o=t.visibleArea,s=t.sizeRestrictions,i=t.getAreaRestrictions,a=t.positionRestrictions,l=t.adjustStencil,r=n.scale,c=n.move,h=w({},o),p=w({},e),u=1,d=1,g=r.factor&&Math.abs(r.factor-1)>.001;h=L(h,{left:c.left||0,top:c.top||0});var m={stencil:{minimum:Math.max(s.minWidth?s.minWidth/p.width:0,s.minHeight?s.minHeight/p.height:0),maximum:Math.min(s.maxWidth?s.maxWidth/p.width:1/0,s.maxHeight?s.maxHeight/p.height:1/0,J(p,a))},area:{maximum:J(h,i({visibleArea:h,type:"resize"}))}};r.factor&&g&&(r.factor<1?(d=Math.max(r.factor,m.stencil.minimum))>1&&(d=1):r.factor>1&&(d=Math.min(r.factor,Math.min(m.area.maximum,m.stencil.maximum)))<1&&(d=1)),d&&(h=W(h,d,r.center));var f=e.left-o.left,b=o.width+o.left-(e.width+e.left),v=e.top-o.top,$=o.height+o.top-(e.height+e.top);return h=T(h=L(h,Z(h,{left:a.left!==void 0?a.left-f*d:void 0,top:a.top!==void 0?a.top-v*d:void 0,bottom:a.bottom!==void 0?a.bottom+$*d:void 0,right:a.right!==void 0?a.right+b*d:void 0})),i({visibleArea:h,type:"move"})),p.width=p.width*d,p.height=p.height*d,p.left=h.left+f*d,p.top=h.top+v*d,p=T(p,ct(F(h),a)),r.factor&&g&&l&&(r.factor>1?u=Math.min(m.area.maximum,r.factor)/d:r.factor<1&&(u=Math.max(p.height/h.height,p.width/h.width,r.factor/d)),u!==1&&(h=L(h=T(h=W(h,u,r.factor>1?r.center:C(p)),i({visibleArea:h,type:"move"})),at(Z(p,F(h)))))),{coordinates:p,visibleArea:h}}function Ue(t){var n=t.aspectRatio,e=t.getAreaRestrictions,o=t.coordinates,s=t.visibleArea,i=t.sizeRestrictions,a=t.positionRestrictions,l=t.imageSize,r=t.previousImageSize,c=t.angle,h=w({},o),p=w({},s),u=N(C(w({left:0,top:0},r)),c);return(h=w(w({},H({sizeRestrictions:i,aspectRatio:n,width:h.width,height:h.height})),N(C(h),c))).left-=u.left-l.width/2+h.width/2,h.top-=u.top-l.height/2+h.height/2,p=W(p,lt(p,e({visibleArea:p,type:"resize"}))),{coordinates:h=T(h,a),visibleArea:p=T(p=L(p,V(C(h),C(o))),e({visibleArea:p,type:"move"}))}}function Ye(t){var n=t.flip,e=t.previousFlip,o=t.rotate,s=t.getAreaRestrictions,i=t.coordinates,a=t.visibleArea,l=t.imageSize,r=w({},i),c=w({},a),h=e.horizontal!==n.horizontal,p=e.vertical!==n.vertical;if(h||p){var u=N({left:l.width/2,top:l.height/2},-o),d=N(C(r),-o),g=N({left:h?u.left-(d.left-u.left):d.left,top:p?u.top-(d.top-u.top):d.top},o);r=L(r,V(g,C(r))),d=N(C(c),-o),c=T(c=L(c,V(g=N({left:h?u.left-(d.left-u.left):d.left,top:p?u.top-(d.top-u.top):d.top},o),C(c))),s({visibleArea:c,type:"move"}))}return{coordinates:r,visibleArea:c}}function Vt(t){var n=t.directions,e=t.coordinates,o=t.positionRestrictions,s=o===void 0?{}:o,i=t.sizeRestrictions,a=t.preserveRatio,l=t.compensate,r=w({},n),c=O(e,r).width,h=O(e,r).height;c<0&&(r.left<0&&r.right<0?(r.left=-(e.width-i.minWidth)/(r.left/r.right),r.right=-(e.width-i.minWidth)/(r.right/r.left)):r.left<0?r.left=-(e.width-i.minWidth):r.right<0&&(r.right=-(e.width-i.minWidth))),h<0&&(r.top<0&&r.bottom<0?(r.top=-(e.height-i.minHeight)/(r.top/r.bottom),r.bottom=-(e.height-i.minHeight)/(r.bottom/r.top)):r.top<0?r.top=-(e.height-i.minHeight):r.bottom<0&&(r.bottom=-(e.height-i.minHeight)));var p=Q(O(e,r),s);l&&(p.left&&p.left>0&&p.right===0?(r.right+=p.left,r.left-=p.left):p.right&&p.right>0&&p.left===0&&(r.left+=p.right,r.right-=p.right),p.top&&p.top>0&&p.bottom===0?(r.bottom+=p.top,r.top-=p.top):p.bottom&&p.bottom>0&&p.top===0&&(r.top+=p.bottom,r.bottom-=p.bottom),p=Q(O(e,r),s));var u={width:1/0,height:1/0,left:1/0,right:1/0,top:1/0,bottom:1/0};if(j.forEach((function(m){var f=p[m];f&&r[m]&&(u[m]=Math.max(0,1-f/r[m]))})),a){var d=Math.min.apply(null,j.map((function(m){return u[m]})));d!==1/0&&j.forEach((function(m){r[m]*=d}))}else j.forEach((function(m){u[m]!==1/0&&(r[m]*=u[m])}));if(c=O(e,r).width,h=O(e,r).height,r.right+r.left&&(c>i.maxWidth?u.width=(i.maxWidth-e.width)/(r.right+r.left):c<i.minWidth&&(u.width=(i.minWidth-e.width)/(r.right+r.left))),r.bottom+r.top&&(h>i.maxHeight?u.height=(i.maxHeight-e.height)/(r.bottom+r.top):h<i.minHeight&&(u.height=(i.minHeight-e.height)/(r.bottom+r.top))),a){var g=Math.min(u.width,u.height);g!==1/0&&j.forEach((function(m){r[m]*=g}))}else u.width!==1/0&&Le.forEach((function(m){r[m]*=u.width})),u.height!==1/0&&_e.forEach((function(m){r[m]*=u.height}));return r}function it(t,n,e){return n==0&&e==0?t/2:n==0?0:e==0?t:t*Math.abs(n/(n+e))}var Ve=I("vue-simple-handler"),Je=I("vue-simple-handler-wrapper"),Ct={name:"SimpleHandler",components:{HandlerWrapper:ie},props:{defaultClass:{type:String},hoverClass:{type:String},wrapperClass:{type:String},horizontalPosition:{type:String},verticalPosition:{type:String},disabled:{type:Boolean,default:!1}},data:function(){return{hover:!1}},computed:{classes:function(){var t,n=(_(t={},this.horizontalPosition,!!this.horizontalPosition),_(t,this.verticalPosition,!!this.verticalPosition),_(t,"".concat(this.horizontalPosition,"-").concat(this.verticalPosition),!!(this.verticalPosition&&this.horizontalPosition)),_(t,"hover",this.hover),t);return{default:M(Ve(n),this.defaultClass,this.hover&&this.hoverClass),wrapper:M(Je(n),this.wrapperClass)}}},methods:{onDrag:function(t){this.$emit("drag",t)},onEnter:function(){this.hover=!0},onLeave:function(){this.hover=!1},onDragEnd:function(){this.$emit("drag-end")}},emits:["drag","drag-end"]};Ct.render=function(t,n,e,o,s,i){var a=P("HandlerWrapper");return k(),z(a,{class:i.classes.wrapper,"vertical-position":e.verticalPosition,"horizontal-position":e.horizontalPosition,disabled:e.disabled,onDrag:i.onDrag,onDragEnd:i.onDragEnd,onEnter:i.onEnter,onLeave:i.onLeave},{default:q((function(){return[E("div",{class:i.classes.default},null,2)]})),_:1},8,["class","vertical-position","horizontal-position","disabled","onDrag","onDragEnd","onEnter","onLeave"])};var Ze=I("vue-simple-line"),Ge=I("vue-simple-line-wrapper"),Tt={name:"SimpleLine",components:{LineWrapper:oe},props:{defaultClass:{type:String},hoverClass:{type:String},wrapperClass:{type:String},position:{type:String},disabled:{type:Boolean,default:!1}},data:function(){return{hover:!1}},computed:{classes:function(){return{root:M(Ze(_({},this.position,!0)),this.defaultClass,this.hover&&this.hoverClass),wrapper:M(Ge(_({},this.position,!0)),this.wrapperClass)}}},methods:{onDrag:function(t){this.$emit("drag",t)},onEnter:function(){this.hover=!0},onLeave:function(){this.hover=!1},onDragEnd:function(){this.$emit("drag-end")}},emits:["drag","drag-end"]};Tt.render=function(t,n,e,o,s,i){var a=P("LineWrapper");return k(),z(a,{class:i.classes.wrapper,position:e.position,disabled:e.disabled,onDrag:i.onDrag,onDragEnd:i.onDragEnd,onEnter:i.onEnter,onLeave:i.onLeave},{default:q((function(){return[E("div",{class:i.classes.root},null,2)]})),_:1},8,["class","position","disabled","onDrag","onDragEnd","onEnter","onLeave"])};var vt=I("vue-bounding-box"),Qe=["east","west",null],Ke=["south","north",null],le={name:"BoundingBox",props:{width:{type:Number},height:{type:Number},transitions:{type:Object},handlers:{type:Object,default:function(){return{eastNorth:!0,north:!0,westNorth:!0,west:!0,westSouth:!0,south:!0,eastSouth:!0,east:!0}}},handlersComponent:{type:[Object,String],default:function(){return Ct}},handlersClasses:{type:Object,default:function(){return{}}},handlersWrappersClasses:{type:Object,default:function(){return{}}},lines:{type:Object,default:function(){return{west:!0,north:!0,east:!0,south:!0}}},linesComponent:{type:[Object,String],default:function(){return Tt}},linesClasses:{type:Object,default:function(){return{}}},linesWrappersClasses:{type:Object,default:function(){return{}}},resizable:{type:Boolean,default:!0}},data:function(){var t=[];return Qe.forEach((function(n){Ke.forEach((function(e){if(n!==e){var o=Kt(n,e),s=o.name,i=o.classname;t.push({name:s,classname:i,verticalDirection:e,horizontalDirection:n})}}))})),{points:t}},computed:{style:function(){var t={};return this.width&&this.height&&(t.width="".concat(this.width,"px"),t.height="".concat(this.height,"px"),this.transitions&&this.transitions.enabled&&(t.transition="".concat(this.transitions.time,"ms ").concat(this.transitions.timingFunction))),t},classes:function(){var t=this.handlersClasses,n=this.handlersWrappersClasses,e=this.linesClasses,o=this.linesWrappersClasses;return{root:vt(),handlers:t,handlersWrappers:n,lines:e,linesWrappers:o}},lineNodes:function(){var t=this,n=[];return this.points.forEach((function(e){e.horizontalDirection&&e.verticalDirection||!t.lines[e.name]||n.push({name:e.name,component:t.linesComponent,class:M(t.classes.lines.default,t.classes.lines[e.name],!t.resizable&&t.classes.lines.disabled),wrapperClass:M(t.classes.linesWrappers.default,t.classes.linesWrappers[e.name],!t.resizable&&t.classes.linesWrappers.disabled),hoverClass:t.classes.lines.hover,verticalDirection:e.verticalDirection,horizontalDirection:e.horizontalDirection,disabled:!t.resizable})})),n},handlerNodes:function(){var t=this,n=[],e=this.width,o=this.height;return this.points.forEach((function(s){if(t.handlers[s.name]){var i={name:s.name,component:t.handlersComponent,class:M(t.classes.handlers.default,t.classes.handlers[s.name]),wrapperClass:M(t.classes.handlersWrappers.default,t.classes.handlersWrappers[s.name]),hoverClass:t.classes.handlers.hover,verticalDirection:s.verticalDirection,horizontalDirection:s.horizontalDirection,disabled:!t.resizable};if(e&&o){var a=s.horizontalDirection,l=s.verticalDirection,r=a==="east"?e:a==="west"?0:e/2,c=l==="south"?o:l==="north"?0:o/2;i.wrapperClass=vt("handler"),i.wrapperStyle={transform:"translate(".concat(r,"px, ").concat(c,"px)")},t.transitions&&t.transitions.enabled&&(i.wrapperStyle.transition="".concat(t.transitions.time,"ms ").concat(t.transitions.timingFunction))}else i.wrapperClass=vt("handler",_({},s.classname,!0));n.push(i)}})),n}},beforeMount:function(){window.addEventListener("mouseup",this.onMouseUp,{passive:!1}),window.addEventListener("mousemove",this.onMouseMove,{passive:!1}),window.addEventListener("touchmove",this.onTouchMove,{passive:!1}),window.addEventListener("touchend",this.onTouchEnd,{passive:!1})},beforeUnmount:function(){window.removeEventListener("mouseup",this.onMouseUp),window.removeEventListener("mousemove",this.onMouseMove),window.removeEventListener("touchmove",this.onTouchMove),window.removeEventListener("touchend",this.onTouchEnd)},mounted:function(){this.touches=[]},methods:{onEnd:function(){this.$emit("resize-end")},onHandlerDrag:function(t,n,e){var o,s=t.shift(),i=s.left,a=s.top,l={left:0,right:0,top:0,bottom:0};n==="west"?l.left-=i:n==="east"&&(l.right+=i),e==="north"?l.top-=a:e==="south"&&(l.bottom+=a),!e&&n?o="width":e&&!n&&(o="height"),this.resizable&&this.$emit("resize",new Ce(l,{allowedDirections:{left:n==="west"||!n,right:n==="east"||!n,bottom:e==="south"||!e,top:e==="north"||!e},preserveAspectRatio:t.nativeEvent&&t.nativeEvent.shiftKey,respectDirection:o}))}},emits:["resize","resize-end"]};le.render=function(t,n,e,o,s,i){return k(),z("div",{ref:"box",class:i.classes.root,style:i.style},[U(t.$slots,"default"),E("div",null,[(k(!0),z(_t,null,Wt(i.lineNodes,(function(a){return k(),z(st(a.component),{key:a.name,"default-class":a.class,"hover-class":a.hoverClass,"wrapper-class":a.wrapperClass,position:a.name,disabled:a.disabled,onDrag:function(l){return i.onHandlerDrag(l,a.horizontalDirection,a.verticalDirection)},onDragEnd:n[1]||(n[1]=function(l){return i.onEnd()})},null,8,["default-class","hover-class","wrapper-class","position","disabled","onDrag"])})),128))]),(k(!0),z(_t,null,Wt(i.handlerNodes,(function(a){return k(),z("div",{key:a.name,style:a.wrapperStyle,class:a.wrapperClass},[(k(),z(st(a.component),{"default-class":a.class,"hover-class":a.hoverClass,"wrapper-class":a.wrapperClass,"horizontal-position":a.horizontalDirection,"vertical-position":a.verticalDirection,disabled:a.disabled,onDrag:function(l){return i.onHandlerDrag(l,a.horizontalDirection,a.verticalDirection)},onDragEnd:n[2]||(n[2]=function(l){return i.onEnd()})},null,8,["default-class","hover-class","wrapper-class","horizontal-position","vertical-position","disabled","onDrag"]))],6)})),128))],6)};var tn=I("vue-draggable-area"),ce={name:"DraggableArea",props:{movable:{type:Boolean,default:!0},activationDistance:{type:Number,default:20}},computed:{classnames:function(){return{default:tn()}}},beforeMount:function(){window.addEventListener("mouseup",this.onMouseUp,{passive:!1}),window.addEventListener("mousemove",this.onMouseMove,{passive:!1}),window.addEventListener("touchmove",this.onTouchMove,{passive:!1}),window.addEventListener("touchend",this.onTouchEnd,{passive:!1})},beforeUnmount:function(){window.removeEventListener("mouseup",this.onMouseUp),window.removeEventListener("mousemove",this.onMouseMove),window.removeEventListener("touchmove",this.onTouchMove),window.removeEventListener("touchend",this.onTouchEnd)},mounted:function(){this.touches=[],this.touchStarted=!1},methods:{onTouchStart:function(t){if(t.cancelable){var n=this.movable&&t.touches.length===1;n&&(this.touches=X(t.touches)),(this.touchStarted||n)&&(t.preventDefault(),t.stopPropagation())}},onTouchEnd:function(){this.touchStarted=!1,this.processEnd()},onTouchMove:function(t){this.touches.length>=1&&(this.touchStarted?(this.processMove(t,t.touches),t.preventDefault(),t.stopPropagation()):ne({x:this.touches[0].clientX,y:this.touches[0].clientY},{x:t.touches[0].clientX,y:t.touches[0].clientY})>this.activationDistance&&(this.initAnchor({clientX:t.touches[0].clientX,clientY:t.touches[0].clientY}),this.touchStarted=!0))},onMouseDown:function(t){if(this.movable&&t.button===0){var n={fake:!0,clientX:t.clientX,clientY:t.clientY};this.touches=[n],this.initAnchor(n),t.stopPropagation()}},onMouseMove:function(t){this.touches.length&&(this.processMove(t,[{fake:!0,clientX:t.clientX,clientY:t.clientY}]),t.preventDefault&&t.cancelable&&t.preventDefault(),t.stopPropagation())},onMouseUp:function(){this.processEnd()},initAnchor:function(t){var n=this.$refs.container.getBoundingClientRect(),e=n.left,o=n.top;this.anchor={x:t.clientX-e,y:t.clientY-o}},processMove:function(t,n){var e=X(n);if(this.touches.length){var o=this.$refs.container.getBoundingClientRect(),s=o.left,i=o.top;this.touches.length===1&&e.length===1&&this.$emit("move",new At({left:e[0].clientX-(s+this.anchor.x),top:e[0].clientY-(i+this.anchor.y)}))}},processEnd:function(){this.touches.length&&this.$emit("move-end"),this.touches=[]}},emits:["move","move-end"]};ce.render=function(t,n,e,o,s,i){return k(),z("div",{ref:"container",onTouchstart:n[1]||(n[1]=function(){return i.onTouchStart&&i.onTouchStart.apply(i,arguments)}),onMousedown:n[2]||(n[2]=function(){return i.onMouseDown&&i.onMouseDown.apply(i,arguments)})},[U(t.$slots,"default")],544)};function wt(t){var n,e;return{rotate:t.rotate||0,flip:{horizontal:((n=t?.flip)===null||n===void 0?void 0:n.horizontal)||!1,vertical:((e=t?.flip)===null||e===void 0?void 0:e.vertical)||!1}}}function en(t){return new Promise((function(n,e){try{if(t)if(/^data:/i.test(t))n((function(r){r=r.replace(/^data:([^;]+);base64,/gim,"");for(var c=atob(r),h=c.length,p=new ArrayBuffer(h),u=new Uint8Array(p),d=0;d<h;d++)u[d]=c.charCodeAt(d);return p})(t));else if(/^blob:/i.test(t)){var o=new FileReader;o.onload=function(r){n(r.target.result)},i=t,a=function(r){o.readAsArrayBuffer(r)},(l=new XMLHttpRequest).open("GET",i,!0),l.responseType="blob",l.onload=function(){this.status!=200&&this.status!==0||a(this.response)},l.send()}else{var s=new XMLHttpRequest;s.onreadystatechange=function(){s.readyState===4&&(s.status===200||s.status===0?n(s.response):e("Warning: could not load an image to parse its orientation"),s=null)},s.onprogress=function(){s.getResponseHeader("content-type")!=="image/jpeg"&&s.abort()},s.withCredentials=!1,s.open("GET",t,!0),s.responseType="arraybuffer",s.send(null)}else e("Error: the image is empty")}catch(r){e(r)}var i,a,l}))}function pe(t){var n=t.rotate,e=t.flip,o=t.scaleX,s=t.scaleY,i="";return i+=" rotate("+n+"deg) ",i+=" scaleX("+o*(e.horizontal?-1:1)+") ",i+=" scaleY("+s*(e.vertical?-1:1)+") "}function nn(t){try{var n,e=new DataView(t),o=void 0,s=void 0,i=void 0,a=void 0;if(e.getUint8(0)===255&&e.getUint8(1)===216)for(var l=e.byteLength,r=2;r+1<l;){if(e.getUint8(r)===255&&e.getUint8(r+1)===225){i=r;break}r++}if(i&&(o=i+10,(function(d,g,m){var f,b="";for(f=g,m+=g;f<m;f++)b+=String.fromCharCode(d.getUint8(f));return b})(e,i+4,4)==="Exif")){var c=e.getUint16(o);if(((s=c===18761)||c===19789)&&e.getUint16(o+2,s)===42){var h=e.getUint32(o+4,s);h>=8&&(a=o+h)}}if(a){for(var p=e.getUint16(a,s),u=0;u<p;u++)if(r=a+12*u+2,e.getUint16(r,s)===274){r+=8,n=e.getUint16(r,s),e.setUint16(r,1,s);break}}return n}catch{return null}}function Jt(t,n){var e=n.getBoundingClientRect(),o=e.left,s=e.top,i={left:0,top:0},a=0;return t.forEach((function(l){i.left+=(l.clientX-o)/t.length,i.top+=(l.clientY-s)/t.length})),t.forEach((function(l){a+=ne({x:i.left,y:i.top},{x:l.clientX-o,y:l.clientY-s})})),{centerMass:i,spread:a,count:t.length}}var he={props:{touchMove:{type:Boolean,required:!0},mouseMove:{type:Boolean,required:!0},touchResize:{type:Boolean,required:!0},wheelResize:{type:[Boolean,Object],required:!0},eventsFilter:{type:Function,required:!1}},beforeMount:function(){window.addEventListener("mouseup",this.onMouseUp,{passive:!1}),window.addEventListener("mousemove",this.onMouseMove,{passive:!1}),window.addEventListener("touchmove",this.onTouchMove,{passive:!1}),window.addEventListener("touchend",this.onTouchEnd,{passive:!1})},beforeUnmount:function(){window.removeEventListener("mouseup",this.onMouseUp),window.removeEventListener("mousemove",this.onMouseMove),window.removeEventListener("touchmove",this.onTouchMove),window.removeEventListener("touchend",this.onTouchEnd)},created:function(){this.transforming=!1,this.debouncedProcessEnd=$t(this.processEnd),this.touches=[]},methods:{processMove:function(t,n){if(this.touches.length){if(this.touches.length===1&&n.length===1)this.$emit("move",new G({left:this.touches[0].clientX-n[0].clientX,top:this.touches[0].clientY-n[0].clientY}));else if(this.touches.length>1&&this.touchResize){var e=Jt(n,this.$refs.container),o=this.oldGeometricProperties;o.count===e.count&&o.count>1&&this.$emit("resize",new G({left:o.centerMass.left-e.centerMass.left,top:o.centerMass.top-e.centerMass.top},{factor:o.spread/e.spread,center:e.centerMass})),this.oldGeometricProperties=e}this.touches=n}},processEnd:function(){this.transforming&&(this.transforming=!1,this.$emit("transform-end"))},processStart:function(){this.transforming=!0,this.debouncedProcessEnd.clear()},processEvent:function(t){return this.eventsFilter?this.eventsFilter(t,this.transforming)!==!1:(t.preventDefault(),t.stopPropagation(),!0)},onTouchStart:function(t){if(t.cancelable&&(this.touchMove||this.touchResize&&t.touches.length>1)&&this.processEvent(t)){var n=this.$refs.container,e=n.getBoundingClientRect(),o=e.left,s=e.top,i=e.bottom,a=e.right;this.touches=X(t.touches).filter((function(l){return l.clientX>o&&l.clientX<a&&l.clientY>s&&l.clientY<i})),this.oldGeometricProperties=Jt(this.touches,n)}},onTouchEnd:function(t){t.touches.length===0&&(this.touches=[],this.processEnd())},onTouchMove:function(t){var n=this;if(this.touches.length){var e=X(t.touches).filter((function(o){return!o.identifier||n.touches.find((function(s){return s.identifier===o.identifier}))}));this.processEvent(t)&&(this.processMove(t,e),this.processStart())}},onMouseDown:function(t){if(this.mouseMove&&"buttons"in t&&t.buttons===1&&this.processEvent(t)){var n={fake:!0,clientX:t.clientX,clientY:t.clientY};this.touches=[n],this.processStart()}},onMouseMove:function(t){this.touches.length&&this.processEvent(t)&&this.processMove(t,[{clientX:t.clientX,clientY:t.clientY}])},onMouseUp:function(){this.touches=[],this.processEnd()},onWheel:function(t){if(this.wheelResize&&this.processEvent(t)){var n=this.$refs.container.getBoundingClientRect(),e=n.left,o=n.top,s=1+this.wheelResize.ratio*(a=t.deltaY||t.detail||t.wheelDelta,(l=+a)==0||ee(l)?l:l>0?1:-1),i={left:t.clientX-e,top:t.clientY-o};this.$emit("resize",new G({},{factor:s,center:i})),this.touches.length||this.debouncedProcessEnd()}var a,l}},emits:["resize","move","transform-end"]};he.render=function(t,n,e,o,s,i){return k(),z("div",{ref:"container",onTouchstart:n[1]||(n[1]=function(){return i.onTouchStart&&i.onTouchStart.apply(i,arguments)}),onMousedown:n[2]||(n[2]=function(){return i.onMouseDown&&i.onMouseDown.apply(i,arguments)}),onWheel:n[3]||(n[3]=function(){return i.onWheel&&i.onWheel.apply(i,arguments)})},[U(t.$slots,"default")],544)};var Mt={components:{TransformableImage:he},props:{touchMove:{type:Boolean,required:!0},mouseMove:{type:Boolean,required:!0},touchResize:{type:Boolean,required:!0},wheelResize:{type:[Boolean,Object],required:!0}},emits:["resize","move"]};Mt.render=function(t,n,e,o,s,i){var a=P("transformable-image");return k(),z(a,{"touch-move":e.touchMove,"touch-resize":e.touchResize,"mouse-move":e.mouseMove,"wheel-resize":e.wheelResize,onMove:n[1]||(n[1]=function(l){return t.$emit("move",l)}),onResize:n[2]||(n[2]=function(l){return t.$emit("resize",l)})},{default:q((function(){return[U(t.$slots,"default")]})),_:3},8,["touch-move","touch-resize","mouse-move","wheel-resize"])};var ot=I("vue-preview"),ue={props:{coordinates:{type:Object},transitions:{type:Object},image:{type:Object,default:function(){return{}}},imageClass:{type:String},width:{type:Number},height:{type:Number},fill:{type:Boolean}},data:function(){return{calculatedImageSize:{width:0,height:0},calculatedSize:{width:0,height:0}}},computed:{classes:function(){return{root:ot({fill:this.fill}),wrapper:ot("wrapper"),imageWrapper:ot("image-wrapper"),image:M(ot("image"),this.imageClass)}},style:function(){if(this.fill)return{};var t={};return this.width&&(t.width="".concat(this.size.width,"px")),this.height&&(t.height="".concat(this.size.height,"px")),this.transitions&&this.transitions.enabled&&(t.transition="".concat(this.transitions.time,"ms ").concat(this.transitions.timingFunction)),t},wrapperStyle:function(){var t={width:"".concat(this.size.width,"px"),height:"".concat(this.size.height,"px"),left:"calc(50% - ".concat(this.size.width/2,"px)"),top:"calc(50% - ".concat(this.size.height/2,"px)")};return this.transitions&&this.transitions.enabled&&(t.transition="".concat(this.transitions.time,"ms ").concat(this.transitions.timingFunction)),t},imageStyle:function(){if(this.coordinates&&this.image){var t=this.coordinates.width/this.size.width,n=x(x({rotate:0,flip:{horizontal:!1,vertical:!1}},this.image.transforms),{},{scaleX:1/t,scaleY:1/t}),e=this.imageSize.width,o=this.imageSize.height,s=se({width:e,height:o},n.rotate),i={width:"".concat(e,"px"),height:"".concat(o,"px"),left:"0px",top:"0px"},a={rotate:{left:(e-s.width)*n.scaleX/2,top:(o-s.height)*n.scaleY/2},scale:{left:(1-n.scaleX)*e/2,top:(1-n.scaleY)*o/2}};return i.transform=`translate(
				`.concat(-this.coordinates.left/t-a.rotate.left-a.scale.left,"px,").concat(-this.coordinates.top/t-a.rotate.top-a.scale.top,"px) ")+pe(n),this.transitions&&this.transitions.enabled&&(i.transition="".concat(this.transitions.time,"ms ").concat(this.transitions.timingFunction)),i}return{}},size:function(){return{width:this.width||this.calculatedSize.width,height:this.height||this.calculatedSize.height}},imageSize:function(){return{width:this.image.width||this.calculatedImageSize.width,height:this.image.height||this.calculatedImageSize.height}}},watch:{image:function(t){(t.width||t.height)&&this.onChangeImage()}},mounted:function(){var t=this;this.onChangeImage(),this.$refs.image.addEventListener("load",(function(){t.refreshImage()})),window.addEventListener("resize",this.refresh),window.addEventListener("orientationchange",this.refresh)},unmounted:function(){window.removeEventListener("resize",this.refresh),window.removeEventListener("orientationchange",this.refresh)},methods:{refreshImage:function(){var t=this.$refs.image;this.calculatedImageSize.height=t.naturalHeight,this.calculatedImageSize.width=t.naturalWidth},refresh:function(){var t=this.$refs.root;this.width||(this.calculatedSize.width=t.clientWidth),this.height||(this.calculatedSize.height=t.clientHeight)},onChangeImage:function(){var t=this.$refs.image;t&&t.complete&&this.refreshImage(),this.refresh()}}};ue.render=function(t,n,e,o,s,i){return k(),z("div",{ref:"root",class:i.classes.root,style:i.style},[E("div",{ref:"wrapper",class:i.classes.wrapper,style:i.wrapperStyle},[Zt(E("img",{ref:"image",src:e.image&&e.image.src,class:i.classes.image,style:i.imageStyle},null,14,["src"]),[[Gt,e.image&&e.image.src]])],6)],6)};var de={components:{Preview:ue},inheritAttrs:!1};de.render=function(t,n,e,o,s,i){var a=P("preview");return k(),z(a,Qt(t.$attrs,{fill:!0}),null,16)};var xt=I("vue-rectangle-stencil"),ge={name:"RectangleStencil",components:{StencilPreview:de,BoundingBox:le,DraggableArea:ce},props:{image:{type:Object},coordinates:{type:Object},stencilCoordinates:{type:Object},handlers:{type:Object},handlersComponent:{type:[Object,String],default:function(){return Ct}},lines:{type:Object},linesComponent:{type:[Object,String],default:function(){return Tt}},aspectRatio:{type:[Number,String]},minAspectRatio:{type:[Number,String]},maxAspectRatio:{type:[Number,String]},movable:{type:Boolean,default:!0},resizable:{type:Boolean,default:!0},transitions:{type:Object},movingClass:{type:String},resizingClass:{type:String},previewClass:{type:String},boundingBoxClass:{type:String},linesClasses:{type:Object,default:function(){return{}}},linesWrappersClasses:{type:Object,default:function(){return{}}},handlersClasses:{type:Object,default:function(){return{}}},handlersWrappersClasses:{type:Object,default:function(){return{}}}},data:function(){return{moving:!1,resizing:!1}},computed:{classes:function(){return{stencil:M(xt({movable:this.movable,moving:this.moving,resizing:this.resizing}),this.moving&&this.movingClass,this.resizing&&this.resizingClass),preview:M(xt("preview"),this.previewClass),boundingBox:M(xt("bounding-box"),this.boundingBoxClass)}},style:function(){var t=this.stencilCoordinates,n=t.height,e=t.width,o=t.left,s=t.top,i={width:"".concat(e,"px"),height:"".concat(n,"px"),transform:"translate(".concat(o,"px, ").concat(s,"px)")};return this.transitions&&this.transitions.enabled&&(i.transition="".concat(this.transitions.time,"ms ").concat(this.transitions.timingFunction)),i}},methods:{onMove:function(t){this.$emit("move",t),this.moving=!0},onMoveEnd:function(){this.$emit("move-end"),this.moving=!1},onResize:function(t){this.$emit("resize",t),this.resizing=!0},onResizeEnd:function(){this.$emit("resize-end"),this.resizing=!1},aspectRatios:function(){return{minimum:this.aspectRatio||this.minAspectRatio,maximum:this.aspectRatio||this.maxAspectRatio}}},emits:["resize","resize-end","move","move-end"]};ge.render=function(t,n,e,o,s,i){var a=P("stencil-preview"),l=P("draggable-area"),r=P("bounding-box");return k(),z("div",{class:i.classes.stencil,style:i.style},[E(r,{width:e.stencilCoordinates.width,height:e.stencilCoordinates.height,transitions:e.transitions,class:i.classes.boundingBox,handlers:e.handlers,"handlers-component":e.handlersComponent,"handlers-classes":e.handlersClasses,"handlers-wrappers-classes":e.handlersWrappersClasses,lines:e.lines,"lines-component":e.linesComponent,"lines-classes":e.linesClasses,"lines-wrappers-classes":e.linesWrappersClasses,resizable:e.resizable,onResize:i.onResize,onResizeEnd:i.onResizeEnd},{default:q((function(){return[E(l,{movable:e.movable,onMove:i.onMove,onMoveEnd:i.onMoveEnd},{default:q((function(){return[E(a,{image:e.image,coordinates:e.coordinates,width:e.stencilCoordinates.width,height:e.stencilCoordinates.height,class:i.classes.preview,transitions:e.transitions},null,8,["image","coordinates","width","height","class","transitions"])]})),_:1},8,["movable","onMove","onMoveEnd"])]})),_:1},8,["width","height","transitions","class","handlers","handlers-component","handlers-classes","handlers-wrappers-classes","lines","lines-component","lines-classes","lines-wrappers-classes","resizable","onResize","onResizeEnd"])],6)};var on=["transitions"],D=I("vue-advanced-cropper"),rn={name:"Cropper",components:{BackgroundWrapper:Mt},props:{src:{type:String,default:null},stencilComponent:{type:[Object,String],default:function(){return ge}},backgroundWrapperComponent:{type:[Object,String],default:function(){return Mt}},stencilProps:{type:Object,default:function(){return{}}},autoZoom:{type:Boolean,default:!1},imageClass:{type:String},boundariesClass:{type:String},backgroundClass:{type:String},foregroundClass:{type:String},minWidth:{type:[Number,String]},minHeight:{type:[Number,String]},maxWidth:{type:[Number,String]},maxHeight:{type:[Number,String]},debounce:{type:[Boolean,Number],default:500},transitions:{type:Boolean,default:!0},checkOrientation:{type:Boolean,default:!0},canvas:{type:[Object,Boolean],default:!0},crossOrigin:{type:[Boolean,String],default:void 0},transitionTime:{type:Number,default:300},imageRestriction:{type:String,default:"fit-area",validator:function(t){return Ie.indexOf(t)!==-1}},roundResult:{type:Boolean,default:!0},defaultSize:{type:[Function,Object]},defaultPosition:{type:[Function,Object]},defaultVisibleArea:{type:[Function,Object]},defaultTransforms:{type:[Function,Object]},defaultBoundaries:{type:[Function,String],validator:function(t){return!(typeof t=="string"&&t!=="fill"&&t!=="fit")}},priority:{type:String,default:"coordinates"},stencilSize:{type:[Object,Function]},resizeImage:{type:[Boolean,Object],default:!0},moveImage:{type:[Boolean,Object],default:!0},autoZoomAlgorithm:{type:Function},resizeAlgorithm:{type:Function,default:function(t){var n=t.event,e=t.coordinates,o=t.aspectRatio,s=t.positionRestrictions,i=t.sizeRestrictions,a=w(w({},e),{right:e.left+e.width,bottom:e.top+e.height}),l=n.params||{},r=w({},n.directions),c=l.allowedDirections||{left:!0,right:!0,bottom:!0,top:!0};i.widthFrozen&&(r.left=0,r.right=0),i.heightFrozen&&(r.top=0,r.bottom=0),j.forEach((function(S){c[S]||(r[S]=0)}));var h=O(a,r=Vt({coordinates:a,directions:r,sizeRestrictions:i,positionRestrictions:s})).width,p=O(a,r).height,u=l.preserveRatio?y(a):bt(h/p,o);if(u){var d=l.respectDirection;if(d||(d=a.width>=a.height||u===1?"width":"height"),d==="width"){var g=h/u-a.height;if(c.top&&c.bottom){var m=r.top,f=r.bottom;r.bottom=it(g,f,m),r.top=it(g,m,f)}else c.bottom?r.bottom=g:c.top?r.top=g:c.right?r.right=0:c.left&&(r.left=0)}else if(d==="height"){var b=a.width-p*u;if(c.left&&c.right){var v=r.left,$=r.right;r.left=-it(b,v,$),r.right=-it(b,$,v)}else c.left?r.left=-b:c.right?r.right=-b:c.top?r.top=0:c.bottom&&(r.bottom=0)}r=Vt({directions:r,coordinates:a,sizeRestrictions:i,positionRestrictions:s,preserveRatio:!0,compensate:l.compensate})}return h=O(a,r).width,p=O(a,r).height,(u=l.preserveRatio?y(a):bt(h/p,o))&&Math.abs(u-h/p)>.001&&j.forEach((function(S){c[S]||(r[S]=0)})),Rt({event:new At({left:-r.left,top:-r.top}),coordinates:{width:e.width+r.right+r.left,height:e.height+r.top+r.bottom,left:e.left,top:e.top},positionRestrictions:s})}},moveAlgorithm:{type:Function,default:Rt},initStretcher:{type:Function,default:function(t){var n=t.stretcher,e=t.imageSize,o=y(e);n.style.width=e.width+"px",n.style.height=n.clientWidth/o+"px",n.style.width=n.clientWidth+"px"}},fitCoordinates:{type:Function,default:function(t){var n=t.visibleArea,e=t.coordinates,o=t.aspectRatio,s=t.sizeRestrictions,i=t.positionRestrictions,a=w(w({},e),H({width:e.width,height:e.height,aspectRatio:o,sizeRestrictions:{maxWidth:n.width,maxHeight:n.height,minHeight:Math.min(n.height,s.minHeight),minWidth:Math.min(n.width,s.minWidth)}}));return a=T(a=L(a,V(C(e),C(a))),ct(F(n),i))}},fitVisibleArea:{type:Function,default:function(t){var n=t.visibleArea,e=t.boundaries,o=t.getAreaRestrictions,s=t.coordinates,i=w({},n);i.height=i.width/y(e),i.top+=(n.height-i.height)/2,(s.height-i.height>0||s.width-i.width>0)&&(i=W(i,Math.max(s.height/i.height,s.width/i.width)));var a=at(Z(s,F(i=W(i,lt(i,o({visibleArea:i,type:"resize"}))))));return i.width<s.width&&(a.left=0),i.height<s.height&&(a.top=0),i=T(i=L(i,a),o({visibleArea:i,type:"move"}))}},areaRestrictionsAlgorithm:{type:Function,default:function(t){var n=t.visibleArea,e=t.boundaries,o=t.imageSize,s=t.imageRestriction,i=t.type,a={};return s==="fill-area"?a={left:0,top:0,right:o.width,bottom:o.height}:s==="fit-area"&&(y(e)>y(o)?(a={top:0,bottom:o.height},n&&i==="move"&&(n.width>o.width?(a.left=-(n.width-o.width)/2,a.right=o.width-a.left):(a.left=0,a.right=o.width))):(a={left:0,right:o.width},n&&i==="move"&&(n.height>o.height?(a.top=-(n.height-o.height)/2,a.bottom=o.height-a.top):(a.top=0,a.bottom=o.height)))),a}},sizeRestrictionsAlgorithm:{type:Function,default:function(t){return{minWidth:t.minWidth,minHeight:t.minHeight,maxWidth:t.maxWidth,maxHeight:t.maxHeight}}},positionRestrictionsAlgorithm:{type:Function,default:function(t){var n=t.imageSize,e={};return t.imageRestriction!=="none"&&(e={left:0,top:0,right:n.width,bottom:n.height}),e}}},data:function(){return{transitionsActive:!1,imageLoaded:!1,imageAttributes:{width:null,height:null,crossOrigin:null,src:null},defaultImageTransforms:{rotate:0,flip:{horizontal:!1,vertical:!1}},appliedImageTransforms:{rotate:0,flip:{horizontal:!1,vertical:!1}},boundaries:{width:0,height:0},visibleArea:null,coordinates:x({},qt)}},computed:{image:function(){return{src:this.imageAttributes.src,width:this.imageAttributes.width,height:this.imageAttributes.height,transforms:this.imageTransforms}},imageTransforms:function(){return{rotate:this.appliedImageTransforms.rotate,flip:{horizontal:this.appliedImageTransforms.flip.horizontal,vertical:this.appliedImageTransforms.flip.vertical},translateX:this.visibleArea?this.visibleArea.left/this.coefficient:0,translateY:this.visibleArea?this.visibleArea.top/this.coefficient:0,scaleX:1/this.coefficient,scaleY:1/this.coefficient}},imageSize:function(){var t=(function(n){return n*Math.PI/180})(this.imageTransforms.rotate);return{width:Math.abs(this.imageAttributes.width*Math.cos(t))+Math.abs(this.imageAttributes.height*Math.sin(t)),height:Math.abs(this.imageAttributes.width*Math.sin(t))+Math.abs(this.imageAttributes.height*Math.cos(t))}},initialized:function(){return!!(this.visibleArea&&this.imageLoaded)},settings:function(){var t=kt(this.resizeImage,{touch:!0,wheel:{ratio:.1},adjustStencil:!0},{touch:!1,wheel:!1,adjustStencil:!1});return{moveImage:kt(this.moveImage,{touch:!0,mouse:!0},{touch:!1,mouse:!1}),resizeImage:t}},coefficient:function(){return this.visibleArea?this.visibleArea.width/this.boundaries.width:0},areaRestrictions:function(){return this.imageLoaded?this.areaRestrictionsAlgorithm({imageSize:this.imageSize,imageRestriction:this.imageRestriction,boundaries:this.boundaries}):{}},transitionsOptions:function(){return{enabled:this.transitionsActive,timingFunction:"ease-in-out",time:350}},sizeRestrictions:function(){if(this.boundaries.width&&this.boundaries.height&&this.imageSize.width&&this.imageSize.height){var t=this.sizeRestrictionsAlgorithm({imageSize:this.imageSize,minWidth:A(this.minWidth)?0:et(this.minWidth),minHeight:A(this.minHeight)?0:et(this.minHeight),maxWidth:A(this.maxWidth)?1/0:et(this.maxWidth),maxHeight:A(this.maxHeight)?1/0:et(this.maxHeight)});if(t=(function(o){var s=o.areaRestrictions,i=o.sizeRestrictions,a=o.boundaries,l=o.positionRestrictions,r=w(w({},i),{minWidth:i.minWidth!==void 0?i.minWidth:0,minHeight:i.minHeight!==void 0?i.minHeight:0,maxWidth:i.maxWidth!==void 0?i.maxWidth:1/0,maxHeight:i.maxHeight!==void 0?i.maxHeight:1/0});l.left!==void 0&&l.right!==void 0&&(r.maxWidth=Math.min(r.maxWidth,l.right-l.left)),l.bottom!==void 0&&l.top!==void 0&&(r.maxHeight=Math.min(r.maxHeight,l.bottom-l.top));var c=zt(s),h=re(a,c);return c.width<1/0&&(!r.maxWidth||r.maxWidth>h.width)&&(r.maxWidth=Math.min(r.maxWidth,h.width)),c.height<1/0&&(!r.maxHeight||r.maxHeight>h.height)&&(r.maxHeight=Math.min(r.maxHeight,h.height)),r.minWidth>r.maxWidth&&(r.minWidth=r.maxWidth,r.widthFrozen=!0),r.minHeight>r.maxHeight&&(r.minHeight=r.maxHeight,r.heightFrozen=!0),r})({sizeRestrictions:t,areaRestrictions:this.getAreaRestrictions({visibleArea:this.visibleArea,type:"resize"}),imageSize:this.imageSize,boundaries:this.boundaries,positionRestrictions:this.positionRestrictions,imageRestriction:this.imageRestriction,visibleArea:this.visibleArea,stencilSize:this.getStencilSize()}),this.visibleArea&&this.stencilSize){var n=this.getStencilSize(),e=zt(this.getAreaRestrictions({visibleArea:this.visibleArea,type:"resize"}));t.maxWidth=Math.min(t.maxWidth,e.width*n.width/this.boundaries.width),t.maxHeight=Math.min(t.maxHeight,e.height*n.height/this.boundaries.height),t.maxWidth<t.minWidth&&(t.minWidth=t.maxWidth),t.maxHeight<t.minHeight&&(t.minHeight=t.maxHeight)}return t}return{minWidth:0,minHeight:0,maxWidth:0,maxHeight:0}},positionRestrictions:function(){return this.positionRestrictionsAlgorithm({imageSize:this.imageSize,imageRestriction:this.imageRestriction})},classes:function(){return{cropper:D(),image:M(D("image"),this.imageClass),stencil:D("stencil"),boundaries:M(D("boundaries"),this.boundariesClass),stretcher:M(D("stretcher")),background:M(D("background"),this.backgroundClass),foreground:M(D("foreground"),this.foregroundClass),imageWrapper:M(D("image-wrapper")),cropperWrapper:M(D("cropper-wrapper"))}},stencilCoordinates:function(){if(this.initialized){var t=this.coordinates,n=t.width,e=t.height,o=t.left,s=t.top;return{width:n/this.coefficient,height:e/this.coefficient,left:(o-this.visibleArea.left)/this.coefficient,top:(s-this.visibleArea.top)/this.coefficient}}return this.defaultCoordinates()},boundariesStyle:function(){var t={width:this.boundaries.width?"".concat(Math.round(this.boundaries.width),"px"):"auto",height:this.boundaries.height?"".concat(Math.round(this.boundaries.height),"px"):"auto",transition:"opacity ".concat(this.transitionTime,"ms"),pointerEvents:this.imageLoaded?"all":"none"};return this.imageLoaded||(t.opacity="0"),t},imageStyle:function(){var t=this.imageAttributes.width>this.imageAttributes.height?{width:Math.min(1024,this.imageAttributes.width),height:Math.min(1024,this.imageAttributes.width)/(this.imageAttributes.width/this.imageAttributes.height)}:{height:Math.min(1024,this.imageAttributes.height),width:Math.min(1024,this.imageAttributes.height)*(this.imageAttributes.width/this.imageAttributes.height)},n={left:(t.width-this.imageSize.width)/(2*this.coefficient),top:(t.height-this.imageSize.height)/(2*this.coefficient)},e={left:(1-1/this.coefficient)*t.width/2,top:(1-1/this.coefficient)*t.height/2},o=x(x({},this.imageTransforms),{},{scaleX:this.imageTransforms.scaleX*(this.imageAttributes.width/t.width),scaleY:this.imageTransforms.scaleY*(this.imageAttributes.height/t.height)}),s={width:"".concat(t.width,"px"),height:"".concat(t.height,"px"),left:"0px",top:"0px",transform:"translate(".concat(-n.left-e.left-this.imageTransforms.translateX,"px, ").concat(-n.top-e.top-this.imageTransforms.translateY,"px)")+pe(o)};return this.transitionsOptions.enabled&&(s.transition="".concat(this.transitionsOptions.time,"ms ").concat(this.transitionsOptions.timingFunction)),s}},watch:{src:function(){this.onChangeImage()},stencilComponent:function(){var t=this;this.$nextTick((function(){t.resetCoordinates(),t.runAutoZoom("setCoordinates"),t.onChange()}))},minWidth:function(){this.onPropsChange()},maxWidth:function(){this.onPropsChange()},minHeight:function(){this.onPropsChange()},maxHeight:function(){this.onPropsChange()},imageRestriction:function(){this.reset()},stencilProps:function(t,n){["aspectRatio","minAspectRatio","maxAspectRatio"].find((function(e){return t[e]!==n[e]}))&&this.$nextTick(this.onPropsChange)}},created:function(){this.debouncedUpdate=$t(this.update,this.debounce),this.debouncedDisableTransitions=$t(this.disableTransitions,this.transitionsOptions.time),this.awaiting=!1},mounted:function(){this.$refs.image.addEventListener("load",this.onSuccessLoadImage),this.$refs.image.addEventListener("error",this.onFailLoadImage),this.onChangeImage(),window.addEventListener("resize",this.refresh),window.addEventListener("orientationchange",this.refresh)},unmounted:function(){window.removeEventListener("resize",this.refresh),window.removeEventListener("orientationchange",this.refresh),this.imageAttributes.revoke&&this.imageAttributes.src&&URL.revokeObjectURL(this.imageAttributes.src),this.debouncedUpdate.clear(),this.debouncedDisableTransitions.clear()},methods:{getResult:function(){var t=this.initialized?this.prepareResult(x({},this.coordinates)):this.defaultCoordinates(),n={rotate:this.imageTransforms.rotate%360,flip:x({},this.imageTransforms.flip)};if(this.src&&this.imageLoaded){var e=this;return{image:this.image,coordinates:t,visibleArea:this.visibleArea?x({},this.visibleArea):null,imageTransforms:n,get canvas(){return e.canvas?e.getCanvas():void 0}}}return{image:this.image,coordinates:t,visibleArea:this.visibleArea?x({},this.visibleArea):null,canvas:void 0,imageTransforms:n}},zoom:function(t,n){var e=arguments.length>2&&arguments[2]!==void 0?arguments[2]:{},o=e.transitions,s=o===void 0||o;this.onManipulateImage(new G({},{factor:1/t,center:n}),{normalize:!1,transitions:s})},move:function(t,n){var e=arguments.length>2&&arguments[2]!==void 0?arguments[2]:{},o=e.transitions,s=o===void 0||o;this.onManipulateImage(new G({left:t||0,top:n||0}),{normalize:!1,transitions:s})},setCoordinates:function(t){var n=this,e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},o=e.autoZoom,s=o===void 0||o,i=e.transitions,a=i===void 0||i;this.$nextTick((function(){n.imageLoaded?(n.transitionsActive||(a&&n.enableTransitions(),n.coordinates=n.applyTransform(t),s&&n.runAutoZoom("setCoordinates"),a&&n.debouncedDisableTransitions()),n.onChange()):n.delayedTransforms=t}))},refresh:function(){var t=this,n=this.$refs.image;if(this.src&&n)return this.initialized?this.updateVisibleArea().then((function(){t.onChange()})):this.resetVisibleArea().then((function(){t.onChange()}))},reset:function(){var t=this;return this.resetVisibleArea().then((function(){t.onChange(!1)}))},awaitRender:function(t){var n=this;this.awaiting||(this.awaiting=!0,this.$nextTick((function(){t(),n.awaiting=!1})))},prepareResult:function(t){return this.roundResult?(function(n){var e=n.coordinates,o=n.sizeRestrictions,s=n.positionRestrictions,i={width:Math.round(e.width),height:Math.round(e.height),left:Math.round(e.left),top:Math.round(e.top)};return i.width>o.maxWidth?i.width=Math.floor(e.width):i.width<o.minWidth&&(i.width=Math.ceil(e.width)),i.height>o.maxHeight?i.height=Math.floor(e.height):i.height<o.minHeight&&(i.height=Math.ceil(e.height)),T(i,s)})(x(x({},this.getPublicProperties()),{},{positionRestrictions:nt(this.positionRestrictions,this.visibleArea),coordinates:t})):t},processAutoZoom:function(t,n,e,o){var s=this.autoZoomAlgorithm;s||(s=this.stencilSize?je:this.autoZoom?Fe:De);var i=s({event:{type:t,params:o},visibleArea:n,coordinates:e,boundaries:this.boundaries,aspectRatio:this.getAspectRatio(),positionRestrictions:this.positionRestrictions,getAreaRestrictions:this.getAreaRestrictions,sizeRestrictions:this.sizeRestrictions,stencilSize:this.getStencilSize()});return x(x({},i),{},{changed:!Xt(i.visibleArea,n)||!Xt(i.coordinates,e)})},runAutoZoom:function(t){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},e=n.transitions,o=e!==void 0&&e,s=Ae(n,on),i=this.processAutoZoom(t,this.visibleArea,this.coordinates,s),a=i.visibleArea,l=i.coordinates,r=i.changed;o&&r&&this.enableTransitions(),this.visibleArea=a,this.coordinates=l,o&&r&&this.debouncedDisableTransitions()},normalizeEvent:function(t){return(function(n){var e=n.event,o=n.visibleArea,s=n.coefficient;if(e.type==="manipulateImage")return w(w({},e),{move:{left:e.move&&e.move.left?s*e.move.left:0,top:e.move&&e.move.top?s*e.move.top:0},scale:{factor:e.scale&&e.scale.factor?e.scale.factor:1,center:e.scale&&e.scale.center?{left:e.scale.center.left*s+o.left,top:e.scale.center.top*s+o.top}:null}});if(e.type==="resize"){var i=w(w({},e),{directions:w({},e.directions)});return j.forEach((function(l){i.directions[l]*=s})),i}if(e.type==="move"){var a=w(w({},e),{directions:w({},e.directions)});return We.forEach((function(l){a.directions[l]*=s})),a}return e})(x(x({},this.getPublicProperties()),{},{event:t}))},getCanvas:function(){if(this.$refs.canvas){var t=this.$refs.canvas,n=this.$refs.image,e=this.imageTransforms.rotate!==0||this.imageTransforms.flip.horizontal||this.imageTransforms.flip.vertical?(function(l,r,c){var h=c.rotate,p=c.flip,u={width:r.naturalWidth,height:r.naturalHeight},d=se(u,h),g=l.getContext("2d");l.height=d.height,l.width=d.width,g.save();var m=N(C(w({left:0,top:0},u)),h);return g.translate(-(m.left-d.width/2),-(m.top-d.height/2)),g.rotate(h*Math.PI/180),g.translate(p.horizontal?u.width:0,p.vertical?u.height:0),g.scale(p.horizontal?-1:1,p.vertical?-1:1),g.drawImage(r,0,0,u.width,u.height),g.restore(),l})(this.$refs.sourceCanvas,n,this.imageTransforms):n,o=x({minWidth:0,minHeight:0,maxWidth:1/0,maxHeight:1/0,maxArea:this.maxCanvasSize,imageSmoothingEnabled:!0,imageSmoothingQuality:"high",fillColor:"transparent"},this.canvas),s=function(l){return l.find((function(r){return c=r,!Number.isNaN(parseFloat(c))&&isFinite(c);var c}))},i=H({sizeRestrictions:{minWidth:s([o.width,o.minWidth])||0,minHeight:s([o.height,o.minHeight])||0,maxWidth:s([o.width,o.maxWidth])||1/0,maxHeight:s([o.height,o.maxHeight])||1/0},width:this.coordinates.width,height:this.coordinates.height,aspectRatio:{minimum:this.coordinates.width/this.coordinates.height,maximum:this.coordinates.width/this.coordinates.height}});if(o.maxArea&&i.width*i.height>o.maxArea){var a=Math.sqrt(o.maxArea/(i.width*i.height));i={width:Math.round(a*i.width),height:Math.round(a*i.height)}}return(function(l,r,c,h,p){l.width=h?h.width:c.width,l.height=h?h.height:c.height;var u=l.getContext("2d");u.clearRect(0,0,l.width,l.height),p&&(p.imageSmoothingEnabled&&(u.imageSmoothingEnabled=p.imageSmoothingEnabled),p.imageSmoothingQuality&&(u.imageSmoothingQuality=p.imageSmoothingQuality),p.fillColor&&(u.fillStyle=p.fillColor,u.fillRect(0,0,l.width,l.height),u.save()));var d=c.left<0?-c.left:0,g=c.top<0?-c.top:0;u.drawImage(r,c.left+d,c.top+g,c.width,c.height,d*(l.width/c.width),g*(l.height/c.height),l.width,l.height)})(t,e,this.coordinates,i,o),t}},update:function(){this.$emit("change",this.getResult())},applyTransform:function(t){var n=arguments.length>1&&arguments[1]!==void 0&&arguments[1],e=this.visibleArea&&n?Be(this.sizeRestrictions,this.visibleArea):this.sizeRestrictions,o=this.visibleArea&&n?nt(this.positionRestrictions,this.visibleArea):this.positionRestrictions;return Oe({transform:t,coordinates:this.coordinates,imageSize:this.imageSize,sizeRestrictions:e,positionRestrictions:o,aspectRatio:this.getAspectRatio(),visibleArea:this.visibleArea})},resetCoordinates:function(){var t=this;if(this.$refs.image){this.$refs.cropper,this.$refs.image;var n=this.defaultSize;n||(n=this.stencilSize?Ne:Pe);var e=this.sizeRestrictions;e.minWidth,e.minHeight,e.maxWidth,e.maxHeight;var o=Y(n)?n({boundaries:this.boundaries,imageSize:this.imageSize,aspectRatio:this.getAspectRatio(),sizeRestrictions:this.sizeRestrictions,stencilSize:this.getStencilSize(),visibleArea:this.visibleArea}):n,s=this.defaultPosition||He,i=[o,function(a){var l=a.coordinates;return x({},Y(s)?s({coordinates:l,imageSize:t.imageSize,visibleArea:t.visibleArea}):t.defaultPosition)}];this.delayedTransforms&&i.push.apply(i,X(Array.isArray(this.delayedTransforms)?this.delayedTransforms:[this.delayedTransforms])),this.coordinates=this.applyTransform(i,!0),this.delayedTransforms=null}},clearImage:function(){var t=this;this.imageLoaded=!1,setTimeout((function(){var n=t.$refs.stretcher;n&&(n.style.height="auto",n.style.width="auto"),t.coordinates=t.defaultCoordinates(),t.boundaries={width:0,height:0}}),this.transitionTime)},enableTransitions:function(){this.transitions&&(this.transitionsActive=!0)},disableTransitions:function(){this.transitionsActive=!1},updateBoundaries:function(){var t=this,n=this.$refs.stretcher,e=this.$refs.cropper;return this.initStretcher({cropper:e,stretcher:n,imageSize:this.imageSize}),this.$nextTick().then((function(){var o={cropper:e,imageSize:t.imageSize};if(Y(t.defaultBoundaries)?t.boundaries=t.defaultBoundaries(o):t.defaultBoundaries==="fit"?t.boundaries=(function(s){var i=s.cropper,a=s.imageSize,l=i.clientHeight,r=i.clientWidth,c=l,h=a.width*l/a.height;return h>r&&(h=r,c=a.height*r/a.width),{width:h,height:c}})(o):t.boundaries=(function(s){var i=s.cropper;return{width:i.clientWidth,height:i.clientHeight}})(o),!t.boundaries.width||!t.boundaries.height)throw new Error("It's impossible to fit the cropper in the current container")}))},resetVisibleArea:function(){var t=this;return this.appliedImageTransforms=x(x({},this.defaultImageTransforms),{},{flip:x({},this.defaultImageTransforms.flip)}),this.updateBoundaries().then((function(){t.priority!=="visible-area"&&(t.visibleArea=null,t.resetCoordinates());var n,e,o,s,i,a,l=t.defaultVisibleArea||qe;t.visibleArea=Y(l)?l({imageSize:t.imageSize,boundaries:t.boundaries,coordinates:t.priority!=="visible-area"?t.coordinates:null,getAreaRestrictions:t.getAreaRestrictions,stencilSize:t.getStencilSize()}):t.defaultVisibleArea,t.visibleArea=(n={visibleArea:t.visibleArea,boundaries:t.boundaries,getAreaRestrictions:t.getAreaRestrictions},e=n.visibleArea,o=n.boundaries,s=n.getAreaRestrictions,i=w({},e),a=y(o),i.width/i.height!==a&&(i.height=i.width/a),T(i,s({visibleArea:i,type:"move"}))),t.priority==="visible-area"?t.resetCoordinates():t.coordinates=t.fitCoordinates({visibleArea:t.visibleArea,coordinates:t.coordinates,aspectRatio:t.getAspectRatio(),positionRestrictions:t.positionRestrictions,sizeRestrictions:t.sizeRestrictions}),t.runAutoZoom("resetVisibleArea")})).catch((function(){t.visibleArea=null}))},updateVisibleArea:function(){var t=this;return this.updateBoundaries().then((function(){t.visibleArea=t.fitVisibleArea({imageSize:t.imageSize,boundaries:t.boundaries,visibleArea:t.visibleArea,coordinates:t.coordinates,getAreaRestrictions:t.getAreaRestrictions}),t.coordinates=t.fitCoordinates({visibleArea:t.visibleArea,coordinates:t.coordinates,aspectRatio:t.getAspectRatio(),positionRestrictions:t.positionRestrictions,sizeRestrictions:t.sizeRestrictions}),t.runAutoZoom("updateVisibleArea")})).catch((function(){t.visibleArea=null}))},onChange:function(){var t=!(arguments.length>0&&arguments[0]!==void 0)||arguments[0];t&&this.debounce?this.debouncedUpdate():this.update()},onChangeImage:function(){var t,n=this;if(this.imageLoaded=!1,this.delayedTransforms=null,this.src){if((function(s){if(Pt(s))return!1;var i=window.location,a=/(\w+:)?(?:\/\/)([\w.-]+)?(?::(\d+))?\/?/.exec(s)||[],l={protocol:a[1]||"",host:a[2]||"",port:a[3]||""},r=function(c){return c.port||((c.protocol||i.protocol)==="http"?80:433)};return!(!l.protocol&&!l.host&&!l.port||l.protocol&&l.protocol==i.protocol&&l.host&&l.host==i.host&&l.host&&r(l)==r(i))})(this.src)){var e=A(this.crossOrigin)?this.canvas:this.crossOrigin;e===!0&&(e="anonymous"),this.imageAttributes.crossOrigin=e||null}if(this.checkOrientation){var o=(t=this.src,new Promise((function(s){en(t).then((function(i){var a=nn(i);s(i?{source:t,arrayBuffer:i,orientation:a}:{source:t,arrayBuffer:null,orientation:null})})).catch((function(i){console.warn(i),s({source:t,arrayBuffer:null,orientation:null})}))})));setTimeout((function(){o.then(n.onParseImage)}),this.transitionTime)}else setTimeout((function(){n.onParseImage({source:n.src})}),this.transitionTime)}else this.clearImage()},onFailLoadImage:function(){this.imageAttributes.src&&(this.clearImage(),this.$emit("error"))},onSuccessLoadImage:function(){var t=this,n=this.$refs.image;n&&!this.imageLoaded&&(this.imageAttributes.height=n.naturalHeight,this.imageAttributes.width=n.naturalWidth,this.imageLoaded=!0,this.resetVisibleArea().then((function(){t.$emit("ready"),t.onChange(!1)})))},onParseImage:function(t){var n=this,e=t.source,o=t.arrayBuffer,s=t.orientation;this.imageAttributes.revoke&&this.imageAttributes.src&&URL.revokeObjectURL(this.imageAttributes.src),this.imageAttributes.revoke=!1,o&&s&&s>1?te(e)||!Pt(e)?(this.imageAttributes.src=URL.createObjectURL(new Blob([o])),this.imageAttributes.revoke=!0):this.imageAttributes.src=(function(i){for(var a=[],l=new Uint8Array(i);l.length>0;){var r=l.subarray(0,8192);a.push(String.fromCharCode.apply(null,Array.from?Array.from(r):r.slice())),l=l.subarray(8192)}return"data:image/jpeg;base64,"+btoa(a.join(""))})(o):this.imageAttributes.src=e,Y(this.defaultTransforms)?this.appliedImageTransforms=wt(this.defaultTransforms()):rt(this.defaultTransforms)?this.appliedImageTransforms=wt(this.defaultTransforms):this.appliedImageTransforms=(function(i){var a=wt({});if(i)switch(i){case 2:a.flip.horizontal=!0;break;case 3:a.rotate=-180;break;case 4:a.flip.vertical=!0;break;case 5:a.rotate=90,a.flip.vertical=!0;break;case 6:a.rotate=90;break;case 7:a.rotate=90,a.flip.horizontal=!0;break;case 8:a.rotate=-90}return a})(s),this.defaultImageTransforms=x(x({},this.appliedImageTransforms),{},{flip:x({},this.appliedImageTransforms.flip)}),this.$nextTick((function(){var i=n.$refs.image;i&&i.complete&&((function(a){return!!a.naturalWidth})(i)?n.onSuccessLoadImage():n.onFailLoadImage())}))},onResizeEnd:function(){this.runAutoZoom("resize",{transitions:!0})},onMoveEnd:function(){this.runAutoZoom("move",{transitions:!0})},onMove:function(t){var n=this;this.transitionsOptions.enabled||this.awaitRender((function(){n.coordinates=n.moveAlgorithm(x(x({},n.getPublicProperties()),{},{positionRestrictions:nt(n.positionRestrictions,n.visibleArea),coordinates:n.coordinates,event:n.normalizeEvent(t)})),n.onChange()}))},onResize:function(t){var n=this;this.transitionsOptions.enabled||this.stencilSize&&!this.autoZoom||this.awaitRender((function(){var e=n.sizeRestrictions,o=Math.min(n.coordinates.width,n.coordinates.height,20*n.coefficient);n.coordinates=n.resizeAlgorithm(x(x({},n.getPublicProperties()),{},{positionRestrictions:nt(n.positionRestrictions,n.visibleArea),sizeRestrictions:{maxWidth:Math.min(e.maxWidth,n.visibleArea.width),maxHeight:Math.min(e.maxHeight,n.visibleArea.height),minWidth:Math.max(e.minWidth,o),minHeight:Math.max(e.minHeight,o)},event:n.normalizeEvent(t)})),n.onChange(),n.ticking=!1}))},onManipulateImage:function(t){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{};if(!this.transitionsOptions.enabled){var e=n.transitions,o=e!==void 0&&e,s=n.normalize,i=s===void 0||s;o&&this.enableTransitions();var a=Xe(x(x({},this.getPublicProperties()),{},{event:i?this.normalizeEvent(t):t,getAreaRestrictions:this.getAreaRestrictions,imageRestriction:this.imageRestriction,adjustStencil:!this.stencilSize&&this.settings.resizeImage.adjustStencil})),l=a.visibleArea,r=a.coordinates;this.visibleArea=l,this.coordinates=r,this.runAutoZoom("manipulateImage"),this.onChange(),o&&this.debouncedDisableTransitions()}},onPropsChange:function(){this.coordinates=this.applyTransform(this.coordinates,!0),this.onChange(!1)},getAreaRestrictions:function(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{},n=t.visibleArea,e=t.type,o=e===void 0?"move":e;return this.areaRestrictionsAlgorithm({boundaries:this.boundaries,imageSize:this.imageSize,imageRestriction:this.imageRestriction,visibleArea:n,type:o})},getAspectRatio:function(t){var n,e,o=this.stencilProps,s=o.aspectRatio,i=o.minAspectRatio,a=o.maxAspectRatio;if(this.$refs.stencil&&this.$refs.stencil.aspectRatios){var l=this.$refs.stencil.aspectRatios();n=l.minimum,e=l.maximum}if(A(n)&&(n=A(s)?i:s),A(e)&&(e=A(s)?a:s),!t&&(A(n)||A(e))){var r=this.getStencilSize(),c=r?y(r):null;A(n)&&(n=Nt(c)?c:void 0),A(e)&&(e=Nt(c)?c:void 0)}return{minimum:n,maximum:e}},getStencilSize:function(){if(this.stencilSize)return t={currentStencilSize:{width:this.stencilCoordinates.width,height:this.stencilCoordinates.height},stencilSize:this.stencilSize,boundaries:this.boundaries,coefficient:this.coefficient,coordinates:this.coordinates,aspectRatio:this.getAspectRatio(!0)},n=t.boundaries,e=t.stencilSize,o=t.aspectRatio,bt(y(s=Y(e)?e({boundaries:n,aspectRatio:o}):e),o)&&(s=H({sizeRestrictions:{maxWidth:n.width,maxHeight:n.height,minWidth:0,minHeight:0},width:s.width,height:s.height,aspectRatio:{minimum:o.minimum,maximum:o.maximum}})),(s.width>n.width||s.height>n.height)&&(s=H({sizeRestrictions:{maxWidth:n.width,maxHeight:n.height,minWidth:0,minHeight:0},width:s.width,height:s.height,aspectRatio:{minimum:y(s),maximum:y(s)}})),s;var t,n,e,o,s},getPublicProperties:function(){return{coefficient:this.coefficient,visibleArea:this.visibleArea,coordinates:this.coordinates,boundaries:this.boundaries,sizeRestrictions:this.sizeRestrictions,positionRestrictions:this.positionRestrictions,aspectRatio:this.getAspectRatio(),imageRestriction:this.imageRestriction}},defaultCoordinates:function(){return x({},qt)},flip:function(t,n){var e=arguments.length>2&&arguments[2]!==void 0?arguments[2]:{},o=e.transitions,s=o===void 0||o;if(!this.transitionsActive){s&&this.enableTransitions();var i=x({},this.imageTransforms.flip),a=Ye({flip:{horizontal:t?!i.horizontal:i.horizontal,vertical:n?!i.vertical:i.vertical},previousFlip:i,rotate:this.imageTransforms.rotate,visibleArea:this.visibleArea,coordinates:this.coordinates,imageSize:this.imageSize,positionRestrictions:this.positionRestrictions,sizeRestrictions:this.sizeRestrictions,getAreaRestrictions:this.getAreaRestrictions,aspectRatio:this.getAspectRatio()}),l=a.visibleArea,r=a.coordinates;t&&(this.appliedImageTransforms.flip.horizontal=!this.appliedImageTransforms.flip.horizontal),n&&(this.appliedImageTransforms.flip.vertical=!this.appliedImageTransforms.flip.vertical),this.visibleArea=l,this.coordinates=r,this.onChange(),s&&this.debouncedDisableTransitions()}},rotate:function(t){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:{},e=n.transitions,o=e===void 0||e;if(!this.transitionsActive){o&&this.enableTransitions();var s=x({},this.imageSize);this.appliedImageTransforms.rotate+=t;var i=Ue({visibleArea:this.visibleArea,coordinates:this.coordinates,previousImageSize:s,imageSize:this.imageSize,angle:t,positionRestrictions:this.positionRestrictions,sizeRestrictions:this.sizeRestrictions,getAreaRestrictions:this.getAreaRestrictions,aspectRatio:this.getAspectRatio()}),a=i.visibleArea,l=i.coordinates,r=this.processAutoZoom("rotateImage",a,l);a=r.visibleArea,l=r.coordinates,this.visibleArea=a,this.coordinates=l,this.onChange(),o&&this.debouncedDisableTransitions()}}},emits:["change","error","ready"]},sn={key:0,ref:"canvas",style:{display:"none"}},an={key:1,ref:"sourceCanvas",style:{display:"none"}};rn.render=function(t,n,e,o,s,i){return k(),z("div",{ref:"cropper",class:i.classes.cropper},[E("div",{ref:"stretcher",class:i.classes.stretcher},null,2),E("div",{class:i.classes.boundaries,style:i.boundariesStyle},[(k(),z(st(e.backgroundWrapperComponent),{class:i.classes.cropperWrapper,"wheel-resize":i.settings.resizeImage.wheel,"touch-resize":i.settings.resizeImage.touch,"touch-move":i.settings.moveImage.touch,"mouse-move":i.settings.moveImage.mouse,onMove:i.onManipulateImage,onResize:i.onManipulateImage},{default:q((function(){return[E("div",{class:i.classes.background,style:i.boundariesStyle},null,6),E("div",{class:i.classes.imageWrapper},[E("img",{ref:"image",crossorigin:s.imageAttributes.crossOrigin,src:s.imageAttributes.src,class:i.classes.image,style:i.imageStyle,onMousedown:n[1]||(n[1]=ye((function(){}),["prevent"]))},null,46,["crossorigin","src"])],2),E("div",{class:i.classes.foreground,style:i.boundariesStyle},null,6),Zt((k(),z(st(e.stencilComponent),Qt({ref:"stencil",image:i.image,coordinates:s.coordinates,"stencil-coordinates":i.stencilCoordinates,transitions:i.transitionsOptions},e.stencilProps,{onResize:i.onResize,onResizeEnd:i.onResizeEnd,onMove:i.onMove,onMoveEnd:i.onMoveEnd}),null,16,["image","coordinates","stencil-coordinates","transitions","onResize","onResizeEnd","onMove","onMoveEnd"])),[[Gt,s.imageLoaded]]),e.canvas?(k(),z("canvas",sn,null,512)):It("",!0),e.canvas?(k(),z("canvas",an,null,512)):It("",!0)]})),_:1},8,["class","wheel-resize","touch-resize","touch-move","mouse-move","onMove","onResize"]))],6)],2)};export{pn as _,rn as f};
