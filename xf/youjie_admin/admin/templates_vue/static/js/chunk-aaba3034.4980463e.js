(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-aaba3034"],{"21a6":function(e,t,o){(function(o){var i,a,n;(function(o,r){a=[],i=r,n="function"===typeof i?i.apply(t,a):i,void 0===n||(e.exports=n)})(0,function(){"use strict";function t(e,t){return"undefined"==typeof t?t={autoBom:!1}:"object"!=typeof t&&(console.warn("Deprecated: Expected third argument to be a object"),t={autoBom:!t}),t.autoBom&&/^\s*(?:text\/\S*|application\/xml|\S*\/\S*\+xml)\s*;.*charset\s*=\s*utf-8/i.test(e.type)?new Blob(["\ufeff",e],{type:e.type}):e}function i(e,t,o){var i=new XMLHttpRequest;i.open("GET",e),i.responseType="blob",i.onload=function(){s(i.response,t,o)},i.onerror=function(){console.error("could not download file")},i.send()}function a(e){var t=new XMLHttpRequest;t.open("HEAD",e,!1);try{t.send()}catch(e){}return 200<=t.status&&299>=t.status}function n(e){try{e.dispatchEvent(new MouseEvent("click"))}catch(i){var t=document.createEvent("MouseEvents");t.initMouseEvent("click",!0,!0,window,0,0,0,80,20,!1,!1,!1,!1,0,null),e.dispatchEvent(t)}}var r="object"==typeof window&&window.window===window?window:"object"==typeof self&&self.self===self?self:"object"==typeof o&&o.global===o?o:void 0,s=r.saveAs||("object"!=typeof window||window!==r?function(){}:"download"in HTMLAnchorElement.prototype?function(e,t,o){var s=r.URL||r.webkitURL,l=document.createElement("a");t=t||e.name||"download",l.download=t,l.rel="noopener","string"==typeof e?(l.href=e,l.origin===location.origin?n(l):a(l.href)?i(e,t,o):n(l,l.target="_blank")):(l.href=s.createObjectURL(e),setTimeout(function(){s.revokeObjectURL(l.href)},4e4),setTimeout(function(){n(l)},0))}:"msSaveOrOpenBlob"in navigator?function(e,o,r){if(o=o||e.name||"download","string"!=typeof e)navigator.msSaveOrOpenBlob(t(e,r),o);else if(a(e))i(e,o,r);else{var s=document.createElement("a");s.href=e,s.target="_blank",setTimeout(function(){n(s)})}}:function(e,t,o,a){if(a=a||open("","_blank"),a&&(a.document.title=a.document.body.innerText="downloading..."),"string"==typeof e)return i(e,t,o);var n="application/octet-stream"===e.type,s=/constructor/i.test(r.HTMLElement)||r.safari,l=/CriOS\/[\d]+/.test(navigator.userAgent);if((l||n&&s)&&"object"==typeof FileReader){var p=new FileReader;p.onloadend=function(){var e=p.result;e=l?e:e.replace(/^data:[^;]*;/,"data:attachment/file;"),a?a.location.href=e:location=e,a=null},p.readAsDataURL(e)}else{var u=r.URL||r.webkitURL,c=u.createObjectURL(e);a?a.location=c:location.href=c,a=null,setTimeout(function(){u.revokeObjectURL(c)},4e4)}});r.saveAs=s.saveAs=s,e.exports=s})}).call(this,o("c8ba"))},"278b":function(e,t,o){"use strict";var i=o("e646"),a=o.n(i);a.a},"5d78":function(e,t,o){"use strict";o.r(t);var i=function(){var e=this,t=e.$createElement,o=e._self._c||t;return o("div",{staticClass:"container"},[o("page-title",{attrs:{"first-title":"push管理","second-title":e.id?"编辑":"新增"}}),o("div",{staticClass:"foem-wrapper"},[o("el-form",{directives:[{name:"loading",rawName:"v-loading",value:e.loading,expression:"loading"}],ref:"form",attrs:{model:e.form,rules:e.rules,"label-width":"150px",disabled:e.isCheck}},[o("el-form-item",{attrs:{label:"标题",prop:"title"}},[o("el-input",{model:{value:e.form.title,callback:function(t){e.$set(e.form,"title",t)},expression:"form.title"}})],1),o("el-form-item",{attrs:{label:"内容",prop:"content"}},[o("el-input",{staticClass:"text-area",attrs:{type:"textarea"},model:{value:e.form.content,callback:function(t){e.$set(e.form,"content",t)},expression:"form.content"}})],1),o("el-form-item",{attrs:{label:"目标人群",prop:"push_type"}},[o("el-radio-group",{on:{change:e.pushType},model:{value:e.form.push_type,callback:function(t){e.$set(e.form,"push_type",t)},expression:"form.push_type"}},[o("el-radio",{attrs:{label:!1}},[e._v("指定用户")]),o("el-radio",{attrs:{label:!0}},[e._v("全部用户")])],1)],1),e.form.push_type?e._e():o("el-form-item",{attrs:{label:"用户列表",prop:"file_path",required:""}},[o("el-upload",{attrs:{action:e.uploadUrl,headers:e.headers,name:"myfile",accept:"application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet","on-success":e.uploadSuccess,"show-file-list":!1,"with-credentials":!0}},[o("el-button",{attrs:{slot:"trigger",type:"primary",size:"small",icon:"el-icon-upload2"},slot:"trigger"},[e._v("批量导入")])],1),o("el-button",{attrs:{type:"text",size:"small"},on:{click:e.download}},[e._v("下载模板")]),void 0!==e.uploadCount?o("p",{staticClass:"upload-count"},[e._v("已导入用户数："+e._s(e.uploadCount))]):e._e()],1),o("el-form-item",{attrs:{label:"发送时间",prop:"push_time_type"}},[o("el-radio-group",{on:{change:e.timeTypeChange},model:{value:e.push_time_type,callback:function(t){e.push_time_type=t},expression:"push_time_type"}},[o("el-radio",{attrs:{label:!0}},[e._v("定时发送")]),o("el-radio",{attrs:{label:!1}},[e._v("即时发送")])],1)],1),e.push_time_type?o("el-form-item",{attrs:{label:"定时发送时间",prop:"plan_exe_time"}},[o("el-date-picker",{attrs:{type:"datetime",placeholder:"请选择发送时间"},model:{value:e.form.plan_exe_time,callback:function(t){e.$set(e.form,"plan_exe_time",t)},expression:"form.plan_exe_time"}})],1):e._e(),o("el-form-item",{attrs:{label:"链接地址",prop:"go_url"}},[o("el-input",{model:{value:e.form.go_url,callback:function(t){e.$set(e.form,"go_url",t)},expression:"form.go_url"}})],1),o("el-form-item",[o("el-button",{attrs:{type:"primary"},on:{click:e.onSubmit}},[e._v("立即创建")]),o("el-button",{on:{click:e.goBack}},[e._v("取消")])],1)],1)],1)],1)},a=[],n=o("be94"),r=o("21a6"),s=o("822c"),l={name:"pushEdit",data(){let e=(e,t,o)=>{if(this.push_time_type){if(!t)return o(new Error("请选择发送时间"));const e=(new Date).getTime(),i=t.getTime();if(i<e)return o(new Error("定时发送时间不得早于当前时间"))}return o()},t=(e,t,o)=>{return this.push_type||this.filePath?o():o(new Error("请上传用户手机号文件"))};return{id:this.$route.query.id,isCheck:!!this.$route.query.isCheck,form:{title:"",content:"",plan_exe_time:"",push_type:!1,file_path:"",go_url:""},loading:!1,push_time_type:!1,uploadUrl:"".concat("https://api.youjiemall.com","/admin/api.app.push.lead"),filePath:"",uploadCount:void 0,rules:{title:[{required:!0,message:"请输入标题",trigger:"blur"},{max:15,message:"长度不超过15个字符",trigger:"blur"}],content:[{required:!0,message:"请填写推送内容",trigger:"blur"},{max:100,message:"长度不超过100个字符",trigger:"blur"}],go_url:[{required:!0,message:"请填写链接地址",trigger:"blur"}],plan_exe_time:[{required:!0,message:"请选择推送时间",trigger:"blur"},{validator:e,trigger:"blur"}],file_path:[{validator:t,message:"指定人群发送必须上传用户手机号",trigger:"blur"}]}}},computed:{headers(){let e="";return e=window.token,{"X-ADMIN-Authorization":e,"X-HH-Ver":"0.4.3"}}},created(){this.id&&this.getDetail()},methods:{onSubmit(){this.$refs["form"].validate(e=>{if(!e)return!1;{const e={title:this.form.title,content:this.form.content,go_url:this.form.go_url,push_type:this.form.push_type?1:0,push_time_type:this.push_time_type?1:0};this.id&&(e.id=this.id),this.form.push_type||(e.file_path=this.filePath),this.push_time_type&&(e.plan_exe_time=parseInt(this.form.plan_exe_time.getTime()/1e3)),this.saveRecord(e)}})},saveRecord(e){Object(s["c"])(e).then(e=>{this.$router.go(-1)})},getDetail(){this.loading=!0,Object(s["d"])(this.id).then(e=>{this.loading=!1,this.form=Object(n["a"])({},e),this.form.plan_exe_time=this.form.plan_exe_time?new Date(1e3*this.form.plan_exe_time):new Date,this.form.push_type=1==this.form.push_type,this.push_time_type=!!this.form.push_time_type})},pushType(e){this.uploadCount=void 0,this.filePath=""},timeTypeChange(e){this.form.plan_exe_time=""},uploadSuccess(e,t,o){0==e.code&&(this.uploadCount=e.data.count,this.filePath=e.data.file_path)},goBack(){this.$router.go(-1)},download(){Object(s["b"])().then(e=>{Object(r["saveAs"])(new Blob([e.data]),e.filename||"app_push.xls")})}},watch:{filePath(e){this.form.file_path=e}}},p=l,u=(o("278b"),o("2877")),c=Object(u["a"])(p,i,a,!1,null,"32f8640a",null);t["default"]=c.exports},"822c":function(e,t,o){"use strict";o.d(t,"e",function(){return a}),o.d(t,"a",function(){return n}),o.d(t,"d",function(){return r}),o.d(t,"c",function(){return s}),o.d(t,"b",function(){return l});var i=o("65c6");const a=e=>{const t={page:e.page,size:e.size,title:e.title,exe_time_start:e.start,exe_time_end:e.end};return"number"==typeof e.exe_code&&(t.exe_code=e.exe_code),Object(i["a"])("/admin/api.app.push.list","POST",t)},n=e=>Object(i["a"])("/admin/api.app.push.delete","POST",{id:e}),r=e=>Object(i["a"])("/admin/api.app.push.info","POST",{id:e}),s=e=>Object(i["a"])("/admin/api.app.push.save","POST",e),l=e=>Object(i["b"])("/admin/api.app.push.down.template","POST",{})},e646:function(e,t,o){}}]);
//# sourceMappingURL=chunk-aaba3034.4980463e.js.map