<template>
  <div class="container">
    <mt-header class="header" title="支付结果">
      <header-item
        v-if="status == order_pay_failed"
        slot="left"
        isLeft
        :icon="require('../../assets/image/hh-icon/icon-关闭.svg')"
        v-on:onclick="goBack"
      ></header-item>
      <div
        class="complete-header-item"
        slot="right"
        v-if="status == order_pay_succeed || status == order_pay_succeed_friend || status == order_pay_succeed_mlm"
        @click="goBack"
      >
        完成
      </div>
    </mt-header>
    <div class="wrapper">
      <div class="content">
        <template v-if="isOnline">
          <p class="wait-time">
            <span>{{ waitSecond }}</span> S
          </p>
          <label class="pay-result-title">请耐心等待支付结果......</label>
        </template>
        <template v-if="!isOnline && (status == order_pay_succeed || status == order_pay_succeed_mlm)">
          <img class="pay-result-icon" src="../../assets/image/hh-icon/b10-pay/pay-success@3x.png" />
          <label class="pay-result-title">支付成功</label>
        </template>
        <template v-if="!isOnline && status == order_pay_failed">
          <img class="pay-result-icon" src="../../assets/image/hh-icon/b10-pay/pay-fail@3x.png" />
          <label class="pay-result-title">支付失败或请求超时</label>
          <p class="pay-result-desc">{{ timeTxt }}</p>
        </template>
        <template v-if="!isOnline && status == order_pay_succeed_friend">
          <img class="pay-result-icon" src="../../assets/image/hh-icon/b10-pay/pay-success-partial@3x.png" />
          <label class="pay-result-title">{{ message ? message : '现金部分 支付成功' }}</label>
        </template>
      </div>
      <div class="friend-pay-wrapper" v-if="!isOnline && status == order_pay_succeed_friend">
        <div class="line"></div>
        <div class="f-p-w-content">
          <div class="f-p-w-title">需要好友代付积分</div>
          <div class="f-p-w-price">
            <img src="../../assets/image/hh-icon/b0-home/money-icon.png" alt="" />
            <span>{{ utils.formatFloat(sharePayInfo.need_surplus) }}</span>
          </div>
        </div>
      </div>
      <div class="btns">
        <template v-if="!isOnline && status == order_pay_succeed">
          <gk-button class="button" type="primary-secondary-white" v-on:click="goPaid">查看订单</gk-button>
          <gk-button class="button" type="primary-secondary-white" v-on:click="goShopping">继续购物</gk-button>
        </template>
        <template v-if="!isOnline && status == order_pay_failed">
          <!-- <gk-button class="button" type="primary-secondary" v-on:click="payAgain">重新支付</gk-button> -->
          <gk-button class="button" type="primary-secondary-white" v-on:click="checkOrder">查看订单</gk-button>
        </template>
        <template v-if="!isOnline && status == order_pay_succeed_friend">
          <gk-button class="button" type="primary-secondary" v-on:click="sharePay">分享好友代付</gk-button>
        </template>
        <template v-if="!isOnline && status == order_pay_succeed_mlm">
          <gk-button class="button" type="primary-secondary" v-on:click="downLoadApp">{{ mlmBottomBtn }}</gk-button>
        </template>
      </div>

      <div class="ad-content" v-if="showAd">
        <ad-banner
          :img="require('../../assets/image/hh-icon/banner-order-success-first@2x.png')"
          url="https://m.huanhuanyiwu.com/operation/index.php?url=https%3A%2F%2Fwww.itouzi.com%2Fe%2Fdownload_wap&from=groupmessage&isappinstalled=0"
        ></ad-banner>
      </div>

      <template v-if="!isOnline && status == order_pay_succeed_friend">
        <popup-share-friend-pay
          ref="shareFriendPayPop"
          v-model="friendPayFlag"
          :options="share_option"
          :sharePayInfo="sharePayInfo"
        ></popup-share-friend-pay>
      </template>
      <mt-popup v-model="popWeChatErCode">
        <div class="popWeChatErCode">
          <img src="../../assets/image/hh-icon/b10-pay/qrcode_for_hhyw_258.jpg" />
          <p>请长按图片识别二维码</p>
        </div>
      </mt-popup>
    </div>
    <!-- 返现弹窗 -->
    <return-cash-popup v-if="red_cash_back" :red_cash_back="red_cash_back"></return-cash-popup>
  </div>
</template>

<script>
import { HeaderItem, Button, PopupShareFriendPay } from '../../components/common'
import { mapState, mapMutations, mapActions } from 'vuex'
import { Header, MessageBox } from 'mint-ui'
import { getPayResult } from '../../api/payment.js'
import { ENUM } from '../../const/enum'
import { ORDEREFFRCTTIME } from '../order/static'
import wechat from '../../config/wechat'
// AD
import AdBanner from '../../components/common/AdBanner'
import ReturnCashPopup from '../../components/common/ReturnCashPopup'

