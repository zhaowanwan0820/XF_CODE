(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-702ab72c"],{"18bd":function(e,t,a){},"1af6":function(e,t,a){var n=a("63b6");n(n.S,"Array",{isArray:a("9003")})},"20fd":function(e,t,a){"use strict";var n=a("d9f6"),i=a("aebd");e.exports=function(e,t,a){t in e?n.f(e,t,i(0,a)):e[t]=a}},"21a6":function(e,t,a){(function(a){var n,i,s;(function(a,r){i=[],n=r,s="function"===typeof n?n.apply(t,i):n,void 0===s||(e.exports=s)})(0,function(){"use strict";function t(e,t){return"undefined"==typeof t?t={autoBom:!1}:"object"!=typeof t&&(console.warn("Deprecated: Expected third argument to be a object"),t={autoBom:!t}),t.autoBom&&/^\s*(?:text\/\S*|application\/xml|\S*\/\S*\+xml)\s*;.*charset\s*=\s*utf-8/i.test(e.type)?new Blob(["\ufeff",e],{type:e.type}):e}function n(e,t,a){var n=new XMLHttpRequest;n.open("GET",e),n.responseType="blob",n.onload=function(){o(n.response,t,a)},n.onerror=function(){console.error("could not download file")},n.send()}function i(e){var t=new XMLHttpRequest;t.open("HEAD",e,!1);try{t.send()}catch(e){}return 200<=t.status&&299>=t.status}function s(e){try{e.dispatchEvent(new MouseEvent("click"))}catch(n){var t=document.createEvent("MouseEvents");t.initMouseEvent("click",!0,!0,window,0,0,0,80,20,!1,!1,!1,!1,0,null),e.dispatchEvent(t)}}var r="object"==typeof window&&window.window===window?window:"object"==typeof self&&self.self===self?self:"object"==typeof a&&a.global===a?a:void 0,o=r.saveAs||("object"!=typeof window||window!==r?function(){}:"download"in HTMLAnchorElement.prototype?function(e,t,a){var o=r.URL||r.webkitURL,l=document.createElement("a");t=t||e.name||"download",l.download=t,l.rel="noopener","string"==typeof e?(l.href=e,l.origin===location.origin?s(l):i(l.href)?n(e,t,a):s(l,l.target="_blank")):(l.href=o.createObjectURL(e),setTimeout(function(){o.revokeObjectURL(l.href)},4e4),setTimeout(function(){s(l)},0))}:"msSaveOrOpenBlob"in navigator?function(e,a,r){if(a=a||e.name||"download","string"!=typeof e)navigator.msSaveOrOpenBlob(t(e,r),a);else if(i(e))n(e,a,r);else{var o=document.createElement("a");o.href=e,o.target="_blank",setTimeout(function(){s(o)})}}:function(e,t,a,i){if(i=i||open("","_blank"),i&&(i.document.title=i.document.body.innerText="downloading..."),"string"==typeof e)return n(e,t,a);var s="application/octet-stream"===e.type,o=/constructor/i.test(r.HTMLElement)||r.safari,l=/CriOS\/[\d]+/.test(navigator.userAgent);if((l||s&&o)&&"object"==typeof FileReader){var c=new FileReader;c.onloadend=function(){var e=c.result;e=l?e:e.replace(/^data:[^;]*;/,"data:attachment/file;"),i?i.location.href=e:location=e,i=null},c.readAsDataURL(e)}else{var u=r.URL||r.webkitURL,p=u.createObjectURL(e);i?i.location=p:location.href=p,i=null,setTimeout(function(){u.revokeObjectURL(p)},4e4)}});r.saveAs=o.saveAs=o,e.exports=o})}).call(this,a("c8ba"))},"549b":function(e,t,a){"use strict";var n=a("d864"),i=a("63b6"),s=a("241e"),r=a("b0dc"),o=a("3702"),l=a("b447"),c=a("20fd"),u=a("7cd6");i(i.S+i.F*!a("4ee1")(function(e){Array.from(e)}),"Array",{from:function(e){var t,a,i,p,d=s(e),f="function"==typeof this?this:Array,m=arguments.length,h=m>1?arguments[1]:void 0,b=void 0!==h,v=0,g=u(d);if(b&&(h=n(h,m>2?arguments[2]:void 0,2)),void 0==g||f==Array&&o(g))for(t=l(d.length),a=new f(t);t>v;v++)c(a,v,b?h(d[v],v):d[v]);else for(p=g.call(d),a=new f;!(i=p.next()).done;v++)c(a,v,b?r(p,h,[i.value,v],!0):i.value);return a.length=v,a}})},"54a1":function(e,t,a){a("6c1c"),a("1654"),e.exports=a("95d5")},"5d6b":function(e,t,a){var n=a("e53d").parseInt,i=a("a1ce").trim,s=a("e692"),r=/^[-+]?0[xX]/;e.exports=8!==n(s+"08")||22!==n(s+"0x16")?function(e,t){var a=i(String(e),3);return n(a,t>>>0||(r.test(a)?16:10))}:n},7445:function(e,t,a){var n=a("63b6"),i=a("5d6b");n(n.G+n.F*(parseInt!=i),{parseInt:i})},"75fc":function(e,t,a){"use strict";var n=a("a745"),i=a.n(n);function s(e){if(i()(e)){for(var t=0,a=new Array(e.length);t<e.length;t++)a[t]=e[t];return a}}var r=a("774e"),o=a.n(r),l=a("c8bb"),c=a.n(l);function u(e){if(c()(Object(e))||"[object Arguments]"===Object.prototype.toString.call(e))return o()(e)}function p(){throw new TypeError("Invalid attempt to spread non-iterable instance")}function d(e){return s(e)||u(e)||p()}a.d(t,"a",function(){return d})},"774e":function(e,t,a){e.exports=a("d2d5")},"79ba":function(e,t,a){"use strict";a.d(t,"b",function(){return n}),a.d(t,"c",function(){return i}),a.d(t,"a",function(){return s});var n=["待支付","待发货","配送中","待评价","已完成","已取消","配货中"],i=[{name:"今",value:0},{name:"昨",value:1},{name:"近7天",value:7},{name:"近30天",value:30}],s=[{id:1,name:"待对账"},{id:2,name:"对账中"},{id:3,name:"已完成"}]},8691:function(e,t,a){"use strict";var n=a("18bd"),i=a.n(n);i.a},"95d5":function(e,t,a){var n=a("40c3"),i=a("5168")("iterator"),s=a("481b");e.exports=a("584a").isIterable=function(e){var t=Object(e);return void 0!==t[i]||"@@iterator"in t||s.hasOwnProperty(n(t))}},a745:function(e,t,a){e.exports=a("f410")},b9e9:function(e,t,a){a("7445"),e.exports=a("584a").parseInt},c8bb:function(e,t,a){e.exports=a("54a1")},d2d5:function(e,t,a){a("1654"),a("549b"),e.exports=a("584a").Array.from},d7dd:function(e,t,a){"use strict";a.r(t);var n=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",{staticClass:"container"},[a("page-title",{attrs:{"first-title":"账户信息"}}),a("suppliers-base-info",{attrs:{suppliersInfo:e.suppliers_info,suppliersId:e.suppliers_id}}),a("div",{staticClass:"search-wrapper"},[a("el-form",{staticClass:"demo-form-inline",attrs:{inline:!0,model:e.searchParams}},[a("el-form-item",{attrs:{label:"结算单编号："}},[a("el-input",{staticStyle:{width:"200px"},model:{value:e.searchParams.statement_no,callback:function(t){e.$set(e.searchParams,"statement_no",t)},expression:"searchParams.statement_no"}})],1),a("el-form-item",{attrs:{label:"生成日期："}},[a("el-date-picker",{staticStyle:{width:"150px"},attrs:{type:"date",placeholder:"选择开始日期"},model:{value:e.addtime,callback:function(t){e.addtime=t},expression:"addtime"}}),e._v("\n        -\n        "),a("el-date-picker",{staticStyle:{width:"150px"},attrs:{type:"date",placeholder:"选择截至日期"},model:{value:e.endtime,callback:function(t){e.endtime=t},expression:"endtime"}}),a("div",{staticClass:"radio-wrapper"},[a("el-radio-group",{attrs:{size:"small"},on:{change:e.selecttime},model:{value:e.timeselect,callback:function(t){e.timeselect=t},expression:"timeselect"}},e._l(e.timelist,function(t,n){return a("el-radio-button",{key:n,attrs:{label:t.value}},[e._v(e._s(t.name))])}),1)],1)],1),a("div",{staticClass:"cuttline"}),a("el-form-item",[a("el-button",{attrs:{type:"primary"},on:{click:e.search}},[e._v("搜索")]),a("el-button",{on:{click:e.clear}},[e._v("清空")]),e.isSettle?e._e():a("el-button",{attrs:{type:"primary"},on:{click:e.exportList}},[e._v("导出账单详情")]),"all"===e.role||"operater"===e.role?[e.isSettle?a("el-button",{on:{click:e.exportPay}},[e._v("生成付款清单")]):e._e(),a("el-switch",{staticStyle:{padding:"0 20px"},model:{value:e.isSettle,callback:function(t){e.isSettle=t},expression:"isSettle"}}),e.isSettle?a("span",[e._v("切换导出账单")]):a("span",[e._v("切换生成付款清单")])]:e._e()],2)],1)],1),a("div",{staticClass:"list-wrapper"},[a("el-table",{directives:[{name:"loading",rawName:"v-loading",value:e.isLoading,expression:"isLoading"}],ref:"statementList",staticStyle:{width:"100%"},attrs:{data:e.list,"header-cell-style":{background:"#eef1f6"}},on:{select:e.handleSelection,"select-all":e.selectAll}},[e.suppliers_id?[e.checkboxStatus?a("el-table-column",{key:"single",attrs:{type:"selection",selectable:e.checkboxFilter}}):a("el-table-column",{key:"multi",attrs:{type:"selection",selectable:e.filterSelect}})]:[a("el-table-column",{attrs:{type:"selection"}})],a("el-table-column",{attrs:{prop:"statement_no",label:"结算单编号"}}),a("el-table-column",{attrs:{prop:"addtime",label:"结算生成日期",formatter:e.dateFormat}}),a("el-table-column",{attrs:{prop:"add_cash",label:"现金收入（元）"}}),1==e.cooperateType?a("el-table-column",{attrs:{prop:"add_huanbi",label:"积分收入（个）","class-name":"dark"}}):e._e(),a("el-table-column",{attrs:{prop:"sub_cash",label:"现金支出（元）"}}),1==e.cooperateType?a("el-table-column",{attrs:{prop:"sub_huanbi",label:"积分支出（个）","class-name":"dark"}}):e._e(),a("el-table-column",{attrs:{prop:"cash",label:"现金结算（元）"}}),1==e.cooperateType?a("el-table-column",{attrs:{prop:"huanbi",label:"积分结算（个）","class-name":"dark"}}):e._e(),a("el-table-column",{attrs:{prop:"status",label:"状态",formatter:e.statusFormat}}),a("el-table-column",{attrs:{label:"操作"},scopedSlots:e._u([{key:"default",fn:function(t){return[a("el-button",{attrs:{type:"text",size:"small"},on:{click:function(a){return e.viewDetail(t.row.suppliers_statement_id)}}},[e._v("查看")])]}}])})],2)],1),a("el-pagination",{attrs:{background:"","current-page":e.currentPage,"page-size":e.searchParams.size,layout:"prev, pager, next, jumper",total:e.total},on:{"current-change":e.handleCurrentChange}})],1)},i=[],s=(a("c5f6"),a("a4bb")),r=a.n(s),o=a("75fc"),l=(a("06db"),a("e814")),c=a.n(l),u=a("cebc"),p=a("afbd"),d=a("2f62"),f=a("21a6"),m=a("79ba"),h=a("7ee9"),b={name:"SuppliersStatementList",data:function(){return{checkboxStatus:!1,isSettle:!1,statementIds:[],total:0,currentPage:1,addtime:"",endtime:"",searchParams:{type:1,suppliers_id:"",size:10,page:1,addtime:"",endtime:"",statement_no:""},list:[],suppliers_info:{suppliers_name:"",shop_name:"",manager_name:"",manager_tel:""},selectObj:{},statementStatus:{1:"未结算",2:"待结算",3:"已结算"},timelist:m["c"],timeselect:"",isLoading:!1}},components:{SuppliersBaseInfo:h["a"]},created:function(){this.getInfo(),this.getStateList()},computed:Object(u["a"])({},Object(d["c"])({suppliers_id:function(e){return e.suppliers.suppliers_id},role:function(e){return e.settle.role},cooperateType:function(e){return e.suppliers.cooperateType}})),watch:{checkboxStatus:function(){this.$refs.statementList.clearSelection(),this.selectObj={}}},methods:Object(u["a"])({},Object(d["b"])({saveStatementPreview:"saveStatementPreview",saveStatementIdList:"saveStatementIdList"}),{getInfo:function(){var e=this;Object(p["g"])(this.suppliers_id).then(function(t){e.suppliers_info=t})},getStateList:function(){var e=this;this.isLoading=!0,this.searchParams.addtime=c()(this.addtime/1e3),this.searchParams.endtime=c()(this.endtime/1e3),this.searchParams.suppliers_id=this.suppliers_id,Object(p["m"])(this.searchParams).then(function(t){e.list=t.list,e.total=t.paged.total},function(e){console.log(e)}).finally(function(){e.isLoading=!1})},viewDetail:function(e){this.$router.push({name:"SuppliersStatementInfo",params:{id:e}})},handleCurrentChange:function(e){this.searchParams.page=e,this.getStateList()},dateFormat:function(e,t){var a=e[t.property];return this.utils.formatDate("YYYY-MM-DD HH:mm:ss",1e3*a)},selecttime:function(e){var t,a=(new Date).getTime(),n=new Date((new Date).toLocaleDateString()).getTime(),i=new Date((new Date).toLocaleDateString()).getTime()-864e5,s=new Date((new Date).toLocaleDateString()).getTime()-6048e5,r=new Date((new Date).toLocaleDateString()).getTime()-2592e6,o=a;e?1===e?(t=i,o=n-1):7===e?t=s:30===e&&(t=r):t=n,this.addtime=t,this.endtime=o,this.getStateList()},clear:function(){this.searchParams.statement_no="",this.addtime="",this.endtime="",this.timeselect="",this.getStateList()},search:function(){this.searchParams.page=1,this.getStateList()},handleSelection:function(e,t){this.selectObj[t.suppliers_statement_id]?this.changeSelectState([t],!1):this.changeSelectState([t],!0)},selectAll:function(e){e.length?this.changeSelectState(e,!0):this.changeSelectState(this.list,!1)},changeSelectState:function(e,t){var a=Object(u["a"])({},this.selectObj);t?e.forEach(function(e){a[e.suppliers_statement_id]=e}):e.forEach(function(e){delete a[e.suppliers_statement_id]}),this.selectObj=Object(u["a"])({},a)},exportList:function(){var e=this,t=Object(o["a"])(r()(this.selectObj));t.length&&Object(p["c"])({suppliers_id:this.suppliers_id,suppliers_statement_id:t}).then(function(t){var a=e.utils.formatDate("YYYY-MM-DD",(new Date).getTime());Object(f["saveAs"])(new Blob([t.data]),t.filename||e.suppliers_info.suppliers_name+a+"结算单.xls")},function(e){console.log(e)})},exportPay:function(){var e=this,t=Object(o["a"])(r()(this.selectObj));t=t.map(function(e){return Number(e)}),this.saveStatementIdList(t),t.length&&Object(p["q"])(this.suppliers_id,t).then(function(t){t.all_cash=t.all_cash>0?t.all_cash:0,t.all_huanbi=t.all_huanbi>0?t.all_huanbi:0,e.saveStatementPreview(t),e.$router.push({name:"SettlementAdd"})},function(e){console.log(e)})},checkboxFilter:function(e){var t=1!=e.status;return t},navToMultiForm:function(){var e=Object(o["a"])(r()(this.selectObj));e.length&&this.$router.push({name:"MultiSuppliersStatementForm",params:{statements:this.selectObj,type:"create"}})},statusFormat:function(e,t){var a=e[t.property];return this.statementStatus[a]},filterSelect:function(e){if(this.isSettle){var t=!(e.status>1);return t}return!0}})},v=b,g=(a("8691"),a("2877")),w=Object(g["a"])(v,n,i,!1,null,"14b4a154",null);t["default"]=w.exports},e814:function(e,t,a){e.exports=a("b9e9")},f410:function(e,t,a){a("1af6"),e.exports=a("584a").Array.isArray}}]);
//# sourceMappingURL=chunk-702ab72c-legacy.066610de.js.map