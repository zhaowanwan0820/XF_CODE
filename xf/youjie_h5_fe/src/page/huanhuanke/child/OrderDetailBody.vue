<!-- OrderDetailBody.vue -->
<template>
  <div class="order-body" v-if="orderDetail.id">
    <div class="order-body-top">
      <!-- 内部分销客 -->
      <div v-if="isInternal">
        <div class="image" v-if="orderDetail.order_status == 0">
          <div class="order-countdown">
            <span class="ms1">未变现</span>
            <span class="ms2">请在变现有效期内尽快变现</span>
          </div>
        </div>

        <div class="image" v-if="orderDetail.order_status == 1">
          <div class="order-countdown">
            <span class="ms1">已变现</span>
            <span class="ms2" v-if="orderDetail.status == 1">佣金及积分变现冻结中 买家确认收货7天后可提现</span>
            <span class="ms2" v-if="orderDetail.status == 4">佣金及积分变现可提现</span>
          </div>
        </div>

        <div class="image" v-if="orderDetail.order_status == 2 || orderDetail.order_status == 4">
          <div class="order-countdown">
            <span class="ms1">已取消</span>
            <span class="ms2">买家已取消订单</span>
          </div>
        </div>

        <div class="image" v-if="orderDetail.order_status == 3">
          <div class="order-countdown">
            <span class="ms1">已过期</span>
            <span class="ms2">该订单已过期 积分无法变现</span>
          </div>
        </div>
      </div>
      <!-- 外部分销客 -->
      <div v-else>
        <div class="image" v-if="orderDetail.order_status == 2 || orderDetail.order_status == 4">
          <div class="order-countdown">
            <span class="ms1">已取消</span>
            <span class="ms2">买家已取消订单</span>
          </div>
        </div>

        <div class="image" v-else>
          <div class="order-countdown">
            <span class="ms1">已完成</span>
            <span class="ms2" v-if="orderDetail.status == 4 && getTotalRebate">佣金可提现</span>
            <span class="ms2" v-if="orderDetail.status != 4 && getTotalRebate">
              佣金冻结中，买家确认收货七天后可提现
            </span>
          </div>
        </div>
      </div>

      <div class="container radius">
        <p class="good-title">购买人：{{ orderDetail.nickname }}</p>

        <div
          class="containers-wrapper"
          v-for="(item, index) in orderDetail.goods"
          v-bind:key="item.id"
          v-on:click="getOrderDetail(item.id)"
          v-if="index <= orderIndex"
        >
          <img
            class="photo"
            v-bind:src="item.thumb"
            data-src="../../../assets/image/change-icon/default_image_02@2x.png"
            v-if="item.thumb"
          />
          <img class="photo" src="../../../assets/image/change-icon/default_image_02@2x.png" v-else />
          <div class="right-wrapper">
            <label class="title">{{ item.name }}</label>
            <div class="property">
              <label>{{ item.property }}</label>
            </div>
            <div class="desc-wrapper">
              <div>
                <label class="price">
                  <span class="price-unit">￥</span>
                  <span>{{ utils.formatFloat(item.product_price) }}</span>
                </label>
              </div>
              <div>
                <label class="count">x{{ item.total_amount }}</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="desc">
        <div class="price-info">
          <p>订单结算</p>
          <checkout-desc class="desc-item" title="订单金额" :subtitle="getTotal"></checkout-desc>
          <checkout-desc class="desc-item" title="买家支付" :subtitle="getPayTotal"></checkout-desc>
        </div>
      </div>

      <div class="desc">
        <div class="price-info">
          <p>分销返佣结算<img src="../../../assets/image/hh-icon/mlm/icon-overdue.png" v-if="InvalidStatus1" /></p>
          <checkout-desc
            class="desc-item"
            v-bind:class="{ brown: InvalidStatus1 }"
            title="分销佣金"
            :subtitle="getRebate"
          ></checkout-desc>
          <checkout-desc
            v-for="item in orderDetail.act_detail"
            :key="item.ext_id"
            class="desc-item"
            v-bind:class="{ brown: InvalidStatus1 }"
            :title="item.name"
            :subtitle="'￥' + utils.formatFloat(item.money)"
            :isAdd="true"
          ></checkout-desc>
          <div class="amount-content">
            <div class="amount-back" v-bind:class="{ brown: InvalidStatus1 }">
              <label class="left">返佣金额</label>
              <label class="right">￥{{ getTotalRebate }}</label>
            </div>
            <label class="cancel" v-bind:class="{ transp: !InvalidStatus1 }" v-if="getTotalRebate && warnTxt1">
              <span>{{ warnTxt1 }}</span>
            </label>
          </div>
        </div>
      </div>

      <div class="desc" v-if="isInternal">
        <div class="price-info">
          <p>积分变现结算<img src="../../../assets/image/hh-icon/mlm/icon-overdue.png" v-if="InvalidStatus2" /></p>
          <checkout-desc
            class="desc-item"
            v-bind:class="{ brown: InvalidStatus2 }"
            v-if="orderDetail.order_status == 0"
            title="可变现积分数"
            :subtitle="getSurplus"
            :isIcon="true"
          ></checkout-desc>
          <checkout-desc
            class="desc-item"
            v-bind:class="{ brown: InvalidStatus2 }"
            v-else
            title="支付积分"
            :subtitle="getSurplus"
            :isIcon="true"
            :isReduce="true"
          ></checkout-desc>
          <checkout-desc
            class="desc-item bt-line"
            v-bind:class="{ brown: InvalidStatus2 }"
            v-if="orderDetail.order_status == 0"
            title="变现金额"
            :subtitle="getOriRebate"
          ></checkout-desc>
          <checkout-desc
            class="desc-item bt-line"
            v-bind:class="{ brown: InvalidStatus2 }"
            v-else
            title="变现金额"
            :subtitle="getOriRebate"
            :isAdd="true"
          ></checkout-desc>
          <div class="button-content" v-if="orderDetail.order_status == 0">
            <button @click="payment(orderDetail.id)">积分变现</button>
          </div>
          <div class="amount-content" v-else>
            <div class="amount-back" v-bind:class="{ brown: InvalidStatus2 }">
              <label class="left">变现金额</label>
              <label class="right">{{ getOriRebate }}</label>
            </div>
            <label class="cancel" v-bind:class="{ transp: !InvalidStatus2 }">
              <span>{{ warnTxt2 }}</span>
            </label>
          </div>
        </div>
      </div>

      <div class="detail">
        <div class="number">
          <div>
            <label>
              <span class="order-title">订单编号：</span>
              <span class="order-sn">{{ orderDetail.sn }}</span>
            </label>
            <label class="tag-read" :data-clipboard-text="orderDetail.sn" v-on:click="getCopy">复制</label>
          </div>
          <div>
            <label>
              <span class="order-title">下单时间：</span>
              <span class="order-sn">{{ orderDetail.created_at | convertTime }}</span>
            </label>
          </div>
          <div>
            <label>
              <span class="order-title">配送方式：</span>
              <span class="order-sn" v-if="orderDetail.shipping">{{ orderDetail.shipping.name }}</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- 支付明细 -->
    <div class="btn" v-if="do_surplus">
      <button v-on:click="payDetail(orderDetail.id)">支付明细</button>
    </div>
  </div>
