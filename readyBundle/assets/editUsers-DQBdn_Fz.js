import{s as L,B as N,f as K,m as S,c,o as p,Y as M,Z as R,b as x,R as q,$ as G,I as w,a,L as $,F as U,G as H,K as O,y as k,A as y,C as g,ae as Q,H as f,a0 as Y,a1 as I,a2 as Z,a3 as J,a4 as W,a5 as X,a6 as tt,a7 as et,a8 as nt,a9 as at,aa as ot,ab as it,ac as st}from"./apiCall-BhOi8FOm.js";var rt={name:"BaseInput",extends:L,props:{size:{type:String,default:null},fluid:{type:Boolean,default:null},variant:{type:String,default:null}},inject:{$parentInstance:{default:void 0},$pcFluid:{default:void 0}},computed:{$variant:function(){var t;return(t=this.variant)!==null&&t!==void 0?t:this.$primevue.config.inputStyle||this.$primevue.config.inputVariant},$fluid:function(){var t;return(t=this.fluid)!==null&&t!==void 0?t:!!this.$pcFluid},hasFluid:function(){return this.$fluid}}},lt=`
    .p-inputtext {
        font-family: inherit;
        font-feature-settings: inherit;
        font-size: 1rem;
        color: dt('inputtext.color');
        background: dt('inputtext.background');
        padding-block: dt('inputtext.padding.y');
        padding-inline: dt('inputtext.padding.x');
        border: 1px solid dt('inputtext.border.color');
        transition:
            background dt('inputtext.transition.duration'),
            color dt('inputtext.transition.duration'),
            border-color dt('inputtext.transition.duration'),
            outline-color dt('inputtext.transition.duration'),
            box-shadow dt('inputtext.transition.duration');
        appearance: none;
        border-radius: dt('inputtext.border.radius');
        outline-color: transparent;
        box-shadow: dt('inputtext.shadow');
    }

    .p-inputtext:enabled:hover {
        border-color: dt('inputtext.hover.border.color');
    }

    .p-inputtext:enabled:focus {
        border-color: dt('inputtext.focus.border.color');
        box-shadow: dt('inputtext.focus.ring.shadow');
        outline: dt('inputtext.focus.ring.width') dt('inputtext.focus.ring.style') dt('inputtext.focus.ring.color');
        outline-offset: dt('inputtext.focus.ring.offset');
    }

    .p-inputtext.p-invalid {
        border-color: dt('inputtext.invalid.border.color');
    }

    .p-inputtext.p-variant-filled {
        background: dt('inputtext.filled.background');
    }

    .p-inputtext.p-variant-filled:enabled:hover {
        background: dt('inputtext.filled.hover.background');
    }

    .p-inputtext.p-variant-filled:enabled:focus {
        background: dt('inputtext.filled.focus.background');
    }

    .p-inputtext:disabled {
        opacity: 1;
        background: dt('inputtext.disabled.background');
        color: dt('inputtext.disabled.color');
    }

    .p-inputtext::placeholder {
        color: dt('inputtext.placeholder.color');
    }

    .p-inputtext.p-invalid::placeholder {
        color: dt('inputtext.invalid.placeholder.color');
    }

    .p-inputtext-sm {
        font-size: dt('inputtext.sm.font.size');
        padding-block: dt('inputtext.sm.padding.y');
        padding-inline: dt('inputtext.sm.padding.x');
    }

    .p-inputtext-lg {
        font-size: dt('inputtext.lg.font.size');
        padding-block: dt('inputtext.lg.padding.y');
        padding-inline: dt('inputtext.lg.padding.x');
    }

    .p-inputtext-fluid {
        width: 100%;
    }
`,dt={root:function(t){var d=t.instance,r=t.props;return["p-inputtext p-component",{"p-filled":d.$filled,"p-inputtext-sm p-inputfield-sm":r.size==="small","p-inputtext-lg p-inputfield-lg":r.size==="large","p-invalid":d.$invalid,"p-variant-filled":d.$variant==="filled","p-inputtext-fluid":d.$fluid}]}},ut=N.extend({name:"inputtext",style:lt,classes:dt}),ct={name:"BaseInputText",extends:rt,style:ut,provide:function(){return{$pcInputText:this,$parentInstance:this}}};function m(e){"@babel/helpers - typeof";return m=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},m(e)}function pt(e,t,d){return(t=ft(t))in e?Object.defineProperty(e,t,{value:d,enumerable:!0,configurable:!0,writable:!0}):e[t]=d,e}function ft(e){var t=mt(e,"string");return m(t)=="symbol"?t:t+""}function mt(e,t){if(m(e)!="object"||!e)return e;var d=e[Symbol.toPrimitive];if(d!==void 0){var r=d.call(e,t);if(m(r)!="object")return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var T={name:"InputText",extends:ct,inheritAttrs:!1,methods:{onInput:function(t){this.writeValue(t.target.value,t)}},computed:{attrs:function(){return S(this.ptmi("root",{context:{filled:this.$filled,disabled:this.disabled}}),this.formField)},dataP:function(){return K(pt({invalid:this.$invalid,fluid:this.$fluid,filled:this.$variant==="filled"},this.size,this.size))}}},vt=["value","name","disabled","aria-invalid","data-p"];function bt(e,t,d,r,b,i){return p(),c("input",S({type:"text",class:e.cx("root"),value:e.d_value,name:e.name,disabled:e.disabled,"aria-invalid":e.$invalid||void 0,"data-p":i.dataP,onInput:t[0]||(t[0]=function(){return i.onInput&&i.onInput.apply(i,arguments)})},i.attrs),null,16,vt)}T.render=bt;const ht={class:"container my-3"},xt={class:"col-1"},yt=["onClick"],gt={class:"col-1"},_t=["textContent"],wt={class:"col-2"},$t=["textContent"],kt={class:"col-2"},Ct=["textContent"],Ut={class:"col-2"},St=["textContent"],It={class:"col-2"},Tt=["textContent"],Pt={key:0,class:"col-1"},zt=["onClick"],Vt={class:"my-3 text-center"},Dt={class:"my-2"},Bt={class:"my-2"},Ft={class:"my-2"},jt={__name:"EditUsers",setup(e){const t=M(),d=R(),r=x([]),b=x(!1),i=x({name:"newUser",email:"barsik@barsik.com",password:"1234",role:"user"});let C=0;const l=x({show:!1,mode:null,header:null,button:null});async function h(n){const o="/a_dmin/api/editUsers";if(!b.value)try{return b.value=!0,(await Q({url:o,data:n,logResult:!1})).data}catch(u){throw document.showToast(u,"error"),new Error("ошибка")}finally{b.value=!1}}async function P(){try{r.value=(await h({command:"getUserList",data:{}})).map(n=>(n.edit=!1,n)),document.showToast("Список считан")}catch{}}async function z(){if(await document.confirmDialog("Сохранить???"))try{const n=await h({command:"editUser",data:i.value});n.edit=!1,console.log(n),r.value[C]=n,i.value=n,document.showToast("Сохранено")}catch{}}async function V(n){if(await document.confirmDialog("Удалить?"))try{const o=await h({command:"deleteUser",data:{id:r.value[n].id}});r.value.splice(n,1)}catch{}}function D(n){C=n,i.value=r.value[n],l.value.show=!0,l.value.header="Изменить",l.value.mode="edit",l.value.header="Сохранить"}function B(){i.value={name:"newUser",email:"barsik@barsik.com",password:"1234",role:"user"},l.value.show=!0,l.value.header="Создать",l.value.mode="add",l.value.header="Создать"}async function F(){try{const n=await h({command:"addUser",data:i.value});n.edit=!1,r.value.push(n),l.value.show=!1}catch{}}return q(()=>{document.showToast=(n="",o="success")=>{const u=o==="success"?5e3:1e5;t.add({severity:o,detail:n,life:u}),o==="error"&&console.log(n)},document.confirmDialog=async n=>new Promise((o,u)=>{d.require({message:n,header:" ",acceptLabel:"Да",rejectLabel:"Нет",accept:()=>o(!0),reject:()=>o(!1)})}),P()}),G(()=>{}),(n,o)=>{const u=w("Dialog"),j=w("Toast"),E=w("ConfirmDialog");return p(),c(U,null,[a("div",ht,[(p(!0),c(U,null,H(r.value,(s,_)=>(p(),c("div",{key:_,class:"row my-2"},[a("div",xt,[a("button",{onClick:A=>D(_),class:"fas fa-edit btn btn-sm btn-success"},null,8,yt)]),a("div",gt,[a("span",{textContent:f(s.id)},null,8,_t)]),a("div",wt,[a("span",{textContent:f(s.name)},null,8,$t)]),a("div",kt,[a("span",{textContent:f(s.email)},null,8,Ct)]),a("div",Ut,[a("span",{textContent:f(s.password)},null,8,St)]),a("div",It,[a("span",{textContent:f(s.role)},null,8,Tt)]),s.edit?k("",!0):(p(),c("div",Pt,[a("button",{class:"fas fa-trash btn btn-sm btn-success",onClick:A=>V(_)},null,8,zt)]))]))),128)),a("div",Vt,[a("button",{class:"btn btn-sm btn-success",onClick:o[0]||(o[0]=s=>B())},"add")])]),$(u,{visible:l.value.show,"onUpdate:visible":o[5]||(o[5]=s=>l.value.show=s),header:l.value.header,modal:"",style:{width:"400px"}},{default:O(()=>[a("div",null,[y(a("input",{type:"text",class:"form-control","onUpdate:modelValue":o[1]||(o[1]=s=>i.value.name=s),placeholder:"name"},null,512),[[g,i.value.name]])]),a("div",Dt,[y(a("input",{type:"text",class:"form-control","onUpdate:modelValue":o[2]||(o[2]=s=>i.value.email=s),placeholder:"email"},null,512),[[g,i.value.email]])]),a("div",Bt,[y(a("input",{type:"text",class:"form-control","onUpdate:modelValue":o[3]||(o[3]=s=>i.value.password=s),placeholder:"password"},null,512),[[g,i.value.password]])]),a("div",Ft,[y(a("input",{type:"text",class:"form-control","onUpdate:modelValue":o[4]||(o[4]=s=>i.value.role=s),placeholder:"password"},null,512),[[g,i.value.role]])]),l.value.mode==="add"?(p(),c("button",{key:0,class:"btn btn-sm btn-success",onClick:F},"Создать")):k("",!0),l.value.mode==="edit"?(p(),c("button",{key:1,class:"btn btn-sm btn-success",onClick:z},"Сохранить")):k("",!0)]),_:1},8,["visible","header"]),$(j,{position:"bottom-right"}),$(E)],64)}}},Et=[I,Z,J,W,X,tt,et,nt,at,T],v=Y(jt);Et.forEach(e=>{v.component(e.name,e)});v.use(ot,{theme:{preset:it,options:{cssLayer:{name:"primevue",order:"theme, base, primevue"}}}});v.use(st);v.use(I);v.mount("#edit_users");
