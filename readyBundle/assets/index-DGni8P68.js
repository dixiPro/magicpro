import{aj as y,o as b,b as m,d as g,T as f,E as I,I as z,W as A,H as M,J as N,bc as R,at as D,k as S,l as P,g as F,n as G}from"./ToastConfirm-BPtxLl-D.js";import{a as H}from"./index-CVQmiYbe.js";async function W(e,n=2){try{return await prettier.format(e,{parser:"php",plugins:prettierPlugins,tabWidth:n})}catch(t){throw console.error("Ошибка при форматировании Php:",t),new Error("Ошибка при форматировании Php")}}async function pn(e,n=2){const t=[],r=[],i=[],o=(d,h)=>`<!--___${d}____${h}-->`;let c=e;c=c.replace(/@php\b([\s\S]*?)@endphp/g,d=>{const h=t.length;return t.push(d),o("PHP",h)}),c=c.replace(/{{--[\s\S]*?--}}/g,d=>{const h=r.length;return r.push(d),o("COMMENT",h)}),c=q(c,d=>{const h=i.length;return i.push(d),o("DIR",h)});const[p,u]=await Promise.all([Promise.all(t.map(d=>Z(d,n))),Promise.all(i.map(d=>U(d,n)))]);let l;try{l=await prettier.format(c,{parser:"html",plugins:[prettierPlugins.html],tabWidth:n,printWidth:160})}catch(d){return Q(d),e}return l=X(l,n),l=T(l,"PHP",p),l=T(l,"DIR",u),l=J(l,"COMMENT",r),l=l.replace(/[ \t]+$/gm,"").replace(/\n{3,}/g,`

`).replace(/\s+$/,""),l+`
`}const V=new Set(["if","elseif","unless","isset","empty","foreach","forelse","for","while","switch","case","section","push","pushonce","prepend","stack","once","error","can","elsecan","cannot","elsecannot","canany","env","production","auth","guest","mproauth","hasSection","sectionMissing","component","slot","fragment","livewire"]);function q(e,n){let t="",r=0;const i=/@(\w+)\s*\(/g;let o;for(;(o=i.exec(e))!==null;){const c=o[1],p=o.index,u=o.index+o[0].length-1,l=B(e,u);if(l===-1)continue;if(V.has(c)){i.lastIndex=l+1;continue}const d=e.slice(p,l+1);t+=e.slice(r,p)+n(d),r=l+1,i.lastIndex=l+1}return t+=e.slice(r),t}function B(e,n){let t=0,r=null;for(let i=n;i<e.length;i++){const o=e[i];if(r){if(o==="\\"){i++;continue}o===r&&(r=null);continue}if(o==='"'||o==="'"||o==="`"){r=o;continue}if(o==="(")t++;else if(o===")"&&(t--,t===0))return i}return-1}async function U(e,n){const t=e.indexOf("("),r=e.slice(0,t),i=e.slice(t+1,e.lastIndexOf(")"));let o;try{o=await prettier.format(`<?php
__d(${i});`,{parser:"php",plugins:prettierPlugins,tabWidth:n,singleQuote:!0})}catch{return e}const c=o.replace(/^<\?php\s*/,"").replace(/\s+$/,"").replace(/^__d\(/,"").replace(/\);$/,"");return`${r}(${c})`}async function Z(e,n){const t=e.replace(/^@php\s*/,"").replace(/\s*@endphp$/,"");return`@php
${(await W(`<?php
${t}`,n)).replace(/^<\?php\s*/,"").replace(/\s+$/,"").split(`
`).map(o=>" ".repeat(n)+o).join(`
`)}
@endphp`}function T(e,n,t){const r=new RegExp(`^([ \\t]*)<!--___${n}____(\\d+)-->[ \\t]*$`,"gm"),i=new RegExp(`<!--___${n}____(\\d+)-->`,"g");return e.replace(r,(o,c,p)=>K(t[p],c)).replace(i,(o,c)=>t[c])}function K(e,n){return e.split(`
`).map(t=>t===""?"":n+t).join(`
`)}function J(e,n,t){const r=new RegExp(`<!--___${n}____(\\d+)-->`,"g");return e.replace(r,(i,o)=>t[o])}function Q(e){const n=String(e&&e.message?e.message:e).split(`
`).map(t=>t.replace(/^\s+/,"")).map(t=>t.replace(/For more info.*/,"")).filter(t=>!t.includes("at")).join(`
`);typeof document<"u"&&typeof document.showToast=="function"&&document.showToast(n,"error")}const s="open",a="close",k="middle",O={if:s,elseif:k,else:k,endif:a,unless:s,endunless:a,isset:s,endisset:a,empty:s,endempty:a,foreach:s,endforeach:a,forelse:s,endforelse:a,for:s,endfor:a,while:s,endwhile:a,switch:s,endswitch:a,case:k,default:k,section:s,endsection:a,show:a,stop:a,append:a,overwrite:a,php:s,endphp:a,push:s,endpush:a,pushonce:s,endpushonce:a,prepend:s,endprepend:a,stack:s,endstack:a,component:s,endcomponent:a,slot:s,endslot:a,once:s,endonce:a,verbatim:s,endverbatim:a,error:s,enderror:a,fragment:s,endfragment:a,can:s,elsecan:k,endcan:a,cannot:s,elsecannot:k,endcannot:a,canany:s,endcanany:a,auth:s,endauth:a,guest:s,endguest:a,mproauth:s,endmproauth:a,hasSection:s,endif_hassection:a,sectionMissing:s,production:s,endproduction:a,env:s,endenv:a,livewire:s,endlivewire:a},E=new Set(["yield","extends","include","includeif","includewhen","includeunless","includefirst","each","csrf","method","props","break","continue","json","js","dd","dump","vite","inject","lang","use","aware","checked","selected","disabled","readonly","required","class","style"]);function X(e,n){const t=e.split(`
`);let r=0;const i=[];for(const o of t){const c=Math.round(Y(o)/n);for(const p of ee(o)){const u=te(p),l=u.text.replace(/^\s+/,"");if(l===""){i.push("");continue}const d=Math.max(0,r+u.before),h=(c+d)*n;i.push(" ".repeat(h)+l),r=Math.max(0,r+u.after)}}return i.join(`
`)}function Y(e){const n=e.match(/^[ \t]*/);return n?n[0].replace(/\t/g," ").length:0}function ee(e){if(e.trim()==="")return[""];const n=[];let t="";const r=()=>{t.trim()!==""&&n.push(t.trim()),t=""},i=/@(\w+)/g;let o=0,c;for(;(c=i.exec(e))!==null;){const p=c[1];if(!ne(p))continue;t+=e.slice(o,c.index),r();let u=c.index+c[0].length;if(e.slice(u).match(/^\s*\(/)){const d=e.indexOf("(",u),h=B(e,d);h!==-1&&(u=h+1)}n.push(e.slice(c.index,u).trim()),o=u,i.lastIndex=u}return t+=e.slice(o),r(),n.length?n:[e]}function ne(e){return E.has(e)?!1:Object.prototype.hasOwnProperty.call(O,e)}function te(e){const n=re(e);if(!n)return{text:e,before:0,after:0};const t=oe(e,n);if(E.has(n))return{text:t,before:0,after:0};if(n==="empty"&&j(t,"empty")===null)return{text:t,before:-1,after:0};const r=O[n];return r?r===s&&ie(t,n)?{text:t,before:0,after:0}:r===s?{text:t,before:0,after:1}:r===a?{text:t,before:-1,after:-1}:{text:t,before:-1,after:0}:{text:t,before:0,after:0}}function re(e){const n=e.match(/^\s*@(\w+)/);return n?n[1]:null}function oe(e,n){return e.replace(new RegExp(`@${n}\\s+\\(`),`@${n}(`)}function ie(e,n){if(n==="section"){const r=j(e,"section");if(r!==null&&ae(r).length>=2)return!0}const t=ce(n);return!!(t&&new RegExp(`@${t}\\b`).test(e))}function ce(e){return{php:"endphp",error:"enderror",section:"endsection",once:"endonce",verbatim:"endverbatim",push:"endpush"}[e]||null}function j(e,n){const t=e.search(new RegExp(`@${n}\\s*\\(`));if(t===-1)return null;const r=e.indexOf("(",t);let i=0;for(let o=r;o<e.length;o++){const c=e[o];if(c==="(")i++;else if(c===")"&&(i--,i===0))return e.slice(r+1,o)}return null}function ae(e){const n=[];let t=0,r=null,i="";for(let o=0;o<e.length;o++){const c=e[o];if(r){c===r&&e[o-1]!=="\\"&&(r=null),i+=c;continue}if(c==='"'||c==="'"){r=c,i+=c;continue}if(c==="("||c==="["||c==="{"){t++,i+=c;continue}if(c===")"||c==="]"||c==="}"){t--,i+=c;continue}if(c===","&&t===0){n.push(i.trim()),i="";continue}i+=c}return i.trim()!==""&&n.push(i.trim()),n}var se={name:"SearchIcon",extends:y};function le(e){return he(e)||pe(e)||ue(e)||de()}function de(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function ue(e,n){if(e){if(typeof e=="string")return v(e,n);var t={}.toString.call(e).slice(8,-1);return t==="Object"&&e.constructor&&(t=e.constructor.name),t==="Map"||t==="Set"?Array.from(e):t==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t)?v(e,n):void 0}}function pe(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function he(e){if(Array.isArray(e))return v(e)}function v(e,n){(n==null||n>e.length)&&(n=e.length);for(var t=0,r=Array(n);t<n;t++)r[t]=e[t];return r}function fe(e,n,t,r,i,o){return b(),m("svg",f({width:"14",height:"14",viewBox:"0 0 14 14",fill:"none",xmlns:"http://www.w3.org/2000/svg"},e.pti()),le(n[0]||(n[0]=[g("path",{"fill-rule":"evenodd","clip-rule":"evenodd",d:"M2.67602 11.0265C3.6661 11.688 4.83011 12.0411 6.02086 12.0411C6.81149 12.0411 7.59438 11.8854 8.32483 11.5828C8.87005 11.357 9.37808 11.0526 9.83317 10.6803L12.9769 13.8241C13.0323 13.8801 13.0983 13.9245 13.171 13.9548C13.2438 13.985 13.3219 14.0003 13.4007 14C13.4795 14.0003 13.5575 13.985 13.6303 13.9548C13.7031 13.9245 13.7691 13.8801 13.8244 13.8241C13.9367 13.7116 13.9998 13.5592 13.9998 13.4003C13.9998 13.2414 13.9367 13.089 13.8244 12.9765L10.6807 9.8328C11.053 9.37773 11.3573 8.86972 11.5831 8.32452C11.8857 7.59408 12.0414 6.81119 12.0414 6.02056C12.0414 4.8298 11.6883 3.66579 11.0268 2.67572C10.3652 1.68564 9.42494 0.913972 8.32483 0.45829C7.22472 0.00260857 6.01418 -0.116618 4.84631 0.115686C3.67844 0.34799 2.60568 0.921393 1.76369 1.76338C0.921698 2.60537 0.348296 3.67813 0.115991 4.84601C-0.116313 6.01388 0.00291375 7.22441 0.458595 8.32452C0.914277 9.42464 1.68595 10.3649 2.67602 11.0265ZM3.35565 2.0158C4.14456 1.48867 5.07206 1.20731 6.02086 1.20731C7.29317 1.20731 8.51338 1.71274 9.41304 2.6124C10.3127 3.51206 10.8181 4.73226 10.8181 6.00457C10.8181 6.95337 10.5368 7.88088 10.0096 8.66978C9.48251 9.45868 8.73328 10.0736 7.85669 10.4367C6.98011 10.7997 6.01554 10.8947 5.08496 10.7096C4.15439 10.5245 3.2996 10.0676 2.62869 9.39674C1.95778 8.72583 1.50089 7.87104 1.31579 6.94046C1.13068 6.00989 1.22568 5.04532 1.58878 4.16874C1.95187 3.29215 2.56675 2.54292 3.35565 2.0158Z",fill:"currentColor"},null,-1)])),16)}se.render=fe;var be=`
    .p-iconfield {
        position: relative;
        display: block;
    }

    .p-inputicon {
        position: absolute;
        top: 50%;
        margin-top: calc(-1 * (dt('icon.size') / 2));
        color: dt('iconfield.icon.color');
        line-height: 1;
        z-index: 1;
    }

    .p-iconfield .p-inputicon:first-child {
        inset-inline-start: dt('form.field.padding.x');
    }

    .p-iconfield .p-inputicon:last-child {
        inset-inline-end: dt('form.field.padding.x');
    }

    .p-iconfield .p-inputtext:not(:first-child),
    .p-iconfield .p-inputwrapper:not(:first-child) .p-inputtext {
        padding-inline-start: calc((dt('form.field.padding.x') * 2) + dt('icon.size'));
    }

    .p-iconfield .p-inputtext:not(:last-child) {
        padding-inline-end: calc((dt('form.field.padding.x') * 2) + dt('icon.size'));
    }

    .p-iconfield:has(.p-inputfield-sm) .p-inputicon {
        font-size: dt('form.field.sm.font.size');
        width: dt('form.field.sm.font.size');
        height: dt('form.field.sm.font.size');
        margin-top: calc(-1 * (dt('form.field.sm.font.size') / 2));
    }

    .p-iconfield:has(.p-inputfield-lg) .p-inputicon {
        font-size: dt('form.field.lg.font.size');
        width: dt('form.field.lg.font.size');
        height: dt('form.field.lg.font.size');
        margin-top: calc(-1 * (dt('form.field.lg.font.size') / 2));
    }
`,me={root:"p-iconfield"},ke=I.extend({name:"iconfield",style:be,classes:me}),ge={name:"BaseIconField",extends:z,style:ke,provide:function(){return{$pcIconField:this,$parentInstance:this}}},xe={name:"IconField",extends:ge,inheritAttrs:!1};function ye(e,n,t,r,i,o){return b(),m("div",f({class:e.cx("root")},e.ptmi("root")),[A(e.$slots,"default")],16)}xe.render=ye;var ve={root:"p-inputicon"},Ce=I.extend({name:"inputicon",classes:ve}),$e={name:"BaseInputIcon",extends:z,style:Ce,props:{class:null},provide:function(){return{$pcInputIcon:this,$parentInstance:this}}},we={name:"InputIcon",extends:$e,inheritAttrs:!1,computed:{containerClass:function(){return[this.cx("root"),this.class]}}};function _e(e,n,t,r,i,o){return b(),m("span",f({class:o.containerClass},e.ptmi("root"),{"aria-hidden":"true"}),[A(e.$slots,"default")],16)}we.render=_e;var Ie={name:"ChevronDownIcon",extends:y};function Ae(e){return ze(e)||Te(e)||Pe(e)||Se()}function Se(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function Pe(e,n){if(e){if(typeof e=="string")return C(e,n);var t={}.toString.call(e).slice(8,-1);return t==="Object"&&e.constructor&&(t=e.constructor.name),t==="Map"||t==="Set"?Array.from(e):t==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t)?C(e,n):void 0}}function Te(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function ze(e){if(Array.isArray(e))return C(e)}function C(e,n){(n==null||n>e.length)&&(n=e.length);for(var t=0,r=Array(n);t<n;t++)r[t]=e[t];return r}function Be(e,n,t,r,i,o){return b(),m("svg",f({width:"14",height:"14",viewBox:"0 0 14 14",fill:"none",xmlns:"http://www.w3.org/2000/svg"},e.pti()),Ae(n[0]||(n[0]=[g("path",{d:"M7.01744 10.398C6.91269 10.3985 6.8089 10.378 6.71215 10.3379C6.61541 10.2977 6.52766 10.2386 6.45405 10.1641L1.13907 4.84913C1.03306 4.69404 0.985221 4.5065 1.00399 4.31958C1.02276 4.13266 1.10693 3.95838 1.24166 3.82747C1.37639 3.69655 1.55301 3.61742 1.74039 3.60402C1.92777 3.59062 2.11386 3.64382 2.26584 3.75424L7.01744 8.47394L11.769 3.75424C11.9189 3.65709 12.097 3.61306 12.2748 3.62921C12.4527 3.64535 12.6199 3.72073 12.7498 3.84328C12.8797 3.96582 12.9647 4.12842 12.9912 4.30502C13.0177 4.48162 12.9841 4.662 12.8958 4.81724L7.58083 10.1322C7.50996 10.2125 7.42344 10.2775 7.32656 10.3232C7.22968 10.3689 7.12449 10.3944 7.01744 10.398Z",fill:"currentColor"},null,-1)])),16)}Ie.render=Be;var Oe={name:"ChevronRightIcon",extends:y};function Ee(e){return Ne(e)||Me(e)||Le(e)||je()}function je(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function Le(e,n){if(e){if(typeof e=="string")return $(e,n);var t={}.toString.call(e).slice(8,-1);return t==="Object"&&e.constructor&&(t=e.constructor.name),t==="Map"||t==="Set"?Array.from(e):t==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t)?$(e,n):void 0}}function Me(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function Ne(e){if(Array.isArray(e))return $(e)}function $(e,n){(n==null||n>e.length)&&(n=e.length);for(var t=0,r=Array(n);t<n;t++)r[t]=e[t];return r}function Re(e,n,t,r,i,o){return b(),m("svg",f({width:"14",height:"14",viewBox:"0 0 14 14",fill:"none",xmlns:"http://www.w3.org/2000/svg"},e.pti()),Ee(n[0]||(n[0]=[g("path",{d:"M4.38708 13C4.28408 13.0005 4.18203 12.9804 4.08691 12.9409C3.99178 12.9014 3.9055 12.8433 3.83313 12.7701C3.68634 12.6231 3.60388 12.4238 3.60388 12.2161C3.60388 12.0084 3.68634 11.8091 3.83313 11.6622L8.50507 6.99022L3.83313 2.31827C3.69467 2.16968 3.61928 1.97313 3.62287 1.77005C3.62645 1.56698 3.70872 1.37322 3.85234 1.22959C3.99596 1.08597 4.18972 1.00371 4.3928 1.00012C4.59588 0.996539 4.79242 1.07192 4.94102 1.21039L10.1669 6.43628C10.3137 6.58325 10.3962 6.78249 10.3962 6.99022C10.3962 7.19795 10.3137 7.39718 10.1669 7.54416L4.94102 12.7701C4.86865 12.8433 4.78237 12.9014 4.68724 12.9409C4.59212 12.9804 4.49007 13.0005 4.38708 13Z",fill:"currentColor"},null,-1)])),16)}Oe.render=Re;var L={name:"MinusIcon",extends:y};function De(e){return We(e)||He(e)||Ge(e)||Fe()}function Fe(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function Ge(e,n){if(e){if(typeof e=="string")return w(e,n);var t={}.toString.call(e).slice(8,-1);return t==="Object"&&e.constructor&&(t=e.constructor.name),t==="Map"||t==="Set"?Array.from(e):t==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t)?w(e,n):void 0}}function He(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function We(e){if(Array.isArray(e))return w(e)}function w(e,n){(n==null||n>e.length)&&(n=e.length);for(var t=0,r=Array(n);t<n;t++)r[t]=e[t];return r}function Ve(e,n,t,r,i,o){return b(),m("svg",f({width:"14",height:"14",viewBox:"0 0 14 14",fill:"none",xmlns:"http://www.w3.org/2000/svg"},e.pti()),De(n[0]||(n[0]=[g("path",{d:"M13.2222 7.77778H0.777778C0.571498 7.77778 0.373667 7.69584 0.227806 7.54998C0.0819442 7.40412 0 7.20629 0 7.00001C0 6.79373 0.0819442 6.5959 0.227806 6.45003C0.373667 6.30417 0.571498 6.22223 0.777778 6.22223H13.2222C13.4285 6.22223 13.6263 6.30417 13.7722 6.45003C13.9181 6.5959 14 6.79373 14 7.00001C14 7.20629 13.9181 7.40412 13.7722 7.54998C13.6263 7.69584 13.4285 7.77778 13.2222 7.77778Z",fill:"currentColor"},null,-1)])),16)}L.render=Ve;var qe=`
    .p-checkbox {
        position: relative;
        display: inline-flex;
        user-select: none;
        vertical-align: bottom;
        width: dt('checkbox.width');
        height: dt('checkbox.height');
    }

    .p-checkbox-input {
        cursor: pointer;
        appearance: none;
        position: absolute;
        inset-block-start: 0;
        inset-inline-start: 0;
        width: 100%;
        height: 100%;
        padding: 0;
        margin: 0;
        opacity: 0;
        z-index: 1;
        outline: 0 none;
        border: 1px solid transparent;
        border-radius: dt('checkbox.border.radius');
    }

    .p-checkbox-box {
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: dt('checkbox.border.radius');
        border: 1px solid dt('checkbox.border.color');
        background: dt('checkbox.background');
        width: dt('checkbox.width');
        height: dt('checkbox.height');
        transition:
            background dt('checkbox.transition.duration'),
            color dt('checkbox.transition.duration'),
            border-color dt('checkbox.transition.duration'),
            box-shadow dt('checkbox.transition.duration'),
            outline-color dt('checkbox.transition.duration');
        outline-color: transparent;
        box-shadow: dt('checkbox.shadow');
    }

    .p-checkbox-icon {
        transition-duration: dt('checkbox.transition.duration');
        color: dt('checkbox.icon.color');
        font-size: dt('checkbox.icon.size');
        width: dt('checkbox.icon.size');
        height: dt('checkbox.icon.size');
    }

    .p-checkbox:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-box {
        border-color: dt('checkbox.hover.border.color');
    }

    .p-checkbox-checked .p-checkbox-box {
        border-color: dt('checkbox.checked.border.color');
        background: dt('checkbox.checked.background');
    }

    .p-checkbox-checked .p-checkbox-icon {
        color: dt('checkbox.icon.checked.color');
    }

    .p-checkbox-checked:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-box {
        background: dt('checkbox.checked.hover.background');
        border-color: dt('checkbox.checked.hover.border.color');
    }

    .p-checkbox-checked:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-icon {
        color: dt('checkbox.icon.checked.hover.color');
    }

    .p-checkbox:not(.p-disabled):has(.p-checkbox-input:focus-visible) .p-checkbox-box {
        border-color: dt('checkbox.focus.border.color');
        box-shadow: dt('checkbox.focus.ring.shadow');
        outline: dt('checkbox.focus.ring.width') dt('checkbox.focus.ring.style') dt('checkbox.focus.ring.color');
        outline-offset: dt('checkbox.focus.ring.offset');
    }

    .p-checkbox-checked:not(.p-disabled):has(.p-checkbox-input:focus-visible) .p-checkbox-box {
        border-color: dt('checkbox.checked.focus.border.color');
    }

    .p-checkbox.p-invalid > .p-checkbox-box {
        border-color: dt('checkbox.invalid.border.color');
    }

    .p-checkbox.p-variant-filled .p-checkbox-box {
        background: dt('checkbox.filled.background');
    }

    .p-checkbox-checked.p-variant-filled .p-checkbox-box {
        background: dt('checkbox.checked.background');
    }

    .p-checkbox-checked.p-variant-filled:not(.p-disabled):has(.p-checkbox-input:hover) .p-checkbox-box {
        background: dt('checkbox.checked.hover.background');
    }

    .p-checkbox.p-disabled {
        opacity: 1;
    }

    .p-checkbox.p-disabled .p-checkbox-box {
        background: dt('checkbox.disabled.background');
        border-color: dt('checkbox.checked.disabled.border.color');
    }

    .p-checkbox.p-disabled .p-checkbox-box .p-checkbox-icon {
        color: dt('checkbox.icon.disabled.color');
    }

    .p-checkbox-sm,
    .p-checkbox-sm .p-checkbox-box {
        width: dt('checkbox.sm.width');
        height: dt('checkbox.sm.height');
    }

    .p-checkbox-sm .p-checkbox-icon {
        font-size: dt('checkbox.icon.sm.size');
        width: dt('checkbox.icon.sm.size');
        height: dt('checkbox.icon.sm.size');
    }

    .p-checkbox-lg,
    .p-checkbox-lg .p-checkbox-box {
        width: dt('checkbox.lg.width');
        height: dt('checkbox.lg.height');
    }

    .p-checkbox-lg .p-checkbox-icon {
        font-size: dt('checkbox.icon.lg.size');
        width: dt('checkbox.icon.lg.size');
        height: dt('checkbox.icon.lg.size');
    }
`,Ue={root:function(n){var t=n.instance,r=n.props;return["p-checkbox p-component",{"p-checkbox-checked":t.checked,"p-disabled":r.disabled,"p-invalid":t.$pcCheckboxGroup?t.$pcCheckboxGroup.$invalid:t.$invalid,"p-variant-filled":t.$variant==="filled","p-checkbox-sm p-inputfield-sm":r.size==="small","p-checkbox-lg p-inputfield-lg":r.size==="large"}]},box:"p-checkbox-box",input:"p-checkbox-input",icon:"p-checkbox-icon"},Ze=I.extend({name:"checkbox",style:qe,classes:Ue}),Ke={name:"BaseCheckbox",extends:H,props:{value:null,binary:Boolean,indeterminate:{type:Boolean,default:!1},trueValue:{type:null,default:!0},falseValue:{type:null,default:!1},readonly:{type:Boolean,default:!1},required:{type:Boolean,default:!1},tabindex:{type:Number,default:null},inputId:{type:String,default:null},inputClass:{type:[String,Object],default:null},inputStyle:{type:Object,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:Ze,provide:function(){return{$pcCheckbox:this,$parentInstance:this}}};function x(e){"@babel/helpers - typeof";return x=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},x(e)}function Je(e,n,t){return(n=Qe(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function Qe(e){var n=Xe(e,"string");return x(n)=="symbol"?n:n+""}function Xe(e,n){if(x(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var r=t.call(e,n);if(x(r)!="object")return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}function Ye(e){return rn(e)||tn(e)||nn(e)||en()}function en(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function nn(e,n){if(e){if(typeof e=="string")return _(e,n);var t={}.toString.call(e).slice(8,-1);return t==="Object"&&e.constructor&&(t=e.constructor.name),t==="Map"||t==="Set"?Array.from(e):t==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t)?_(e,n):void 0}}function tn(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function rn(e){if(Array.isArray(e))return _(e)}function _(e,n){(n==null||n>e.length)&&(n=e.length);for(var t=0,r=Array(n);t<n;t++)r[t]=e[t];return r}var on={name:"Checkbox",extends:Ke,inheritAttrs:!1,emits:["change","focus","blur","update:indeterminate"],inject:{$pcCheckboxGroup:{default:void 0}},data:function(){return{d_indeterminate:this.indeterminate}},watch:{indeterminate:function(n){this.d_indeterminate=n,this.updateIndeterminate()}},mounted:function(){this.updateIndeterminate()},updated:function(){this.updateIndeterminate()},methods:{getPTOptions:function(n){var t=n==="root"?this.ptmi:this.ptm;return t(n,{context:{checked:this.checked,indeterminate:this.d_indeterminate,disabled:this.disabled}})},onChange:function(n){var t=this;if(!this.disabled&&!this.readonly){var r=this.$pcCheckboxGroup?this.$pcCheckboxGroup.d_value:this.d_value,i;this.binary?i=this.d_indeterminate?this.trueValue:this.checked?this.falseValue:this.trueValue:this.checked||this.d_indeterminate?i=r.filter(function(o){return!D(o,t.value)}):i=r?[].concat(Ye(r),[this.value]):[this.value],this.d_indeterminate&&(this.d_indeterminate=!1,this.$emit("update:indeterminate",this.d_indeterminate)),this.$pcCheckboxGroup?this.$pcCheckboxGroup.writeValue(i,n):this.writeValue(i,n),this.$emit("change",n)}},onFocus:function(n){this.$emit("focus",n)},onBlur:function(n){var t,r;this.$emit("blur",n),(t=(r=this.formField).onBlur)===null||t===void 0||t.call(r,n)},updateIndeterminate:function(){this.$refs.input&&(this.$refs.input.indeterminate=this.d_indeterminate)}},computed:{groupName:function(){return this.$pcCheckboxGroup?this.$pcCheckboxGroup.groupName:this.$formName},checked:function(){var n=this.$pcCheckboxGroup?this.$pcCheckboxGroup.d_value:this.d_value;return this.d_indeterminate?!1:this.binary?n===this.trueValue:R(this.value,n)},dataP:function(){return N(Je({invalid:this.$invalid,checked:this.checked,disabled:this.disabled,filled:this.$variant==="filled"},this.size,this.size))}},components:{CheckIcon:M,MinusIcon:L}},cn=["data-p-checked","data-p-indeterminate","data-p-disabled","data-p"],an=["id","value","name","checked","tabindex","disabled","readonly","required","aria-labelledby","aria-label","aria-invalid"],sn=["data-p"];function ln(e,n,t,r,i,o){var c=S("CheckIcon"),p=S("MinusIcon");return b(),m("div",f({class:e.cx("root")},o.getPTOptions("root"),{"data-p-checked":o.checked,"data-p-indeterminate":i.d_indeterminate||void 0,"data-p-disabled":e.disabled,"data-p":o.dataP}),[g("input",f({ref:"input",id:e.inputId,type:"checkbox",class:[e.cx("input"),e.inputClass],style:e.inputStyle,value:e.value,name:o.groupName,checked:o.checked,tabindex:e.tabindex,disabled:e.disabled,readonly:e.readonly,required:e.required,"aria-labelledby":e.ariaLabelledby,"aria-label":e.ariaLabel,"aria-invalid":e.invalid||void 0,onFocus:n[0]||(n[0]=function(){return o.onFocus&&o.onFocus.apply(o,arguments)}),onBlur:n[1]||(n[1]=function(){return o.onBlur&&o.onBlur.apply(o,arguments)}),onChange:n[2]||(n[2]=function(){return o.onChange&&o.onChange.apply(o,arguments)})},o.getPTOptions("input")),null,16,an),g("div",f({class:e.cx("box")},o.getPTOptions("box"),{"data-p":o.dataP}),[A(e.$slots,"icon",{checked:o.checked,indeterminate:i.d_indeterminate,class:G(e.cx("icon")),dataP:o.dataP},function(){return[o.checked?(b(),P(c,f({key:0,class:e.cx("icon")},o.getPTOptions("icon"),{"data-p":o.dataP}),null,16,["class","data-p"])):i.d_indeterminate?(b(),P(p,f({key:1,class:e.cx("icon")},o.getPTOptions("icon"),{"data-p":o.dataP}),null,16,["class","data-p"])):F("",!0)]})],16,sn)],16,cn)}on.render=ln;export{W as a,xe as b,we as c,L as d,Oe as e,pn as f,Ie as g,on as h,se as s};