</template>

<script>
import OrderPrice from './OrderPrice'
import ContactSupplier from '../../../components/common/ContactSupplier'
import { Indicator, MessageBox, Popup } from 'mint-ui'
import CheckoutDesc from './CheckoutDesc'
import { PopupShareFriendPay } from '../../../components/common'
import Promos from '../../checkout/Promos'
import { ENUM } from '../../../const/enum'
import { AFTERSALEDAYS } from '../static'

import { huanOrderGet } from '../../../api/huanhuanke'
import { Toast } from 'mint-ui'
import Clipboard from 'clipboard'
import { mapState, mapMutations } from 'vuex'
export default {
  mixins: [Promos],
  data() {
    return {
      orderDetail: {},
      orderIndex: 2,
      do_surplus: 0
    }
    // order.status  0未代付 1已付款(冻结) 4已完成(解冻) 5已取消
    // order.order_status  0代付款 1已付款 2已取消 3已过期 4售后
  },

  components: {
    CheckoutDesc
  },

  created() {
    let id = this.$route.query.id ? this.$route.query.id : null
    this.orderInfo(id)
  },
  methods: {
    ...mapMutations({
      changeItem: 'changeItem'
    }),

    // 获取订单详情数据
    orderInfo(id) {
      huanOrderGet(id).then(res => {
        if (res) {
          this.orderDetail = res
          this.do_surplus = res.do_surplus * 1
        }
      })
    },

    payDetail(id) {
      this.$router.push({ name: 'HuanKeOrderPayDetail', query: { id: id } })
    },

    // 复制
    getCopy() {
      var clipboard = new Clipboard('.tag-read')
      clipboard.on('success', e => {
        console.log('复制成功')
        // 释放内存
        clipboard.destroy()
      })
      clipboard.on('error', e => {
        // 不支持复制
        console.log('该浏览器不支持自动复制')
        // 释放内存
        clipboard.destroy()
      })
      Toast({
        message: '复制成功',
        iconClass: 'mintui mintui-field-success',
        duration: 2000
      })
    },

    // 去商品详情
    getOrderDetail(orderId) {
      this.$router.push({ name: 'product', query: { id: orderId } })
    },
    payment(order) {
      this.$router.push({ name: 'HuanKeConfirm', params: { id: order } })
    }
  },

  computed: {
    ...mapState({
      isInternal: state => state.mlm.isInternal //是否为内部分销客
    }),
    //分销总额
    getTotal() {
      let total = Number(this.orderDetail.total)
      if (!total) {
        total = this.utils.formatFloat(this.orderDetail.goods[0].total_price)
      }
      return '￥' + total
    },
    // 买家支付
    getPayTotal() {
      let total = Number(this.orderDetail.total)
      if (!total) {
        total = this.utils.formatFloat(this.orderDetail.goods[0].total_price)
      }
      return '-￥' + total
    },
    // 分销佣金
    getRebate() {
      let cp = Number(this.orderDetail.custom_profit)
      if (cp < 0) cp = 0
      return '+￥' + cp
    },
    // 返佣金额
    getTotalRebate() {
      let tr = 0
      let cp = Number(this.orderDetail.custom_profit)
      if (cp >= 0) tr += cp
      if (this.orderDetail.act_detail && this.orderDetail.act_detail.length) {
        this.orderDetail.act_detail.forEach(val => {
          tr += Number(val.money)
        })
      }

      return tr
    },
    // 支付积分
    getSurplus() {
      return this.utils.formatFloat(this.orderDetail.surplus)
    },
    // 变现金额
    getOriRebate() {
      return '￥' + this.utils.formatFloat(this.orderDetail.change_rebate)
    },

    // 是否为部分退款
    isRefund() {
      let refund = Number(this.orderDetail.refund) ? true : false
      return refund
    },
    warnTxt1() {
      let warn_txt = ''
      let os = this.orderDetail.order_status
      let s = this.orderDetail.status
      let ds = Number(this.orderDetail.do_surplus)

      if (os == 0 || (os == 1 && s == 1)) {
        warn_txt = '佣金冻结中，买家确认收货七天后可提现'
      } else if ((os == 1 && s == 4) || os == 3) {
        warn_txt = '可提现'
      } else if (os == 2 || os == 4) {
        if (!ds) {
          warn_txt = '订单已取消 佣金将会退回'
        }
      }
      return warn_txt
    },
    warnTxt2() {
      let warn_txt = ''
      let os = this.orderDetail.order_status
      let s = this.orderDetail.status
      let ds = Number(this.orderDetail.do_surplus)

      if (os == 1 && s == 1) {
        warn_txt = '变现金额冻结中，买家确认收货7天后可提现'
      } else if (os == 1 && s == 4) {
        warn_txt = '可提现'
      } else if (os == 2 && ds) {
        warn_txt = '订单已取消 代付的积分 变现的金额将会退回'
      }
      return warn_txt
    },
    // 已失效图标
    InvalidStatus1() {
      let flag
      let os = this.orderDetail.order_status
      flag = os == 2 || os == 4 ? true : false
      return flag
    },
    InvalidStatus2() {
      let flag
      let os = this.orderDetail.order_status
      flag = os == 2 || os == 3 || os == 4 ? true : false
      return flag
    }
  }
}
</script>

