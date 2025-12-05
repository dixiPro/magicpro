import{q as V,r as k,s as J,x as U,y as B,z as R,A as Y,b as W,o as z,I,N as G,j as D,e as P,k as L,l as H,d as _,h as Z,_ as K,F as Q,O as ee,T as X,P as te,Q as ne,R as ie,S as re,U as oe,V as ae,W as se,X as pe,Y as le,Z as ce,$ as de,a0 as ue}from"./ToastConfirm-Dx9X-4_N.js";import{s as ge}from"./index-C-hXeCQ3.js";import{a as j,M as me,c as fe}from"./ModalWindow-BTZBOPaq.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";var S={exports:{}},A;function he(){return A||(A=1,(function(x,$){ace.define("ace/mode/css_highlight_rules",["require","exports","module","ace/lib/oop","ace/lib/lang","ace/mode/text_highlight_rules"],function(p,g,h){var m=p("../lib/oop");p("../lib/lang");var f=p("./text_highlight_rules").TextHighlightRules,d=g.supportType="align-content|align-items|align-self|all|animation|animation-delay|animation-direction|animation-duration|animation-fill-mode|animation-iteration-count|animation-name|animation-play-state|animation-timing-function|backface-visibility|background|background-attachment|background-blend-mode|background-clip|background-color|background-image|background-origin|background-position|background-repeat|background-size|border|border-bottom|border-bottom-color|border-bottom-left-radius|border-bottom-right-radius|border-bottom-style|border-bottom-width|border-collapse|border-color|border-image|border-image-outset|border-image-repeat|border-image-slice|border-image-source|border-image-width|border-left|border-left-color|border-left-style|border-left-width|border-radius|border-right|border-right-color|border-right-style|border-right-width|border-spacing|border-style|border-top|border-top-color|border-top-left-radius|border-top-right-radius|border-top-style|border-top-width|border-width|bottom|box-shadow|box-sizing|caption-side|clear|clip|color|column-count|column-fill|column-gap|column-rule|column-rule-color|column-rule-style|column-rule-width|column-span|column-width|columns|content|counter-increment|counter-reset|cursor|direction|display|empty-cells|filter|flex|flex-basis|flex-direction|flex-flow|flex-grow|flex-shrink|flex-wrap|float|font|font-family|font-size|font-size-adjust|font-stretch|font-style|font-variant|font-weight|hanging-punctuation|height|justify-content|left|letter-spacing|line-height|list-style|list-style-image|list-style-position|list-style-type|margin|margin-bottom|margin-left|margin-right|margin-top|max-height|max-width|max-zoom|min-height|min-width|min-zoom|nav-down|nav-index|nav-left|nav-right|nav-up|opacity|order|outline|outline-color|outline-offset|outline-style|outline-width|overflow|overflow-x|overflow-y|padding|padding-bottom|padding-left|padding-right|padding-top|page-break-after|page-break-before|page-break-inside|perspective|perspective-origin|position|quotes|resize|right|tab-size|table-layout|text-align|text-align-last|text-decoration|text-decoration-color|text-decoration-line|text-decoration-style|text-indent|text-justify|text-overflow|text-shadow|text-transform|top|transform|transform-origin|transform-style|transition|transition-delay|transition-duration|transition-property|transition-timing-function|unicode-bidi|user-select|user-zoom|vertical-align|visibility|white-space|width|word-break|word-spacing|word-wrap|z-index",s=g.supportFunction="rgb|rgba|url|attr|counter|counters",a=g.supportConstant="absolute|after-edge|after|all-scroll|all|alphabetic|always|antialiased|armenian|auto|avoid-column|avoid-page|avoid|balance|baseline|before-edge|before|below|bidi-override|block-line-height|block|bold|bolder|border-box|both|bottom|box|break-all|break-word|capitalize|caps-height|caption|center|central|char|circle|cjk-ideographic|clone|close-quote|col-resize|collapse|column|consider-shifts|contain|content-box|cover|crosshair|cubic-bezier|dashed|decimal-leading-zero|decimal|default|disabled|disc|disregard-shifts|distribute-all-lines|distribute-letter|distribute-space|distribute|dotted|double|e-resize|ease-in|ease-in-out|ease-out|ease|ellipsis|end|exclude-ruby|flex-end|flex-start|fill|fixed|georgian|glyphs|grid-height|groove|hand|hanging|hebrew|help|hidden|hiragana-iroha|hiragana|horizontal|icon|ideograph-alpha|ideograph-numeric|ideograph-parenthesis|ideograph-space|ideographic|inactive|include-ruby|inherit|initial|inline-block|inline-box|inline-line-height|inline-table|inline|inset|inside|inter-ideograph|inter-word|invert|italic|justify|katakana-iroha|katakana|keep-all|last|left|lighter|line-edge|line-through|line|linear|list-item|local|loose|lower-alpha|lower-greek|lower-latin|lower-roman|lowercase|lr-tb|ltr|mathematical|max-height|max-size|medium|menu|message-box|middle|move|n-resize|ne-resize|newspaper|no-change|no-close-quote|no-drop|no-open-quote|no-repeat|none|normal|not-allowed|nowrap|nw-resize|oblique|open-quote|outset|outside|overline|padding-box|page|pointer|pre-line|pre-wrap|pre|preserve-3d|progress|relative|repeat-x|repeat-y|repeat|replaced|reset-size|ridge|right|round|row-resize|rtl|s-resize|scroll|se-resize|separate|slice|small-caps|small-caption|solid|space|square|start|static|status-bar|step-end|step-start|steps|stretch|strict|sub|super|sw-resize|table-caption|table-cell|table-column-group|table-column|table-footer-group|table-header-group|table-row-group|table-row|table|tb-rl|text-after-edge|text-before-edge|text-bottom|text-size|text-top|text|thick|thin|transparent|underline|upper-alpha|upper-latin|upper-roman|uppercase|use-script|vertical-ideographic|vertical-text|visible|w-resize|wait|whitespace|z-index|zero|zoom",c=g.supportConstantColor="aliceblue|antiquewhite|aqua|aquamarine|azure|beige|bisque|black|blanchedalmond|blue|blueviolet|brown|burlywood|cadetblue|chartreuse|chocolate|coral|cornflowerblue|cornsilk|crimson|cyan|darkblue|darkcyan|darkgoldenrod|darkgray|darkgreen|darkgrey|darkkhaki|darkmagenta|darkolivegreen|darkorange|darkorchid|darkred|darksalmon|darkseagreen|darkslateblue|darkslategray|darkslategrey|darkturquoise|darkviolet|deeppink|deepskyblue|dimgray|dimgrey|dodgerblue|firebrick|floralwhite|forestgreen|fuchsia|gainsboro|ghostwhite|gold|goldenrod|gray|green|greenyellow|grey|honeydew|hotpink|indianred|indigo|ivory|khaki|lavender|lavenderblush|lawngreen|lemonchiffon|lightblue|lightcoral|lightcyan|lightgoldenrodyellow|lightgray|lightgreen|lightgrey|lightpink|lightsalmon|lightseagreen|lightskyblue|lightslategray|lightslategrey|lightsteelblue|lightyellow|lime|limegreen|linen|magenta|maroon|mediumaquamarine|mediumblue|mediumorchid|mediumpurple|mediumseagreen|mediumslateblue|mediumspringgreen|mediumturquoise|mediumvioletred|midnightblue|mintcream|mistyrose|moccasin|navajowhite|navy|oldlace|olive|olivedrab|orange|orangered|orchid|palegoldenrod|palegreen|paleturquoise|palevioletred|papayawhip|peachpuff|peru|pink|plum|powderblue|purple|rebeccapurple|red|rosybrown|royalblue|saddlebrown|salmon|sandybrown|seagreen|seashell|sienna|silver|skyblue|slateblue|slategray|slategrey|snow|springgreen|steelblue|tan|teal|thistle|tomato|turquoise|violet|wheat|white|whitesmoke|yellow|yellowgreen",e=g.supportConstantFonts="arial|century|comic|courier|cursive|fantasy|garamond|georgia|helvetica|impact|lucida|symbol|system|tahoma|times|trebuchet|utopia|verdana|webdings|sans-serif|serif|monospace",o=g.numRe="\\-?(?:(?:[0-9]+(?:\\.[0-9]+)?)|(?:\\.[0-9]+))",t=g.pseudoElements="(\\:+)\\b(after|before|first-letter|first-line|moz-selection|selection)\\b",r=g.pseudoClasses="(:)\\b(active|checked|disabled|empty|enabled|first-child|first-of-type|focus|hover|indeterminate|invalid|last-child|last-of-type|link|not|nth-child|nth-last-child|nth-last-of-type|nth-of-type|only-child|only-of-type|required|root|target|valid|visited)\\b",i=function(){var n=this.createKeywordMapper({"support.function":s,"support.constant":a,"support.type":d,"support.constant.color":c,"support.constant.fonts":e},"text",!0);this.$rules={start:[{include:["strings","url","comments"]},{token:"paren.lparen",regex:"\\{",next:"ruleset"},{token:"paren.rparen",regex:"\\}"},{token:"string",regex:"@(?!viewport)",next:"media"},{token:"keyword",regex:"#[a-z0-9-_]+"},{token:"keyword",regex:"%"},{token:"variable",regex:"\\.[a-z0-9-_]+"},{token:"string",regex:":[a-z0-9-_]+"},{token:"constant.numeric",regex:o},{token:"constant",regex:"[a-z0-9-_]+"},{caseInsensitive:!0}],media:[{include:["strings","url","comments"]},{token:"paren.lparen",regex:"\\{",next:"start"},{token:"paren.rparen",regex:"\\}",next:"start"},{token:"string",regex:";",next:"start"},{token:"keyword",regex:"(?:media|supports|document|charset|import|namespace|media|supports|document|page|font|keyframes|viewport|counter-style|font-feature-values|swash|ornaments|annotation|stylistic|styleset|character-variant)"}],comments:[{token:"comment",regex:"\\/\\*",push:[{token:"comment",regex:"\\*\\/",next:"pop"},{defaultToken:"comment"}]}],ruleset:[{regex:"-(webkit|ms|moz|o)-",token:"text"},{token:"punctuation.operator",regex:"[:;]"},{token:"paren.rparen",regex:"\\}",next:"start"},{include:["strings","url","comments"]},{token:["constant.numeric","keyword"],regex:"("+o+")(ch|cm|deg|em|ex|fr|gd|grad|Hz|in|kHz|mm|ms|pc|pt|px|rad|rem|s|turn|vh|vmax|vmin|vm|vw|%)"},{token:"constant.numeric",regex:o},{token:"constant.numeric",regex:"#[a-f0-9]{6}"},{token:"constant.numeric",regex:"#[a-f0-9]{3}"},{token:["punctuation","entity.other.attribute-name.pseudo-element.css"],regex:t},{token:["punctuation","entity.other.attribute-name.pseudo-class.css"],regex:r},{include:"url"},{token:n,regex:"\\-?[a-zA-Z_][a-zA-Z0-9_\\-]*"},{token:"paren.lparen",regex:"\\{"},{caseInsensitive:!0}],url:[{token:"support.function",regex:"(?:url(:?-prefix)?|domain|regexp)\\(",push:[{token:"support.function",regex:"\\)",next:"pop"},{defaultToken:"string"}]}],strings:[{token:"string.start",regex:"'",push:[{token:"string.end",regex:"'|$",next:"pop"},{include:"escapes"},{token:"constant.language.escape",regex:/\\$/,consumeLineEnd:!0},{defaultToken:"string"}]},{token:"string.start",regex:'"',push:[{token:"string.end",regex:'"|$',next:"pop"},{include:"escapes"},{token:"constant.language.escape",regex:/\\$/,consumeLineEnd:!0},{defaultToken:"string"}]}],escapes:[{token:"constant.language.escape",regex:/\\([a-fA-F\d]{1,6}|[^a-fA-F\d])/}]},this.normalizeRules()};m.inherits(i,f),g.CssHighlightRules=i}),ace.define("ace/mode/matching_brace_outdent",["require","exports","module","ace/range"],function(p,g,h){var m=p("../range").Range,f=function(){};(function(){this.checkOutdent=function(d,s){return/^\s+$/.test(d)?/^\s*\}/.test(s):!1},this.autoOutdent=function(d,s){var a=d.getLine(s),c=a.match(/^(\s*\})/);if(!c)return 0;var e=c[1].length,o=d.findMatchingBracket({row:s,column:e});if(!o||o.row==s)return 0;var t=this.$getIndent(d.getLine(o.row));d.replace(new m(s,0,s,e-1),t)},this.$getIndent=function(d){return d.match(/^\s*/)[0]}}).call(f.prototype),g.MatchingBraceOutdent=f}),ace.define("ace/mode/css_completions",["require","exports","module"],function(p,g,h){var m={background:{"#$0":1},"background-color":{"#$0":1,transparent:1,fixed:1},"background-image":{"url('/$0')":1},"background-repeat":{repeat:1,"repeat-x":1,"repeat-y":1,"no-repeat":1,inherit:1},"background-position":{bottom:2,center:2,left:2,right:2,top:2,inherit:2},"background-attachment":{scroll:1,fixed:1},"background-size":{cover:1,contain:1},"background-clip":{"border-box":1,"padding-box":1,"content-box":1},"background-origin":{"border-box":1,"padding-box":1,"content-box":1},border:{"solid $0":1,"dashed $0":1,"dotted $0":1,"#$0":1},"border-color":{"#$0":1},"border-style":{solid:2,dashed:2,dotted:2,double:2,groove:2,hidden:2,inherit:2,inset:2,none:2,outset:2,ridged:2},"border-collapse":{collapse:1,separate:1},bottom:{px:1,em:1,"%":1},clear:{left:1,right:1,both:1,none:1},color:{"#$0":1,"rgb(#$00,0,0)":1},cursor:{default:1,pointer:1,move:1,text:1,wait:1,help:1,progress:1,"n-resize":1,"ne-resize":1,"e-resize":1,"se-resize":1,"s-resize":1,"sw-resize":1,"w-resize":1,"nw-resize":1},display:{none:1,block:1,inline:1,"inline-block":1,"table-cell":1},"empty-cells":{show:1,hide:1},float:{left:1,right:1,none:1},"font-family":{Arial:2,"Comic Sans MS":2,Consolas:2,"Courier New":2,Courier:2,Georgia:2,Monospace:2,"Sans-Serif":2,"Segoe UI":2,Tahoma:2,"Times New Roman":2,"Trebuchet MS":2,Verdana:1},"font-size":{px:1,em:1,"%":1},"font-weight":{bold:1,normal:1},"font-style":{italic:1,normal:1},"font-variant":{normal:1,"small-caps":1},height:{px:1,em:1,"%":1},left:{px:1,em:1,"%":1},"letter-spacing":{normal:1},"line-height":{normal:1},"list-style-type":{none:1,disc:1,circle:1,square:1,decimal:1,"decimal-leading-zero":1,"lower-roman":1,"upper-roman":1,"lower-greek":1,"lower-latin":1,"upper-latin":1,georgian:1,"lower-alpha":1,"upper-alpha":1},margin:{px:1,em:1,"%":1},"margin-right":{px:1,em:1,"%":1},"margin-left":{px:1,em:1,"%":1},"margin-top":{px:1,em:1,"%":1},"margin-bottom":{px:1,em:1,"%":1},"max-height":{px:1,em:1,"%":1},"max-width":{px:1,em:1,"%":1},"min-height":{px:1,em:1,"%":1},"min-width":{px:1,em:1,"%":1},overflow:{hidden:1,visible:1,auto:1,scroll:1},"overflow-x":{hidden:1,visible:1,auto:1,scroll:1},"overflow-y":{hidden:1,visible:1,auto:1,scroll:1},padding:{px:1,em:1,"%":1},"padding-top":{px:1,em:1,"%":1},"padding-right":{px:1,em:1,"%":1},"padding-bottom":{px:1,em:1,"%":1},"padding-left":{px:1,em:1,"%":1},"page-break-after":{auto:1,always:1,avoid:1,left:1,right:1},"page-break-before":{auto:1,always:1,avoid:1,left:1,right:1},position:{absolute:1,relative:1,fixed:1,static:1},right:{px:1,em:1,"%":1},"table-layout":{fixed:1,auto:1},"text-decoration":{none:1,underline:1,"line-through":1,blink:1},"text-align":{left:1,right:1,center:1,justify:1},"text-transform":{capitalize:1,uppercase:1,lowercase:1,none:1},top:{px:1,em:1,"%":1},"vertical-align":{top:1,bottom:1},visibility:{hidden:1,visible:1},"white-space":{nowrap:1,normal:1,pre:1,"pre-line":1,"pre-wrap":1},width:{px:1,em:1,"%":1},"word-spacing":{normal:1},filter:{"alpha(opacity=$0100)":1},"text-shadow":{"$02px 2px 2px #777":1},"text-overflow":{"ellipsis-word":1,clip:1,ellipsis:1},"-moz-border-radius":1,"-moz-border-radius-topright":1,"-moz-border-radius-bottomright":1,"-moz-border-radius-topleft":1,"-moz-border-radius-bottomleft":1,"-webkit-border-radius":1,"-webkit-border-top-right-radius":1,"-webkit-border-top-left-radius":1,"-webkit-border-bottom-right-radius":1,"-webkit-border-bottom-left-radius":1,"-moz-box-shadow":1,"-webkit-box-shadow":1,transform:{"rotate($00deg)":1,"skew($00deg)":1},"-moz-transform":{"rotate($00deg)":1,"skew($00deg)":1},"-webkit-transform":{"rotate($00deg)":1,"skew($00deg)":1}},f=function(){};(function(){this.completionsDefined=!1,this.defineCompletions=function(){if(document){var d=document.createElement("c").style;for(var s in d)if(typeof d[s]=="string"){var a=s.replace(/[A-Z]/g,function(c){return"-"+c.toLowerCase()});m.hasOwnProperty(a)||(m[a]=1)}}this.completionsDefined=!0},this.getCompletions=function(d,s,a,c){if(this.completionsDefined||this.defineCompletions(),d==="ruleset"||s.$mode.$id=="ace/mode/scss"){var e=s.getLine(a.row).substr(0,a.column),o=/\([^)]*$/.test(e);return o&&(e=e.substr(e.lastIndexOf("(")+1)),/:[^;]+$/.test(e)?this.getPropertyValueCompletions(d,s,a,c):this.getPropertyCompletions(d,s,a,c,o)}return[]},this.getPropertyCompletions=function(d,s,a,c,e){e=e||!1;var o=Object.keys(m);return o.map(function(t){return{caption:t,snippet:t+": $0"+(e?"":";"),meta:"property",score:1e6}})},this.getPropertyValueCompletions=function(d,s,a,c){var e=s.getLine(a.row).substr(0,a.column),o=(/([\w\-]+):[^:]*$/.exec(e)||{})[1];if(!o)return[];var t=[];return o in m&&typeof m[o]=="object"&&(t=Object.keys(m[o])),t.map(function(r){return{caption:r,snippet:r,meta:"property value",score:1e6}})}}).call(f.prototype),g.CssCompletions=f}),ace.define("ace/mode/behaviour/css",["require","exports","module","ace/lib/oop","ace/mode/behaviour","ace/mode/behaviour/cstyle","ace/token_iterator"],function(p,g,h){var m=p("../../lib/oop");p("../behaviour").Behaviour;var f=p("./cstyle").CstyleBehaviour,d=p("../../token_iterator").TokenIterator,s=function(){this.inherit(f),this.add("colon","insertion",function(a,c,e,o,t){if(t===":"&&e.selection.isEmpty()){var r=e.getCursorPosition(),i=new d(o,r.row,r.column),n=i.getCurrentToken();if(n&&n.value.match(/\s+/)&&(n=i.stepBackward()),n&&n.type==="support.type"){var l=o.doc.getLine(r.row),u=l.substring(r.column,r.column+1);if(u===":")return{text:"",selection:[1,1]};if(/^(\s+[^;]|\s*$)/.test(l.substring(r.column)))return{text:":;",selection:[1,1]}}}}),this.add("colon","deletion",function(a,c,e,o,t){var r=o.doc.getTextRange(t);if(!t.isMultiLine()&&r===":"){var i=e.getCursorPosition(),n=new d(o,i.row,i.column),l=n.getCurrentToken();if(l&&l.value.match(/\s+/)&&(l=n.stepBackward()),l&&l.type==="support.type"){var u=o.doc.getLine(t.start.row),b=u.substring(t.end.column,t.end.column+1);if(b===";")return t.end.column++,t}}}),this.add("semicolon","insertion",function(a,c,e,o,t){if(t===";"&&e.selection.isEmpty()){var r=e.getCursorPosition(),i=o.doc.getLine(r.row),n=i.substring(r.column,r.column+1);if(n===";")return{text:"",selection:[1,1]}}}),this.add("!important","insertion",function(a,c,e,o,t){if(t==="!"&&e.selection.isEmpty()){var r=e.getCursorPosition(),i=o.doc.getLine(r.row);if(/^\s*(;|}|$)/.test(i.substring(r.column)))return{text:"!important",selection:[10,10]}}})};m.inherits(s,f),g.CssBehaviour=s}),ace.define("ace/mode/folding/cstyle",["require","exports","module","ace/lib/oop","ace/range","ace/mode/folding/fold_mode"],function(p,g,h){var m=p("../../lib/oop"),f=p("../../range").Range,d=p("./fold_mode").FoldMode,s=g.FoldMode=function(a){a&&(this.foldingStartMarker=new RegExp(this.foldingStartMarker.source.replace(/\|[^|]*?$/,"|"+a.start)),this.foldingStopMarker=new RegExp(this.foldingStopMarker.source.replace(/\|[^|]*?$/,"|"+a.end)))};m.inherits(s,d),(function(){this.foldingStartMarker=/([\{\[\(])[^\}\]\)]*$|^\s*(\/\*)/,this.foldingStopMarker=/^[^\[\{\(]*([\}\]\)])|^[\s\*]*(\*\/)/,this.singleLineBlockCommentRe=/^\s*(\/\*).*\*\/\s*$/,this.tripleStarBlockCommentRe=/^\s*(\/\*\*\*).*\*\/\s*$/,this.startRegionRe=/^\s*(\/\*|\/\/)#?region\b/,this._getFoldWidgetBase=this.getFoldWidget,this.getFoldWidget=function(a,c,e){var o=a.getLine(e);if(this.singleLineBlockCommentRe.test(o)&&!this.startRegionRe.test(o)&&!this.tripleStarBlockCommentRe.test(o))return"";var t=this._getFoldWidgetBase(a,c,e);return!t&&this.startRegionRe.test(o)?"start":t},this.getFoldWidgetRange=function(a,c,e,o){var t=a.getLine(e);if(this.startRegionRe.test(t))return this.getCommentRegionBlock(a,t,e);var n=t.match(this.foldingStartMarker);if(n){var r=n.index;if(n[1])return this.openingBracketBlock(a,n[1],e,r);var i=a.getCommentFoldRange(e,r+n[0].length,1);return i&&!i.isMultiLine()&&(o?i=this.getSectionRange(a,e):c!="all"&&(i=null)),i}if(c!=="markbegin"){var n=t.match(this.foldingStopMarker);if(n){var r=n.index+n[0].length;return n[1]?this.closingBracketBlock(a,n[1],e,r):a.getCommentFoldRange(e,r,-1)}}},this.getSectionRange=function(a,c){var e=a.getLine(c),o=e.search(/\S/),t=c,r=e.length;c=c+1;for(var i=c,n=a.getLength();++c<n;){e=a.getLine(c);var l=e.search(/\S/);if(l!==-1){if(o>l)break;var u=this.getFoldWidgetRange(a,"all",c);if(u){if(u.start.row<=t)break;if(u.isMultiLine())c=u.end.row;else if(o==l)break}i=c}}return new f(t,r,i,a.getLine(i).length)},this.getCommentRegionBlock=function(a,c,e){for(var o=c.search(/\s*$/),t=a.getLength(),r=e,i=/^\s*(?:\/\*|\/\/|--)#?(end)?region\b/,n=1;++e<t;){c=a.getLine(e);var l=i.exec(c);if(l&&(l[1]?n--:n++,!n))break}var u=e;if(u>r)return new f(r,o,u,c.length)}}).call(s.prototype)}),ace.define("ace/mode/css",["require","exports","module","ace/lib/oop","ace/mode/text","ace/mode/css_highlight_rules","ace/mode/matching_brace_outdent","ace/worker/worker_client","ace/mode/css_completions","ace/mode/behaviour/css","ace/mode/folding/cstyle"],function(p,g,h){var m=p("../lib/oop"),f=p("./text").Mode,d=p("./css_highlight_rules").CssHighlightRules,s=p("./matching_brace_outdent").MatchingBraceOutdent,a=p("../worker/worker_client").WorkerClient,c=p("./css_completions").CssCompletions,e=p("./behaviour/css").CssBehaviour,o=p("./folding/cstyle").FoldMode,t=function(){this.HighlightRules=d,this.$outdent=new s,this.$behaviour=new e,this.$completer=new c,this.foldingRules=new o};m.inherits(t,f),(function(){this.foldingRules="cStyle",this.blockComment={start:"/*",end:"*/"},this.getNextLineIndent=function(r,i,n){var l=this.$getIndent(i),u=this.getTokenizer().getLineTokens(i,r).tokens;if(u.length&&u[u.length-1].type=="comment")return l;var b=i.match(/^.*\{\s*$/);return b&&(l+=n),l},this.checkOutdent=function(r,i,n){return this.$outdent.checkOutdent(i,n)},this.autoOutdent=function(r,i,n){this.$outdent.autoOutdent(i,n)},this.getCompletions=function(r,i,n,l){return this.$completer.getCompletions(r,i,n,l)},this.createWorker=function(r){var i=new a(["ace"],"ace/mode/css_worker","Worker");return i.attachToDocument(r.getDocument()),i.on("annotate",function(n){r.setAnnotations(n.data)}),i.on("terminate",function(){r.clearAnnotations()}),i},this.$id="ace/mode/css",this.snippetFileId="ace/snippets/css"}).call(t.prototype),g.Mode=t}),(function(){ace.require(["ace/mode/css"],function(p){x&&(x.exports=p)})})()})(S)),S.exports}he();var M={exports:{}},N;function be(){return N||(N=1,(function(x,$){ace.define("ace/snippets/css.snippets",["require","exports","module"],function(p,g,h){h.exports=`snippet .
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
`}),ace.define("ace/snippets/css",["require","exports","module","ace/snippets/css.snippets"],function(p,g,h){g.snippetText=p("./css.snippets"),g.scope="css"}),(function(){ace.require(["ace/snippets/css"],function(p){x&&(x.exports=p)})})()})(M)),M.exports}be();var F={exports:{}},q;function xe(){return q||(q=1,(function(x,$){ace.define("ace/mode/jsdoc_comment_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/text_highlight_rules"],function(p,g,h){var m=p("../lib/oop"),f=p("./text_highlight_rules").TextHighlightRules,d=function(){this.$rules={start:[{token:["comment.doc.tag","comment.doc.text","lparen.doc"],regex:"(@(?:param|member|typedef|property|namespace|var|const|callback))(\\s*)({)",push:[{token:"lparen.doc",regex:"{",push:[{include:"doc-syntax"},{token:"rparen.doc",regex:"}|(?=$)",next:"pop"}]},{token:["rparen.doc","text.doc","variable.parameter.doc","lparen.doc","variable.parameter.doc","rparen.doc"],regex:/(})(\s*)(?:([\w=:\/\.]+)|(?:(\[)([\w=:\/\.\-\'\" ]+)(\])))/,next:"pop"},{token:"rparen.doc",regex:"}|(?=$)",next:"pop"},{include:"doc-syntax"},{defaultToken:"text.doc"}]},{token:["comment.doc.tag","text.doc","lparen.doc"],regex:"(@(?:returns?|yields|type|this|suppress|public|protected|private|package|modifies|implements|external|exception|throws|enum|define|extends))(\\s*)({)",push:[{token:"lparen.doc",regex:"{",push:[{include:"doc-syntax"},{token:"rparen.doc",regex:"}|(?=$)",next:"pop"}]},{token:"rparen.doc",regex:"}|(?=$)",next:"pop"},{include:"doc-syntax"},{defaultToken:"text.doc"}]},{token:["comment.doc.tag","text.doc","variable.parameter.doc"],regex:'(@(?:alias|memberof|instance|module|name|lends|namespace|external|this|template|requires|param|implements|function|extends|typedef|mixes|constructor|var|memberof\\!|event|listens|exports|class|constructs|interface|emits|fires|throws|const|callback|borrows|augments))(\\s+)(\\w[\\w#.:/~"\\-]*)?'},{token:["comment.doc.tag","text.doc","variable.parameter.doc"],regex:"(@method)(\\s+)(\\w[\\w.\\(\\)]*)"},{token:"comment.doc.tag",regex:"@access\\s+(?:private|public|protected)"},{token:"comment.doc.tag",regex:"@kind\\s+(?:class|constant|event|external|file|function|member|mixin|module|namespace|typedef)"},{token:"comment.doc.tag",regex:"@\\w+(?=\\s|$)"},d.getTagRule(),{defaultToken:"comment.doc.body",caseInsensitive:!0}],"doc-syntax":[{token:"operator.doc",regex:/[|:]/},{token:"paren.doc",regex:/[\[\]]/}]},this.normalizeRules()};m.inherits(d,f),d.getTagRule=function(s){return{token:"comment.doc.tag.storage.type",regex:"\\b(?:TODO|FIXME|XXX|HACK)\\b"}},d.getStartRule=function(s){return{token:"comment.doc",regex:/\/\*\*(?!\/)/,next:s}},d.getEndRule=function(s){return{token:"comment.doc",regex:"\\*\\/",next:s}},g.JsDocCommentHighlightRules=d}),ace.define("ace/mode/javascript_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/jsdoc_comment_highlight_rules","ace/mode/text_highlight_rules"],function(p,g,h){var m=p("../lib/oop"),f=p("./jsdoc_comment_highlight_rules").JsDocCommentHighlightRules,d=p("./text_highlight_rules").TextHighlightRules,s="[a-zA-Z\\$_¡-￿][a-zA-Z\\d\\$_¡-￿]*",a=function(o){var t={"variable.language":"Array|Boolean|Date|Function|Iterator|Number|Object|RegExp|String|Proxy|Symbol|Namespace|QName|XML|XMLList|ArrayBuffer|Float32Array|Float64Array|Int16Array|Int32Array|Int8Array|Uint16Array|Uint32Array|Uint8Array|Uint8ClampedArray|Error|EvalError|InternalError|RangeError|ReferenceError|StopIteration|SyntaxError|TypeError|URIError|decodeURI|decodeURIComponent|encodeURI|encodeURIComponent|eval|isFinite|isNaN|parseFloat|parseInt|JSON|Math|this|arguments|prototype|window|document",keyword:"const|yield|import|get|set|async|await|break|case|catch|continue|default|delete|do|else|finally|for|if|in|of|instanceof|new|return|switch|throw|try|typeof|let|var|while|with|debugger|__parent__|__count__|escape|unescape|with|__proto__|class|enum|extends|super|export|implements|private|public|interface|package|protected|static|constructor","storage.type":"const|let|var|function","constant.language":"null|Infinity|NaN|undefined","support.function":"alert","constant.language.boolean":"true|false"},r=this.createKeywordMapper(t,"identifier"),i="case|do|else|finally|in|instanceof|return|throw|try|typeof|yield|void",n="\\\\(?:x[0-9a-fA-F]{2}|u[0-9a-fA-F]{4}|u{[0-9a-fA-F]{1,6}}|[0-2][0-7]{0,2}|3[0-7][0-7]?|[4-7][0-7]?|.)",l="(function)(\\s*)(\\*?)",u={token:["identifier","text","paren.lparen"],regex:"(\\b(?!"+Object.values(t).join("|")+"\\b)"+s+")(\\s*)(\\()"};this.$rules={no_regex:[f.getStartRule("doc-start"),e("no_regex"),u,{token:"string",regex:"'(?=.)",next:"qstring"},{token:"string",regex:'"(?=.)',next:"qqstring"},{token:"constant.numeric",regex:/0(?:[xX][0-9a-fA-F]+|[oO][0-7]+|[bB][01]+)\b/},{token:"constant.numeric",regex:/(?:\d\d*(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+\b)?/},{token:["entity.name.function","text","keyword.operator","text","storage.type","text","storage.type","text","paren.lparen"],regex:"("+s+")(\\s*)(=)(\\s*)"+l+"(\\s*)(\\()",next:"function_arguments"},{token:["storage.type","text","storage.type","text","text","entity.name.function","text","paren.lparen"],regex:"(function)(?:(?:(\\s*)(\\*)(\\s*))|(\\s+))("+s+")(\\s*)(\\()",next:"function_arguments"},{token:["entity.name.function","text","punctuation.operator","text","storage.type","text","storage.type","text","paren.lparen"],regex:"("+s+")(\\s*)(:)(\\s*)"+l+"(\\s*)(\\()",next:"function_arguments"},{token:["text","text","storage.type","text","storage.type","text","paren.lparen"],regex:"(:)(\\s*)"+l+"(\\s*)(\\()",next:"function_arguments"},{token:"keyword",regex:`from(?=\\s*('|"))`},{token:"keyword",regex:"(?:"+i+")\\b",next:"start"},{token:"support.constant",regex:/that\b/},{token:["storage.type","punctuation.operator","support.function.firebug"],regex:/(console)(\.)(warn|info|log|error|debug|time|trace|timeEnd|assert)\b/},{token:r,regex:s},{token:"punctuation.operator",regex:/[.](?![.])/,next:"property"},{token:"storage.type",regex:/=>/,next:"start"},{token:"keyword.operator",regex:/--|\+\+|\.{3}|===|==|=|!=|!==|<+=?|>+=?|!|&&|\|\||\?:|[!$%&*+\-~\/^]=?/,next:"start"},{token:"punctuation.operator",regex:/[?:,;.]/,next:"start"},{token:"paren.lparen",regex:/[\[({]/,next:"start"},{token:"paren.rparen",regex:/[\])}]/},{token:"comment",regex:/^#!.*$/}],property:[{token:"text",regex:"\\s+"},{token:"keyword.operator",regex:/=/},{token:["storage.type","text","storage.type","text","paren.lparen"],regex:l+"(\\s*)(\\()",next:"function_arguments"},{token:["storage.type","text","storage.type","text","text","entity.name.function","text","paren.lparen"],regex:"(function)(?:(?:(\\s*)(\\*)(\\s*))|(\\s+))(\\w+)(\\s*)(\\()",next:"function_arguments"},{token:"punctuation.operator",regex:/[.](?![.])/},{token:"support.function",regex:"prototype"},{token:"support.function",regex:/(s(?:h(?:ift|ow(?:Mod(?:elessDialog|alDialog)|Help))|croll(?:X|By(?:Pages|Lines)?|Y|To)?|t(?:op|rike)|i(?:n|zeToContent|debar|gnText)|ort|u(?:p|b(?:str(?:ing)?)?)|pli(?:ce|t)|e(?:nd|t(?:Re(?:sizable|questHeader)|M(?:i(?:nutes|lliseconds)|onth)|Seconds|Ho(?:tKeys|urs)|Year|Cursor|Time(?:out)?|Interval|ZOptions|Date|UTC(?:M(?:i(?:nutes|lliseconds)|onth)|Seconds|Hours|Date|FullYear)|FullYear|Active)|arch)|qrt|lice|avePreferences|mall)|h(?:ome|andleEvent)|navigate|c(?:har(?:CodeAt|At)|o(?:s|n(?:cat|textual|firm)|mpile)|eil|lear(?:Timeout|Interval)?|a(?:ptureEvents|ll)|reate(?:StyleSheet|Popup|EventObject))|t(?:o(?:GMTString|S(?:tring|ource)|U(?:TCString|pperCase)|Lo(?:caleString|werCase))|est|a(?:n|int(?:Enabled)?))|i(?:s(?:NaN|Finite)|ndexOf|talics)|d(?:isableExternalCapture|ump|etachEvent)|u(?:n(?:shift|taint|escape|watch)|pdateCommands)|j(?:oin|avaEnabled)|p(?:o(?:p|w)|ush|lugins.refresh|a(?:ddings|rse(?:Int|Float)?)|r(?:int|ompt|eference))|e(?:scape|nableExternalCapture|val|lementFromPoint|x(?:p|ec(?:Script|Command)?))|valueOf|UTC|queryCommand(?:State|Indeterm|Enabled|Value)|f(?:i(?:nd|lter|le(?:ModifiedDate|Size|CreatedDate|UpdatedDate)|xed)|o(?:nt(?:size|color)|rward|rEach)|loor|romCharCode)|watch|l(?:ink|o(?:ad|g)|astIndexOf)|a(?:sin|nchor|cos|t(?:tachEvent|ob|an(?:2)?)|pply|lert|b(?:s|ort))|r(?:ou(?:nd|teEvents)|e(?:size(?:By|To)|calc|turnValue|place|verse|l(?:oad|ease(?:Capture|Events)))|andom)|g(?:o|et(?:ResponseHeader|M(?:i(?:nutes|lliseconds)|onth)|Se(?:conds|lection)|Hours|Year|Time(?:zoneOffset)?|Da(?:y|te)|UTC(?:M(?:i(?:nutes|lliseconds)|onth)|Seconds|Hours|Da(?:y|te)|FullYear)|FullYear|A(?:ttention|llResponseHeaders)))|m(?:in|ove(?:B(?:y|elow)|To(?:Absolute)?|Above)|ergeAttributes|a(?:tch|rgins|x))|b(?:toa|ig|o(?:ld|rderWidths)|link|ack))\b(?=\()/},{token:"support.function.dom",regex:/(s(?:ub(?:stringData|mit)|plitText|e(?:t(?:NamedItem|Attribute(?:Node)?)|lect))|has(?:ChildNodes|Feature)|namedItem|c(?:l(?:ick|o(?:se|neNode))|reate(?:C(?:omment|DATASection|aption)|T(?:Head|extNode|Foot)|DocumentFragment|ProcessingInstruction|E(?:ntityReference|lement)|Attribute))|tabIndex|i(?:nsert(?:Row|Before|Cell|Data)|tem)|open|delete(?:Row|C(?:ell|aption)|T(?:Head|Foot)|Data)|focus|write(?:ln)?|a(?:dd|ppend(?:Child|Data))|re(?:set|place(?:Child|Data)|move(?:NamedItem|Child|Attribute(?:Node)?)?)|get(?:NamedItem|Element(?:sBy(?:Name|TagName|ClassName)|ById)|Attribute(?:Node)?)|blur)\b(?=\()/},{token:"support.constant",regex:/(s(?:ystemLanguage|cr(?:ipts|ollbars|een(?:X|Y|Top|Left))|t(?:yle(?:Sheets)?|atus(?:Text|bar)?)|ibling(?:Below|Above)|ource|uffixes|e(?:curity(?:Policy)?|l(?:ection|f)))|h(?:istory|ost(?:name)?|as(?:h|Focus))|y|X(?:MLDocument|SLDocument)|n(?:ext|ame(?:space(?:s|URI)|Prop))|M(?:IN_VALUE|AX_VALUE)|c(?:haracterSet|o(?:n(?:structor|trollers)|okieEnabled|lorDepth|mp(?:onents|lete))|urrent|puClass|l(?:i(?:p(?:boardData)?|entInformation)|osed|asses)|alle(?:e|r)|rypto)|t(?:o(?:olbar|p)|ext(?:Transform|Indent|Decoration|Align)|ags)|SQRT(?:1_2|2)|i(?:n(?:ner(?:Height|Width)|put)|ds|gnoreCase)|zIndex|o(?:scpu|n(?:readystatechange|Line)|uter(?:Height|Width)|p(?:sProfile|ener)|ffscreenBuffering)|NEGATIVE_INFINITY|d(?:i(?:splay|alog(?:Height|Top|Width|Left|Arguments)|rectories)|e(?:scription|fault(?:Status|Ch(?:ecked|arset)|View)))|u(?:ser(?:Profile|Language|Agent)|n(?:iqueID|defined)|pdateInterval)|_content|p(?:ixelDepth|ort|ersonalbar|kcs11|l(?:ugins|atform)|a(?:thname|dding(?:Right|Bottom|Top|Left)|rent(?:Window|Layer)?|ge(?:X(?:Offset)?|Y(?:Offset)?))|r(?:o(?:to(?:col|type)|duct(?:Sub)?|mpter)|e(?:vious|fix)))|e(?:n(?:coding|abledPlugin)|x(?:ternal|pando)|mbeds)|v(?:isibility|endor(?:Sub)?|Linkcolor)|URLUnencoded|P(?:I|OSITIVE_INFINITY)|f(?:ilename|o(?:nt(?:Size|Family|Weight)|rmName)|rame(?:s|Element)|gColor)|E|whiteSpace|l(?:i(?:stStyleType|n(?:eHeight|kColor))|o(?:ca(?:tion(?:bar)?|lName)|wsrc)|e(?:ngth|ft(?:Context)?)|a(?:st(?:M(?:odified|atch)|Index|Paren)|yer(?:s|X)|nguage))|a(?:pp(?:MinorVersion|Name|Co(?:deName|re)|Version)|vail(?:Height|Top|Width|Left)|ll|r(?:ity|guments)|Linkcolor|bove)|r(?:ight(?:Context)?|e(?:sponse(?:XML|Text)|adyState))|global|x|m(?:imeTypes|ultiline|enubar|argin(?:Right|Bottom|Top|Left))|L(?:N(?:10|2)|OG(?:10E|2E))|b(?:o(?:ttom|rder(?:Width|RightWidth|BottomWidth|Style|Color|TopWidth|LeftWidth))|ufferDepth|elow|ackground(?:Color|Image)))\b/},{token:"identifier",regex:s},{regex:"",token:"empty",next:"no_regex"}],start:[f.getStartRule("doc-start"),e("start"),{token:"string.regexp",regex:"\\/",next:"regex"},{token:"text",regex:"\\s+|^$",next:"start"},{token:"empty",regex:"",next:"no_regex"}],regex:[{token:"regexp.keyword.operator",regex:"\\\\(?:u[\\da-fA-F]{4}|x[\\da-fA-F]{2}|.)"},{token:"string.regexp",regex:"/[sxngimy]*",next:"no_regex"},{token:"invalid",regex:/\{\d+\b,?\d*\}[+*]|[+*$^?][+*]|[$^][?]|\?{3,}/},{token:"constant.language.escape",regex:/\(\?[:=!]|\)|\{\d+\b,?\d*\}|[+*]\?|[()$^+*?.]/},{token:"constant.language.delimiter",regex:/\|/},{token:"constant.language.escape",regex:/\[\^?/,next:"regex_character_class"},{token:"empty",regex:"$",next:"no_regex"},{defaultToken:"string.regexp"}],regex_character_class:[{token:"regexp.charclass.keyword.operator",regex:"\\\\(?:u[\\da-fA-F]{4}|x[\\da-fA-F]{2}|.)"},{token:"constant.language.escape",regex:"]",next:"regex"},{token:"constant.language.escape",regex:"-"},{token:"empty",regex:"$",next:"no_regex"},{defaultToken:"string.regexp.charachterclass"}],default_parameter:[{token:"string",regex:"'(?=.)",push:[{token:"string",regex:"'|$",next:"pop"},{include:"qstring"}]},{token:"string",regex:'"(?=.)',push:[{token:"string",regex:'"|$',next:"pop"},{include:"qqstring"}]},{token:"constant.language",regex:"null|Infinity|NaN|undefined"},{token:"constant.numeric",regex:/0(?:[xX][0-9a-fA-F]+|[oO][0-7]+|[bB][01]+)\b/},{token:"constant.numeric",regex:/(?:\d\d*(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+\b)?/},{token:"punctuation.operator",regex:",",next:"function_arguments"},{token:"text",regex:"\\s+"},{token:"punctuation.operator",regex:"$"},{token:"empty",regex:"",next:"no_regex"}],function_arguments:[e("function_arguments"),{token:"variable.parameter",regex:s},{token:"punctuation.operator",regex:","},{token:"text",regex:"\\s+"},{token:"punctuation.operator",regex:"$"},{token:"empty",regex:"",next:"no_regex"}],qqstring:[{token:"constant.language.escape",regex:n},{token:"string",regex:"\\\\$",consumeLineEnd:!0},{token:"string",regex:'"|$',next:"no_regex"},{defaultToken:"string"}],qstring:[{token:"constant.language.escape",regex:n},{token:"string",regex:"\\\\$",consumeLineEnd:!0},{token:"string",regex:"'|$",next:"no_regex"},{defaultToken:"string"}]},(!o||!o.noES6)&&(this.$rules.no_regex.unshift({regex:"[{}]",onMatch:function(b,y,v){if(this.next=b=="{"?this.nextState:"",b=="{"&&v.length)v.unshift("start",y);else if(b=="}"&&v.length&&(v.shift(),this.next=v.shift(),this.next.indexOf("string")!=-1||this.next.indexOf("jsx")!=-1))return"paren.quasi.end";return b=="{"?"paren.lparen":"paren.rparen"},nextState:"start"},{token:"string.quasi.start",regex:/`/,push:[{token:"constant.language.escape",regex:n},{token:"paren.quasi.start",regex:/\${/,push:"start"},{token:"string.quasi.end",regex:/`/,next:"pop"},{defaultToken:"string.quasi"}]},{token:["variable.parameter","text"],regex:"("+s+")(\\s*)(?=\\=>)"},{token:"paren.lparen",regex:"(\\()(?=[^\\(]+\\s*=>)",next:"function_arguments"},{token:"variable.language",regex:"(?:(?:(?:Weak)?(?:Set|Map))|Promise)\\b"}),this.$rules.function_arguments.unshift({token:"keyword.operator",regex:"=",next:"default_parameter"},{token:"keyword.operator",regex:"\\.{3}"}),this.$rules.property.unshift({token:"support.function",regex:"(findIndex|repeat|startsWith|endsWith|includes|isSafeInteger|trunc|cbrt|log2|log10|sign|then|catch|finally|resolve|reject|race|any|all|allSettled|keys|entries|isInteger)\\b(?=\\()"},{token:"constant.language",regex:"(?:MAX_SAFE_INTEGER|MIN_SAFE_INTEGER|EPSILON)\\b"}),(!o||o.jsx!=!1)&&c.call(this)),this.embedRules(f,"doc-",[f.getEndRule("no_regex")]),this.normalizeRules()};m.inherits(a,d);function c(){var o=s.replace("\\d","\\d\\-"),t={onMatch:function(i,n,l){var u=i.charAt(1)=="/"?2:1;return u==1?(n!=this.nextState?l.unshift(this.next,this.nextState,0):l.unshift(this.next),l[2]++):u==2&&n==this.nextState&&(l[1]--,(!l[1]||l[1]<0)&&(l.shift(),l.shift())),[{type:"meta.tag.punctuation."+(u==1?"":"end-")+"tag-open.xml",value:i.slice(0,u)},{type:"meta.tag.tag-name.xml",value:i.substr(u)}]},regex:"</?(?:"+o+"|(?=>))",next:"jsxAttributes",nextState:"jsx"};this.$rules.start.unshift(t);var r={regex:"{",token:"paren.quasi.start",push:"start"};this.$rules.jsx=[r,t,{include:"reference"},{defaultToken:"string.xml"}],this.$rules.jsxAttributes=[{token:"meta.tag.punctuation.tag-close.xml",regex:"/?>",onMatch:function(i,n,l){return n==l[0]&&l.shift(),i.length==2&&(l[0]==this.nextState&&l[1]--,(!l[1]||l[1]<0)&&l.splice(0,2)),this.next=l[0]||"start",[{type:this.token,value:i}]},nextState:"jsx"},r,e("jsxAttributes"),{token:"entity.other.attribute-name.xml",regex:o},{token:"keyword.operator.attribute-equals.xml",regex:"="},{token:"text.tag-whitespace.xml",regex:"\\s+"},{token:"string.attribute-value.xml",regex:"'",stateName:"jsx_attr_q",push:[{token:"string.attribute-value.xml",regex:"'",next:"pop"},{include:"reference"},{defaultToken:"string.attribute-value.xml"}]},{token:"string.attribute-value.xml",regex:'"',stateName:"jsx_attr_qq",push:[{token:"string.attribute-value.xml",regex:'"',next:"pop"},{include:"reference"},{defaultToken:"string.attribute-value.xml"}]},t],this.$rules.reference=[{token:"constant.language.escape.reference.xml",regex:"(?:&#[0-9]+;)|(?:&#x[0-9a-fA-F]+;)|(?:&[a-zA-Z0-9_:\\.-]+;)"}]}function e(o){return[{token:"comment",regex:/\/\*/,next:[f.getTagRule(),{token:"comment",regex:"\\*\\/",next:o||"pop"},{defaultToken:"comment",caseInsensitive:!0}]},{token:"comment",regex:"\\/\\/",next:[f.getTagRule(),{token:"comment",regex:"$|^",next:o||"pop"},{defaultToken:"comment",caseInsensitive:!0}]}]}g.JavaScriptHighlightRules=a}),ace.define("ace/mode/matching_brace_outdent",["require","exports","module","ace/range"],function(p,g,h){var m=p("../range").Range,f=function(){};(function(){this.checkOutdent=function(d,s){return/^\s+$/.test(d)?/^\s*\}/.test(s):!1},this.autoOutdent=function(d,s){var a=d.getLine(s),c=a.match(/^(\s*\})/);if(!c)return 0;var e=c[1].length,o=d.findMatchingBracket({row:s,column:e});if(!o||o.row==s)return 0;var t=this.$getIndent(d.getLine(o.row));d.replace(new m(s,0,s,e-1),t)},this.$getIndent=function(d){return d.match(/^\s*/)[0]}}).call(f.prototype),g.MatchingBraceOutdent=f}),ace.define("ace/mode/behaviour/xml",["require","exports","module","ace/lib/oop","ace/mode/behaviour","ace/token_iterator"],function(p,g,h){var m=p("../../lib/oop"),f=p("../behaviour").Behaviour,d=p("../../token_iterator").TokenIterator;function s(c,e){return c&&c.type.lastIndexOf(e+".xml")>-1}var a=function(){this.add("string_dquotes","insertion",function(c,e,o,t,r){if(r=='"'||r=="'"){var i=r,n=t.doc.getTextRange(o.getSelectionRange());if(n!==""&&n!=="'"&&n!='"'&&o.getWrapBehavioursEnabled())return{text:i+n+i,selection:!1};var l=o.getCursorPosition(),u=t.doc.getLine(l.row),b=u.substring(l.column,l.column+1),y=new d(t,l.row,l.column),v=y.getCurrentToken();if(b==i&&(s(v,"attribute-value")||s(v,"string")))return{text:"",selection:[1,1]};if(v||(v=y.stepBackward()),!v)return;for(;s(v,"tag-whitespace")||s(v,"whitespace");)v=y.stepBackward();var w=!b||b.match(/\s/);if(s(v,"attribute-equals")&&(w||b==">")||s(v,"decl-attribute-equals")&&(w||b=="?"))return{text:i+i,selection:[1,1]}}}),this.add("string_dquotes","deletion",function(c,e,o,t,r){var i=t.doc.getTextRange(r);if(!r.isMultiLine()&&(i=='"'||i=="'")){var n=t.doc.getLine(r.start.row),l=n.substring(r.start.column+1,r.start.column+2);if(l==i)return r.end.column++,r}}),this.add("autoclosing","insertion",function(c,e,o,t,r){if(r==">"){var i=o.getSelectionRange().start,n=new d(t,i.row,i.column),l=n.getCurrentToken()||n.stepBackward();if(!l||!(s(l,"tag-name")||s(l,"tag-whitespace")||s(l,"attribute-name")||s(l,"attribute-equals")||s(l,"attribute-value"))||s(l,"reference.attribute-value"))return;if(s(l,"attribute-value")){var u=n.getCurrentTokenColumn()+l.value.length;if(i.column<u)return;if(i.column==u){var b=n.stepForward();if(b&&s(b,"attribute-value"))return;n.stepBackward()}}if(/^\s*>/.test(t.getLine(i.row).slice(i.column)))return;for(;!s(l,"tag-name");)if(l=n.stepBackward(),l.value=="<"){l=n.stepForward();break}var y=n.getCurrentTokenRow(),v=n.getCurrentTokenColumn();if(s(n.stepBackward(),"end-tag-open"))return;var w=l.value;return y==i.row&&(w=w.substring(0,i.column-v)),this.voidElements&&this.voidElements.hasOwnProperty(w.toLowerCase())?void 0:{text:"></"+w+">",selection:[1,1]}}}),this.add("autoindent","insertion",function(c,e,o,t,r){if(r==`
`){var i=o.getCursorPosition(),n=t.getLine(i.row),l=new d(t,i.row,i.column),u=l.getCurrentToken();if(s(u,"")&&u.type.indexOf("tag-close")!==-1){if(u.value=="/>")return;for(;u&&u.type.indexOf("tag-name")===-1;)u=l.stepBackward();if(!u)return;var b=u.value,y=l.getCurrentTokenRow();if(u=l.stepBackward(),!u||u.type.indexOf("end-tag")!==-1)return;if(this.voidElements&&!this.voidElements[b]||!this.voidElements){var v=t.getTokenAt(i.row,i.column+1),n=t.getLine(y),w=this.$getIndent(n),T=w+t.getTabString();return v&&v.value==="</"?{text:`
`+T+`
`+w,selection:[1,T.length,1,T.length]}:{text:`
`+T}}}}})};m.inherits(a,f),g.XmlBehaviour=a}),ace.define("ace/mode/behaviour/javascript",["require","exports","module","ace/lib/oop","ace/token_iterator","ace/mode/behaviour/cstyle","ace/mode/behaviour/xml"],function(p,g,h){var m=p("../../lib/oop"),f=p("../../token_iterator").TokenIterator,d=p("../behaviour/cstyle").CstyleBehaviour,s=p("../behaviour/xml").XmlBehaviour,a=function(){var c=new s({closeCurlyBraces:!0}).getBehaviours();this.addBehaviours(c),this.inherit(d),this.add("autoclosing-fragment","insertion",function(e,o,t,r,i){if(i==">"){var n=t.getSelectionRange().start,l=new f(r,n.row,n.column),u=l.getCurrentToken()||l.stepBackward();if(!u)return;if(u.value=="<")return{text:"></>",selection:[1,1]}}})};m.inherits(a,d),g.JavaScriptBehaviour=a}),ace.define("ace/mode/folding/xml",["require","exports","module","ace/lib/oop","ace/range","ace/mode/folding/fold_mode"],function(p,g,h){var m=p("../../lib/oop"),f=p("../../range").Range,d=p("./fold_mode").FoldMode,s=g.FoldMode=function(e,o){d.call(this),this.voidElements=e||{},this.optionalEndTags=m.mixin({},this.voidElements),o&&m.mixin(this.optionalEndTags,o)};m.inherits(s,d);var a=function(){this.tagName="",this.closing=!1,this.selfClosing=!1,this.start={row:0,column:0},this.end={row:0,column:0}};function c(e,o){return e&&e.type&&e.type.lastIndexOf(o+".xml")>-1}(function(){this.getFoldWidget=function(e,o,t){var r=this._getFirstTagInLine(e,t);return r?r.closing||!r.tagName&&r.selfClosing?o==="markbeginend"?"end":"":!r.tagName||r.selfClosing||this.voidElements.hasOwnProperty(r.tagName.toLowerCase())||this._findEndTagInLine(e,t,r.tagName,r.end.column)?"":"start":this.getCommentFoldWidget(e,t)},this.getCommentFoldWidget=function(e,o){return/comment/.test(e.getState(o))&&/<!-/.test(e.getLine(o))?"start":""},this._getFirstTagInLine=function(e,o){for(var t=e.getTokens(o),r=new a,i=0;i<t.length;i++){var n=t[i];if(c(n,"tag-open")){if(r.end.column=r.start.column+n.value.length,r.closing=c(n,"end-tag-open"),n=t[++i],!n)return null;if(r.tagName=n.value,n.value===""){if(n=t[++i],!n)return null;r.tagName=n.value}for(r.end.column+=n.value.length,i++;i<t.length;i++)if(n=t[i],r.end.column+=n.value.length,c(n,"tag-close")){r.selfClosing=n.value=="/>";break}return r}else if(c(n,"tag-close"))return r.selfClosing=n.value=="/>",r;r.start.column+=n.value.length}return null},this._findEndTagInLine=function(e,o,t,r){for(var i=e.getTokens(o),n=0,l=0;l<i.length;l++){var u=i[l];if(n+=u.value.length,!(n<r-1)&&c(u,"end-tag-open")&&(u=i[l+1],c(u,"tag-name")&&u.value===""&&(u=i[l+2]),u&&u.value==t))return!0}return!1},this.getFoldWidgetRange=function(e,o,t){var r=this._getFirstTagInLine(e,t);if(!r)return this.getCommentFoldWidget(e,t)&&e.getCommentFoldRange(t,e.getLine(t).length);var i=e.getMatchingTags({row:t,column:0});if(i)return new f(i.openTag.end.row,i.openTag.end.column,i.closeTag.start.row,i.closeTag.start.column)}}).call(s.prototype)}),ace.define("ace/mode/folding/cstyle",["require","exports","module","ace/lib/oop","ace/range","ace/mode/folding/fold_mode"],function(p,g,h){var m=p("../../lib/oop"),f=p("../../range").Range,d=p("./fold_mode").FoldMode,s=g.FoldMode=function(a){a&&(this.foldingStartMarker=new RegExp(this.foldingStartMarker.source.replace(/\|[^|]*?$/,"|"+a.start)),this.foldingStopMarker=new RegExp(this.foldingStopMarker.source.replace(/\|[^|]*?$/,"|"+a.end)))};m.inherits(s,d),(function(){this.foldingStartMarker=/([\{\[\(])[^\}\]\)]*$|^\s*(\/\*)/,this.foldingStopMarker=/^[^\[\{\(]*([\}\]\)])|^[\s\*]*(\*\/)/,this.singleLineBlockCommentRe=/^\s*(\/\*).*\*\/\s*$/,this.tripleStarBlockCommentRe=/^\s*(\/\*\*\*).*\*\/\s*$/,this.startRegionRe=/^\s*(\/\*|\/\/)#?region\b/,this._getFoldWidgetBase=this.getFoldWidget,this.getFoldWidget=function(a,c,e){var o=a.getLine(e);if(this.singleLineBlockCommentRe.test(o)&&!this.startRegionRe.test(o)&&!this.tripleStarBlockCommentRe.test(o))return"";var t=this._getFoldWidgetBase(a,c,e);return!t&&this.startRegionRe.test(o)?"start":t},this.getFoldWidgetRange=function(a,c,e,o){var t=a.getLine(e);if(this.startRegionRe.test(t))return this.getCommentRegionBlock(a,t,e);var n=t.match(this.foldingStartMarker);if(n){var r=n.index;if(n[1])return this.openingBracketBlock(a,n[1],e,r);var i=a.getCommentFoldRange(e,r+n[0].length,1);return i&&!i.isMultiLine()&&(o?i=this.getSectionRange(a,e):c!="all"&&(i=null)),i}if(c!=="markbegin"){var n=t.match(this.foldingStopMarker);if(n){var r=n.index+n[0].length;return n[1]?this.closingBracketBlock(a,n[1],e,r):a.getCommentFoldRange(e,r,-1)}}},this.getSectionRange=function(a,c){var e=a.getLine(c),o=e.search(/\S/),t=c,r=e.length;c=c+1;for(var i=c,n=a.getLength();++c<n;){e=a.getLine(c);var l=e.search(/\S/);if(l!==-1){if(o>l)break;var u=this.getFoldWidgetRange(a,"all",c);if(u){if(u.start.row<=t)break;if(u.isMultiLine())c=u.end.row;else if(o==l)break}i=c}}return new f(t,r,i,a.getLine(i).length)},this.getCommentRegionBlock=function(a,c,e){for(var o=c.search(/\s*$/),t=a.getLength(),r=e,i=/^\s*(?:\/\*|\/\/|--)#?(end)?region\b/,n=1;++e<t;){c=a.getLine(e);var l=i.exec(c);if(l&&(l[1]?n--:n++,!n))break}var u=e;if(u>r)return new f(r,o,u,c.length)}}).call(s.prototype)}),ace.define("ace/mode/folding/javascript",["require","exports","module","ace/lib/oop","ace/mode/folding/xml","ace/mode/folding/cstyle"],function(p,g,h){var m=p("../../lib/oop"),f=p("./xml").FoldMode,d=p("./cstyle").FoldMode,s=g.FoldMode=function(a){a&&(this.foldingStartMarker=new RegExp(this.foldingStartMarker.source.replace(/\|[^|]*?$/,"|"+a.start)),this.foldingStopMarker=new RegExp(this.foldingStopMarker.source.replace(/\|[^|]*?$/,"|"+a.end))),this.xmlFoldMode=new f};m.inherits(s,d),(function(){this.getFoldWidgetRangeBase=this.getFoldWidgetRange,this.getFoldWidgetBase=this.getFoldWidget,this.getFoldWidget=function(a,c,e){var o=this.getFoldWidgetBase(a,c,e);return o||this.xmlFoldMode.getFoldWidget(a,c,e)},this.getFoldWidgetRange=function(a,c,e,o){var t=this.getFoldWidgetRangeBase(a,c,e,o);return t||this.xmlFoldMode.getFoldWidgetRange(a,c,e)}}).call(s.prototype)}),ace.define("ace/mode/javascript",["require","exports","module","ace/lib/oop","ace/mode/text","ace/mode/javascript_highlight_rules","ace/mode/matching_brace_outdent","ace/worker/worker_client","ace/mode/behaviour/javascript","ace/mode/folding/javascript"],function(p,g,h){var m=p("../lib/oop"),f=p("./text").Mode,d=p("./javascript_highlight_rules").JavaScriptHighlightRules,s=p("./matching_brace_outdent").MatchingBraceOutdent,a=p("../worker/worker_client").WorkerClient,c=p("./behaviour/javascript").JavaScriptBehaviour,e=p("./folding/javascript").FoldMode,o=function(){this.HighlightRules=d,this.$outdent=new s,this.$behaviour=new c,this.foldingRules=new e};m.inherits(o,f),(function(){this.lineCommentStart="//",this.blockComment={start:"/*",end:"*/"},this.$quotes={'"':'"',"'":"'","`":"`"},this.$pairQuotesAfter={"`":/\w/},this.getNextLineIndent=function(t,r,i){var n=this.$getIndent(r),l=this.getTokenizer().getLineTokens(r,t),u=l.tokens,b=l.state;if(u.length&&u[u.length-1].type=="comment")return n;if(t=="start"||t=="no_regex"){var y=r.match(/^.*(?:\bcase\b.*:|[\{\(\[])\s*$/);y&&(n+=i)}else if(t=="doc-start"&&(b=="start"||b=="no_regex"))return"";return n},this.checkOutdent=function(t,r,i){return this.$outdent.checkOutdent(r,i)},this.autoOutdent=function(t,r,i){this.$outdent.autoOutdent(r,i)},this.createWorker=function(t){var r=new a(["ace"],"ace/mode/javascript_worker","JavaScriptWorker");return r.attachToDocument(t.getDocument()),r.on("annotate",function(i){t.setAnnotations(i.data)}),r.on("terminate",function(){t.clearAnnotations()}),r},this.$id="ace/mode/javascript",this.snippetFileId="ace/snippets/javascript"}).call(o.prototype),g.Mode=o}),(function(){ace.require(["ace/mode/javascript"],function(p){x&&(x.exports=p)})})()})(F)),F.exports}xe();var E={exports:{}},O;function ve(){return O||(O=1,(function(x,$){ace.define("ace/snippets/javascript.snippets",["require","exports","module"],function(p,g,h){h.exports=`# Prototype
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
`}),ace.define("ace/snippets/javascript",["require","exports","module","ace/snippets/javascript.snippets"],function(p,g,h){g.snippetText=p("./javascript.snippets"),g.scope="javascript"}),(function(){ace.require(["ace/snippets/javascript"],function(p){x&&(x.exports=p)})})()})(E)),E.exports}ve();const $e=["id"],ye={__name:"AceFileEditor",props:V({fileExtention:{type:String,default:"html"},theme:{type:String,default:"monokai"},readOnly:{type:Boolean,default:!1},tabSize:{type:Number,default:4},wrap:{type:Boolean,default:!1},height:{type:Number,default:100}},{modelValue:{type:String,default:""},modelModifiers:{}}),emits:["update:modelValue"],setup(x){j.config.set("basePath","/vendor/magicpro/ace");const $=k(J()),p=x,g=U(x,"modelValue"),h=k(null);let m=null;function f(){const s={js:"ace/mode/javascript",css:"ace/mode/css"};return s[p.fileExtention]?s[p.fileExtention]:"ace/mode/text"}const d=s=>`ace/theme/${s}`;return B(()=>{f(),m=j.edit($.value,{value:g.value,mode:f(),theme:d(p.theme),readOnly:p.readOnly,wrap:p.wrap,tabSize:p.tabSize,useWorker:!1}),m.setOptions({enableBasicAutocompletion:!0,enableLiveAutocompletion:!0,enableEmmet:!0}),m.setFontSize(14),m.session.on("change",()=>{g.value=m.getValue()})}),R(g,s=>{if(!m)return;const a=m.getValue();s!==a&&m.setValue(s)}),Y(()=>m?.destroy()),R(()=>p.fileExtention,s=>{const a=f();m?.session.setMode(a)}),R(()=>p.theme,s=>m?.setTheme(d(s))),(s,a)=>(z(),W("div",{ref_key:"el",ref:h,id:$.value,style:{width:"100%",height:"100%"}},null,8,$e))}},we={class:"d-flex gx-2 py-2 mx-0 align-items-center"},ke={class:"flex-grow-1"},_e={__name:"EditFile",props:{fileName:{type:String,default:"/design/1.txt"},width:{type:String,default:"800"},height:{type:String,default:"600"}},emits:["close"],setup(x,{emit:$}){const p=x,g=k(""),h=k(""),m=k(!1),f=async e=>{e.ctrlKey&&e.code==="KeyS"&&(console.log("save file"),e.stopImmediatePropagation(),e.preventDefault(),e.stopPropagation(),await s())};B(async()=>{g.value=p.fileName.split(".").pop();const e=await I({command:"loadFile",fileName:p.fileName});h.value=e.fileData,m.value=!0,window.addEventListener("keydown",f,{capture:!0})}),G(()=>{window.removeEventListener("keydown",f)});const d=k(!0);async function s(){console.log(h.value),await I({command:"saveFile",fileName:p.fileName,fileData:h.value}),document.showToast("Сохранен файл "+p.fileName)}async function a(){let e="";switch(g.value){case"css":{e=await prettier.format(h.value,{parser:"css",plugins:[prettierPlugins.postcss],tabWidth:4,printWidth:160});break}case"js":{e=await prettier.format(h.value,{parser:"babel",plugins:prettierPlugins,semi:!0,tabWidth:4,printWidth:120});break}default:return}document.showToast("Отформатировано"),h.value=e}const c=$;return(e,o)=>m.value?(z(),D(me,{key:0,visible:d.value,"onUpdate:visible":o[1]||(o[1]=t=>d.value=t),width:"props.width",height:"props.height",header:"Имя файла",onClose:o[2]||(o[2]=t=>{c("close"),m.value=!1})},{header:L(()=>[_("div",we,[_("div",ke,Z(x.fileName),1),_("div",{class:"mx-2"},[_("button",{class:"btn btn-sm btn-primary",onClick:s},"Save→")]),_("div",{class:""},[_("button",{class:"btn fas fa-magic btn-success",onClick:a})])])]),default:L(()=>[H(ye,{modelValue:h.value,"onUpdate:modelValue":o[0]||(o[0]=t=>h.value=t),fileExtention:g.value,theme:"chrome"},null,8,["modelValue","fileExtention"])]),_:1},8,["visible"])):P("",!0)}},Ce={__name:"FileEditor",setup(x){B(()=>{});const $=k(!0),p=k("/design/css/common.css");return(g,h)=>(z(),W(Q,null,[$.value?(z(),D(_e,{key:0,fileName:p.value,zIndex:1e3,onClose:h[0]||(h[0]=m=>$.value=!1)},null,8,["fileName"])):P("",!0),H(K)],64))}},Te=[X,te,ne,ie,re,oe,ae,se,pe,ge],C=ee(Ce);Te.forEach(x=>{C.component(x.name,x)});C.use(le,{theme:{preset:ce,options:{cssLayer:{name:"primevue",order:"theme, base, primevue"}}}});C.use(de);C.use(X);C.use(fe());C.mount("#file_editor");Object.assign(globalThis,ue);
