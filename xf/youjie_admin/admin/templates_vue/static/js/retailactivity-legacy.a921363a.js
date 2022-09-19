(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["retailactivity"],{"05b4":function(t,e,a){"use strict";var s=a("dc90"),i=a.n(s);i.a},"243e":function(t,e,a){"use strict";var s=a("cda5"),i=a.n(s);i.a},"4b5c":function(t,e,a){"use strict";a.r(e);var s=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{staticClass:"container"},[a("page-title",{attrs:{"first-title":"分销活动"}}),a("div",{staticClass:"button-wrapper"},[a("el-button",{attrs:{type:"primary"},on:{click:function(e){return t.activityAdd()}}},[t._v("创建活动")])],1),a("div",{staticClass:"search-wrapper"},[a("el-form",{staticClass:"demo-form-inline",attrs:{inline:!0}},[a("el-form-item",{attrs:{label:"活动ID"}},[a("el-input",{attrs:{placeholder:"活动ID"},model:{value:t.params.actId,callback:function(e){t.$set(t.params,"actId",e)},expression:"params.actId"}})],1),a("el-form-item",{attrs:{label:"活动名称"}},[a("el-input",{attrs:{placeholder:"活动名称"},model:{value:t.params.actName,callback:function(e){t.$set(t.params,"actName",e)},expression:"params.actName"}})],1),a("el-form-item",{attrs:{label:"开始时间"}},[a("el-date-picker",{attrs:{type:"datetime",placeholder:"开始时间"},model:{value:t.params.startTime,callback:function(e){t.$set(t.params,"startTime",e)},expression:"params.startTime"}})],1),a("el-form-item",{attrs:{label:"结束时间"}},[a("el-date-picker",{attrs:{type:"datetime",placeholder:"结束时间"},model:{value:t.params.endTime,callback:function(e){t.$set(t.params,"endTime",e)},expression:"params.endTime"}})],1),a("el-form-item",{attrs:{label:"申请人"}},[a("el-input",{attrs:{placeholder:"申请人"},model:{value:t.params.applicant,callback:function(e){t.$set(t.params,"applicant",e)},expression:"params.applicant"}})],1),a("el-form-item",{attrs:{label:"审批人"}},[a("el-input",{attrs:{placeholder:"审批人"},model:{value:t.params.approver,callback:function(e){t.$set(t.params,"approver",e)},expression:"params.approver"}})],1),a("el-form-item",{attrs:{label:"活动状态"}},[a("el-select",{attrs:{placeholder:"活动状态"},model:{value:t.params.isCheck,callback:function(e){t.$set(t.params,"isCheck",e)},expression:"params.isCheck"}},t._l(t.activityStateList,function(t){return a("el-option",{key:t.id,attrs:{label:t.name,value:t.id}})}),1)],1),a("el-form-item",[a("el-button",{attrs:{type:"primary",icon:"el-icon-search"},on:{click:function(e){return t.getActivityList()}}},[t._v("搜索")]),a("el-button",{attrs:{type:"primary",icon:"el-icon-refresh"},on:{click:function(e){return t.clear()}}},[t._v("清空")])],1)],1)],1),a("div",{staticClass:"list-wrapper"},[a("el-table",{staticStyle:{width:"100%"},attrs:{data:t.retailactivity_list,stripe:"","header-cell-style":{background:"#eef1f6"}}},[a("el-table-column",{attrs:{prop:"actId",label:"活动ID"}}),a("el-table-column",{attrs:{prop:"actName",label:"活动名称"}}),a("el-table-column",{attrs:{prop:"applicant",label:"申请人"}}),a("el-table-column",{attrs:{prop:"approver",label:"审批人"}}),a("el-table-column",{attrs:{prop:"spuQuantity",label:"SPU数量"}}),a("el-table-column",{attrs:{prop:"startTime",label:"开始时间",formatter:t.dateFormat}}),a("el-table-column",{attrs:{prop:"endTime",label:"结束时间",formatter:t.dateFormat}}),a("el-table-column",{attrs:{prop:"isCheckName",label:"当前状态"}}),a("el-table-column",{attrs:{label:"操作"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("el-button",{attrs:{type:"text",size:"small"},on:{click:function(a){return t.activityInfos(e.row.actId)}}},[t._v("查看")])]}}])})],1)],1),a("el-pagination",{attrs:{background:"","current-page":t.currentPage,"page-size":t.params.pageSize,layout:"prev, pager, next, jumper",total:t.total},on:{"current-change":t.handleCurrentChange}})],1)},i=[],o=a("f499"),n=a.n(o),l=(a("a481"),a("cebc")),r=a("94e5"),c=a("2f62"),p={name:"RetailActivityList",data:function(){return{retailactivity_list:[],currentPage:1,params:{pageNumber:1,pageSize:10,actId:"",actName:"",startTime:"",endTime:"",applicant:"",approver:"",isCheck:""},total:0,activityStateList:r["a"]}},created:function(){this.getActivityList()},methods:Object(l["a"])({},Object(c["b"])({setActivityInfos:"setActivityInfos"}),{getActivityList:function(){var t=this,e=this.params.startTime,a=this.params.endTime;this.params.startTime=e?e.getTime()/1e3:"",this.params.endTime=a?a.getTime()/1e3:"";var s=n()(this.params).replace(/:/g,"=").replace("{","").replace("}","").replace(/,/g,"&").replace(/"/g,"").replace(/\s+/g,"");this.$api.get("/mlm/activity/list?"+s,null,function(e){1===e.code&&(t.retailactivity_list=e.data.mlmActivityList,t.total=e.data.totalElements)})},handleCurrentChange:function(t){this.params.pageNumber=t,this.getActivityList()},activityAdd:function(){this.setActivityInfos({actId:"",actName:"",actDesc:"",startTime:"",endTime:""}),this.$router.push({name:"RetailActivityAdd",params:{}})},clear:function(){this.params.pageNumber=1,this.params.pageSize=10;var t=this.params;t.actId="",t.actName="",t.startTime="",t.endTime="",t.approver="",t.applicant="",t.isCheck=""},activityInfos:function(t){this.$router.push({name:"RetailActivityAdd",params:{actId:t}})},dateFormat:function(t,e){var a=t[e.property];return a?this.utils.formatDate("YYYY-MM-DD HH:mm:ss",1e3*a):"长期有效"}})},u=p,m=(a("05b4"),a("2877")),f=Object(m["a"])(u,s,i,!1,null,"06e40970",null);e["default"]=f.exports},5251:function(t,e,a){"use strict";a.r(e);var s=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{staticClass:"container"},[a("page-title",{attrs:{"first-title":"分销活动管理"}}),t.fill_in_info?a("v-fill-act-info"):a("v-wait-verify"),a("section",[a("div",[a("h2",[a("span",[t._v("批量导入商品")]),t.import_flag?a("span",{staticClass:"down-temp",on:{click:t.exporttemplate}},[t._v("导出")]):a("span",{staticClass:"down-temp",on:{click:t.downtemplate}},[t._v("模板下载")])]),t.import_flag?[t.isWaitVerify?a("v-act-search"):t._e(),a("el-table",{staticStyle:{width:"100%"},attrs:{data:t.act_supplier_lists,stripe:"","header-cell-style":{background:"#eef1f6"}}},[a("el-table-column",{attrs:{prop:"goodsId",label:"商品编号"}}),a("el-table-column",{attrs:{prop:"spu",label:"SPU货号"}}),a("el-table-column",{attrs:{prop:"goodsName",label:"商品名称"}}),a("el-table-column",{attrs:{label:"本店售价/积分价"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("span",[t._v(t._s(e.row.ourShopPrice))]),t._v(" /\n              "),a("span",[t._v(t._s(e.row.ourShopCoin))])]}}],null,!1,2113797931)}),a("el-table-column",{attrs:{label:"分销底价/积分价"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("span",[t._v(t._s(e.row.realShopPrice))]),t._v(" /\n              "),a("span",[t._v(t._s(e.row.distributionCoin))])]}}],null,!1,2237764847)}),a("el-table-column",{attrs:{label:"+￥+Ⓗ"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("span",[t._v("￥"+t._s(e.row.differencePrice))]),t._v(" /\n              "),a("span",[t._v("Ⓗ"+t._s(e.row.differenceCoin))])]}}],null,!1,4248170483)}),a("el-table-column",{attrs:{label:"运营底价/积分价"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("span",[t._v(t._s(e.row.shopPrice))]),t._v(" /\n              "),a("span",[t._v(t._s(e.row.operationCoin))])]}}],null,!1,2190377594)}),a("el-table-column",{attrs:{label:"活动底价/积分价"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("span",[t._v(t._s(e.row.actOperationPrice))]),t._v(" /\n              "),a("span",[t._v(t._s(e.row.actOperationCoin))])]}}],null,!1,3491768939)}),a("el-table-column",{attrs:{prop:"newcomerCommission",label:"拉新奖励"}}),a("el-table-column",{attrs:{prop:"actCommission",label:"活动奖励"}}),a("el-table-column",{attrs:{prop:"statusName",label:"状态"}}),a("el-table-column",{attrs:{label:"操作"},scopedSlots:t._u([{key:"default",fn:function(e){return 1===e.row.status?[a("el-button",{staticStyle:{color:"#f00"},attrs:{type:"text",size:"small"},on:{click:function(a){return t.stopThisSupplier(e.row.goodsId,e.row.goodsName,e.row.spu)}}},[t._v("停用")])]:void 0}}],null,!0)})],1)]:t._e(),t.isWaitVerify?t._e():a("div",[a("div",{staticClass:"add-wrapper"},[a("p",{staticClass:"add"},[a("el-upload",{attrs:{action:t.action,data:t.params_info,"on-success":t.importSuccess,"show-file-list":!1,disabled:!t.info_flag,"with-credentials":!0}},[t._v("+ "+t._s(t.importSuppliers.add_content))])],1)]),a("div",{staticClass:"btn-wrapper"},[a("el-button",{attrs:{type:t.importSuppliers.type,disabled:!(t.import_flag&&t.info_flag)},on:{click:function(e){return t.save()}}},[t._v(t._s(t.importSuppliers.btn_content))]),a("el-button",{on:{click:function(e){return t.cancel()}}},[t._v("取消")])],1)])],2)]),a("div",{staticClass:"self_dialog"},[a("el-dialog",{attrs:{visible:t.centerDialogVisible,width:"50%","show-close":!1},on:{"update:visible":function(e){t.centerDialogVisible=e}}},[a("div",{attrs:{slot:"title"},slot:"title"},[t._v("导入商品确认")]),a("div",[a("div",{staticClass:"main-title"},[a("p",[t._v("\n            本次共导入\n            "),a("span",[t._v(t._s(t.res_data.totalCount))]),t._v("件SPU数据\n          ")]),a("p",[t._v("\n            其中成功\n            "),a("span",[t._v(t._s(t.successcount))]),t._v("件，失败 "),a("span",[t._v(t._s(t.res_data.failCount))]),t._v("件\n          ")]),a("p",[t._v("请仔细核对商品信息后重新上传失败数据")])]),a("div",{staticClass:"fail_info"},[a("p",[t._v("失败数据如下：")]),t._l(t.res_data.failInfos,function(e){return a("p",{key:e.failGoodsNum},[a("span",[t._v("商品编码："+t._s(e.failGoodsNum))]),t._v("\n                  \n            "),a("span",[t._v("失败提示："+t._s(e.failMsg))])])})],2)]),a("div",{staticClass:"dialog-footer",attrs:{slot:"footer"},slot:"footer"},[a("el-button",{attrs:{type:"primary"},on:{click:t.confirmCallBack}},[t._v("确 定")])],1)]),a("el-dialog",{attrs:{visible:t.agreeDialogVisible,width:"50%",center:"","show-close":!1},on:{"update:visible":function(e){t.agreeDialogVisible=e}}},[a("div",{attrs:{slot:"title"},slot:"title"},[t._v("审核通过确认")]),a("div",[a("p",[t._v("\n          确定要通过\n          "),a("label",[t._v(t._s(t.actInfos.actName))]),t._v("活动吗\n        ")]),a("p",[t._v("\n          该活动包含\n          "),a("span",{staticClass:"color-01"},[t._v(t._s(t.actInfos.spuQuantity))]),t._v("个SPU\n        ")]),a("p",[t._v("活动通过后，在相应时段中，活动中的全部商品的价格将会刷新，请慎重操作")])]),a("div",{staticClass:"dialog-footer",attrs:{slot:"footer"},slot:"footer"},[a("el-button",{attrs:{type:"primary"},on:{click:function(e){return t.verifyOperate(1)}}},[t._v("通过")]),a("el-button",{staticClass:"refuse",on:{click:function(e){return t.verifyOperate(2)}}},[t._v("拒绝")])],1)]),a("el-dialog",{attrs:{visible:t.stopDialogVisible,width:"50%",center:"","show-close":!1},on:{"update:visible":function(e){t.stopDialogVisible=e}}},[a("div",{attrs:{slot:"title"},slot:"title"},[t._v("活动停用确认")]),a("div",[a("p",[t._v("\n          确定要停用\n          "),a("label",[t._v(t._s(t.actInfos.actName))]),t._v("活动吗\n        ")]),a("p",[t._v("活动停用后，将不能再次编辑并回复，请慎重操作")])]),a("div",[a("p",{staticStyle:{"text-align":"left"}},[a("label",[t._v("*")]),t._v("请输入停用理由（不少于10个汉字）")]),a("el-input",{attrs:{type:"textarea",rows:2,placeholder:"请输入内容"},model:{value:t.stop_reason,callback:function(e){t.stop_reason=e},expression:"stop_reason"}})],1),a("div",{staticClass:"dialog-footer",attrs:{slot:"footer"},slot:"footer"},[a("el-button",{attrs:{type:"primary"},on:{click:t.stopCancel}},[t._v("取消")]),a("el-button",{staticClass:"refuse",on:{click:t.stopActivity}},[t._v("停用")])],1)]),a("el-dialog",{attrs:{visible:t.stopGoodsDialog,width:"50%",center:"","show-close":!1},on:{"update:visible":function(e){t.stopGoodsDialog=e}}},[a("div",{attrs:{slot:"title"},slot:"title"},[t._v("商品停用确认")]),a("div",[a("p",[t._v("确定要停用以下SPU商品分销价格吗？")]),a("p",[t._v("\n          商品名称：\n          "),a("span",[t._v(t._s(t.cur_stop_good_name))])]),a("p",[t._v("\n          SPU货号：\n          "),a("span",[t._v(t._s(t.cur_stop_spu))])]),a("p",[t._v("\n          该操作不影响该活动中的其他SPU商品的分销价格。停用后不能再次启用，请调整该商品的分销价格设置后，重新上传该商品\n        ")]),a("p",[t._v("停用后，该SPU商品将沿用初始设置的分销价格")])]),a("div",{staticClass:"dialog-footer",attrs:{slot:"footer"},slot:"footer"},[a("el-button",{attrs:{type:"primary"},on:{click:t.stopGoodsCancel}},[t._v("取消")]),a("el-button",{staticClass:"refuse",on:{click:t.stopGoods}},[t._v("停用")])],1)])],1)],1)},i=[],o=a("cebc"),n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("el-form",{attrs:{inline:!0}},[a("el-form-item",{attrs:{label:"商品编号"}},[a("el-input",{attrs:{placeholder:"请输入商品编号"},model:{value:t.params.goodsId,callback:function(e){t.$set(t.params,"goodsId",e)},expression:"params.goodsId"}})],1),a("el-form-item",{attrs:{label:"SPU货号"}},[a("el-input",{attrs:{placeholder:"请输入SPU货号"},model:{value:t.params.spu,callback:function(e){t.$set(t.params,"spu",e)},expression:"params.spu"}})],1),a("el-form-item",{attrs:{label:"商品名称"}},[a("el-input",{attrs:{placeholder:"请输入商品名称"},model:{value:t.params.goodsName,callback:function(e){t.$set(t.params,"goodsName",e)},expression:"params.goodsName"}})],1),a("el-form-item",{attrs:{label:"状态"}},[a("el-select",{attrs:{placeholder:"请选择商品状态"},model:{value:t.params.status,callback:function(e){t.$set(t.params,"status",e)},expression:"params.status"}},t._l(t.supplierStatus,function(t){return a("el-option",{key:t.id,attrs:{label:t.name,value:t.id}})}),1)],1),a("el-form-item",{staticStyle:{float:"right"}},[a("el-button",{attrs:{type:"primary",icon:"el-icon-search"},on:{click:t.searchSupplier}},[t._v("搜索")]),a("el-button",{attrs:{type:"primary",icon:"el-icon-refresh"},on:{click:t.clear}},[t._v("清空")])],1)],1)],1)},l=[],r=a("2f62"),c={name:"ActSupplierSearch",data:function(){return{supplierStatus:[{id:0,name:"已停用"},{id:1,name:"启用"}],params:{goodsId:"",spu:"",goodsName:"",status:""}}},computed:Object(o["a"])({},Object(r["c"])({actInfos:function(t){return t.retailactivity.actInfos}})),methods:Object(o["a"])({searchSupplier:function(){var t=this;console.log(this.params),this.$api.get("/mlm/activity/".concat(this.actInfos.actId,"/goods/list"),this.params,function(e){1===e.code&&(console.log(e),t.updateGoodsList(e.data.mlmActivityGoodsList))})},clear:function(){var t=this.params;for(var e in t)t[e]=""}},Object(r["b"])({updateGoodsList:"updateGoodsList"}))},p=c,u=a("2877"),m=Object(u["a"])(p,n,l,!1,null,"67b84143",null),f=m.exports,d=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("section",[a("el-form",{ref:"actInfos",attrs:{model:t.actInfos,rules:t.rules,"label-width":"80px"}},[a("h2",[t._v("活动基础信息")]),a("el-form-item",{attrs:{label:"活动名称",prop:"actName"}},[a("el-input",{staticStyle:{width:"400px"},attrs:{placeholder:"如： 0601儿童节专场"},model:{value:t.actInfos.actName,callback:function(e){t.$set(t.actInfos,"actName",e)},expression:"actInfos.actName"}}),a("span",{staticClass:"warn"},[t._v("必填10个汉字以内，后台记录该活动名称，历史名称唯一")])],1),a("el-form-item",{attrs:{label:"活动描述",prop:"actDesc"}},[a("el-input",{staticStyle:{width:"400px"},attrs:{placeholder:"如： 女王节满减/折扣活动"},model:{value:t.actInfos.actDesc,callback:function(e){t.$set(t.actInfos,"actDesc",e)},expression:"actInfos.actDesc"}}),a("span",{staticClass:"warn"},[t._v("选填20个汉字以内，描述该活动内容，方便管理记录")])],1),a("el-form-item",{attrs:{label:"活动时间",required:""}},[a("el-col",{attrs:{span:4}},[a("el-form-item",{attrs:{prop:"startTime"}},[a("el-date-picker",{staticStyle:{width:"100%"},attrs:{type:"datetime",placeholder:"选择开始日期"},model:{value:t.actInfos.startTime,callback:function(e){t.$set(t.actInfos,"startTime",e)},expression:"actInfos.startTime"}})],1)],1),a("el-col",{staticClass:"line",staticStyle:{"text-align":"center"},attrs:{span:1}},[t._v("-")]),a("el-col",{attrs:{span:4}},[a("el-form-item",{attrs:{prop:"endTime"}},[a("el-date-picker",{staticStyle:{width:"100%"},attrs:{type:"datetime",placeholder:"选择结束时间"},model:{value:t.actInfos.endTime,callback:function(e){t.$set(t.actInfos,"endTime",e)},expression:"actInfos.endTime"}})],1)],1),a("el-col",{staticClass:"line",attrs:{span:8}},[a("span",{staticClass:"warn"},[t._v("结束时间留空，则长期有效")])])],1)],1)],1)},v=[],_={name:"FillinActInfo",data:function(){return{rules:{actName:{required:!0,message:"请输入活动名称",trigger:"blur"},actDesc:{required:!0,message:"请输入活动描述",trigger:"blur"},startTime:{type:"date",required:!0,message:"请选择活动开始时间",trigger:"change"}}}},watch:{actInfos:{handler:function(){var t=this;this.$refs.actInfos.validate(function(e){if(e){if(t.actInfos.endTime&&t.actInfos.endTime.getTime()<t.actInfos.startTime.getTime())return void t.$alert("结束时间不能早于开始时间",{confirmButtonText:"确定",callback:function(){t.actInfos.endTime=""}});t.actInfoFilled(!0),t.setActivityInfos(t.actInfos)}else t.actInfoFilled(!1)})},deep:!0}},computed:Object(o["a"])({},Object(r["c"])({actInfos:function(t){return t.retailactivity.actInfos}})),methods:Object(o["a"])({},Object(r["b"])({setActivityInfos:"setActivityInfos",actInfoFilled:"actInfoFilled"}))},h=_,b=(a("243e"),Object(u["a"])(h,d,v,!1,null,"335d963d",null)),g=b.exports,y=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("section",[a("h2",[t._v("活动基础信息")]),a("el-row",{attrs:{gutter:20}},[a("el-col",{attrs:{span:6}},[t._v("活动ID："+t._s(t.actInfos.actId))]),a("el-col",{attrs:{span:6}},[t._v("活动描述："+t._s(t.actInfos.actDesc))]),a("el-col",{attrs:{span:6}},[a("h2",[a("i",[t._v("活动状态："+t._s(t.status))])])]),t.show_operate?a("el-col",{attrs:{span:6}},[0===t.activity_state?[a("el-button",{attrs:{type:"primary"},on:{click:t.agreement}},[t._v("通过")]),a("el-button",{staticClass:"refuse",on:{click:t.refuse}},[t._v("拒绝")])]:5===t.activity_state||6===t.activity_state?[a("el-button",{attrs:{type:"primary"},on:{click:t.exitact}},[t._v("编辑")]),a("el-button",{staticClass:"refuse",on:{click:t.stopact}},[t._v("停用")])]:3===t.activity_state?[a("p",[t._v("操作员："+t._s(t.appr_infos.approver))]),a("p",[t._v("\n            停用时间："),t.appr_infos.disableTime?[t._v(t._s(t.utils.formatDate("YYYY-MM-DD HH:mm:ss",1e3*t.appr_infos.disableTime)))]:t._e()],2),a("p",[t._v("停用原因："+t._s(t.appr_infos.disableReason))])]:t._e()],2):t._e()],1),a("el-row",{attrs:{gutter:20}},[a("el-col",{attrs:{span:6}},[t._v("活动名称："+t._s(t.actInfos.actName))]),a("el-col",{attrs:{span:6}},[t._v("SPU数量："+t._s(t.actInfos.spuQuantity))])],1),a("el-row",{attrs:{gutter:20}},[a("el-col",{attrs:{span:6}},[t._v("申请人："+t._s(t.appr_infos.applicant))]),a("el-col",{attrs:{span:6}},[t._v("审批人："+t._s(t.appr_infos.approver))])],1),a("el-row",{attrs:{gutter:20}},[a("el-col",{attrs:{span:6}},[t._v("提交时间：\n        "),t.appr_infos.createdAt?[t._v(t._s(t.utils.formatDate("YYYY-MM-DD HH:mm:ss",1e3*t.appr_infos.createdAt)))]:t._e()],2),a("el-col",{attrs:{span:6}},[t._v("开始时间：\n        "),t.appr_infos.startTime?[t._v(t._s(t.utils.formatDate("YYYY-MM-DD HH:mm:ss",t.appr_infos.startTime)))]:t._e()],2)],1),a("el-row",{attrs:{gutter:20}},[a("el-col",{attrs:{span:6}},[t._v("\n        审核时间：\n        "),t.appr_infos.approvalTime?[t._v(t._s(t.utils.formatDate("YYYY-MM-DD HH:mm:ss",1e3*t.appr_infos.approvalTime)))]:t._e()],2),a("el-col",{attrs:{span:6}},[t._v("结束时间：\n        "),t.appr_infos.endTime?[t._v(t._s(t.utils.formatDate("YYYY-MM-DD HH:mm:ss",t.appr_infos.endTime)))]:t._e()],2)],1)],1),a("el-dialog",{attrs:{title:"活动信息编辑",visible:t.dialogTableVisible},on:{"update:visible":function(e){t.dialogTableVisible=e}}},[a("v-info"),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("el-button",{attrs:{type:"primary"},on:{click:t.saveInfos}},[t._v("保存")])],1)],1)],1)},I=[],w=(a("7f7f"),a("7618")),k=a("94e5"),D={name:"WaitVerify",data:function(){return{show_operate:!0,startTime:"",endTime:"",click_exit:!0,dialogTableVisible:!1}},components:{"v-info":g},mounted:function(){this.startTime=this.utils.formatDate("YYYY-MM-DD HH:mm:ss",this.actInfos.startTime),this.endTime=this.utils.formatDate("YYYY-MM-DD HH:mm:ss",this.actInfos.endTime)},watch:{activity_state:function(t){2!==t&&7!==t||(this.show_operate=!1)}},computed:Object(o["a"])({},Object(r["c"])({actInfos:function(t){return t.retailactivity.actInfos},activity_state:function(t){return t.retailactivity.activity_state},stop_reason:function(t){return t.retailactivity.stop_reason},stop_date:function(t){return t.retailactivity.stop_date},appr_infos:function(t){return t.retailactivity.appr_infos},successcount:function(t){return t.retailactivity.successcount},committime:function(t){return t.retailactivity.committime},params_info:function(t){return t.retailactivity.params_info}}),{status:function(){var t="";if(this.activity_state&&"object"!==Object(w["a"])(this.activity_state))for(var e=k["a"],a=0;a<e.length;a++)if(this.activity_state===e[a].id){t=e[a].name;break}return t}}),methods:Object(o["a"])({},Object(r["b"])({setActivityInfos:"setActivityInfos",actStateChange:"actStateChange",showAgreeDialog:"showAgreeDialog",showStopDialog:"showStopDialog",setApprInfos:"setApprInfos"}),{agreement:function(){this.showAgreeDialog()},refuse:function(){var t=this;this.$api.put("/mlm/activity/"+this.actInfos.actId+"/check?isCheck=2",null,function(e){console.log(e),1===e.code&&(t.actStateChange(e.data.isCheck),t.setApprInfos(e.data))})},exitact:function(){this.dialogTableVisible=!0,this.actInfos.startTime=new Date(this.actInfos.startTime),this.actInfos.endTime=new Date(this.actInfos.endTime)},saveInfos:function(){var t=this;this.setActivityInfos(this.actInfos),this.$api.put("/mlm/activity",this.params_info,function(e){1===e.code&&(t.dialogTableVisible=!1)})},stopact:function(){this.showStopDialog()}})},C=D,S=(a("a7bd"),Object(u["a"])(C,y,I,!1,null,"5031a874",null)),T=S.exports,A={name:"RetailActivityAdd",data:function(){return{action:"https://docker.youjiemall.com/mlm/activity/goods/list/excel",actId:this.$route.params.actId?this.$route.params.actId:"",fill_in_info:!0,import_flag:0,stopGoodsDialog:!1,centerDialogVisible:!1,act_supplier_lists:[],isWaitVerify:!1,cur_stop_good:"",cur_stop_good_name:"",cur_stop_spu:"",cur_good_id:[],res_data:{failInfos:[],totalCount:0,failCount:0,successCount:0}}},mounted:function(){var t=this;console.log(this.$route.params.actId);var e=this.$route.params.actId;e&&(this.fill_in_info=!1,this.$api.get("/mlm/activity/".concat(e),null,function(e){1===e.code&&(console.log(e),t.import_flag=!0,t.isWaitVerify=!0,e.data.mlmActivityVo.startTime*=1e3,e.data.mlmActivityVo.endTime*=1e3,t.setApprInfos(e.data.mlmActivityVo),t.setActivityInfos(e.data.mlmActivityVo),t.actStateChange(e.data.mlmActivityVo.isCheck),t.updateGoodsList(e.data.mlmActivityGoodsList),t.act_supplier_lists=e.data.mlmActivityGoodsList)}))},components:{"v-act-search":f,"v-fill-act-info":g,"v-wait-verify":T},watch:{goods_lists:{handler:function(){this.act_supplier_lists=this.goods_lists},deep:!0}},computed:Object(o["a"])({importSuppliers:function(){return this.import_flag?{add_content:"继续导入商品，新数据将会覆盖原数据",type:"primary",btn_content:"保存并提审"}:{add_content:"批量导入商品（活动信息填写完毕方可导入）",type:"info",btn_content:"保存"}},stop_reason:{get:function(){return this.$store.state.retailactivity.stop_reason},set:function(t){this.$store.commit("setStopReason",t)}}},Object(r["c"])({info_flag:function(t){return t.retailactivity.info_flag},params_info:function(t){return t.retailactivity.params_info},actInfos:function(t){return t.retailactivity.actInfos},agreeDialogVisible:function(t){return t.retailactivity.agreeDialogVisible},stopDialogVisible:function(t){return t.retailactivity.stopDialogVisible},appr_infos:function(t){return t.retailactivity.appr_infos},goods_lists:function(t){return t.retailactivity.goods_lists},committime:function(t){return t.retailactivity.committime},successcount:function(t){return t.retailactivity.successcount}})),methods:Object(o["a"])({importSuccess:function(t){console.log("import success",t),1===t.code&&(this.res_data=t.data.templates,this.act_supplier_lists=t.data.mlmActivityGoodsPage.mlmActivityGoodsList,this.actInfos.actId=t.data.templates.actId,this.setActivityInfos(this.actInfos),this.centerDialogVisible=!0,this.updateGoodsList(t.data.mlmActivityGoodsPage.mlmActivityGoodsList),this.setSpuCount(t.data.templates.successCount))},confirmCallBack:function(){this.centerDialogVisible=!1,this.import_flag=1},save:function(){var t=this;this.$api.post("/mlm/activity/",this.params_info,function(e){1===e.code&&t.$alert("活动保存成功","提示信息",{confirmButtonText:"确定",callback:function(){t.isWaitVerify=!0,t.fill_in_info=!1,t.actStateChange(0),t.setCommitTime(t.utils.formatDate("YYYY-MM-DD HH:mm:ss",new Date)),t.setApprInfos(e.data)}})})},cancel:function(){var t=this;this.import_flag?this.$api.delete("/mlm/activity/"+this.actInfos.actId,null,function(e){1===e.code&&t.$router.go(-1)}):this.$router.go(-1)},verifyOperate:function(t){var e=this;this.$api.put("/mlm/activity/"+this.actInfos.actId+"/check?isCheck="+t,null,function(t){console.log(t),1===t.code&&(e.actStateChange(t.data.isCheck),e.setApprInfos(t.data)),e.showAgreeDialog()})},stopCancel:function(){this.showStopDialog()},stopActivity:function(){var t=this;this.stop_reason?this.$api.put("/mlm/activity/".concat(this.actInfos.actId,"/stop?disableReason=").concat(this.stop_reason),null,function(e){1===e.code&&t.$alert("活动已停用","提示信息",{confirmButtonText:"确定",callback:function(){t.actStateChange(e.data.isCheck),t.setStopDate(t.utils.formatDate("YYYY-MM-DD HH:mm:ss",new Date)),t.showStopDialog()}})}):this.$message.error("停用理由必填")},stopThisSupplier:function(t,e,a){this.cur_stop_good=t,this.cur_stop_good_name=e,this.cur_stop_spu=a,this.stopGoodsDialog=!0},stopGoodsCancel:function(){this.stopGoodsDialog=!1},stopGoods:function(){var t=this;this.$api.put("/mlm/activity/".concat(this.actInfos.actId,"/goods/").concat(this.cur_stop_good,"/stop"),null,function(e){console.log(e),1===e.code&&(t.stopGoodsDialog=!1,window.location.reload())})},downtemplate:function(){var t=document.createElement("a");t.href="https://docker.youjiemall.com/mlm/goods/template",t.click()},exporttemplate:function(){var t=document.createElement("a");t.href="".concat("https://docker.youjiemall.com","/mlm/activity/").concat(this.actId,"/goods/list/excel"),t.click()}},Object(r["b"])({setActivityInfos:"setActivityInfos",actStateChange:"actStateChange",showAgreeDialog:"showAgreeDialog",showStopDialog:"showStopDialog",setStopDate:"setStopDate",setApprInfos:"setApprInfos",updateGoodsList:"updateGoodsList",setCommitTime:"setCommitTime",setSpuCount:"setSpuCount"}))},$=A,x=(a("8e4b"),a("d5ee"),Object(u["a"])($,s,i,!1,null,"33b618ee",null));e["default"]=x.exports},"6b7f":function(t,e,a){},"72d4":function(t,e,a){},"8e4b":function(t,e,a){"use strict";var s=a("6b7f"),i=a.n(s);i.a},"94e5":function(t,e,a){"use strict";a.d(e,"a",function(){return s}),a.d(e,"b",function(){return i});new Date;var s=[{name:"待审核",id:0},{name:"已拒绝",id:2},{name:"已停用",id:3},{name:"未开始",id:5},{name:"进行中",id:6},{name:"已结束",id:7}],i=[{id:1,name:"retailing",label:"分销中"},{id:0,name:"removed",label:"已移除"}]},a7bd:function(t,e,a){"use strict";var s=a("72d4"),i=a.n(s);i.a},cda5:function(t,e,a){},d5ee:function(t,e,a){"use strict";var s=a("e50b"),i=a.n(s);i.a},dc90:function(t,e,a){},e50b:function(t,e,a){}}]);
//# sourceMappingURL=retailactivity-legacy.a921363a.js.map