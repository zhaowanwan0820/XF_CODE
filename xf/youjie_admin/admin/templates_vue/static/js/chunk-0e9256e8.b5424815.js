(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-0e9256e8"],{"097d":function(t,a,s){"use strict";var e=s("5ca1"),l=s("8378"),o=s("7726"),_=s("ebd6"),n=s("bcaa");e(e.P+e.R,"Promise",{finally:function(t){var a=_(this,l.Promise||o.Promise),s="function"==typeof t;return this.then(s?function(s){return n(a,t()).then(function(){return s})}:t,s?function(s){return n(a,t()).then(function(){throw s})}:t)}})},3476:function(t,a,s){"use strict";var e=s("c04a"),l=s.n(e);l.a},a5b8:function(t,a,s){"use strict";var e=s("d8e8");function l(t){var a,s;this.promise=new t(function(t,e){if(void 0!==a||void 0!==s)throw TypeError("Bad Promise constructor");a=t,s=e}),this.resolve=e(a),this.reject=e(s)}t.exports.f=function(t){return new l(t)}},bcaa:function(t,a,s){var e=s("cb7c"),l=s("d3f4"),o=s("a5b8");t.exports=function(t,a){if(e(t),l(a)&&a.constructor===t)return a;var s=o.f(t),_=s.resolve;return _(a),s.promise}},c04a:function(t,a,s){},c1c0:function(t,a,s){"use strict";s.r(a);var e=function(){var t=this,a=t.$createElement,s=t._self._c||a;return s("div",{staticClass:"container"},[s("div",{staticClass:"header"},[s("el-breadcrumb",{attrs:{"separator-class":"el-icon-arrow-right"}},[s("el-breadcrumb-item",[t._v(t._s(t.storeName)+"商城管理中心")]),s("el-breadcrumb-item",[t._v("关键运营数据")])],1)],1),s("div",{staticClass:"data-wrapper"},[s("el-card",{staticClass:"box-card"},[s("div",{staticClass:"clearfix title",attrs:{slot:"header"},slot:"header"},[s("span",[t._v("用户统计信息")])]),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计注册用户数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_regist["total"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("当日注册用户数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_regist["today"]))])])]),s("el-col",{attrs:{span:8}})],1)],1),s("el-card",{staticClass:"box-card"},[s("div",{staticClass:"clearfix title",attrs:{slot:"header"},slot:"header"},[s("span",[t._v("积分兑换&使用统计信息")])]),s("div",{staticClass:"item-line"},[s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计同意兑换协议人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_agree["total"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("当日同意兑换协议人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_agree["today"]))])])])],1)],1),s("div",{staticClass:"item-line"},[s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计兑换积分次数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.times_exchange["total"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("当日兑换积分次数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.times_exchange["today"]))])])])],1)],1),s("div",{staticClass:"item-line"},[s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计兑换积分人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_exchange_token["total"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("当日兑换积分人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_exchange_token["today"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("当日首次兑换积分人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_exchange_token["todayNew"]))])])])],1),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("尊享累计兑换积分人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_exchange_by_debt["total"]["zx"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("尊享当日兑换积分人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_exchange_by_debt["today"]["zx"]))])])]),s("el-col",{attrs:{span:8}})],1),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("普惠供应链累计兑换积分人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_exchange_by_debt["total"]["ph"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("普惠供应链当日兑换积分人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.user_exchange_by_debt["today"]["ph"]))])])]),s("el-col",{attrs:{span:8}})],1)],1),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计兑换积分总额：")]),s("span",{staticClass:"value"},[t._v(t._s(t.amount_exchange_token["total"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("当日兑换总额：")]),s("span",{staticClass:"value"},[t._v(t._s(t.amount_exchange_token["today"]))])])]),s("el-col",{attrs:{span:8}})],1),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("尊享累计兑换积分总额：")]),s("span",{staticClass:"value"},[t._v(t._s(t.amount_exchange_by_debt["total"]["zx"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("尊享当日兑换积分总额：")]),s("span",{staticClass:"value"},[t._v(t._s(t.amount_exchange_by_debt["today"]["zx"]))])])]),s("el-col",{attrs:{span:8}})],1),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("普惠供应链累计兑换积分总额：")]),s("span",{staticClass:"value"},[t._v(t._s(t.amount_exchange_by_debt["total"]["ph"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("普惠供应链当日兑换积分总额：")]),s("span",{staticClass:"value"},[t._v(t._s(t.amount_exchange_by_debt["today"]["ph"]))])])]),s("el-col",{attrs:{span:8}})],1)],1),s("el-card",{staticClass:"box-card"},[s("div",{staticClass:"clearfix title",attrs:{slot:"header"},slot:"header"},[s("span",[t._v("出清人数统计信息（截止到昨日）")])]),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计出清人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.cleared_person_numbers["total"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("昨日出清人数：")]),s("span",{staticClass:"value"},[t._v(t._s(t.cleared_person_numbers["yesterday"]))])])]),s("el-col",{attrs:{span:8}})],1)],1),s("el-card",{staticClass:"box-card"},[s("div",{staticClass:"clearfix title",attrs:{slot:"header"},slot:"header"},[s("span",[t._v("订单统计信息")])]),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计订单消耗积分：")]),s("span",{staticClass:"value"},[t._v(t._s(t.tokens_of_goods_sold["total"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("当日订单消耗积分：")]),s("span",{staticClass:"value"},[t._v(t._s(t.tokens_of_goods_sold["today"]))])])]),s("el-col",{attrs:{span:8}})],1)],1),s("el-card",{staticClass:"box-card"},[s("div",{staticClass:"clearfix title",attrs:{slot:"header"},slot:"header"},[s("span",[t._v("商品统计信息")])]),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("已上架商品：")]),s("span",{staticClass:"value"},[t._v(t._s(t.goods_data["quantity_of_goods_on_sale"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("已下架商品：")]),s("span",{staticClass:"value"},[t._v(t._s(t.goods_data["quantity_of_goods_on_off_sale"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("已售罄商品：")]),s("span",{staticClass:"value"},[t._v(t._s(t.goods_data["quantity_of_goods_on_sold_out"]))])])])],1),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("待审核商品：")]),s("span",{staticClass:"value"},[t._v(t._s(t.goods_data["quantity_of_goods_on_checking"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("审核通过商品：")]),s("span",{staticClass:"value"},[t._v(t._s(t.goods_data["quantity_of_goods_on_checked"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("审核失败商品：")]),s("span",{staticClass:"value"},[t._v(t._s(t.goods_data["quantity_of_goods_on_check_fail"]))])])])],1),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计销售商品数量：")]),s("span",{staticClass:"value"},[t._v(t._s(t.goods_data["quantity_of_goods_sold"]))])])]),s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("当日销售商品数量：")]),s("span",{staticClass:"value"},[t._v(t._s(t.goods_data["quantity_of_goods_sold_today"]))])])]),s("el-col",{attrs:{span:8}})],1)],1),s("el-card",{staticClass:"box-card"},[s("div",{staticClass:"clearfix title",attrs:{slot:"header"},slot:"header"},[s("span",[t._v("商家统计信息")])]),s("el-row",[s("el-col",{attrs:{span:8}},[s("div",{staticClass:"text item"},[s("span",{staticClass:"label"},[t._v("累计入驻商家数量：")]),s("span",{staticClass:"value"},[t._v(t._s(t.shops_data["shops_total"]))])])]),s("el-col",{attrs:{span:8}}),s("el-col",{attrs:{span:8}})],1)],1)],1)])},l=[],o=s("be94"),_=(s("ac6a"),s("097d"),s("65c6"));const n=t=>Object(_["a"])("/admin/api.shop.board","POST",{}),i=t=>Object(_["a"])("/admin/api.debt.subtotal","POST",{});var c={name:"MainOperateData",data(){return{user_regist:{total:0,today:0},user_agree:{total:0,today:0},times_exchange:{total:0,today:0},user_exchange_token:{total:0,today:0,todayNew:0},user_exchange_by_debt:{total:{ph:0,zx:0},today:{ph:0,zx:0}},amount_exchange_token:{total:0,today:0},amount_exchange_by_debt:{total:{ph:0,zx:0},today:{ph:0,zx:0}},cleared_person_numbers:{total:0,yesterday:0},tokens_of_goods_sold:{total:0,today:0},goods_data:{quantity_of_goods_on_sale:0,quantity_of_goods_on_off_sale:0,quantity_of_goods_on_sold_out:0,quantity_of_goods_on_checking:0,quantity_of_goods_on_checked:0,quantity_of_goods_on_check_fail:0,quantity_of_goods_sold:0,quantity_of_goods_sold_today:0},shops_data:{shops_total:0},loading:null}},created(){this.getData()},methods:{getData(){const t=function(){return new Promise((t,a)=>{n().then(a=>{t(a)},a=>{t(a)})})},a=function(){return new Promise((t,a)=>{i().then(a=>{t(a)},a=>{t(a)})})};this.loading=this.$loading({lock:!0,text:"Loading",background:"rgba(0, 0, 0, 0.6)"}),Promise.all([t(),a()]).then(t=>{if(t[0].errorCode)this.$message.error(t[0].errorMsg);else{const a=t[0],s=a.total_reg,e=a.today_reg,l=a.total_agree_people,_=a.today_agree_people,n=a.total_exchange_num,i=a.today_exchange_num,c=a.total_exchange_people,r=a.today_exchange_people,d=a.new_exchange_people,p=a.total_exchange_amount,u=a.today_exchange_amount,v=a.total_integral,h=a.today_integral,g=a.sale_on,C=a.sale_off,x=a.sold_out,b=a.is_check_ing,y=a.is_check_on,m=a.is_check_off,f=a.total_number,k=a.today_number,w=a.suppliers;this.user_regist.total=s,this.user_regist.today=e,this.user_agree.total=l,this.user_agree.today=_,this.times_exchange.total=n,this.times_exchange.today=i,this.user_exchange_token.total=c,this.user_exchange_token.today=r,this.user_exchange_token.todayNew=d,this.amount_exchange_token.total=p,this.amount_exchange_token.today=u,this.cleared_person_numbers.total="",this.cleared_person_numbers.yesterday="",this.tokens_of_goods_sold.total=v,this.tokens_of_goods_sold.today=h,this.goods_data=Object(o["a"])({},this.goods_data,{quantity_of_goods_on_sale:g,quantity_of_goods_on_off_sale:C,quantity_of_goods_on_sold_out:x,quantity_of_goods_on_checking:b,quantity_of_goods_on_checked:y,quantity_of_goods_on_check_fail:m,quantity_of_goods_sold:f,quantity_of_goods_sold_today:k}),this.shops_data.shops_total=w}t[1].errorCode?this.$message.error(t[1].errorMsg):(this.cleared_person_numbers.total=t[1].data.total_quite_number,this.cleared_person_numbers.yesterday=t[1].data.yesterday_quite_number,this.user_exchange_by_debt.today.ph=t[1].data.ph_today_exchange_user,this.user_exchange_by_debt.today.zx=t[1].data.zx_today_exchange_user,this.user_exchange_by_debt.total.ph=t[1].data.ph_total_exchange_user,this.user_exchange_by_debt.total.zx=t[1].data.zx_total_exchange_user,this.amount_exchange_by_debt.today.ph=t[1].data.ph_today_exchange_num,this.amount_exchange_by_debt.today.zx=t[1].data.zx_today_exchange_num,this.amount_exchange_by_debt.total.ph=t[1].data.ph_total_exchange_num,this.amount_exchange_by_debt.total.zx=t[1].data.zx_total_exchange_num)}).finally(()=>{this.loading.close()})}}},r=c,d=(s("3476"),s("2877")),p=Object(d["a"])(r,e,l,!1,null,"85db6978",null);a["default"]=p.exports}}]);
//# sourceMappingURL=chunk-0e9256e8.b5424815.js.map