export default {
  data() {
    return {
      popWeChatErCode: false,
      friendPayFlag: false,
      orderSn: undefined, // 请求支付结果用
      order_id: undefined, // 请求订单详情用
      waitSecond: 0,
      countDownTimer: null,
      getResultTimer: null,
      status: ENUM.ORDER_PAY_STATUS.WAITTING,
      isOnline: this.$route.query.isOnline, // 是否需要通过查询Api获取订单支付结果
      count: 0,
      timeTxt: '',
      isAppWxAvailabel: false, // App端微信登录功能是否OK

      // 最多请求几次结果，都未返回支付成功则展示支付失败
      max_request_acount: 10,

      sharePayInfo: {
        sn: '',
        need_money: 0,
        need_surplus: 0,
        thumb: ''
      },
      share_option: ['WechatSession'],

      order_pay_succeed: ENUM.ORDER_PAY_STATUS.SUCCEED, // 支付成功（无好友代付）
      order_pay_failed: ENUM.ORDER_PAY_STATUS.FAILED, // 支付失败
      order_pay_succeed_friend: ENUM.ORDER_PAY_STATUS.SUCCEED_FRIEND, // 支付成功（好友代付）
      order_status_paid: ENUM.ORDER_STATUS.PAID,
      order_status_created: ENUM.ORDER_STATUS.CREATED,
      order_pay_succeed_mlm: ENUM.ORDER_PAY_STATUS.SUCCEED_MLM, // 支付成功（分销订单购买成功）

      showAd: false, // 新手首单 广告位

      red_cash_back: '' // 红包返现
    }
  },
  components: {
    AdBanner,
    ReturnCashPopup
  },
  beforeRouteLeave(to, from, next) {
    const toArr = ['order', 'payment', 'orderDetail', 'home', 'HuankeAccount']
    if (toArr.indexOf(to.name) > -1) {
      next()
      return
    }
    if (this.status == this.order_pay_succeed || this.status == this.order_pay_succeed_mlm) {
      this.changeStatus(this.order_status_paid)
      next({ name: 'order', params: { order: this.order_status_paid } })
    } else {
      this.changeStatus(this.order_status_created)
      next({ name: 'order', params: { order: this.order_status_created } })
    }
  },
  beforeRouteUpdate(to, from, next) {
    if (from.name === to.name && from.query.repeat === 1) {
      // 点击 浏览器返回
      this.phoneBack()
    } else {
      next()
    }
  },
  created() {
    this.status = this.payStatus
    this.orderSn = this.$route.query.order || this.order_sn
    this.order_id = this.orderId
    if (this.isOnline) {
      this.countDownTimer = setInterval(() => {
        ++this.waitSecond
      }, 1000)
      this.getResult()
      // setTimeout(this.getResult, 3000)
    } else if (this.status == this.order_pay_failed) {
      this.getRestTime(this.createdAt)
    }
    if (this.sharePayInfoState.sn) {
      this.sharePayInfo = { ...this.sharePayInfoState }
      this.sharePayInfo.thumb = this.sharePayInfoState.thumb
    }
  },
  mounted() {
    this.$router.push({
      name: this.$router.history.current.name,
      query: { ...this.$route.query, repeat: 1 }
    })
  },
  computed: {
    ...mapState({
      payStatus: state => state.paymentResult.status,
      order_sn: state => state.paymentResult.order,
      orderId: state => state.paymentResult.order_id,
      message: state => state.paymentResult.message,
      sharePayInfoState: state => state.paymentResult.share_pay,
      createdAt: state => state.paymentResult.created_at
    }),
    mlmBottomBtn() {
      let txt = '关注微信公众号'
      if (this.isAppWxAvailabel || !this.wxApi.isweixin()) {
        // 原生商城App 微信登录已完全ready 或者 当前非微信环境
        txt = `下载${this.utils.storeName}App`
      }
      return txt
    }
  },
  methods: {
    ...mapMutations({
      initPaymentResultState: 'initPaymentResultState',
      changeStatus: 'changeStatus'
    }),
    goBack() {
      if (this.status == this.order_pay_succeed || this.status == this.order_pay_succeed_mlm) {
        this.changeStatus(this.order_status_paid)
        this.$router.push({ name: 'order', params: { order: this.order_status_paid } })
      } else {
        this.changeStatus(this.order_status_created)
        this.$router.push({ name: 'order', params: { order: this.order_status_created } })
      }
    },
    goPaid() {
      this.changeStatus(this.order_status_paid)
      this.$router.push({ name: 'order', params: { order: this.order_status_paid } })
    },
    // payAgain() {
    //   this.$router.push({ name: 'payment', query: {order: this.orderSn, } })
    // },
    // 分享好友代付
    sharePay() {
      this.$refs.shareFriendPayPop.open()
    },
    checkOrder() {
      this.$router.push({ name: 'order', params: { order: this.order_status_created } })
    },
    goShopping() {
      this.$router.push('/home')
    },
    getResult() {
      if (!this.orderSn || this.count++ == this.max_request_acount) {
        this.isOnline = false
        this.status = this.order_pay_failed
        clearInterval(this.countDownTimer)
        return
      }
      getPayResult(this.orderSn)
        .then(
          res => {
            this.isOnline = false
            this.order_id = res.order_id
            this.red_cash_back = res.red_cash_back
            if (res.share_pay && res.share_pay.sn) {
              // 分享好友代付
              this.sharePayInfo = { ...res.share_pay }
              this.status = this.order_pay_succeed_friend
            } else if (1 == res.isMLM) {
              // 分销商品购买支付成功
              this.status = this.order_pay_succeed_mlm
            } else {
              this.status = this.order_pay_succeed
            }
            clearInterval(this.countDownTimer)
            // 首单AD
            this.showAd = Boolean(res.isFirstOrder)
            clearInterval(this.countDownTimer)
          },
          err => {
            this.status = this.order_pay_succeed
            // 首单AD
            this.showAd = Boolean(res.isFirstOrder)
            clearInterval(this.countDownTimer)
          }
        )
        .catch(err => {
          this.getResultTimer = setTimeout(this.getResult, 3000)
        })
        .then(() => {})
    },
    getRestTime(time) {
      var RestTime = ORDEREFFRCTTIME - (Math.floor(new Date().getTime() / 1000) - time)
      if (RestTime > 0 && RestTime < ORDEREFFRCTTIME) {
        this.timeTxt = this.exportTime(RestTime)
        var timer = setInterval(() => {
          --RestTime
          this.timeTxt = this.exportTime(RestTime)
        }, 1000)
      } else {
        if (timer) {
          clearInterval(timer)
        }
        this.timeTxt = '该订单已失效'
      }
    },
    exportTime(orderTime) {
      let minite = Math.floor(orderTime / 60)
      let sec = orderTime % 60
      return '请在' + minite + '分' + sec + '秒内完成现金支付,否则系统会自动取消订单，已支付的积分将自动退回您的账户'
    },
    phoneBack() {
      this.changeStatus(this.order_status_paid)
      this.$router.push({ name: 'order', params: { order: this.order_status_paid } })
    },
    downLoadApp() {
      if (this.isAppWxAvailabel || !this.wxApi.isweixin()) {
        // 原生商城App 微信登录已完全ready 或者 当前非微信环境
        window.location.href = this.hhAppUrl
      } else {
        this.popWeChatErCode = true
        // 目前微信的这个页面 的 ‘关注’按钮已被微信屏蔽，故 弹出二维码 引导用户长按识别
        // window.location.href = wechat.followUrl
      }
    }
  },
  beforeDestroy() {
    this.initPaymentResultState()
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: #fff;
}
.header {
  @include header;
  border-bottom: 1px solid $lineColor;
  .complete-header-item {
    color: #552e20;
    font-size: 16px;
  }
}
.wait-time {
  font-size: 16px;
  span {
    font-size: 30px;
  }
}
.wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.content {
  display: flex;
  flex-direction: column;
  text-align: justify;
  justify-content: center;
  align-items: center;
  padding: 60px 25px 0;
  .pay-result-icon {
    width: 62px;
    height: 62px;
  }
  .pay-result-title {
    color: #404040;
    font-size: 18px;
    margin-top: 20px;
    line-height: 1.5;
  }
  .pay-result-desc {
    margin: 0 18px;
    line-height: 1.5;
    font-size: 14px;
    margin-top: 15px;
    color: #b5b6b6;
  }
}
.friend-pay-wrapper {
  margin-top: 60px;
  .line {
    @include thin-border(#f4f4f4, 0, 0);
  }
  .f-p-w-content {
    padding: 0 30px;
    margin-top: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .f-p-w-title {
    font-size: 16px;
    color: #404040;
    line-height: 1.5;
  }
  .f-p-w-price {
    display: flex;
    align-items: baseline;
    justify-content: flex-end;
    img {
      width: 13px;
      margin-right: 3px;
    }
    span {
      font-size: 18px;
      color: #404040;
      font-weight: bold;
    }
  }
}
.btns {
  margin-top: 50px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 25px;
  .button {
    width: 100%;
    font-size: 18px;
    @include button($margin: 0, $radius: 2px, $spacing: 2px);
    & + .button {
      margin-top: 25px;
    }
  }
}
.ad-content {
  padding: 40px 5px 12px;
}
.popWeChatErCode p {
  font-size: 14px;
  text-align: center;
  padding-bottom: 10px;
}
</style>
