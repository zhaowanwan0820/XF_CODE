(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-166e6a78"],{bcfa:function(t,e,a){"use strict";var i=a("eec2"),s=a.n(i);s.a},d81a:function(t,e,a){"use strict";a.r(e);var i=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{staticClass:"registration"},[a("page-title",{attrs:{"first-title":"秒杀活动报名"}}),t._m(0),a("div",{staticClass:"transfer-info"},[a("el-form",{attrs:{inline:!0}},[a("el-form-item",{attrs:{label:"商品分类"}},[a("el-select",{attrs:{multiple:"",placeholder:"请选择"},on:{"remove-tag":t.removeTag},model:{value:t.params.catId,callback:function(e){t.$set(t.params,"catId",e)},expression:"params.catId"}},t._l(t.options,function(t){return a("el-option",{key:t.value,attrs:{label:t.label,value:t.value}})}),1)],1),a("el-form-item",[a("el-button",{attrs:{type:"primary",icon:"el-icon-search"},on:{click:t.search}},[t._v("搜索")]),a("el-button",{attrs:{type:"primary",icon:"el-icon-refresh"},on:{click:t.clear}},[t._v("清空")])],1)],1)],1),a("div",{staticClass:"transfer-table"},[a("el-table",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticStyle:{width:"100%"},attrs:{data:t.spikeList,stripe:"","header-cell-style":{background:"#eef1f6"}}},[a("el-table-column",{attrs:{label:"序号",type:"index",width:"80px"}}),a("el-table-column",{attrs:{label:"报名主题",prop:"secName","show-overflow-tooltip":!0}}),a("el-table-column",{attrs:{label:"支持类目",prop:"categoryId"}}),a("el-table-column",{attrs:{label:"报名时间"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v("\n          "+t._s(t.utils.format_date(e.row.startTime))),a("br"),t._v("\n          至 "),a("br"),t._v(t._s(t.utils.format_date(e.row.endTime))+"\n        ")]}}])}),a("el-table-column",{attrs:{label:"主题说明",prop:"secDesc"}}),a("el-table-column",{attrs:{label:"报名状态",prop:"signUpStatusStr"}}),a("el-table-column",{attrs:{label:"操作"},scopedSlots:t._u([{key:"default",fn:function(e){return[1==e.row.signUpStatus?a("el-button",{attrs:{type:"text"},on:{click:function(a){return t.signUp(e.row)}}},[t._v("去报名")]):t._e(),2==e.row.signUpStatus?a("el-button",{attrs:{type:"text"},on:{click:function(a){return t.signUpInfo(e.row)}}},[t._v("查看报名详情")]):t._e()]}}])})],1)],1),a("el-pagination",{attrs:{background:"","current-page":t.currentPage,"page-size":t.params.limit,layout:"prev, pager, next, jumper",total:t.total},on:{"current-change":t.handleCurrentChange,"update:currentPage":function(e){t.currentPage=e},"update:current-page":function(e){t.currentPage=e}}})],1)},s=[function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{staticClass:"header title-header"},[a("div",{staticClass:"in"},[a("div",{staticClass:"in-left"},[t._v("报名须知：")]),a("div",{staticClass:"in-right"},[a("div",[t._v("1.限时秒杀活动无法指定日期、场次及场次的数量投放，介意者请勿报名；")]),a("div",[t._v("\n          2.请保证提交报名10天内您提报的秒杀价格有效，商品库存>秒杀总库存，原价>秒杀价，原价现金>秒杀现金，每场秒杀库存由运营设置，库存一般为10~20，请您按您的情况，设置足够的秒杀总库存，10天内未被选中，可能您的商品不符合本次活动主题，建议您关注其他报名通知；\n        ")]),a("div",[t._v("\n          3.报名成功后，活动未开始到结束期间运营可删除商品，可取消场次秒杀活动，商户不能自行操作，需要删除、取消的，请自行联系运营。\n        ")])])])])}],r={name:"spikeEventRegistration",data(){return{currentPage:1,total:0,spikeList:[],loading:!1,options:[],params:{limit:10,page:1,catId:[]}}},created(){this.getProductList(),this.spikeListArr()},methods:{handleCurrentChange(t){this.params.page=t,this.spikeListArr()},search(){this.currentPage=1,this.params.page=1,this.spikeListArr()},clear(){this.currentPage=1,this.params.page=1,this.params.catId=[],this.spikeListArr()},removeTag(t){this.spikeListArr()},getProductList(){this.$api.get("hd/itzSecbuySignup/categoryList",null,t=>{200==t.status&&(this.options=t.data)})},spikeListArr(){this.$api.get("hd/itzSecbuySignup/signup?categoryId=".concat(this.params.catId.join(","),"&type=1&page=").concat(this.params.page,"&limit=").concat(this.params.limit),null,t=>{200===t.status&&(this.spikeList=t.data.itzSecbuySignupVoList,this.total=t.data.total)})},signUp(t){console.log(t),this.$router.push({path:"registrationDetails/".concat(t.id),query:{mark:"signUp"}})},signUpInfo(t){console.log(t),this.$router.push({path:"registrationDetails/".concat(t.id),query:{mark:"info"}})}}},n=r,l=(a("bcfa"),a("2877")),o=Object(l["a"])(n,i,s,!1,null,"46599592",null);e["default"]=o.exports},eec2:function(t,e,a){}}]);
//# sourceMappingURL=chunk-166e6a78.971affca.js.map