<style lang="scss" scoped>
.order-body {
  position: fixed;
  width: 100%;
  top: 44px;
  bottom: 0;
  overflow: auto;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  .order-body-top {
    overflow-y: auto;
    padding-bottom: 20px;
  }
}
.image {
  background-image: url('../../../assets/image/hh-icon/mlm/bg-order.png');
  background-size: cover;
  height: 110px;
  .order-countdown {
    height: 75px;
    padding-left: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    span {
      // display: block;
      color: #552e20;
    }
    .ms1 {
      font-size: 18px;
      line-height: 25px;
      font-weight: 500;
      // padding-top: 16px;
    }
    .ms2 {
      position: relative;
      font-size: 11px;
      color: #552e20;
      opacity: 0.5;
      line-height: 16px;
      margin-top: 3px;
      font-weight: 400;
    }
  }
  img {
    height: 20px;
    padding: 0 10px 0 20px;
  }
}
.container {
  // padding: 0 15px;
  overflow: hidden;
  background-color: #fff;
  .good-title {
    font-size: 14px;
    font-weight: 300;
    line-height: 20px;
    color: #404040;
    padding: 20px 15px 10px;
    @include thin-border(#f4f4f4, 15px);
  }
  .containers-wrapper {
    display: flex;
    flex-direction: row;
    justify-content: flex-start;
    align-items: stretch;
    @include thin-border(#f4f4f4, 15px);
    padding: 10px 15px;
  }
}
.radius {
  border-radius: 8px 8px 0px 0px;
  margin-top: -34px;
}
.onClick {
  height: 44px;
  line-height: 44px;
  text-align: center;
  background-color: #fff;
  p {
    font-size: 14px;
    color: #4e545d;
  }
}
.photo {
  width: 85px;
  height: 85px;
  margin-right: 12px;
  flex-basis: 85px;
  flex-shrink: 0;
  border-radius: 2px;
}
.right-wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
  overflow: hidden;
}
.title {
  font-size: 13px;
  color: $baseColor;
  line-height: 16px;
  height: 32px;
  margin-top: 3px;
  padding-right: 48px;

  // overflow: hidden;
  // text-overflow: ellipsis;
  // white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.property {
  display: flex;
  justify-content: flex-start;
  height: 18px;
  margin-top: 1px;
  label {
    @include sc(10px, #888);
    line-height: 18px;
  }
}
.desc-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-top: 15px;
  overflow: hidden;
  .price {
    font-size: 14px;
    line-height: 12px;
    color: $baseColor;
    span {
      font-size: 15px;
      &.price-unit {
        @include sc(9px, #404040);
      }
    }
  }
  .count {
    color: #888;
    font-size: 12px;
  }
}
// .propertyOrder {
//   padding-top: 34px;
// }
.address {
  background-color: #fff;
  width: 375px;
  height: 73px;
  border-radius: 8px 8px 0px 0px;
  margin-top: -34px;
  margin-bottom: 10px;
  div {
    padding: 16px 15px 0;
    height: 18px;
  }
  span {
    display: inline-block;
    color: #404040;
    font-size: 13px;
    line-height: 18px;
    font-weight: 300;
    &.mobile {
      padding-left: 17px;
    }
  }
  p {
    padding: 4px 18px 17px 15px;
    font-size: 13px;
    line-height: 18px;
    color: #888;

    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }
}
.total-price /deep/ {
  label.title {
    font-size: 12px;
    line-height: 17px;
    color: $baseColor;
  }
  .warn {
    font-size: 12px;
    color: $deleteColor;
    line-height: 17px;
  }
  label.subtitle {
    font-size: 15px;
    font-weight: bold;
    color: $baseColor;
    line-height: 21px;
  }
}

.contact {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  height: 46px;
  background-color: #fff;
  margin-top: 8px;
  border-bottom: 1px solid $lineColor;
  padding: 0 13px;
  span {
    font-size: 12px;
    color: #4e545d;
    padding-right: 6px;
  }
  img {
    width: 12px;
    height: 13px;
  }
}

.detail {
  display: flex;
  flex-direction: column;
  font-size: 14px;
  color: #7c7f88;
  background-color: #fff;
  box-sizing: border-box;
  margin-top: 10px;
  .number {
    padding: 15px;
    border-bottom: 1px solid #f4f4f4;
    font-size: 12px;
    line-height: 17px;
    color: $subbaseColor;
    div {
      display: flex;
      justify-content: space-between;
      height: 17px;
      margin-bottom: 8px;
      .order-title {
        color: #888;
      }
      .order-sn {
        color: #404040;
      }
      .tag-read {
        @include sc(11px, #552e20);
      }
      &:nth-last-child(1) {
        margin: 0;
      }
    }
    p {
      padding-top: 6px;
      font-size: 13px;
      line-height: 18px;
      color: $subbaseColor;
    }
  }
  .pay {
    border-bottom: 1px solid $lineColor;
    padding: 14px 16px;
  }
  .givetime {
    padding: 16px 20px;
    font-size: 13px;
    line-height: 18px;
    border-bottom: 1px solid $lineColor;
  }
  .mall-phone {
    height: 24px;
    padding: 11px 0;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid $lineColor;
    div {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 22px;
      font-size: 13px;
      line-height: 18px;
      color: #552e20;
      & + div {
        border-left: 1px solid #f4f4f4;
      }
      img {
        width: 16px;
        height: 16px;
        margin-right: 7px;
      }
      span {
        line-height: 1;
      }
    }
  }
  input {
    background-color: #fff;
    border: 1px solid #7c7f88;
  }
}
.desc {
  background-color: #fff;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  padding: 13px 0;
  margin-top: 10px;
  box-sizing: border-box;
  .price-info {
    padding: 0 15px;
    p {
      @include thin-border(#f4f4f4, 15px);
      position: relative;
      font-size: 14px;
      font-weight: 300;
      color: #404040;
      line-height: 20px;
      padding-bottom: 10px;
      img {
        width: 35px;
        height: 35px;
        position: absolute;
        top: -8px;
        right: -7px;
      }
    }
    .container {
      margin: 0;
      padding: 8px 0 0;
    }
    .button-content {
      display: flex;
      justify-content: center;
      align-items: center;
      button {
        margin-top: 15px;
        width: 327px;
        height: 46px;
        background: #772508;
        border-radius: 2px;
        color: #fff;
        font-size: 18px;
        line-height: 46px;
      }
    }
    .amount-content {
      .amount-back {
        display: flex;
        justify-content: space-between;
        align-items: center;

        padding: 9px 0 10px;
        color: #772508;
        font-size: 14px;
        line-height: 20px;
      }
    }
  }
  .desc-item {
    flex: 1;
  }
  .bt-line {
    padding-bottom: 8px !important;
    @include thin-border(#f4f4f4);
  }
  .brown {
    opacity: 0.3;
  }
  .amount-wrapper {
    padding: 0 15px;
    overflow: hidden;
  }
  span {
    display: flex;
    justify-content: flex-end;
  }
  .amount {
    display: flex;
    justify-content: flex-end;
    height: 18px;
    margin-bottom: 7px;
    .surplus,
    .cash {
      margin-left: 5px;
    }
    .amount-title {
      font-size: 13px;
      line-height: 18px;
      font-weight: normal;
    }
    .surplus {
      display: flex;
      align-items: center;
      img {
        width: 13px;
        height: 13px;
      }
      span {
        font-size: 16px;
        line-height: 19px;
      }
    }
    .cash {
      display: flex;
      align-items: center;
      .cash-surplus {
        line-height: 12px;
        @include sc(11px, #772508);
        font-weight: bold;
      }
      .cash-icon {
        line-height: 14px;
        @include sc(10px, #772508);
        font-weight: bold;
        padding-top: 4px;
        margin-left: 2px;
        margin-right: -2px;
      }
      .cash-num {
        font-size: 16px;
        line-height: 19px;
      }
    }
    span {
      font-weight: bold;
      color: #772508;
    }
  }
  .cancel {
    display: flex;
    justify-content: flex-end;
    span {
      @include sc(10px, #552e20, right center);
      line-height: 14px;
      &.none {
        margin: 0;
      }
      &.deep {
        @include sc(10px, #552e20);
      }
    }
  }
  .transp {
    opacity: 0.5;
  }
}
.btn {
  height: 62px;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
  background-color: #f9f9f9;
  align-items: center;
  button {
    width: 84px;
    height: 30px;
    font-size: 13px;
    margin-right: 15px;
    background-color: #fff;
    color: #552e20;
    border-radius: 2px;
    border: 1px solid #552e20;
  }
  .buttonbottom {
    background-color: #772508;
    color: #fff;
    border: none;
  }
  .buttondetail {
    border-radius: 2px;
    background: #772508;
    color: #fff;
  }
  .mint-popup {
    width: 100%;
    height: 235px;
  }
  .cancels {
    height: 100%;
    .cancelInfo {
      display: flex;
      flex-wrap: nowrap;
      justify-content: space-between;
      border-bottom: 1px solid #f0f0f0;
      span {
        color: #000;
        font-size: 14px;
      }
      .cancel {
        padding: 10px 15px;
      }
      .success {
        padding: 10px 15px;
      }
    }
    .reason {
      margin-top: 10px;
      p {
        height: 16px;
        line-height: 16px;
        text-align: center;
        padding: 10px;
        &.red {
          color: red;
        }
      }
    }
  }
}
</style>

<!-- 字体图标样式覆盖 -->
<style>
.mint-toast-icon {
  font-size: 38px;
}
button {
  padding: 0;
}
</style>
