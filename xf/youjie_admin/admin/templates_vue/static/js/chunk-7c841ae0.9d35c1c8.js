(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-7c841ae0"],{"097d":function(e,t,n){"use strict";var a=n("5ca1"),r=n("8378"),o=n("7726"),s=n("ebd6"),i=n("bcaa");a(a.P+a.R,"Promise",{finally:function(e){var t=s(this,r.Promise||o.Promise),n="function"==typeof e;return this.then(n?function(n){return i(t,e()).then(function(){return n})}:e,n?function(n){return i(t,e()).then(function(){throw n})}:e)}})},"16b5":function(e,t,n){"use strict";n.d(t,"b",function(){return a}),n.d(t,"a",function(){return r});const a=[{id:1,name:"安卓"},{id:2,name:"IOS"}],r=[{id:0,name:"正常"},{id:-1,name:"逻辑删除"}]},"404a":function(e,t,n){"use strict";n.d(t,"c",function(){return r}),n.d(t,"a",function(){return o}),n.d(t,"b",function(){return s});var a=n("65c6");const r=function(e){let t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:10;return Object(a["a"])("/admin/api.appPackage.list","POST",{page:e,size:t})},o=e=>Object(a["a"])("/admin/api.appPackage.add","POST",e),s=e=>Object(a["a"])("/admin/api.appPackage.info","POST",{id:e})},"89b0":function(e,t,n){},a5b8:function(e,t,n){"use strict";var a=n("d8e8");function r(e){var t,n;this.promise=new e(function(e,a){if(void 0!==t||void 0!==n)throw TypeError("Bad Promise constructor");t=e,n=a}),this.resolve=a(t),this.reject=a(n)}e.exports.f=function(e){return new r(e)}},bcaa:function(e,t,n){var a=n("cb7c"),r=n("d3f4"),o=n("a5b8");e.exports=function(e,t){if(a(e),r(t)&&t.constructor===e)return t;var n=o.f(e),s=n.resolve;return s(t),n.promise}},c641:function(e,t,n){"use strict";n.r(t);var a=function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("div",{staticClass:"add-edit-wrapper"},[n("page-title",{attrs:{"first-title":e.title}}),n("div",{staticClass:"version-info-wrapper"},[n("el-form",{directives:[{name:"loading",rawName:"v-loading",value:e.loading,expression:"loading"}],attrs:{"label-width":"100px"}},[n("el-row",{attrs:{gutter:20}},[n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"更新类型"}},[n("el-select",{attrs:{clearable:""},model:{value:e.versionInfo.type,callback:function(t){e.$set(e.versionInfo,"type",t)},expression:"versionInfo.type"}},e._l(e.typeList,function(e){return n("el-option",{key:e.id,attrs:{label:e.name,value:e.id}})}),1)],1)],1),e.isAndroid?n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"apk包名"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.apk_name,callback:function(t){e.$set(e.versionInfo,"apk_name",t)},expression:"versionInfo.apk_name"}})],1)],1):e._e(),e.isAndroid?n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"apk包地址"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.apk_url,callback:function(t){e.$set(e.versionInfo,"apk_url",t)},expression:"versionInfo.apk_url"}})],1)],1):e._e(),e.isAndroid?n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"包大小"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.apk_size,callback:function(t){e.$set(e.versionInfo,"apk_size",t)},expression:"versionInfo.apk_size"}})],1)],1):e._e()],1),e.isCommon?n("el-row",{attrs:{gutter:20}},[n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"版本号"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.version_name,callback:function(t){e.$set(e.versionInfo,"version_name",t)},expression:"versionInfo.version_name"}})],1)],1),n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"版本码"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.version_code,callback:function(t){e.$set(e.versionInfo,"version_code",t)},expression:"versionInfo.version_code"}})],1)],1),n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"图片路径"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.update_image,callback:function(t){e.$set(e.versionInfo,"update_image",t)},expression:"versionInfo.update_image"}})],1)],1),n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"标题"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.update_title,callback:function(t){e.$set(e.versionInfo,"update_title",t)},expression:"versionInfo.update_title"}})],1)],1)],1):e._e(),e.isCommon?n("el-row",{attrs:{gutter:20}},[n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"弱更新版本"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.update_version1,callback:function(t){e.$set(e.versionInfo,"update_version1",t)},expression:"versionInfo.update_version1"}})],1)],1),n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"正常更新版本"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.update_version2,callback:function(t){e.$set(e.versionInfo,"update_version2",t)},expression:"versionInfo.update_version2"}})],1)],1),n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"强更新版本"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.update_version3,callback:function(t){e.$set(e.versionInfo,"update_version3",t)},expression:"versionInfo.update_version3"}})],1)],1),n("el-col",{attrs:{span:6}})],1):e._e(),n("el-row",{attrs:{gutter:20}},[n("el-col",{attrs:{span:6}},[e.isAndroid?n("el-form-item",{attrs:{label:"安卓md5"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.apk_md5,callback:function(t){e.$set(e.versionInfo,"apk_md5",t)},expression:"versionInfo.apk_md5"}})],1):e._e()],1),e.isIos?n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"iOS升级的url"}},[n("el-input",{attrs:{clearable:""},model:{value:e.versionInfo.update_url,callback:function(t){e.$set(e.versionInfo,"update_url",t)},expression:"versionInfo.update_url"}})],1)],1):e._e(),e.$route.query.id&&e.isCommon?n("el-col",{attrs:{span:6}},[n("el-form-item",{attrs:{label:"更新状态"}},[n("el-select",{attrs:{clearable:""},model:{value:e.versionInfo.status,callback:function(t){e.$set(e.versionInfo,"status",t)},expression:"versionInfo.status"}},e._l(e.statusList,function(e){return n("el-option",{key:e.id,attrs:{label:e.name,value:e.id}})}),1)],1)],1):e._e()],1),n("el-row",[n("el-col",{attrs:{span:24}},[n("el-form-item",{attrs:{label:"更新内容"}},[n("el-input",{attrs:{type:"textarea",clearable:""},model:{value:e.versionInfo.update_content,callback:function(t){e.$set(e.versionInfo,"update_content",t)},expression:"versionInfo.update_content"}})],1)],1)],1)],1)],1),n("div",{staticClass:"btn-wrapper"},[n("el-button",{attrs:{type:"primary"},on:{click:e.saveVersion}},[e._v("保存")]),n("el-button",{attrs:{type:"default"},on:{click:function(t){return e.$router.go(-1)}}},[e._v("返回")])],1)],1)},r=[],o=n("be94"),s=(n("097d"),n("16b5")),i=n("404a"),l={name:"AddandEditVer",data(){return{id:this.$route.query.id||"",versionInfo:{id:"",apk_md5:"",apk_name:"",apk_size:"",apk_url:"",status:"",type:"",update_content:"",update_image:"",update_title:"",update_url:"",update_version1:"",update_version2:"",update_version3:"",version_code:"",version_name:""},typeList:s["b"],statusList:s["a"],loading:!1}},computed:{title(){return this.id?"编辑版本":"新增版本"},isAndroid(){return 1===this.versionInfo.type},isIos(){return 2===this.versionInfo.type},isCommon(){return 1===this.versionInfo.type||2===this.versionInfo.type}},created(){this.id&&(this.loading=!0,Object(i["b"])(this.id).then(e=>{this.versionInfo=Object(o["a"])({},e)},e=>{this.$message.error(e.errorMsg)}).finally(()=>{this.loading=!1}))},methods:{saveVersion(){this.versionInfo.update_url.length>100?this.$message.error("iOS升级的url长度不能超过100"):(this.loading=!0,Object(i["a"])(this.versionInfo).then(e=>{this.$message.success("保存成功"),this.$router.go(-1)},e=>{this.$message.error(e.errorMsg)}).finally(()=>{this.loading=!1}))}}},u=l,c=(n("db18"),n("2877")),p=Object(c["a"])(u,a,r,!1,null,"a2046506",null);t["default"]=p.exports},db18:function(e,t,n){"use strict";var a=n("89b0"),r=n.n(a);r.a}}]);
//# sourceMappingURL=chunk-7c841ae0.9d35c1c8.js.map