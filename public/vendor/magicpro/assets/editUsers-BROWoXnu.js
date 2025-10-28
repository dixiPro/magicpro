import{Y as L,Z as V,_ as O,$ as M,a0 as U,c as p,d as f,I as K,J as Q,r as b,o as R,E as q,g as _,e as a,j as w,F as k,k as H,i as J,l as F,v as x,y,a1 as W,t as m,K as Y,T as N,L as Z,M as G,N as X,O as tt,P as et,Q as nt,R as it,S as ot,U as at,V as lt,W as rt}from"./apiCall-xy8N_ukK.js";var st={name:"BaseEditableHolder",extends:L,emits:["update:modelValue","value-change"],props:{modelValue:{type:null,default:void 0},defaultValue:{type:null,default:void 0},name:{type:String,default:void 0},invalid:{type:Boolean,default:void 0},disabled:{type:Boolean,default:!1},formControl:{type:Object,default:void 0}},inject:{$parentInstance:{default:void 0},$pcForm:{default:void 0},$pcFormField:{default:void 0}},data:function(){return{d_value:this.defaultValue!==void 0?this.defaultValue:this.modelValue}},watch:{modelValue:{deep:!0,handler:function(t){this.d_value=t}},defaultValue:function(t){this.d_value=t},$formName:{immediate:!0,handler:function(t){var e,i;this.formField=((e=this.$pcForm)===null||e===void 0||(i=e.register)===null||i===void 0?void 0:i.call(e,t,this.$formControl))||{}}},$formControl:{immediate:!0,handler:function(t){var e,i;this.formField=((e=this.$pcForm)===null||e===void 0||(i=e.register)===null||i===void 0?void 0:i.call(e,this.$formName,t))||{}}},$formDefaultValue:{immediate:!0,handler:function(t){this.d_value!==t&&(this.d_value=t)}},$formValue:{immediate:!1,handler:function(t){var e;(e=this.$pcForm)!==null&&e!==void 0&&e.getFieldState(this.$formName)&&t!==this.d_value&&(this.d_value=t)}}},formField:{},methods:{writeValue:function(t,e){var i,u;this.controlled&&(this.d_value=t,this.$emit("update:modelValue",t)),this.$emit("value-change",t),(i=(u=this.formField).onChange)===null||i===void 0||i.call(u,{originalEvent:e,value:t})},findNonEmpty:function(){for(var t=arguments.length,e=new Array(t),i=0;i<t;i++)e[i]=arguments[i];return e.find(V)}},computed:{$filled:function(){return V(this.d_value)},$invalid:function(){var t,e;return!this.$formNovalidate&&this.findNonEmpty(this.invalid,(t=this.$pcFormField)===null||t===void 0||(t=t.$field)===null||t===void 0?void 0:t.invalid,(e=this.$pcForm)===null||e===void 0||(e=e.getFieldState(this.$formName))===null||e===void 0?void 0:e.invalid)},$formName:function(){var t;return this.$formNovalidate?void 0:this.name||((t=this.$formControl)===null||t===void 0?void 0:t.name)},$formControl:function(){var t;return this.formControl||((t=this.$pcFormField)===null||t===void 0?void 0:t.formControl)},$formNovalidate:function(){var t;return(t=this.$formControl)===null||t===void 0?void 0:t.novalidate},$formDefaultValue:function(){var t,e;return this.findNonEmpty(this.d_value,(t=this.$pcFormField)===null||t===void 0?void 0:t.initialValue,(e=this.$pcForm)===null||e===void 0||(e=e.initialValues)===null||e===void 0?void 0:e[this.$formName])},$formValue:function(){var t,e;return this.findNonEmpty((t=this.$pcFormField)===null||t===void 0||(t=t.$field)===null||t===void 0?void 0:t.value,(e=this.$pcForm)===null||e===void 0||(e=e.getFieldState(this.$formName))===null||e===void 0?void 0:e.value)},controlled:function(){return this.$inProps.hasOwnProperty("modelValue")||!this.$inProps.hasOwnProperty("modelValue")&&!this.$inProps.hasOwnProperty("defaultValue")},filled:function(){return this.$filled}}},dt={name:"BaseInput",extends:st,props:{size:{type:String,default:null},fluid:{type:Boolean,default:null},variant:{type:String,default:null}},inject:{$parentInstance:{default:void 0},$pcFluid:{default:void 0}},computed:{$variant:function(){var t;return(t=this.variant)!==null&&t!==void 0?t:this.$primevue.config.inputStyle||this.$primevue.config.inputVariant},$fluid:function(){var t;return(t=this.fluid)!==null&&t!==void 0?t:!!this.$pcFluid},hasFluid:function(){return this.$fluid}}},ut=`
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
`,ct={root:function(t){var e=t.instance,i=t.props;return["p-inputtext p-component",{"p-filled":e.$filled,"p-inputtext-sm p-inputfield-sm":i.size==="small","p-inputtext-lg p-inputfield-lg":i.size==="large","p-invalid":e.$invalid,"p-variant-filled":e.$variant==="filled","p-inputtext-fluid":e.$fluid}]}},pt=O.extend({name:"inputtext",style:ut,classes:ct}),ft={name:"BaseInputText",extends:dt,style:pt,provide:function(){return{$pcInputText:this,$parentInstance:this}}};function v(n){"@babel/helpers - typeof";return v=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},v(n)}function mt(n,t,e){return(t=vt(t))in n?Object.defineProperty(n,t,{value:e,enumerable:!0,configurable:!0,writable:!0}):n[t]=e,n}function vt(n){var t=ht(n,"string");return v(t)=="symbol"?t:t+""}function ht(n,t){if(v(n)!="object"||!n)return n;var e=n[Symbol.toPrimitive];if(e!==void 0){var i=e.call(n,t);if(v(i)!="object")return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(n)}var S={name:"InputText",extends:ft,inheritAttrs:!1,methods:{onInput:function(t){this.writeValue(t.target.value,t)}},computed:{attrs:function(){return U(this.ptmi("root",{context:{filled:this.$filled,disabled:this.disabled}}),this.formField)},dataP:function(){return M(mt({invalid:this.$invalid,fluid:this.$fluid,filled:this.$variant==="filled"},this.size,this.size))}}},$t=["value","name","disabled","aria-invalid","data-p"];function bt(n,t,e,i,u,r){return f(),p("input",U({type:"text",class:n.cx("root"),value:n.d_value,name:n.name,disabled:n.disabled,"aria-invalid":n.$invalid||void 0,"data-p":r.dataP,onInput:t[0]||(t[0]=function(){return r.onInput&&r.onInput.apply(r,arguments)})},r.attrs),null,16,$t)}S.render=bt;const xt={class:"container my-3"},yt={class:"col-1"},gt=["onClick"],_t={class:"col-1"},wt=["textContent"],Ft={class:"col-2"},Ct=["textContent"],Vt={class:"col-2"},kt=["textContent"],Ut={class:"col-2"},Nt=["textContent"],St={class:"col-2"},Pt=["textContent"],Et={key:0,class:"col-1"},It=["onClick"],Tt={class:"my-3 text-center"},Dt={class:"my-2"},jt={class:"my-2"},zt={class:"my-2"},Bt={__name:"EditUsers",setup(n){const t=K(),e=Q(),i=b([]),u=b(!1),r=b({name:"newUser",email:"barsik@barsik.com",password:"1234",role:"user"});let C=0;const d=b({show:!1,mode:null,header:null,button:null});async function $(o){const l="/a_dmin/api/editUsers";if(!u.value)try{return u.value=!0,(await W({url:l,data:o,logResult:!1})).data}catch(c){throw document.showToast(c,"error"),new Error("ошибка")}finally{u.value=!1}}async function P(){try{i.value=(await $({command:"getUserList",data:{}})).map(o=>(o.edit=!1,o)),document.showToast("Список считан")}catch{}}async function E(){if(await document.confirmDialog("Сохранить???"))try{const o=await $({command:"editUser",data:r.value});o.edit=!1,console.log(o),i.value[C]=o,r.value=o,document.showToast("Сохранено")}catch{}}async function I(o){if(await document.confirmDialog("Удалить?"))try{const l=await $({command:"deleteUser",data:{id:i.value[o].id}});i.value.splice(o,1)}catch{}}function T(o){C=o,r.value=i.value[o],d.value.show=!0,d.value.header="Изменить",d.value.mode="edit",d.value.header="Сохранить"}function D(){r.value={name:"newUser",email:"barsik@barsik.com",password:"1234",role:"user"},d.value.show=!0,d.value.header="Создать",d.value.mode="add",d.value.header="Создать"}async function j(){try{const o=await $({command:"addUser",data:r.value});o.edit=!1,i.value.push(o),d.value.show=!1}catch{}}return R(()=>{document.showToast=(o="",l="success")=>{const c=l==="success"?5e3:1e5;t.add({severity:l,detail:o,life:c}),l==="error"&&console.log(o)},document.confirmDialog=async o=>new Promise((l,c)=>{e.require({message:o,header:" ",acceptLabel:"Да",rejectLabel:"Нет",accept:()=>l(!0),reject:()=>l(!1)})}),P()}),q(()=>{}),(o,l)=>{const c=_("Dialog"),z=_("Toast"),B=_("ConfirmDialog");return f(),p(k,null,[a("div",xt,[(f(!0),p(k,null,H(i.value,(s,g)=>(f(),p("div",{key:g,class:"row my-2"},[a("div",yt,[a("button",{onClick:A=>T(g),class:"fas fa-edit btn btn-sm btn-success"},null,8,gt)]),a("div",_t,[a("span",{textContent:m(s.id)},null,8,wt)]),a("div",Ft,[a("span",{textContent:m(s.name)},null,8,Ct)]),a("div",Vt,[a("span",{textContent:m(s.email)},null,8,kt)]),a("div",Ut,[a("span",{textContent:m(s.password)},null,8,Nt)]),a("div",St,[a("span",{textContent:m(s.role)},null,8,Pt)]),s.edit?F("",!0):(f(),p("div",Et,[a("button",{class:"fas fa-trash btn btn-sm btn-success",onClick:A=>I(g)},null,8,It)]))]))),128)),a("div",Tt,[a("button",{class:"btn btn-sm btn-success",onClick:l[0]||(l[0]=s=>D())},"add")])]),w(c,{visible:d.value.show,"onUpdate:visible":l[5]||(l[5]=s=>d.value.show=s),header:d.value.header,modal:"",style:{width:"400px"}},{default:J(()=>[a("div",null,[x(a("input",{type:"text",class:"form-control","onUpdate:modelValue":l[1]||(l[1]=s=>r.value.name=s),placeholder:"name"},null,512),[[y,r.value.name]])]),a("div",Dt,[x(a("input",{type:"text",class:"form-control","onUpdate:modelValue":l[2]||(l[2]=s=>r.value.email=s),placeholder:"email"},null,512),[[y,r.value.email]])]),a("div",jt,[x(a("input",{type:"text",class:"form-control","onUpdate:modelValue":l[3]||(l[3]=s=>r.value.password=s),placeholder:"password"},null,512),[[y,r.value.password]])]),a("div",zt,[x(a("input",{type:"text",class:"form-control","onUpdate:modelValue":l[4]||(l[4]=s=>r.value.role=s),placeholder:"password"},null,512),[[y,r.value.role]])]),d.value.mode==="add"?(f(),p("button",{key:0,class:"btn btn-sm btn-success",onClick:j},"Создать")):F("",!0),d.value.mode==="edit"?(f(),p("button",{key:1,class:"btn btn-sm btn-success",onClick:E},"Сохранить")):F("",!0)]),_:1},8,["visible","header"]),w(z,{position:"bottom-right"}),w(B)],64)}}},At=[N,Z,G,X,tt,et,nt,it,ot,S],h=Y(Bt);At.forEach(n=>{h.component(n.name,n)});h.use(at,{theme:{preset:lt,options:{cssLayer:{name:"primevue",order:"theme, base, primevue"}}}});h.use(rt);h.use(N);h.mount("#edit_users");
