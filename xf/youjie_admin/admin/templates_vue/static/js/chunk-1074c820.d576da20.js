(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-1074c820"],{"205f":function(e,t,s){"use strict";var i=s("91f5"),a=s.n(i);a.a},"3a9e":function(e,t,s){},7821:function(e,t,s){"use strict";var i=s("a7b3"),a=s.n(i);a.a},"91f5":function(e,t,s){},a7b3:function(e,t,s){},b406:function(e,t,s){"use strict";s.r(t);var i=function(){var e=this,t=e.$createElement,s=e._self._c||t;return s("div",{staticClass:"details"},[s("page-title",{attrs:{"first-title":"秒杀活动报名","second-title":"报名详情"}}),s("div",{staticClass:"btn-box"},["signUp"==e.mark?s("el-button",{staticStyle:{float:"right"},attrs:{type:"primary",size:"medium"},on:{click:function(t){e.dialogVisible=!0}}},[e._v("提交商品")]):e._e()],1),s("div",{staticClass:"button-wrapper"},[s("div",{staticClass:"title"},[s("div",{staticClass:"title-name"},[s("span",[e._v("报名主题：")]),s("span",{staticClass:"rear"},[e._v(e._s(e.form.secName))])]),s("div",{staticClass:"title-name"},[s("span",[e._v("支持类目：")]),s("span",{staticClass:"rear"},[e._v(e._s(e.form.categoryId))])]),s("div",{staticClass:"title-name"},[s("span",[e._v("报名时间：")]),s("span",{staticClass:"rear"},[e._v(e._s(e.utils.format_date(e.form.startTime)))]),s("span",{staticStyle:{margin:"0 10px"}},[e._v("至")]),s("span",{staticClass:"rear"},[e._v(e._s(e.utils.format_date(e.form.endTime)))])])]),s("div",{staticClass:"title-below"},[s("div",{staticClass:"below-title"},[e._v("主题说明：")]),s("div",{staticClass:"below-rear"},[e._v(e._s(e.form.secDesc))])])]),s("p",{staticClass:"border"}),s("el-tabs",{attrs:{type:"border-card"}},[s("el-tab-pane",{attrs:{label:"已提交"}},[s("spikeListView",{attrs:{id:e.id},on:{"update:id":function(t){e.id=t},info:e.goodsInfo}})],1),s("el-tab-pane",{attrs:{label:"报名成功"}},[s("successSpikeListView",{attrs:{id:e.id},on:{"update:id":function(t){e.id=t}}})],1)],1),s("el-dialog",{attrs:{title:"提交商品",visible:e.dialogVisible,width:"80%","v-loading":e.dialogLoading,"close-on-click-modal":e.closeModal},on:{"update:visible":function(t){e.dialogVisible=t}}},[[s("el-tabs",{attrs:{type:"card"},on:{"tab-click":e.handleClick},model:{value:e.activeName,callback:function(t){e.activeName=t},expression:"activeName"}},[s("el-tab-pane",{attrs:{label:"全部",name:"All"}},[s("div",{staticClass:"goods"},[s("div",{directives:[{name:"show",rawName:"v-show",value:0==e.tableIndex,expression:"tableIndex == 0"}],staticClass:"search-wrapper"},[s("el-form",{staticClass:"demo-form-inline",attrs:{inline:!0}},[s("el-form-item",{attrs:{label:"商品名称"}},[s("el-input",{attrs:{placeholder:"商品名称"},model:{value:e.params.goodsName,callback:function(t){e.$set(e.params,"goodsName",t)},expression:"params.goodsName"}})],1),s("el-form-item",{attrs:{label:"商品编号"}},[s("el-input",{attrs:{placeholder:"商品编号"},model:{value:e.params.goodsId,callback:function(t){e.$set(e.params,"goodsId",t)},expression:"params.goodsId"}})],1),s("el-form-item",{attrs:{label:"商品分类"}},[s("el-select",{attrs:{multiple:"",placeholder:"请选择"},model:{value:e.value,callback:function(t){e.value=t},expression:"value"}},e._l(e.options,function(e){return s("el-option",{key:e.value,attrs:{label:e.label,value:e.value}})}),1)],1),s("el-form-item",[s("el-button",{attrs:{type:"primary",icon:"el-icon-search"},on:{click:e.search}},[e._v("搜索")]),s("el-button",{attrs:{type:"primary",icon:"el-icon-refresh"},on:{click:e.clear}},[e._v("清空")])],1)],1)],1),s("div",{staticClass:"list-wrapper"},[s("el-table",{directives:[{name:"loading",rawName:"v-loading",value:e.loading,expression:"loading"}],ref:"multipleTable",staticStyle:{width:"100%"},attrs:{data:e.goodsListArr,"row-key":e.getRowKey,stripe:"","header-cell-style":{background:"#eef1f6"}},on:{"selection-change":e.selectCall}},[s("el-table-column",{attrs:{type:"selection","reserve-selection":e.isReserve,width:"55"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsId",label:"商品编号"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsName",label:"商品名称"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsNumber",label:"可售库存"}}),s("el-table-column",{attrs:{align:"center",prop:"surplusQuantity",label:"秒杀可售库存"},scopedSlots:e._u([{key:"default",fn:function(t){return[s("el-input",{attrs:{placeholder:"秒杀可售库存"},model:{value:e.objSplike.saleQuantity[t.row.goodsId],callback:function(s){e.$set(e.objSplike.saleQuantity,t.row.goodsId,"string"===typeof s?s.trim():s)},expression:"objSplike.saleQuantity[scope.row.goodsId]"}})]}}])}),s("el-table-column",{attrs:{align:"center",prop:"maxQuantity",label:"每人限购"},scopedSlots:e._u([{key:"default",fn:function(t){return[s("el-input",{attrs:{placeholder:"每人限购"},model:{value:e.objSplike.maxQuantity[t.row.goodsId],callback:function(s){e.$set(e.objSplike.maxQuantity,t.row.goodsId,"string"===typeof s?s.trim():s)},expression:"objSplike.maxQuantity[scope.row.goodsId]"}})]}}])}),s("el-table-column",{attrs:{align:"center",prop:"shopPrice",label:"原价"},scopedSlots:e._u([{key:"default",fn:function(t){return[e._v("\n                    "+e._s(t.row.shopPrice)),s("br"),e._v("\n                    (现金价:"+e._s(t.row.cashPrice)+" + 积分支付上限:"+e._s(t.row.moneyLine)+")\n                  ")]}}])}),s("el-table-column",{attrs:{align:"center",prop:"signupShopPrice",label:"秒杀总价"},scopedSlots:e._u([{key:"default",fn:function(t){return[s("el-input",{attrs:{placeholder:"秒杀总价"},on:{change:function(s){return e.inputshopPrice(t.row.goodsId)}},model:{value:e.objSplike.shopPrice[t.row.goodsId],callback:function(s){e.$set(e.objSplike.shopPrice,t.row.goodsId,"string"===typeof s?s.trim():s)},expression:"objSplike.shopPrice[scope.row.goodsId]"}})]}}])}),s("el-table-column",{attrs:{align:"center",prop:"signupCashPrice",label:"秒杀现金价"},scopedSlots:e._u([{key:"default",fn:function(t){return[s("el-input",{attrs:{placeholder:"秒杀现金价"},on:{change:function(s){return e.inputCashPrice(t.row.goodsId)}},model:{value:e.objSplike.cashPrice[t.row.goodsId],callback:function(s){e.$set(e.objSplike.cashPrice,t.row.goodsId,"string"===typeof s?s.trim():s)},expression:"objSplike.cashPrice[scope.row.goodsId]"}})]}}])}),s("el-table-column",{attrs:{align:"center",prop:"signupMoneyLine",label:"秒杀积分支付上限"},scopedSlots:e._u([{key:"default",fn:function(t){return[s("el-input",{attrs:{placeholder:"秒杀积分支付上限",disabled:""},model:{value:e.objSplike.huanbiPrice[t.row.goodsId],callback:function(s){e.$set(e.objSplike.huanbiPrice,t.row.goodsId,"string"===typeof s?s.trim():s)},expression:"objSplike.huanbiPrice[scope.row.goodsId]"}})]}}])}),s("el-table-column",{attrs:{align:"center",prop:"signupMoneyLine",label:"每日限购数量"},scopedSlots:e._u([{key:"default",fn:function(t){return[s("el-input",{attrs:{placeholder:"每日限购数量",disabled:""},model:{value:e.objSplike.maxQuantity[t.row.goodsId],callback:function(s){e.$set(e.objSplike.maxQuantity,t.row.goodsId,"string"===typeof s?s.trim():s)},expression:"objSplike.maxQuantity[scope.row.goodsId]"}})]}}])})],1)],1),s("el-pagination",{attrs:{background:"","current-page":e.currentPage,"page-size":e.params.pageSize,layout:"prev, pager, next, jumper",total:e.total},on:{"current-change":e.handleCurrentChange,"update:currentPage":function(t){e.currentPage=t},"update:current-page":function(t){e.currentPage=t}}})],1)]),s("el-tab-pane",{attrs:{label:e.title,name:"selected"}},[s("div",{staticClass:"goods"},[s("div",{staticClass:"list-wrapper"},[s("el-table",{directives:[{name:"loading",rawName:"v-loading",value:e.loadingObj,expression:"loadingObj"}],ref:"objTable",staticStyle:{width:"100%"},attrs:{data:e.goodsSpikeArr,"row-key":e.getRowKeyObj,stripe:"","header-cell-style":{background:"#eef1f6"}},on:{"selection-change":e.selectCallObj}},[s("el-table-column",{attrs:{type:"selection","reserve-selection":e.isReserveObj,width:"55"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsId",label:"商品编号"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsName",label:"商品名称"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsNumber",label:"可售库存"}}),s("el-table-column",{attrs:{align:"center",prop:"surplusQuantity",label:"秒杀可售库存"}}),s("el-table-column",{attrs:{align:"center",prop:"maxQuantity",label:"每人限购"}}),s("el-table-column",{attrs:{align:"center",prop:"shopPrice",label:"原价"},scopedSlots:e._u([{key:"default",fn:function(t){return[e._v("\n                    "+e._s(t.row.shopPrice)),s("br"),e._v("\n                    (现金价:"+e._s(t.row.cashPrice)+" + 积分支付上限:"+e._s(t.row.moneyLine)+")\n                  ")]}}])}),s("el-table-column",{attrs:{align:"center",prop:"signupShopPrice",label:"秒杀总价"}}),s("el-table-column",{attrs:{align:"center",prop:"signupCashPrice",label:"秒杀现金价"}}),s("el-table-column",{attrs:{align:"center",prop:"signupMoneyLine",label:"秒杀积分支付上限"}})],1)],1),s("div",{staticClass:"btn-delete"},[s("el-button",{attrs:{type:"primary",icon:"el-icon-delete",size:"small"},on:{click:function(t){return e.allRemoveGoods(e.tableIndex)}}},[e._v("删除")])],1),s("el-pagination",{attrs:{background:"","current-page":e.currentPageObj,"page-size":e.paramsObj.limit,layout:"prev, pager, next, jumper",total:e.totalObj},on:{"current-change":e.handleCurrentChangeObj,"update:currentPage":function(t){e.currentPageObj=t},"update:current-page":function(t){e.currentPageObj=t}}})],1)])],1)],s("span",{directives:[{name:"show",rawName:"v-show",value:0==e.tableIndex,expression:"tableIndex == 0"}],staticClass:"dialog-footer",attrs:{slot:"footer"},slot:"footer"},[s("el-button",{attrs:{type:"primary",loading:e.btnLoading},on:{click:e.onSubmit}},[e._v("提 交")]),s("el-button",{on:{click:e.quxiao}},[e._v("取 消")])],1)],2)],1)},a=[],o=(s("6b54"),function(){var e=this,t=e.$createElement,s=e._self._c||t;return s("div",{staticClass:"list"},[s("div",{staticClass:"search-wrapper"},[s("el-form",{staticClass:"demo-form-inline",attrs:{inline:!0}},[s("el-form-item",{attrs:{label:"商品名称"}},[s("el-input",{attrs:{placeholder:"商品名称"},model:{value:e.params.goods_name,callback:function(t){e.$set(e.params,"goods_name",t)},expression:"params.goods_name"}})],1),s("el-form-item",{attrs:{label:"商品编号"}},[s("el-input",{attrs:{placeholder:"商品编号"},model:{value:e.params.goods_number,callback:function(t){e.$set(e.params,"goods_number",t)},expression:"params.goods_number"}})],1),s("el-form-item",[s("el-button",{attrs:{type:"primary",icon:"el-icon-search"},on:{click:e.search}},[e._v("搜索")]),s("el-button",{attrs:{type:"primary",icon:"el-icon-refresh"},on:{click:e.clear}},[e._v("清空")])],1),e.id?s("div"):e._e()],1)],1),s("div",{staticClass:"list-wrapper"},[s("el-table",{directives:[{name:"loading",rawName:"v-loading",value:e.loading,expression:"loading"}],staticStyle:{width:"100%"},attrs:{data:e.spilkeArr,stripe:"","header-cell-style":{background:"#eef1f6"}}},[s("el-table-column",{attrs:{align:"center",type:"index",label:"序号"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsId",label:"商品编号"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsName",label:"商品名称"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsNumber",label:"可售库存"}}),s("el-table-column",{attrs:{align:"center",prop:"surplusQuantity",label:"秒杀可售库存"}}),s("el-table-column",{attrs:{align:"center",prop:"maxQuantity",label:"每人限购"}}),s("el-table-column",{attrs:{align:"center",prop:"shopPrice",label:"原价"},scopedSlots:e._u([{key:"default",fn:function(t){return[e._v("\n          "+e._s(t.row.shopPrice)),s("br"),e._v("\n          (现金价:"+e._s(t.row.cashPrice)+" + 积分支付上限:"+e._s(t.row.moneyLine)+")\n        ")]}}])}),s("el-table-column",{attrs:{align:"center",prop:"signupShopPrice",label:"秒杀总价"}}),s("el-table-column",{attrs:{align:"center",prop:"signupCashPrice",label:"秒杀现金价"}}),s("el-table-column",{attrs:{align:"center",prop:"signupMoneyLine",label:"秒杀积分支付上限"}}),s("el-table-column",{attrs:{align:"center",prop:"createdAt",label:"提交时间"},scopedSlots:e._u([{key:"default",fn:function(t){return[e._v("\n          "+e._s(e.utils.format_date(t.row.createdAt))+"\n        ")]}}])}),s("el-table-column",{attrs:{align:"center",label:"操作"},scopedSlots:e._u([{key:"default",fn:function(t){return[s("el-button",{attrs:{type:"text",size:"small"},on:{click:function(s){return e.removeGoods(t.row)}}},[e._v("移除")]),s("el-button",{attrs:{type:"text",size:"small"},on:{click:function(s){return e.editGoods(t.row)}}},[e._v("编辑")])]}}])})],1)],1),s("el-dialog",{attrs:{title:"编辑商品",visible:e.editView,width:"40%","v-loading":e.editLoading,"close-on-click-modal":e.closeModal},on:{"update:visible":function(t){e.editView=t}}},[s("el-form",{ref:"ruleForm",attrs:{rules:e.rules,model:e.form,"label-width":"120px"}},[s("el-form-item",{attrs:{label:"商品名称"}},[s("el-input",{attrs:{disabled:""},model:{value:e.form.goodsName,callback:function(t){e.$set(e.form,"goodsName","string"===typeof t?t.trim():t)},expression:"form.goodsName"}})],1),s("el-form-item",{attrs:{label:"商品编号"}},[s("el-input",{attrs:{disabled:""},model:{value:e.form.goodsId,callback:function(t){e.$set(e.form,"goodsId","string"===typeof t?t.trim():t)},expression:"form.goodsId"}})],1),s("el-form-item",{attrs:{label:"秒杀价",prop:"signupShopPrice"}},[s("el-input",{attrs:{type:"number"},model:{value:e.form.signupShopPrice,callback:function(t){e.$set(e.form,"signupShopPrice","string"===typeof t?t.trim():t)},expression:"form.signupShopPrice"}})],1),s("el-form-item",{attrs:{label:"现金价",prop:"signupCashPrice"}},[s("el-input",{attrs:{type:"number"},model:{value:e.form.signupCashPrice,callback:function(t){e.$set(e.form,"signupCashPrice","string"===typeof t?t.trim():t)},expression:"form.signupCashPrice"}})],1),s("el-form-item",{attrs:{label:"积分支付上限",prop:"signupMoneyLine"}},[s("el-input",{attrs:{type:"number"},model:{value:e.form.signupMoneyLine,callback:function(t){e.$set(e.form,"signupMoneyLine","string"===typeof t?t.trim():t)},expression:"form.signupMoneyLine"}})],1),s("el-form-item",{attrs:{label:"原价"}},[s("el-input",{attrs:{disabled:""},model:{value:e.form.shopPrice,callback:function(t){e.$set(e.form,"shopPrice","string"===typeof t?t.trim():t)},expression:"form.shopPrice"}})],1),s("el-form-item",{attrs:{label:"现金价"}},[s("el-input",{attrs:{disabled:""},model:{value:e.form.cashPrice,callback:function(t){e.$set(e.form,"cashPrice","string"===typeof t?t.trim():t)},expression:"form.cashPrice"}})],1),s("el-form-item",{attrs:{label:"积分支付上限"}},[s("el-input",{attrs:{disabled:""},model:{value:e.form.moneyLine,callback:function(t){e.$set(e.form,"moneyLine","string"===typeof t?t.trim():t)},expression:"form.moneyLine"}})],1),s("el-form-item",{attrs:{label:"可售库存"}},[s("el-input",{attrs:{disabled:""},model:{value:e.form.goodsNumber,callback:function(t){e.$set(e.form,"goodsNumber","string"===typeof t?t.trim():t)},expression:"form.goodsNumber"}})],1),s("el-form-item",{attrs:{label:"活动可售库存",prop:"surplusQuantity"}},[s("el-input",{attrs:{type:"number"},model:{value:e.form.surplusQuantity,callback:function(t){e.$set(e.form,"surplusQuantity","string"===typeof t?t.trim():t)},expression:"form.surplusQuantity"}})],1),s("el-form-item",{attrs:{label:"每人限购",prop:"maxQuantity"}},[s("el-input",{attrs:{type:"number"},model:{value:e.form.maxQuantity,callback:function(t){e.$set(e.form,"maxQuantity","string"===typeof t?t.trim():t)},expression:"form.maxQuantity"}})],1),s("el-form-item",[s("el-button",{attrs:{type:"primary",loading:e.btnLoading},on:{click:function(t){return e.onSubmit(e.form)}}},[e._v("提交")]),s("el-button",{on:{click:function(t){e.editView=!1}}},[e._v("取消")])],1)],1)],1)],1)}),r=[],l={name:"spikeList",props:["id"],data(){return{currentPage:1,total:0,params:{id:"",goods_name:"",goods_number:"",type:1},loading:!1,spilkeArr:[],editView:!1,editLoading:!1,closeModal:!1,form:{},btnLoading:!1,signupId:"",rules:{signupShopPrice:[{required:!0,message:"请填写秒杀价",trigger:"blur"}],signupCashPrice:[{required:!0,message:"请填写现金价",trigger:"blur"}],signupMoneyLine:[{required:!0,message:"请填写积分支付上限",trigger:"blur"}],surplusQuantity:[{required:!0,message:"请填写活动可售库存",trigger:"blur"}]}}},created(){this.params.id=this.id,this.goodsList()},methods:{handleCurrentChange(e){this.params.page=e},search(){this.currentPage=1,this.params.page=1,this.goodsList()},clear(){this.currentPage=1,this.params.page=1,this.params.goods_name="",this.params.goods_number="",this.goodsList()},goodsList(){this.loading=!0,this.$api.get("hd/itzSecbuySignupGoods/signup/detail",this.params,e=>{this.loading=!1,200==e.status&&(this.spilkeArr=e.data.goodsList,this.signupId=e.data.signup.id,this.$emit("info",e.data.signup))},e=>{this.loading=!1})},removeGoods(e){this.$confirm("是否删除此条商品信息?","提示",{confirmButtonText:"确定",cancelButtonText:"取消",type:"warning"}).then(()=>{this.$api.delete("hd/itzSecbuySignupGoods/signup/detail/".concat(e.signupGoodsId),null,e=>{200==e.status&&(this.$message({type:"success",message:"删除成功!"}),this.goodsList())})}).catch(()=>{})},editGoods(e){this.form=e,this.$api.put("hd/itzSecbuySignupGoods/goods/lock/".concat(e.signupGoodsId),null,e=>{200==e.status&&(this.editView=!0,this.goodsList())})},onSubmit(e){this.btnLoading=!0;let t={shopPrice:e.signupShopPrice,cashPrice:e.signupCashPrice,huanbiPrice:e.signupMoneyLine,surplusQuantity:e.surplusQuantity,goodsId:e.goodsId,signupId:this.signupId,maxQuantity:e.maxQuantity};this.$api.put("hd/itzSecbuySignupGoods/goods/".concat(e.signupGoodsId),t,e=>{200==e.status&&(this.$message({type:"success",message:e.message}),this.editView=!1,this.btnLoading=!1,this.goodsList())},e=>{this.btnLoading=!1})}}},n=l,c=(s("205f"),s("2877")),u=Object(c["a"])(n,o,r,!1,null,"43a88452",null),p=u.exports,d=function(){var e=this,t=e.$createElement,s=e._self._c||t;return s("div",{staticClass:"success"},[s("div",{staticClass:"search-wrapper"},[s("el-form",{staticClass:"demo-form-inline",attrs:{inline:!0}},[s("el-form-item",{attrs:{label:"商品名称"}},[s("el-input",{attrs:{placeholder:"商品名称"},model:{value:e.params.goods_name,callback:function(t){e.$set(e.params,"goods_name",t)},expression:"params.goods_name"}})],1),s("el-form-item",{attrs:{label:"商品编号"}},[s("el-input",{attrs:{placeholder:"商品编号"},model:{value:e.params.goods_number,callback:function(t){e.$set(e.params,"goods_number",t)},expression:"params.goods_number"}})],1),s("el-form-item",{attrs:{label:"活动名称"}},[s("el-input",{attrs:{placeholder:"活动名称"},model:{value:e.params.secbuy_name,callback:function(t){e.$set(e.params,"secbuy_name",t)},expression:"params.secbuy_name"}})],1),s("el-form-item",{attrs:{label:"活动状态"}},[s("el-select",{attrs:{placeholder:"请选择"},model:{value:e.params.secbuy_status,callback:function(t){e.$set(e.params,"secbuy_status",t)},expression:"params.secbuy_status"}},e._l(e.options,function(e){return s("el-option",{key:e.value,attrs:{label:e.label,value:e.value}})}),1)],1),s("el-form-item",[s("el-button",{attrs:{type:"primary",icon:"el-icon-search"},on:{click:e.search}},[e._v("搜索")]),s("el-button",{attrs:{type:"primary",icon:"el-icon-refresh"},on:{click:e.clear}},[e._v("清空")])],1),e.id?s("div"):e._e()],1)],1),s("div",{staticClass:"list-wrapper"},[s("el-table",{directives:[{name:"loading",rawName:"v-loading",value:e.loading,expression:"loading"}],staticStyle:{width:"100%"},attrs:{data:e.spilkeArr,stripe:"","header-cell-style":{background:"#eef1f6"}}},[s("el-table-column",{attrs:{align:"center",type:"index",label:"序号"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsId",label:"商品编号"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsName",label:"商品名称"}}),s("el-table-column",{attrs:{align:"center",prop:"goodsNumber",label:"可售库存"}}),s("el-table-column",{attrs:{align:"center",prop:"surplusQuantity",label:"秒杀库存"}}),s("el-table-column",{attrs:{align:"center",prop:"maxQuantity",label:"每人限购"}}),s("el-table-column",{attrs:{align:"center",prop:"cashPrice",label:"原价"},scopedSlots:e._u([{key:"default",fn:function(t){return[e._v("\n          "+e._s(t.row.shopPrice)),s("br"),e._v("\n          (现金价:"+e._s(t.row.cashPrice)+" + 积分支付上限:"+e._s(t.row.moneyLine)+")\n        ")]}}])}),s("el-table-column",{attrs:{align:"center",prop:"signupShopPrice",label:"秒杀总价"},scopedSlots:e._u([{key:"default",fn:function(t){return[e._v("\n          "+e._s(t.row.signupShopPrice)),s("br"),e._v("\n          (现金价:"+e._s(t.row.signupCashPrice)+" + 积分支付上限:"+e._s(t.row.signupMoneyLine)+")\n        ")]}}])}),s("el-table-column",{attrs:{align:"center",prop:"secbuyName",label:"活动名称"}}),s("el-table-column",{attrs:{align:"center",label:"活动时间"},scopedSlots:e._u([{key:"default",fn:function(t){return[e._v("\n          "+e._s(e.utils.format_date(t.row.secbuyStartTime))),s("br"),e._v("\n          至 "),s("br"),e._v(e._s(e.utils.format_date(t.row.secbuyEndTime))+"\n        ")]}}])}),s("el-table-column",{attrs:{align:"center",prop:"secbuyStatus_text",label:"活动状态"}}),s("el-table-column",{attrs:{align:"center",label:"活动创建时间"},scopedSlots:e._u([{key:"default",fn:function(t){return[e._v("\n          "+e._s(e.utils.format_date(t.row.createdAt))+"\n        ")]}}])})],1)],1)])},g=[],m={name:"successSpikeList",props:["id"],data(){return{currentPage:1,total:0,params:{id:"",goods_name:"",goods_number:"",secbuy_name:"",secbuy_status:"",type:2},loading:!1,options:[{value:"0",label:"未开始"},{value:"1",label:"进行中"},{value:"2",label:"已结束"},{value:"3",label:"已禁用"}],spilkeArr:[]}},created(){this.params.id=this.id,this.goodsList()},methods:{handleCurrentChange(e){this.params.pageNum=e},search(){this.currentPage=1,this.goodsList()},clear(){this.currentPage=1,this.params.goods_name="",this.params.goods_number="",this.params.secbuy_name="",this.params.secbuy_status="",this.goodsList()},goodsList(){this.loading=!0,this.$api.get("hd/itzSecbuySignupGoods/signup/detail",this.params,e=>{if(this.loading=!1,200==e.status){for(let t of e.data.goodsList)switch(t.secbuyStatus){case 0:t.secbuyStatus_text="未开始";break;case 1:t.secbuyStatus_text="进行中";break;case 2:t.secbuyStatus_text="已结束";break;case 3:t.secbuyStatus_text="禁用";break;default:t.secbuyStatus_text=""}this.spilkeArr=e.data.goodsList}},e=>{this.loading=!1})},removeGoods(){},editGoods(){}}},h=m,b=(s("e043"),Object(c["a"])(h,d,g,!1,null,"854e12ce",null)),f=b.exports,y={name:"registrationDetails",components:{spikeListView:p,successSpikeListView:f},inject:["reload"],data(){return{spilkeArr:[],dialogVisible:!1,dialogLoading:!1,activeName:"All",selectedGoods:{index:1,arr:[]},tableIndex:0,closeModal:!1,title:"已选中(0)",mark:"",id:null,editView:!1,editLoading:!1,form:{},currentPage:1,total:0,params:{signUpId:"",goodsName:"",goodsId:"",categories:[],page:1,limit:6},objSplike:{saleQuantity:[],shopPrice:[],cashPrice:[],huanbiPrice:[],max_quantity:[],maxQuantity:[]},isReserve:!0,loading:!1,options:[],value:[],selectAll:[],goodsListArr:[],getRowKey(e){return e.goodsId},currentPageObj:1,totalObj:0,paramsObj:{page:1,limit:6},isReserveObj:!0,loadingObj:!1,goodsListobj:[],goodsArrList:[],goodsSpikeArr:[],selectAllObj:[],selectAlls:[],getRowKeyObj(e){return e.goodsId},btnLoading:!1}},created(){this.mark=this.$route.query.mark,this.id=this.$route.params.id,this.getProductList(this.$route.params.id)},methods:{goodsInfo(e){this.form=e,this.params.signUpId=e.id,this.goodsList()},getProductList(e){this.$api.get("hd/itzSecbuySignup/".concat(e,"/category"),null,e=>{200==e.status&&(this.options=e.data)})},handleCurrentChange(e){this.params.page=e,this.goodsList()},handleCurrentChangeObj(e){this.paramsObj.page=e,console.log(this.goodsArrList);for(let t in this.goodsArrList)Number(t)+1==e&&(console.log(t+1),this.goodsSpikeArr=this.goodsArrList[t])},reSetData(e,t){let s=[],i=e.length;for(let a=0;a<i;a+=t)s.push(e.slice(a,a+t));return s},search(){this.currentPage=1,this.params.page=1,this.goodsList()},clear(){this.currentPage=1,this.params.page=1,this.params.goodsName="",this.params.goodsId="",this.params.categories="",this.value=[],this.goodsList()},inputshopPrice(e){if(this.objSplike.cashPrice[e]){if(Number(this.objSplike.cashPrice[e])>Number(this.objSplike.shopPrice[e]))return this.$message({message:"商品编码为".concat(e,"的商品，秒杀总价不能小于秒杀现金价！"),type:"warning"}),void this.$set(this.objSplike.shopPrice,e,"");this.$set(this.objSplike.huanbiPrice,e,Number(this.objSplike.shopPrice[e])-Number(this.objSplike.cashPrice[e]))}else this.$set(this.objSplike.huanbiPrice,e,Number(this.objSplike.shopPrice[e]))},inputCashPrice(e){if(this.objSplike.shopPrice[e]){if(Number(this.objSplike.cashPrice[e])>=Number(this.objSplike.shopPrice[e]))return this.$message({message:"商品编码为".concat(e,"的商品，秒杀现金价需小于秒杀总价！"),type:"warning"}),void this.$set(this.objSplike.cashPrice,e,"");this.$set(this.objSplike.huanbiPrice,e,Number(this.objSplike.shopPrice[e])-Number(this.objSplike.cashPrice[e]))}else this.$set(this.objSplike.huanbiPrice,e,Number(this.objSplike.cashPrice[e]))},handleClick(e){this.tableIndex=e.index,1==e.index&&(this.forEachArr(this.objSplike.saleQuantity,this.goodsListobj,"surplusQuantity"),this.forEachArr(this.objSplike.shopPrice,this.goodsListobj,"signupShopPrice"),this.forEachArr(this.objSplike.cashPrice,this.goodsListobj,"signupCashPrice"),this.forEachArr(this.objSplike.huanbiPrice,this.goodsListobj,"signupMoneyLine"),this.forEachArr(this.objSplike.maxQuantity,this.goodsListobj,"maxQuantity"))},selectCall(e){this.forEachArr(this.objSplike.saleQuantity,e,"surplusQuantity"),this.forEachArr(this.objSplike.shopPrice,e,"signupShopPrice"),this.forEachArr(this.objSplike.cashPrice,e,"signupCashPrice"),this.forEachArr(this.objSplike.huanbiPrice,e,"signupMoneyLine"),this.forEachArr(this.objSplike.maxQuantity,this.goodsListobj,"maxQuantity"),this.selectAll=e,this.title="已选中(".concat(e.length,")"),this.goodsListobj=e,this.totalObj=this.goodsListobj.length,this.goodsArrList=this.reSetData(this.goodsListobj,this.paramsObj.limit);for(let t in this.goodsArrList)Number(t)+1==this.paramsObj.page&&(this.goodsSpikeArr=this.goodsArrList[t])},goodsList(){this.params.categories=Array.from(this.value).toString(),this.loading=!0,this.$api.get("hd/itzSecbuySignupGoods/supplierGoods",this.params,e=>{this.loading=!1,200==e.status&&(this.goodsListArr=e.data.rows,this.total=e.data.total)},e=>{this.loading=!1})},selectCallObj(e){this.selectAllObj=e},allRemoveGoods(e){if(console.log(e),1==e){let e=this.selectAllObj,t=this;for(let i of e)t.getIndex(t.goodsListobj,i);let s=t.selectAllObj.filter(e=>!t.goodsListobj.includes(e.goodsId));t.selectAllObj=t.goodsListobj,this.title="已选中(".concat(t.selectAllObj.length,")"),this.selectAlls=s,this.totalObj=this.goodsListobj.length,this.goodsArrList=this.reSetData(this.goodsListobj,this.paramsObj.limit),this.paramsObj.page=1,this.currentPageObj=1,0==this.goodsArrList.length&&(this.goodsSpikeArr=[]);for(let i in this.goodsArrList)Number(i)+1==this.paramsObj.page&&(console.log(this.goodsArrList[i],"tt"),this.goodsSpikeArr=this.goodsArrList[i])}},getIndex(e,t){let s=e.length;for(let i=0;i<s;i++)if(e[i]==t)return 0==i?(e.shift(),e):i==s-1?(e.pop(),e):(e.splice(i,1),e)},onSubmit(){this.forEachArr(this.objSplike.saleQuantity,this.goodsListobj,"surplusQuantity"),this.forEachArr(this.objSplike.shopPrice,this.goodsListobj,"signupShopPrice"),this.forEachArr(this.objSplike.cashPrice,this.goodsListobj,"signupCashPrice"),this.forEachArr(this.objSplike.huanbiPrice,this.goodsListobj,"signupMoneyLine"),this.forEachArr(this.objSplike.maxQuantity,this.goodsListobj,"maxQuantity");let e=this.params.signUpId,t=this.objSplike.saleQuantity,s=this.objSplike.shopPrice,i=this.objSplike.cashPrice,a=this.objSplike.huanbiPrice,o=this.objSplike.maxQuantity,r={},l=[];for(let n in t)r={goodsId:n,signupId:e,saleQuantity:t[n],shopPrice:s[n],cashPrice:i[n],huanbiPrice:a[n],maxQuantity:o[n]},l.push(r);for(let n in this.goodsArrList)for(let e of this.goodsArrList[n]){if(!e.surplusQuantity)return void this.$message({message:"商品编码为".concat(e.goodsId,"的商品，秒杀库存不能为空！"),type:"warning"});if(!e.signupShopPrice)return void this.$message({message:"商品编码为".concat(e.goodsId,"的商品，秒杀总价不能为空！"),type:"warning"});if(!e.signupCashPrice)return void this.$message({message:"商品编码为".concat(e.goodsId,"的商品，秒杀现金价不能为空！"),type:"warning"})}for(let n of l)for(let e of this.goodsListArr)if(n.goodsId==e.goodsId){if(e.goodsNumber<n.saleQuantity)return void this.$message({message:"商品编码为".concat(n.goodsId,"的商品，秒杀库存不能大于商品库存！"),type:"warning"});if(e.shopPrice<n.shopPrice)return void this.$message({message:"商品编码为".concat(n.goodsId,"的商品，秒杀总价不能大于原商品总价！"),type:"warning"});if(e.cashPrice<n.cashPrice)return void this.$message({message:"商品编码为".concat(n.goodsId,"的商品，秒杀现金价不能大于原商品现金价！"),type:"warning"})}this.btnLoading=!0,this.$api.post("hd/itzSecbuySignupGoods/batch",l,e=>{this.btnLoading=!1,200==e.status&&(this.$message({message:e.message,type:"success"}),this.dialogVisible=!1,this.totalObj=0,this.goodsListobj=[],this.goodsArrList=[],this.goodsSpikeArr=[],this.selectAllObj=[],this.selectAlls=[],this.selectAll=[],this.objSplike.saleQuantity=[],this.objSplike.shopPrice=[],this.objSplike.cashPrice=[],this.objSplike.huanbiPrice=[],this.objSplike.maxQuantity=[],this.title="已选中(0)",this.$refs.multipleTable.clearSelection(),this.goodsList(),this.reload())},e=>{this.btnLoading=!1})},forEachArr(e,t,s){for(let i in e)for(let a of t)a.goodsId==i&&(a[s]=e[i],a.signupId=this.params.signUpId)},quxiao(){this.toggleSelection(),this.dialogVisible=!1,this.objSplike.saleQuantity=[],this.objSplike.shopPrice=[],this.objSplike.cashPrice=[],this.objSplike.huanbiPrice=[],this.objSplike.maxQuantity=[]},toggleSelection(e,t){e?1==t?e.forEach(e=>{this.$refs.multipleTable.toggleRowSelection(e,!1)}):e.forEach(e=>{this.$refs.objTable.toggleRowSelection(e,!1)}):this.$refs.multipleTable.clearSelection(),this.selectAlls=[]}},watch:{tableIndex(e,t){0==e?(console.log(this.selectAlls),this.toggleSelection(this.selectAlls,1)):this.toggleSelection(this.goodsListArr,2)}}},k=y,v=(s("7821"),Object(c["a"])(k,i,a,!1,null,"0bde1d03",null));t["default"]=v.exports},e043:function(e,t,s){"use strict";var i=s("3a9e"),a=s.n(i);a.a}}]);
//# sourceMappingURL=chunk-1074c820.d576da20.js.map