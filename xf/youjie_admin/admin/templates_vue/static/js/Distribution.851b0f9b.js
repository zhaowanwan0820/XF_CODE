(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["Distribution"],{"5a1e":function(e,t,a){},"6b9d":function(e,t,a){"use strict";var r=a("5a1e"),s=a.n(r);s.a},abd3:function(e,t,a){"use strict";a.r(t);var r=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",{staticClass:"container"},[a("page-title",{attrs:{"first-title":"分销数据看板"}}),a("section",{staticClass:"search-wrapper"},[a("el-row",{attrs:{gutter:20}},[a("el-col",{attrs:{span:6}},[a("div",{staticClass:"grid-content bg-purple"},[e._v("\n          用户账号：\n          "),a("span",[e._v(e._s(this.wor_id))])])]),a("el-col",{attrs:{span:6,offset:6}},[a("div",{staticClass:"grid-content bg-purple"},[e._v("\n          小店名称：\n          "),a("span",[e._v(e._s(this.$route.params.name))])])])],1)],1),a("div",{staticClass:"search-wrapper"},[a("el-form",{attrs:{inline:!0}},[a("el-form-item",{attrs:{label:"商品货号"}},[a("el-input",{attrs:{placeholder:"商品货号"},model:{value:e.params.spu,callback:function(t){e.$set(e.params,"spu",t)},expression:"params.spu"}})],1),a("el-form-item",{attrs:{label:"商家"}},[a("el-autocomplete",{staticClass:"inline-input",attrs:{"fetch-suggestions":e.querySearch,placeholder:"请输入内容",clearable:""},model:{value:e.params.suppliersName,callback:function(t){e.$set(e.params,"suppliersName",t)},expression:"params.suppliersName"}})],1),a("el-form-item",{attrs:{label:"商品名称"}},[a("el-input",{attrs:{placeholder:"商品名称"},model:{value:e.params.goodsName,callback:function(t){e.$set(e.params,"goodsName",t)},expression:"params.goodsName"}})],1),a("el-form-item",{attrs:{label:"状态"}},[a("el-select",{attrs:{placeholder:"状态"},on:{change:e.statusChage},model:{value:e.params.status,callback:function(t){e.$set(e.params,"status",t)},expression:"params.status"}},e._l(e.StatusOption,function(e){return a("el-option",{key:e.id,attrs:{label:e.name,value:e.id}})}),1)],1),a("el-form-item",[a("el-button",{attrs:{type:"primary",icon:"el-icon-search"},on:{click:e.search}},[e._v("搜索")]),a("el-button",{attrs:{type:"primary",icon:"el-icon-refresh-left"},on:{click:e.clear}},[e._v("清空")])],1)],1)],1),a("div",{staticClass:"list-wrapper"},[a("el-table",{attrs:{data:e.retailsupplier_list,stripe:"","header-cell-style":{background:"#eef1f6"}}},[a("el-table-column",{attrs:{prop:"spu",label:"SPU货号"}}),a("el-table-column",{attrs:{label:"商品图"},scopedSlots:e._u([{key:"default",fn:function(e){return[a("img",{directives:[{name:"show",rawName:"v-show",value:e.row.goodsPic,expression:"scope.row.goodsPic"}],attrs:{src:e.row.goodsPic,width:"50",height:"50"}})]}}])}),a("el-table-column",{attrs:{prop:"goodsName",label:"商品名称"}}),a("el-table-column",{attrs:{label:"本店售价/积分价"},scopedSlots:e._u([{key:"default",fn:function(t){return[a("span",[e._v("￥"+e._s(t.row.ourShopPrice))]),a("span",[e._v("Ⓗ "+e._s(t.row.ourShopCoin))])]}}])}),a("el-table-column",{attrs:{label:"分销底价/积分价"},scopedSlots:e._u([{key:"default",fn:function(t){return[a("span",[e._v("￥"+e._s(t.row.realShopPrice))]),a("span",[e._v("Ⓗ "+e._s(t.row.distributionCoin))])]}}])}),a("el-table-column",{attrs:{label:"+￥+Ⓗ"},scopedSlots:e._u([{key:"default",fn:function(t){return[a("span",[e._v("￥"+e._s(t.row.differencePrice))]),a("span",[e._v("Ⓗ "+e._s(t.row.differenceCoin))])]}}])}),a("el-table-column",{attrs:{label:"运营底价/积分价"},scopedSlots:e._u([{key:"default",fn:function(t){return[a("span",[e._v("￥"+e._s(t.row.shopPrice))]),a("span",[e._v("Ⓗ "+e._s(t.row.operationCoin))])]}}])}),a("el-table-column",{attrs:{prop:"actName",label:"活动名称"}}),a("el-table-column",{attrs:{prop:"startTime",label:"开始时间",formatter:e.dateFormat}}),a("el-table-column",{attrs:{prop:"endTime",label:"结束时间",formatter:e.dateFormat}}),a("el-table-column",{attrs:{label:"活动运营底价/积分价"},scopedSlots:e._u([{key:"default",fn:function(t){return[a("span",[e._v("￥"+e._s(t.row.actOperationPrice))]),a("span",[e._v("Ⓗ "+e._s(t.row.actOperationCoin))])]}}])}),a("el-table-column",{attrs:{prop:"newcomerCommission",label:"拉新奖励",width:"50"}}),a("el-table-column",{attrs:{prop:"activityCommission",label:"活动奖励",width:"50"}}),a("el-table-column",{attrs:{prop:"mlmShopPrice",label:"分销价"}}),a("el-table-column",{attrs:{prop:"volume",label:"成交数"}}),a("el-table-column",{attrs:{prop:"conversionRate",label:"转化率成交/点击"}}),a("el-table-column",{attrs:{prop:"statusName",label:"状态"}})],1)],1),a("el-pagination",{attrs:{background:"","current-page":e.currentPage,"page-size":e.params.pageSize,layout:"prev, pager, next, jumper",total:e.total},on:{"current-change":e.handleCurrentChange}})],1)},s=[],l=(a("a481"),{name:"DistributionDatil",data(){return{wor_id:this.$route.params.id?this.$route.params.id:"",retailsupplier_list:[],currentPage:1,StatusOption:[{id:1,name:"上架中"},{id:0,name:"已下架"}],params:{pageNumber:1,pageSize:10,spu:"",suppliersName:"",goodsName:"",status:"",mobilePhone:"",userName:""},total:0,restaurants:[]}},created(){this.getList()},mounted(){this.restaurants=this.getSer()},methods:{getList(){let e=JSON.stringify(this.params).replace(/:/g,"=").replace("{","").replace("}","").replace(/,/g,"&").replace(/"/g,"").replace(/\s+/g,"");console.log(this.wor_id),this.$api.get("/mlm/data-board/users/"+this.wor_id+"/goods/list?"+e,null,e=>{1===e.code&&(this.retailsupplier_list=e.data.mlmShopGoodsList,this.total=e.data.totalElements)})},handleCurrentChange(e){this.params.pageNumber=e,this.getList()},getSer(){this.$api.get("/wo/merchant/userType",null,e=>{if(1==e.code){var t=[];e.data.suppliersList.forEach(function(e){let a={};a["value"]=e.suppliersName,t.push(a)}),this.restaurants=t}else this.$message.error(e.msg)})},querySearch(e,t){var a=this.restaurants,r=e?a.filter(this.createFilter(e)):a;t(r)},createFilter(e){return t=>{return 0===t.value.toLowerCase().indexOf(e.toLowerCase())}},statusChage(e){this.params.status=e},search(){this.params.pageNumber=1,this.getList()},clear(){this.getList(),this.params.pageNumber=1,this.params.pageSize=10,this.params.spu="",this.params.suppliersName="",this.params.goodsName="",this.params.status=""},statusName:function(e,t){let a=e[t.property];return 1==a?"上架中":"已下架"},dateFormat:function(e,t){let a=e[t.property];return a?this.utils.formatDate("YYYY-MM-DD HH:mm:ss",1e3*a):0==a?"长期有效":void 0}}}),o=l,i=(a("bd92"),a("2877")),n=Object(i["a"])(o,r,s,!1,null,null,null);t["default"]=n.exports},bd92:function(e,t,a){"use strict";var r=a("cea7"),s=a.n(r);s.a},cea7:function(e,t,a){},ed17:function(e,t,a){"use strict";a.r(t);var r=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",{staticClass:"container"},[a("page-title",{attrs:{"first-title":"分销数据看板"}}),a("div",{staticClass:"search-wrapper"},[a("el-form",{attrs:{inline:!0}},[a("el-form-item",{attrs:{label:"小店名称"}},[a("el-input",{attrs:{placeholder:"小店名称"},model:{value:e.params.userName,callback:function(t){e.$set(e.params,"userName",t)},expression:"params.userName"}})],1),a("el-form-item",{attrs:{label:"用户编号"}},[a("el-input",{attrs:{placeholder:"用户编号"},model:{value:e.params.userId,callback:function(t){e.$set(e.params,"userId",t)},expression:"params.userId"}})],1),a("el-form-item",[a("el-button",{attrs:{type:"primary",icon:"el-icon-search"},on:{click:e.search}},[e._v("搜索")]),a("el-button",{attrs:{type:"primary",icon:"el-icon-refresh-left"},on:{click:e.clear}},[e._v("清空")])],1)],1)],1),a("div",{staticClass:"list-wrapper"},[a("el-table",{staticStyle:{width:"100%"},attrs:{data:e.RetailSupplierList,"header-cell-style":{background:"#eef1f6"}}},[a("el-table-column",{attrs:{prop:"userId",label:"用户账号"}}),a("el-table-column",{attrs:{prop:"userName",label:"小店名称"}}),a("el-table-column",{attrs:{prop:"userMoney",label:"账户佣金"}}),a("el-table-column",{attrs:{prop:"totalMoney",label:"佣金总额"}}),a("el-table-column",{attrs:{prop:"amount",label:"累积提现",formatter:e.numb}}),a("el-table-column",{attrs:{prop:"payPeopleNum",label:"付款人数"}}),a("el-table-column",{attrs:{prop:"payNum",label:"付款笔数"}}),a("el-table-column",{attrs:{prop:"orderAmount",label:"销量总额"}}),a("el-table-column",{attrs:{prop:"inviteCount",label:"粉丝数"}}),a("el-table-column",{attrs:{prop:"total",label:"小店点击",formatter:e.numb}}),a("el-table-column",{attrs:{prop:"shelfCount",label:"累计上架商品",formatter:e.numb}}),a("el-table-column",{attrs:{label:"操作"},scopedSlots:e._u([{key:"default",fn:function(t){return[a("el-button",{attrs:{type:"text",size:"small"},on:{click:function(a){return e.ViewDatil(t.row)}}},[e._v("查看分销"),a("br"),e._v("商品列表")])]}}])})],1)],1),a("el-pagination",{attrs:{background:"","current-page":e.currentPage,"page-size":e.params.pageSize,layout:"prev, pager, next, jumper",total:e.total},on:{"current-change":e.handleCurrentChange}})],1)},s=[],l=(a("a481"),{name:"Distributiondata",data(){return{RetailSupplierList:[],currentPage:1,params:{pageNumber:1,pageSize:10,userName:"",userId:""},total:0}},created(){this.getList()},methods:{search(){this.params.pageNumber=1,this.getList()},getList(){let e=JSON.stringify(this.params).replace(/:/g,"=").replace("{","").replace("}","").replace(/,/g,"&").replace(/"/g,"").replace(/\s+/g,"");this.$api.get("/mlm/data-board/users/list?"+e,null,e=>{1===e.code?(this.RetailSupplierList=e.data.dataBoardList,this.total=e.data.totalElements):this.RetailSupplierList=[]})},handleCurrentChange(e){this.params.pageNumber=e,this.getList()},clear(){this.params.pageNumber=1,this.params.pageSize=10,this.params.userName="",this.params.userId="",this.getList()},ViewDatil(e){this.$router.push({name:"DistributionDatil",params:{id:e.userId,name:e.userName,number:e.mobilePhone}})},numb:function(e,t){let a=e[t.property];return a||"0"}}}),o=l,i=(a("6b9d"),a("2877")),n=Object(i["a"])(o,r,s,!1,null,null,null);t["default"]=n.exports}}]);
//# sourceMappingURL=Distribution.851b0f9b.js.map