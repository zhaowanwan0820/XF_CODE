<!-- OrderDetailBody.vue -->
<template>
  <div class="order-body" v-if="orderDetail.id">
    <div class="order-body-top" v-bind:class="{ ship: orderDetail.status == 1 }">
      <div
        class="receipt"
        v-if="orderDetail.status == 1 && trackList.length >= 1"
        v-on:click="goOrderrack(orderDetail.id)"
      >
        <label>
          <img src="../../../assets/image/change-icon/e0_delivery@2x.png" />
          <span>{{ trackList[0].content }}</span>
        </label>
        <img class="arrow" src="../../../assets/image/change-icon/enter@2x.png" />
      </div>

      <div class="image" v-if="orderDetail.status == 2">
        <div class="order-countdown">
          <span class="ms1">配送中</span>
          <span class="ms2">宝贝正在快马加鞭地配送中</span>
        </div>
      </div>

      <div
        class="receipt"
        v-if="orderDetail.status == 2 && trackList.length >= 1"
        v-on:click="goOrderrack(orderDetail.id)"
      >
        <label>
          <img src="../../../assets/image/change-icon/icon_car@2x.png" />
          <span>{{ trackList[0].content }}</span>
        </label>
        <img class="arrow" src="../../../assets/image/change-icon/enter@2x.png" />
      </div>

      <div class="image" v-if="orderDetail.status == 3">
        <div class="order-countdown">
          <span class="ms1">待评价</span>
          <span class="ms2">收到宝贝了 给个好评吧</span>
        </div>
      </div>

      <div
        class="receipt"
        v-if="orderDetail.status == 3 && trackList.length >= 1"
        v-on:click="goOrderrack(orderDetail.id)"
      >
        <label>
          <span>{{ trackList[0].content }}</span>
        </label>
        <img class="arrow" src="../../../assets/image/change-icon/enter@2x.png" />
      </div>

      <div class="image" v-if="orderDetail.status == 4">
        <div class="order-countdown">
          <span class="ms1">已完成</span>
          <span class="ms2">感谢您的慷慨支付</span>
        </div>
      </div>

      <div class="image" v-if="orderDetail.status == 5">
        <div class="order-countdown">
          <span class="ms1">已取消</span>
          <span class="ms2">期待再次为您服务</span>
        </div>
      </div>

      <div class="image" v-if="orderDetail.status == 6">
        <div class="order-countdown">
          <span class="ms1">配货中</span>
          <span class="ms2"></span>
        </div>
      </div>

      <div
        class="receipt"
        v-if="orderDetail.status == 6 && trackList.length >= 1"
        v-on:click="goOrderrack(orderDetail.id)"
      >
        <label>
          <img src="../../../assets/image/change-icon/e0_delivery@2x.png" />
          <span>{{ trackList[0].content }}</span>
        </label>
        <img class="arrow" src="../../../assets/image/change-icon/enter@2x.png" />
      </div>

      <div class="container radius">
        <p class="good-title">申请人：{{ orderDetail.nickname }}</p>
        <div
          class="containers-wrapper"
          v-for="(item, index) in orderDetail.goods"
          v-bind:key="item.id"
          v-on:click="getOrderDetail(item.id, orderDetail.extension_code)"
          v-if="index <= orderIndex"
        >
          <img class="photo" src="../../../assets/image/change-icon/default_image_02@2x.png" v-if="!item.thumb" />
          <img
            class="photo"
            v-bind:src="item.thumb"
            data-src="../../../assets/image/change-icon/default_image_02@2x.png"
            v-else
          />
          <div class="right-wrapper">
            <label class="title">{{ item.name }}</label>
            <div class="property">
              <label>{{ item.property }}</label>
            </div>
            <div class="desc-wrapper" v-bind:class="{ propertyOrder: item.property == '' }">
              <div>
                <label class="price" v-if="!orderDetail.extension_code">
                  <span class="price-unit">￥</span>
                  <span>{{ utils.formatFloat(item.product_price) }}</span>
                </label>
                <!-- <label class="price" v-if="orderDetail.extension_code == isExchangeGood">{{ orderDetail.use_score.score }}积分</label> -->
              </div>
              <div>
                <label class="count">x{{ item.total_amount }}</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- <div class="onClick" v-if="orderDetail.goods.length > 3 && !isShow">
        <p v-on:click="getNumber">还有 {{ orderDetail.goods.length - 3 }} 件</p>
      </div>-->

      <div class="desc section-header section-footer" v-bind:class="{ ship: orderDetail.status == 1 }">
        <div class="price-info">
          <checkout-desc class="desc-item" title="商品总额" :subtitle="getOrderProductPrice"></checkout-desc>
          <checkout-desc class="desc-item" title="运费" :subtitle="getOrderShippingPrice"></checkout-desc>
          <template v-if="getPromos && getPromos.length > 0">
            <checkout-desc
              class="desc-item"
              v-for="(item, index) in getPromos"
              :key="index"
              v-if="orderDetail.status == 0 || item.price > 0"
              :title="getPromoTitle(item)"
              :subtitle="getOrderDiscountPrice(item)"
            ></checkout-desc>
            <checkout-desc
              class="desc-item"
              title="已支付"
              :isIcon="isIcon"
              :subtitle="getOrderFriendHadPay"
              v-if="orderDetail.status == 5"
            ></checkout-desc>
            <checkout-desc
              class="desc-item"
              title="好友支付"
              :subtitle="getOrderFriendPayCash"
              v-if="orderDetail.share_pay"
            ></checkout-desc>
          </template>
        </div>
        <!-- <checkout-desc class="desc-item" title="积分" :subtitle="getBalance" :warn='showWarn' :status='orderDetail.status'>
        </checkout-desc>
        <checkout-desc class="desc-item" v-for="(item, index) in getPromos" :key="index" :title=" getPromoTitle(item)" :subtitle="'-' + getOrderDiscountPrice(item)">
        </checkout-desc>
        <checkout-desc class="desc-item total-price" title="订单总价" :subtitle="getOrderTotalPrice">
        </checkout-desc>-->
        <div class="amount-wrapper">
          <label class="amount" v-if="orderDetail.status != 5">
            <span class="amount-title">实际支付:</span>
            <!-- 积分部分 -->
            <label class="surplus">
              <img src="../../../assets/image/hh-icon/b0-home/money-icon.png" />
              <span class="surplus-num">{{ getOrderTotalPrice }}</span>
            </label>
          </label>
          <label class="cancel" v-if="orderDetail.status == 5 && (money_paid || surplus_paid)">
            <span>订单已取消，已支付的部分将会按照原支付方式退回</span>
          </label>
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
          <div v-if="orderDetail.status > 1 && orderDetail.status != 5">
            <span class="order-title">配送方式：</span>
            <span class="order-sn">{{ orderDetail.shipping.name }}</span>
          </div>
        </div>
        <!-- <div class="pay" v-if="orderDetail.status !== 0">
          <p v-if="orderDetail.extension_code == isExchangeGood">支付方式：积分兑换</p>
          <p v-else>支付方式：{{orderDetail.payment.name}}</p>
        </div>-->
        <div
          class="mall-phone"
          v-if="(orderDetail.status == 1 || orderDetail.status == 2) && orderDetail.supplier.service_tel"
        >
          <div>
            <img src="../../../assets/image/hh-icon/e5-orderDetail/icon-phone.png" />
            <span
              @click="
                () => {
                  this.isShowPhone = !this.isShowPhone
                }
              "
              >联系商家</span
            >
          </div>
          <div>&nbsp;</div>
        </div>
      </div>

      <contact-supplier
        @cancelPhone="cancelPhone"
        v-if="isShowPhone"
        :phone="orderDetail.supplier.service_tel"
      ></contact-supplier>
    </div>
    <!-- 待付款按钮 -->
    <div class="btn" v-if="orderDetail.status == 0 && !isFailure">
      <button v-on:click="cancel">取消订单</button>
      <mt-popup v-model="popupVisible" position="bottom" class="mint-popup">
        <div class="cancels">
          <div class="cancelInfo">
            <span class="cancel" v-on:click="cancelInfo">取消</span>
            <span class="success" v-on:click="complete(orderDetail.id)">完成</span>
          </div>
          <div class="reason">
            <p
              v-for="(item, list) in reasonList"
              v-bind:key="list"
              v-on:click="getReasonItem(item)"
              :class="{ red: reasonId == item.id }"
            >
              {{ item.name }}
            </p>
          </div>
        </div>
      </mt-popup>
      <button class="buttonbottom" v-on:click="sharePay" v-if="orderDetail.share_sn && money_paid">好友支付</button>
      <button class="buttonbottom" v-on:click="payment" v-else>继续支付</button>
    </div>

    <!-- 待发货按钮 -->
    <div class="btn" v-if="orderDetail.status == 1 ? '' : checkState"></div>

    <!-- 待收货按钮 -->
    <div class="btn" v-if="orderDetail.status == 2">
      <button v-on:click="payDetail(orderDetail.id)">支付明细</button>
      <button class="buttonbottom" v-on:click="confirm(orderDetail.id, index)">确认收货</button>
    </div>

    <!-- 支付明细 -->
    <div class="btn" v-if="orderDetail.status == 1 || orderDetail.status == 3">
      <button v-on:click="payDetail(orderDetail.id)">支付明细</button>
    </div>

    <!-- 配货中 -->
    <div class="btn" v-if="orderDetail.status == 6">
      <button class="buttonbottom" v-on:click="confirm(orderDetail.id, index)">确认收货</button>
    </div>
  </div>
