(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-3d2b2f94"],{1615:function(t,e,a){},"21a6":function(t,e,a){(function(a){var s,n,i;(function(a,o){n=[],s=o,i="function"===typeof s?s.apply(e,n):s,void 0===i||(t.exports=i)})(0,function(){"use strict";function e(t,e){return"undefined"==typeof e?e={autoBom:!1}:"object"!=typeof e&&(console.warn("Deprecated: Expected third argument to be a object"),e={autoBom:!e}),e.autoBom&&/^\s*(?:text\/\S*|application\/xml|\S*\/\S*\+xml)\s*;.*charset\s*=\s*utf-8/i.test(t.type)?new Blob(["\ufeff",t],{type:t.type}):t}function s(t,e,a){var s=new XMLHttpRequest;s.open("GET",t),s.responseType="blob",s.onload=function(){l(s.response,e,a)},s.onerror=function(){console.error("could not download file")},s.send()}function n(t){var e=new XMLHttpRequest;e.open("HEAD",t,!1);try{e.send()}catch(t){}return 200<=e.status&&299>=e.status}function i(t){try{t.dispatchEvent(new MouseEvent("click"))}catch(s){var e=document.createEvent("MouseEvents");e.initMouseEvent("click",!0,!0,window,0,0,0,80,20,!1,!1,!1,!1,0,null),t.dispatchEvent(e)}}var o="object"==typeof window&&window.window===window?window:"object"==typeof self&&self.self===self?self:"object"==typeof a&&a.global===a?a:void 0,l=o.saveAs||("object"!=typeof window||window!==o?function(){}:"download"in HTMLAnchorElement.prototype?function(t,e,a){var l=o.URL||o.webkitURL,r=document.createElement("a");e=e||t.name||"download",r.download=e,r.rel="noopener","string"==typeof t?(r.href=t,r.origin===location.origin?i(r):n(r.href)?s(t,e,a):i(r,r.target="_blank")):(r.href=l.createObjectURL(t),setTimeout(function(){l.revokeObjectURL(r.href)},4e4),setTimeout(function(){i(r)},0))}:"msSaveOrOpenBlob"in navigator?function(t,a,o){if(a=a||t.name||"download","string"!=typeof t)navigator.msSaveOrOpenBlob(e(t,o),a);else if(n(t))s(t,a,o);else{var l=document.createElement("a");l.href=t,l.target="_blank",setTimeout(function(){i(l)})}}:function(t,e,a,n){if(n=n||open("","_blank"),n&&(n.document.title=n.document.body.innerText="downloading..."),"string"==typeof t)return s(t,e,a);var i="application/octet-stream"===t.type,l=/constructor/i.test(o.HTMLElement)||o.safari,r=/CriOS\/[\d]+/.test(navigator.userAgent);if((r||i&&l)&&"object"==typeof FileReader){var p=new FileReader;p.onloadend=function(){var t=p.result;t=r?t:t.replace(/^data:[^;]*;/,"data:attachment/file;"),n?n.location.href=t:location=t,n=null},p.readAsDataURL(t)}else{var u=o.URL||o.webkitURL,c=u.createObjectURL(t);n?n.location=c:location.href=c,n=null,setTimeout(function(){u.revokeObjectURL(c)},4e4)}});o.saveAs=l.saveAs=l,t.exports=l})}).call(this,a("c8ba"))},"28a3":function(t,e,a){"use strict";var s=a("1615"),n=a.n(s);n.a},"3da1":function(t,e,a){"use strict";a.r(e);var s=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{staticClass:"container"},[a("page-title",{attrs:{"first-title":"账户信息"}}),a("suppliers-base-info",{attrs:{suppliersInfo:t.suppliers_info,suppliersId:t.suppliers_id}}),a("section",[a("h3",[t._v(t._s(t.statement_info.statement_no)+"账单汇总详情")]),a("span",[t._v("结算单编号：")]),a("el-button",{staticClass:"export",attrs:{type:"primary"},on:{click:t.exportWindList}},[t._v("导出结算单")]),a("label",[t._v(t._s(t.statement_info.statement_no))]),a("div",[a("h4",{staticClass:"title-item"},[t._v("收入")]),a("div",{staticClass:"title-item"},[a("p",[t._v("销售总额（元）")]),a("p",[t._v(t._s(t.statement_info.total_add))])]),a("div",{staticClass:"title-item"},[a("p",[t._v("现金收入（元）")]),a("p",[t._v(t._s(t.statement_info.add_cash))])]),1==t.cooperateType?a("div",{staticClass:"title-item"},[a("p",[t._v("积分收入")]),a("p",[t._v(t._s(t.statement_info.add_huanbi))])]):t._e(),a("div",{staticClass:"title-item"},[a("p",[t._v("笔数")]),a("p",[t._v(t._s(t.statement_info.num_add))])])]),a("div",[a("h4",{staticClass:"title-item"},[t._v("支出")]),a("div",{staticClass:"title-item"},[a("p",[t._v("退款总额（元）")]),a("p",[t._v("-"+t._s(t.statement_info.total_sub))])]),a("div",{staticClass:"title-item"},[a("p",[t._v("退款现金（元）")]),a("p",[t._v("-"+t._s(t.statement_info.sub_cash))])]),1==t.cooperateType?a("div",{staticClass:"title-item"},[a("p",[t._v("退积分 ")]),a("p",[t._v("-"+t._s(t.statement_info.sub_huanbi))])]):t._e(),a("div",{staticClass:"title-item"},[a("p",[t._v("笔数")]),a("p",[t._v(t._s(t.statement_info.num_sub))])])]),a("div",[a("h4",{staticClass:"title-item"},[t._v("结算")]),a("div",{staticClass:"title-item"},[a("p",[t._v("结算总额（元）")]),a("p",[t._v(t._s(t.statement_info.total))])]),a("div",{staticClass:"title-item"},[a("p",[t._v("结算现金（元）")]),a("p",[t._v(t._s(t.statement_info.cash))])]),1==t.cooperateType?a("div",{staticClass:"title-item"},[a("p",[t._v("结算积分")]),a("p",[t._v(t._s(t.statement_info.huanbi))])]):t._e()])],1),a("div",{staticClass:"list-wrapper"},[a("el-tabs",{attrs:{type:"border-card"},on:{"tab-click":t.tabClick},model:{value:t.listParams.type,callback:function(e){t.$set(t.listParams,"type",e)},expression:"listParams.type"}},[a("el-tab-pane",{attrs:{label:"总收入",name:"1"}},[a("el-table",{directives:[{name:"loading",rawName:"v-loading",value:t.isLoading,expression:"isLoading"}],staticStyle:{width:"100%"},attrs:{data:t.list,"header-cell-style":{background:"#eef1f6"}}},[a("el-table-column",{attrs:{prop:"order_sn",label:"订单号"}}),a("el-table-column",{attrs:{prop:"add_time",label:"下单时间",formatter:t.dateFormat}}),a("el-table-column",{attrs:{label:"实付金额（元）"},scopedSlots:t._u([{key:"default",fn:function(e){return[1==e.row.order_type?a("span",[t._v(t._s(e.row.total_amount))]):a("span",[t._v(t._s(e.row.goods_amount))])]}}])}),a("el-table-column",{attrs:{label:"支付现金额（元）"},scopedSlots:t._u([{key:"default",fn:function(e){return[1==e.row.order_type?a("span",[t._v(t._s(e.row.real_money_paid))]):a("span",[t._v(t._s(e.row.money_paid))])]}}])}),1==t.cooperateType?a("el-table-column",{attrs:{label:"支付积分额"},scopedSlots:t._u([{key:"default",fn:function(e){return[1==e.row.order_type?a("span",[t._v(t._s(e.row.real_surplus))]):a("span",[t._v(t._s(e.row.surplus))])]}}],null,!1,4031455201)}):t._e(),a("el-table-column",{attrs:{prop:"real_cash_back",label:"现金退款"}}),1==t.cooperateType?a("el-table-column",{attrs:{prop:"real_surplus_back",label:"积分退款"}}):t._e(),a("el-table-column",{attrs:{prop:"shipping_fee",label:"运费"}}),a("el-table-column",{attrs:{prop:"showStatus",label:"订单状态"}}),a("el-table-column",{attrs:{label:"操作"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("el-button",{attrs:{type:"text",size:"small"},on:{click:function(a){return t.viewDetail(e.row.order_id)}}},[t._v("查看")])]}}])})],1)],1),a("el-tab-pane",{directives:[{name:"loading",rawName:"v-loading",value:t.isLoading,expression:"isLoading"}],attrs:{label:"总支出",name:"2"}},[a("el-table",{staticStyle:{width:"100%"},attrs:{data:t.list}},[a("el-table-column",{attrs:{prop:"order_sn",label:"订单号"}}),a("el-table-column",{attrs:{prop:"add_time",label:"下单时间",formatter:t.dateFormat}}),a("el-table-column",{attrs:{prop:"totalBack",label:"支出金额（元）"}}),a("el-table-column",{attrs:{prop:"cash_back",label:"支出现金额（元）"}}),1==t.cooperateType?a("el-table-column",{attrs:{prop:"surplus_back",label:"支出积分额"}}):t._e(),a("el-table-column",{attrs:{prop:"showStatus",label:"订单状态"}}),a("el-table-column",{attrs:{label:"操作"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("el-button",{attrs:{type:"text",size:"small"},on:{click:function(a){return t.viewDetail(e.row.order_id)}}},[t._v("查看")])]}}])})],1)],1)],1)],1),a("el-pagination",{attrs:{background:"","current-page":t.currentPage,"page-size":t.listParams.size,layout:"prev, pager, next, jumper",total:t.total},on:{"current-change":t.handleCurrentChange}})],1)},n=[],i=(a("7f7f"),a("06db"),a("cebc")),o=a("afbd"),l=a("2f62"),r=a("21a6"),p=a("7ee9"),u={name:"SuppliersStatementInfo",data:function(){return{suppliers_statement_id:this.$route.params.id?this.$route.params.id:"",total:10,currentPage:1,listParams:{type:"1",suppliers_id:"",suppliers_statement_id:this.$route.params.id?this.$route.params.id:"",size:10,page:1},suppliers_info:{suppliers_name:"",shop_name:"",manager_name:"",manager_tel:"",suppliers_nam:""},list:[],statement_info:{suppliers_statement_id:"",type:1,statement_no:"",addtime:"",total_add:0,add_cash:0,add_huanbi:0,num_add:0,total_sub:0,sub_cash:0,sub_huanbi:0,num_sub:0,huanbi:0,cash:0,total:0,bill_start_time:"",bill_end_time:""},isLoading:!1}},components:{SuppliersBaseInfo:p["a"]},created:function(){this.getStatementInfo(),this.getDetailList()},computed:Object(i["a"])({},Object(l["c"])({suppliers_id:function(t){return t.suppliers.suppliers_id},suppliers_name:function(t){return t.suppliers.suppliers_name},cooperateType:function(t){return t.suppliers.cooperateType}})),methods:{getStatementInfo:function(){var t=this,e={};e["suppliers_statement_id"]=this.suppliers_statement_id,e["suppliers_id"]=this.suppliers_id,Object(o["l"])(e).then(function(e){t.suppliers_info=e.suppliers_info,t.statement_info=e.statement_info},function(t){console.log(t)})},getDetailList:function(){var t=this;this.isLoading=!0,this.listParams.suppliers_id=this.suppliers_id,Object(o["k"])(this.listParams).then(function(e){t.list=e.list,t.total=e.paged.total},function(e){console.log(e),t.list=[],t.total=0}).finally(function(){t.isLoading=!1})},handleCurrentChange:function(t){this.listParams.page=t,this.getDetailList()},dateFormat:function(t,e){var a=t[e.property];return this.utils.formatDate("YYYY-MM-DD HH:mm:ss",1e3*a)},viewDetail:function(t){window.location.href="/admin/order.php?act=info&order_id="+t},tabClick:function(t){this.listParams.type=t.name,this.currentPage=1,this.getDetailList()},exportWindList:function(){var t=this,e=[this.suppliers_statement_id];Object(o["c"])({suppliers_id:this.suppliers_id,suppliers_statement_id:e}).then(function(e){var a=t.utils.formatDate("YYYY-MM-DD",(new Date).getTime());Object(r["saveAs"])(new Blob([e.data]),e.filename||t.suppliers_name+a+"结算单.xls")},function(t){console.log(t)})}}},c=u,d=(a("28a3"),a("2877")),_=Object(d["a"])(c,s,n,!1,null,"55d4002b",null);e["default"]=_.exports}}]);
//# sourceMappingURL=chunk-3d2b2f94-legacy.1317ecaf.js.map