</template>

<script>
import { ORDERSTATUS, ORDERNAV, ORDEREFFRCTTIME } from '../static'
import OrderPrice from './OrderPrice'
import ContactSupplier from '../../../components/common/ContactSupplier'
import { Indicator, MessageBox, Popup } from 'mint-ui'
import CheckoutDesc from './CheckoutDesc'
import Promos from '../../checkout/Promos'
import { ENUM } from '../../../const/enum'

import { orderFriendPayGet, orderReasonList, orderCancel, orderConfirm } from '../../../api/order'
import { Toast } from 'mint-ui'
import Clipboard from 'clipboard'
import { mapState, mapMutations } from 'vuex'
export default {
  mixins: [Promos],
  data() {
    return {
      isShowPhone: false,
      orderDetail: {},
      popupVisible: false,
      reasonList: [],
      orderCancel: [],
      checkState: '',
      ORDERSTATUS: ORDERSTATUS,
      currentNAVId: '',
      orderListParams: { page: 0, per_page: 10, status: '' },
      index: '',
      total_price: [],
      orderIndex: 2,
      isShow: false,
      trackList: [],
      // isExchangeGood: ENUM.ORDER_EXTENSION_CODE.EXCHANGE_GOODS,
      orderTime: 0,
      timeTxt: '',
      reasonId: '',
      isFailure: false, // 订单是否失效

      isIcon: true,
      money_paid: 0,
      surplus_paid: 0,
      friendPayFlag: false,
      share_sn: '',
      share_option: ['WechatSession']
    }
  },

  props: {
    item: {
      type: Object
    }
  },

  components: {
    OrderPrice,
    CheckoutDesc,
    ContactSupplier
  },

  created() {
    let id = this.$route.query.id ? this.$route.query.id : null
    this.orderInfo(id)
    this.orderReasonList()
  },
  methods: {
    ...mapMutations({
      changeItem: 'changeItem'
    }),

    // 获取订单详情数据
    orderInfo(id) {
      orderFriendPayGet(id).then(res => {
        this.orderDetail = res
        this.total_price = res.goods
        this.orderTime = res.created_at
        this.money_paid = this.utils.formatFloat(res.hhpay.money_paid) * 1
        this.surplus_paid = this.utils.formatFloat(res.hhpay.surplus_paid) * 1
        this.share_sn = res.share_sn ? res.share_sn : ''
        this.getRestTime(this.orderTime)
      })
    },

    // 获取订单倒计时
    getRestTime(orderTime) {
      var RestTime = ORDEREFFRCTTIME - (Math.floor(new Date().getTime() / 1000) - orderTime)
      if (RestTime > 0 && RestTime < ORDEREFFRCTTIME) {
        var timer = setInterval(() => {
          --RestTime
          if (RestTime < 0) {
            clearInterval(timer)
            this.timeTxt = '该订单已失效'
            this.isFailure = true
            return false
          }
          this.timeTxt = this.exportTime(RestTime)
        }, 1000)
      } else {
        if (timer) {
          clearInterval(timer)
        }
        this.timeTxt = '该订单已失效'
        this.isFailure = true
      }
    },
    exportTime(orderTime) {
      let minite = Math.floor(orderTime / 60)
      let sec = orderTime % 60
      return minite + '分' + sec + '秒后订单会自动取消'
    },

    // 取消订单
    cancel() {
      this.popupVisible = true
    },

    cancelInfo() {
      this.popupVisible = false
    },

    complete(id, index) {
      this.popupVisible = false
      this.getordersuccess(id, index)
    },

    // 去支付
    payment() {
      let order = this.orderDetail
      if (order.id) {
        this.$router.push({ name: 'payment', query: { order: order.id, total: order.total } })
      }
    },

    // 获取退货原因数据
    orderReasonList() {
      orderReasonList().then(res => {
        this.reasonList = Object.assign([], this.reasonList, res)
      })
    },

    // 获取取消订单数据
    getordersuccess(id, index) {
      orderCancel(id, this.reasonId).then(res => {
        this.orderInfo(id)
      })
    },

    getReasonItem(item) {
      this.reasonId = item.id
    },

    // 确认收货
    confirm(id, index) {
      MessageBox.confirm('是否确认收货？', '确认收货').then(action => {
        this.orderConfirms(id)
      })
    },
    payDetail(id) {
      this.$router.push({ name: 'orderPayDetail', query: { id: id } })
    },

    // 获取确认收货数据
    orderConfirms(id, index) {
      orderConfirm(id).then(res => {
        this.orderDetail.status = res.status
      })
    },

    getOrderDiscountPrice(item) {
      return '-￥' + (item.price ? this.utils.formatFloat(item.price) : 0)
    },

    getFormatPrice(key) {
      let price = this.getPriceByKey(key)
      let priceStr = '￥' + (price ? this.utils.formatFloat(price) : '0')
      return priceStr
    },

    getPriceByKey(key) {
      let total = ''
      let order = this.order
      if (order && order[key]) {
        total = order[key]
      }
      return total
    },

    // 计算商品总额
    goodsTotalPrice() {
      let totalPrice = 0
      let total_price = this.total_price
      if (total_price.length > 0) {
        for (let i = 0, len = total_price.length; i <= len - 1; i++) {
          if (total_price[i].total_price) {
            totalPrice += parseFloat(total_price[i].total_price)
          }
        }
        return this.utils.formatFloat(totalPrice)
      } else {
        return this.utils.formatFloat(totalPrice)
      }
    },

    // 分享好友代付
    sharePay() {
      this.$refs.shareFriendPayPop.open()
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
    getOrderDetail(orderId, code) {
      // this.$router.push({ name: 'product', query: { id: orderId, isExchange: (code == this.isExchangeGood) } });
      this.$router.push({ name: 'product', query: { id: orderId } })
    },

    // 点击展示所有商品
    // getNumber() {
    //   this.orderIndex = this.orderDetail.goods.length - 1
    //   this.isShow = true
    // },

    // 从订单详情去订单跟踪页面
    goOrderrack(id) {
      this.$router.push({
        name: 'orderTrack',
        params: { orderTrack: id, isTrack: true }
      })
    },

    cancelPhone(bool) {
      this.isShowPhone = bool
    }
  },

  computed: {
    ...mapState({
      orderItem: state => state.order.orderItem,
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user,
      currentBalance: state => state.balance.currentBalance
    }),
    getPromos: function() {
      return this.getPriceByKey('promos')
    },

    getOrderTotalPrice: function() {
      return this.orderDetail.hhpay.surplus_paid
    },

    getOrderProductPrice: function() {
      return '￥' + this.goodsTotalPrice()
    },

    getOrderTaxPrice: function() {
      return this.getFormatPrice('tax')
    },

    getOrderFriendHadPay: function() {
      return this.utils.formatFloat(this.orderDetail.hhpay.surplus_paid)
    },

    getOrderFriendPayCash: function() {
      return '-￥' + this.utils.formatFloat(this.orderDetail.hhpay.money_paid) //发起人支付的现金
    },

    getOrderShippingPrice: function() {
      let priceStr = ''
      let price = this.getPriceByKey('shipping')
      if (price) {
        priceStr = '￥' + this.utils.formatFloat(price.price)
      } else {
        priceStr = '免运费'
      }
      return priceStr
    },
    getBalance: function() {
      // let balance = '-'+ this.currentBalance
      let balance = '- ' + this.goodsTotalPrice()
      return balance
    },
    showWarn: function() {
      let totaoPrice = parseFloat(this.total_price[0].total_price)
      if (totaoPrice > this.currentBalance) {
        return true
      } else {
        return false
      }
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
  background-image: url('../../../assets/image/hh-icon/e5-orderDetail/orderDetail-bg@3x.png');
  background-size: cover;
  height: 108px;
  .order-countdown {
    height: 73px;
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
.receipt {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: auto;
  padding: 13px;
  background-color: #fff;
  margin-bottom: 8px;
  label {
    display: flex;
    align-items: center;
  }
  img {
    height: 16px;
    margin: 0 15px 0 10px;
  }
  .arrow {
    width: 5px;
    height: 10px;
  }
  span {
    font-size: 14px;
    color: #4e545d;
  }
}
.container {
  // padding: 0 15px;
  overflow: hidden;
  background-color: #fff;
  .good-title {
    font-size: 14px;
    font-weight: 300;
    line-height: 37px;
    color: #404040;
    padding: 0 15px;
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
    margin-left: -1.6%;
  }
}
// .count {
//   margin-top: 4px;
//   color: #7c7f88;
//   font-size: 13px;
//   margin-right: 10px;
// }
.desc-wrapper {
  // position: absolute;
  // bottom: 9px;
  // width: 261px;
  // height: 20px;
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-top: 15px;
  overflow: hidden;
  .price {
    // font-size: 0;
    font-size: 14px;
    line-height: 16px;
    color: $baseColor;
    span {
      font-size: 15px;
      &.price-unit {
        font-size: 12px;
      }
    }
  }
  .count {
    color: $subbaseColor;
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
  padding-top: 15px;
  box-sizing: border-box;
  .price-info {
    @include thin-border(#f4f4f4, 15px);
    padding: 0 15px 15px;
    overflow: hidden;
  }
  .desc-item {
    flex: 1;
  }
  .amount-wrapper {
    padding: 15px 15px;
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
        margin-left: 4px;
        margin-right: 4px;
      }
      span {
        font-size: 16px;
        line-height: 19px;
      }
    }
    .cash {
      display: flex;
      align-items: center;
      margin-left: 2px;
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
    margin-top: 7px;
    margin-right: -7%;
    span {
      @include sc(10px, #552e20);
      line-height: 14px;
    }
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
.ship {
  margin-bottom: 0;
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
