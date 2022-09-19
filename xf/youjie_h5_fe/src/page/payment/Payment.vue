<template>
  <div class="container">
    <mt-header class="header" title="确认付款">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="leftClick"></header-item>
    </mt-header>
    <div class="top-wrapper">订单提交成功</div>

    <!-- 支付信息 -->
    <div class="pay-msg-wrapper">
      <!-- 积分支付信息 -->
      <div class="pay-wrapper surplus-container" v-if="!isMlmPay || isLoading">
        <!-- 未支付积分or积分 -->
        <template v-if="!Number(hhpay.surplus_paid)">
          <!-- A类用户 原有支付逻辑不变 隐藏 兑换入口(8.7)+好友代付switch按钮 -->
          <template v-if="isHbUser">
            <div :class="{ 'is-friend-pay': isFriendPay || is_only_money }">
              <div class="pay-container pay-wrapper-title">
                <label class="pay-name">积分最高可抵扣</label>
                <div class="wrapper-price">
                  <span class="pay-price"> {{ utils.formatMoney(maxHuan) }} </span>
                </div>
              </div>
              <label
                class="pay-container pay-wrapper-title"
                for="coin"
                @click="setHuanBeanType(ENUM.HUANTYPE.COIN)"
                v-if="showCoinPay"
              >
                <label class="pay-name"> 积分 </label>
                <div class="pay-check">
                  <span>剩余{{ hhpay.user_money }}币</span>
                  <label class="check-radio" for="coin">
                    <label :class="{ active: isHuanBean === ENUM.HUANTYPE.COIN }" for="coin"></label>
                  </label>
                </div>
                <!--  <div class="wrapper-price">
                好友代付的时候展示商品应付积分数
                <template v-if="isFriendPay">
                  <img class="surplus-icon" src="../../assets/image/hh-icon/b0-home/money-icon.png" />
                  <span class="pay-price">{{ utils.formatMoney(hhpay.friend_high_surplus) }}</span>
                </template>
                混合支付
                <template v-else-if="hhpay.need_money > 0">
                  <span class="surplus-paid" v-if="hhpay.surplus_paid > 0">已支付</span>
                  <img class="surplus-icon" src="../../assets/image/hh-icon/b0-home/money-icon.png" />
                  <span class="pay-price">{{
                    utils.formatMoney(hhpay.surplus_paid == 0 ? hhpay.need_surplus : hhpay.surplus_paid)
                  }}</span>
                </template>

                <template v-else>
                  <img class="surplus-icon" src="../../assets/image/hh-icon/b0-home/money-icon.png" />
                  <span class="pay-price">{{ utils.formatMoney(getOrderAmount) }}</span>
                </template>
              </div> -->
              </label>
              <template v-if="showExchangeCoin">
                <div class="pay-container pay-wrapper-exchange" @click="goExchange(false)">
                  <div></div>
                  <label class="go-exchange">
                    兑换更多积分
                    <img src="../../assets/image/hh-icon/b10-pay/icon-right-triangle.png" alt />
                  </label>
                </div>
              </template>
              <!-- 积分 -->
              <label
                class="pay-container pay-wrapper-title"
                for="bean"
                @click="setHuanBeanType(ENUM.HUANTYPE.BEAN)"
                v-if="showBeanPay"
              >
                <label class="pay-name"> 积分 </label>
                <div class="pay-check">
                  <template v-if="isHuanBean != ENUM.HUANTYPE.BEAN">
                    <span v-if="hasBean">有积分可用</span>
                  </template>
                  <template v-else>
                    <label class="go-exchange" @click.stop="openPopup" v-if="!Number(hhpay.surplus_paid)">
                      更换积分支付方式
                      <img src="../../assets/image/hh-icon/b10-pay/icon-right-triangle.png" alt />
                    </label>
                  </template>
                  <label class="check-radio" for="bean">
                    <label :class="{ active: isHuanBean === ENUM.HUANTYPE.BEAN }" for="bean"></label>
                  </label>
                </div>
              </label>
              <!-- <template v-if="hhpay.surplus_paid > 0">
              <div class="pay-container pay-wrapper-rules">
                <div v-show="isShowRules" class="rules-content">
                  <label class="rules-title">规则说明</label>
                  <p>您的积分部分已支付。如取消订单，已支付积分将退回您的账户余额中</p>
                </div>
                <label class="show-rules" @click="isShowRules = !isShowRules"> 查看规则说明 </label>
              </div>
            </template> -->
            </div>
            <template v-if="is_only_money">
              <div class="only-cash-pay-wrapper">本单仅限现金结算</div>
            </template>
            <!-- 积分极速版 暂时取消积分好友支付 -->
            <!-- <template v-if="hhpay.support_friend_pay && hhpay.surplus_paid == 0">
            <div class="share-pay-wrapper">
              <span class="share-pay-wrapper-title">好友代付积分</span>
              <mt-switch class="payment-switch" v-model="isSharePay"></mt-switch>
            </div>
          </template> -->
          </template>
          <!-- 非A类用户 -->
          <template v-else>
            <div class="pay-container pay-wrapper-title">
              <label class="pay-name">积分最高可抵扣</label>
              <div class="wrapper-price">
                <span class="pay-price"> {{ utils.formatMoney(maxHuan) }} </span>
              </div>
            </div>
            <label
              class="pay-container pay-wrapper-title"
              for="bean"
              @click="setHuanBeanType(ENUM.HUANTYPE.BEAN)"
              v-if="showBeanPay"
            >
              <label class="pay-name"> 积分 </label>
              <div class="pay-check">
                <template v-if="isHuanBean != ENUM.HUANTYPE.BEAN">
                  <span v-if="hasBean">有积分可用</span>
                </template>
                <template v-else>
                  <label class="go-exchange" @click.stop="openPopup" v-if="!Number(hhpay.surplus_paid)">
                    更换积分支付方式
                    <img src="../../assets/image/hh-icon/b10-pay/icon-right-triangle.png" alt />
                  </label>
                </template>
                <label class="check-radio" for="bean">
                  <label :class="{ active: isHuanBean === ENUM.HUANTYPE.BEAN }" for="bean"></label>
                </label>
              </div>
            </label>
          </template>
        </template>
        <!-- 已支付积分or积分 -->
        <template v-else>
          <!-- <div class="is-friend-pay"></div> -->
          <div>
            <div class="pay-container pay-wrapper-title">
              <label class="pay-name">积分已抵扣</label>
              <div class="wrapper-price">
                <span class="pay-price"> {{ utils.formatMoney(hhpay.surplus_paid) }} </span>
              </div>
            </div>
            <label class="pay-container pay-wrapper-title" v-if="hhpay.token_type === ENUM.HUANTYPE.COIN">
              <label class="pay-name"> 积分 </label>
              <div class="pay-check">
                <span>已支付{{ utils.formatMoney(hhpay.surplus_paid) }}币</span>
                <label class="check-radio" for="coin">
                  <label class="active"></label>
                </label>
              </div>
            </label>
            <label class="pay-container pay-wrapper-title" v-if="hhpay.token_type === ENUM.HUANTYPE.BEAN">
              <label class="pay-name"> 积分 </label>
              <div class="pay-check">
                <span>已支付{{ utils.formatMoney(hhpay.surplus_paid) }}积分</span>
                <label class="check-radio" for="bean">
                  <label class="active"></label>
                </label>
              </div>
            </label>
            <template v-if="showExchangeCoin">
              <div class="pay-container pay-wrapper-exchange" @click="goExchange(false)">
                <div></div>
                <label class="go-exchange">
                  兑换更多积分
                  <img src="../../assets/image/hh-icon/b10-pay/icon-right-triangle.png" alt />
                </label>
              </div>
            </template>
          </div>
        </template>
      </div>

      <!-- 现金支付信息 -->
      <div class="pay-wrapper cash-container">
        <div class="pay-container pay-wrapper-title">
          <label class="pay-name">现金支付</label>
          <div class="wrapper-price">
            <span class="price-unit">￥</span>
            <span class="pay-price">
              {{ utils.formatMoney(order_need_money) }}
              <!-- <template v-if="isFriendPay">{{ utils.formatMoney(order.total - hhpay.friend_high_surplus) }}</template>
              <template v-else>{{ utils.formatMoney(hhpay.need_money) }}</template> -->
            </span>
          </div>
        </div>
        <template v-if="isShowCashPay && !isFriendPayAndSurplusTotal">
          <div class="item-wrapper" v-for="(item, index) in payList" :key="index">
            <label
              class="item-choose-wrapper"
              v-if="item.code == ENUM.CASH_PAY_TYPE.ALIPAY_H5 || item.code == ENUM.CASH_PAY_TYPE.ALIPAY_APP"
              for="alipayInput"
            >
              <label for="index" class="item-title item-left-wrapper">
                <img class="icon" src="../../assets/image/hh-icon/b10-pay/icon-alipay.png" />
                {{ item.name }}
              </label>
              <input
                :checked="item.code == code"
                type="radio"
                id="alipayInput"
                class="pay-inpput"
                @change="payType(item.code, index)"
                name="payType"
              />
              <label class="sel-radius" placeholder="v" for="alipayInput"></label>
            </label>
            <label
              class="item-choose-wrapper"
              v-if="
                item.code == ENUM.CASH_PAY_TYPE.WXPAY_H5 ||
                  item.code == ENUM.CASH_PAY_TYPE.WXPAY_APP ||
                  item.code == ENUM.CASH_PAY_TYPE.WXPAY_JS
              "
              for="wxpayInput"
            >
              <label for="index" class="item-title item-left-wrapper">
                <img class="icon" src="../../assets/image/hh-icon/b10-pay/icon-wxpay.png" />
                {{ item.name }}
              </label>
              <input
                :checked="item.code == code"
                type="radio"
                id="wxpayInput"
                class="pay-inpput"
                @change="payType(item.code, index)"
                name="payType"
              />
              <label class="sel-radius" placeholder="v" for="wxpayInput"></label>
            </label>
          </div>
        </template>
      </div>

      <!-- 合计 -->
      <div class="pay-wrapper total-container">
        <div class="pay-container pay-wrapper-total">
          <!-- <template v-if="hhpay.need_surplus > 0 && hhpay.need_money > 0">
            积分支付+现金支付：
          </template>
          <template v-else-if="hhpay.need_money == 0">
            积分支付：
          </template>
          <template v-else-if="hhpay.need_surplus == 0">
            现金支付：
          </template>-->
          <div class="price-wrapper">
            <span class="price-w-title">实际支付：</span>
            <template v-if="order_need_surplus">
              <img src="../../assets/image/hh-icon/b0-home/money-icon.png" v-if="isHuanBean === ENUM.HUANTYPE.BEAN" />
              <img src="../../assets/image/hh-icon/b0-home/money-icon-hb.png" v-else />
              <span class="price-w-amount">
                {{ utils.formatMoney(order_need_surplus) }}
              </span>
            </template>
            <span class="price-w-plus" v-if="order_need_money && order_need_surplus">+</span>
            <template v-if="order_need_money">
              <span v-if="order_need_money">
                <span class="price-w-unit">￥</span
                ><span class="price-w-amount">{{ utils.formatMoney(order_need_money) }}</span>
              </span>
            </template>

            <!-- <span v-if="orderNeedSurplus">
              <img src="../../assets/image/hh-icon/b0-home/money-icon.png" alt />
              <span class="price-w-amount">
                {{ utils.formatMoney(orderNeedSurplus) }}
              </span>
            </span>
            <span class="price-w-plus" v-if="orderNeedSurplus > 0 && orderNeedMoney > 0">+</span>
            <span v-if="orderNeedMoney">
              <span class="price-w-unit">￥</span
              ><span class="price-w-amount">{{ utils.formatMoney(orderNeedMoney) }}</span>
            </span>
            <template v-if="orderNeedSurplus == 0 && orderNeedMoney == 0">
              <span> <span class="price-w-unit">￥</span><span class="price-w-amount">0</span> </span>
            </template> -->
          </div>
        </div>
      </div>
    </div>
    <div class="action-wrapper" v-if="canPay">
      <gk-button class="button" type="primary-secondary" v-on:click="pay">立即付款</gk-button>
    </div>

    <count-down-popup
      @showFlag="getShowFlag"
      @leavePage="leavePay"
      :canceledTime="canceledTime"
      v-if="showflag"
    ></count-down-popup>

    <message-popup
      @checkOrder="checkOrder"
      @payAgain="payAgain"
      v-if="isShowPayPop"
      :isNoSurplus="isSharePay || isMlmPay || hhpay.need_surplus"
    ></message-popup>

    <mt-popup v-model="isPopupShow" position="bottom" class="popup">
      <template>
        <div class="popup-head">
          <h2>使用积分</h2>
          <div @click="close"></div>
        </div>
        <div class="popup-body" ref="popup_body">
          <div class="body-item b-line" @click="setBeanType(ENUM.BEANTYPE.MY)">
            <div class="left">
              <p class="title">
                <span>我的积分</span><label>(余额:{{ utils.formatMoney(hhpay.token) }})</label>
              </p>
              <p class="line">
                本单最多可用{{ utils.formatMoney(hhpay.need_token) }}个积分，抵扣{{
                  utils.formatMoney(hhpay.need_token)
                }}元现金
              </p>
            </div>
            <div class="right" :class="{ active: beanType === ENUM.BEANTYPE.MY }"></div>
          </div>
          <template v-if="companyList.length">
            <div class="body-item" @click="setBeanType(ENUM.BEANTYPE.COM)">
              <div class="left">
                <p class="title">
                  <span>代付积分</span><label>(可代付:{{ utils.formatMoney(selectCompany.user_partner_token) }})</label>
                </p>
                <p class="line">
                  本单最多可用{{ utils.formatMoney(selectCompany.need_surplus) }}个积分，抵扣{{
                    utils.formatMoney(selectCompany.need_surplus)
                  }}元现金
                </p>
              </div>
              <div class="right" :class="{ active: beanType === ENUM.BEANTYPE.COM }"></div>
            </div>
            <div class="select-box">
              <div class="select-head select-wrapper" @click="openCompanyList">
                <p>{{ selectCompany.partner_name }}</p>
                <div></div>
              </div>
              <div class="select-body">
                <template v-for="item in companyList" v-if="isShowCompany">
                  <div class="select-body-item select-wrapper" @click="selectCompanyItem(item)">
                    <p>{{ item.partner_name }}</p>
                    <div :class="{ active: selectCompanyId === item.partner_id }"></div>
                  </div>
                </template>
              </div>
            </div>
          </template>
        </div>
        <div class="popup-bottom">
          <button @click="confirm" :class="{ undeep: [ENUM.BEANTYPE.MY, ENUM.BEANTYPE.COM].indexOf(beanType) === -1 }">
            <span>确定</span>
          </button>
        </div>
      </template>
    </mt-popup>
  </div>
</template>

<script>
import $cookie from 'js-cookie'
import { HeaderItem, Button } from '../../components/common'
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { Header, MessageBox, Indicator, Toast, Radio, Popup } from 'mint-ui'
import { orderGet } from '../../api/order.js'
import { bondGet } from '../../api/bond'
import { paymentPay, paymentTypesList } from '../../api/payment'
import CountDownPopup from '../../components/common/CountDownPopup'
import MessagePopup from '../../components/common/MessagePopup'
import { ENUM } from '../../const/enum'
import { authWeb, getWxConfig } from '../../api/auth-web'
// import XXTEA from '../../assets/js/xxtea'
import wechat from '../../config/wechat'
import wx from 'weixin-js-sdk'
import Vue from 'vue'
export default {
  data() {
    return {
      goods_id: '', // 获取商品的id（兑换债券时候使用，is_borrow==1时赋值）
      payList: [],
      alipay_html: '',
      config: this.config,
      code: 'balance', // 支付方式： 账户积分余额 or ...
      isHuanBean: 0, //积分or积分 1积分 2积分
      beanType: 0, //支付积分种类 0我的积分 1代付积分
      type: this.type,
      miss: 0,
      showflag: false,
      isShowRules: false, // 是否展示规则
      canPay: false, // 是否可以付款
      isShowPayPop: false, // 提交订单时展示遮罩（用于返回时刷新页面）
      isPending: false, // 订单提交请求是否在提交中
      version0: false, //app版本是否大于0.2.0
      isSharePay: false, // 是否好友代付
      share_type: ENUM.SHARE_TYPE.ONLY_HB, // 好友代付类型（目前只支持纯积分代付）
      ENUM,
      isMlmPay: false, // 是否是 商品分销买家 付款
      isLoading: true, // 页面是否正在加载初始数据
      isInstalment: this.$route.query.isInstalment, // 是否是分期付款
      isPopupShow: false,
      selectCompany: {},
      selectCompanyId: 0,
      isShowCompany: false,
      orderCreateTime: 0, // 订单创建时间
      canceledTime: 0, // 订单失效时间
      orderList: [], // 剩余未支付订单

      friendPayFlag: false,
      share_pay_info: {
        sn: '',
        need_money: 0,
        need_surplus: 0
      }
    }
  },
  watch: {
    isSharePay(value) {
      this.saveFriendPayFlag(Number(value))
    }
  },
  components: {
    CountDownPopup,
    MessagePopup
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      openId: state => state.auth.openId,
      order: state => state.payment.order,
      hhpay: state => state.payment.hhpay,
      isFriendPay: state => state.payment.isFriendPay,
      platform: state => state.auth.platform,
      authStatus: state => state.itouzi.authStatus,
      balance: state => state.balance.currentBalance,
      systemTime: state => state.app.systemTime
    }),
    ...mapGetters({
      // orderNeedSurplus: 'orderNeedSurplus',
      // orderNeedMoney: 'orderNeedMoney',
      companyList: 'companyList',
      isHbUser: 'isHbUser'
    }),
    getOrderAmount: function() {
      let surplus = Number(this.hhpay.need_surplus)
      let money = Number(this.hhpay.need_money)
      if (money != 0) {
        return surplus + money
      } else {
        return this.$route.query.total || ''
      }
    },
    is_only_money() {
      return (
        this.hhpay.need_money > 0 &&
        this.hhpay.need_surplus == 0 &&
        this.hhpay.surplus_paid == 0 &&
        this.hhpay.user_debt == 0 &&
        this.hhpay.support_friend_pay != 1
      )
    },
    isFriendPayAndSurplusTotal() {
      return !!(this.isFriendPay && this.hhpay.friend_high_surplus == this.order.total)
    },
    isExchangeClose() {
      //取消8.7 12点关闭入口
      return false
      // let flag = false
      // //8.7 中午12点 关闭兑换积分入口
      // if (this.systemTime >= ENUM.TIMESTAMP2019090712) {
      //   flag = true
      // }
    },
    order_need_money() {
      let money = 0
      if (!this.isHuanBean) {
        // 纯现金支付
        money = Number(this.hhpay.need_surplus) + Number(this.hhpay.need_money)
      } else if (this.isHuanBean === ENUM.HUANTYPE.COIN) {
        // 积分支付
        money = this.hhpay.need_money
      } else {
        // 积分支付
        if (this.beanType === ENUM.BEANTYPE.MY) {
          // 我的积分
          money = this.hhpay.use_token_need_money
        } else {
          // 代付积分
          if (this.selectCompany.need_money) {
            money = this.selectCompany.need_money
          } else {
            // 无代付公司时勾选 默认为纯现金支付
            money = Number(this.hhpay.need_surplus) + Number(this.hhpay.need_money)
          }
        }
      }
      return Number(money)
    },
    order_need_surplus() {
      let surplus = 0
      if (this.isHuanBean === ENUM.HUANTYPE.COIN) {
        // 积分支付
        surplus = this.hhpay.need_surplus
      } else if (this.isHuanBean === ENUM.HUANTYPE.BEAN) {
        // 积分支付
        if (this.beanType === ENUM.BEANTYPE.MY) {
          surplus = this.hhpay.need_token
        } else {
          surplus = this.selectCompany.need_surplus
        }
      }
      return Number(surplus)
    },
    // 是否还有积分
    hasBean() {
      let has = false
      if (this.hhpay.token > 0) has = true
      if (this.companyList.length) has = true
      return has
    },
    showCoinPay() {
      return this.hhpay.token_type != ENUM.HUANTYPE.BEAN
    },
    showBeanPay() {
      return this.hhpay.token_type != ENUM.HUANTYPE.COIN
    },
    showExchangeCoin() {
      return this.showCoinPay && (this.hhpay.user_debt > 0 && this.hhpay.need_money != 0 && !this.isExchangeClose)
    },
    // 积分最高可抵扣
    maxHuan() {
      let company = []
      if (this.companyList.length) {
        this.companyList.forEach(item => {
          company.push(item.need_surplus)
        })
      }
      return Math.max(this.hhpay.need_surplus, this.hhpay.need_token, ...company)
    },
    isShowCashPay() {
      if (this.isHuanBean === ENUM.HUANTYPE.COIN && this.hhpay.need_money > 0) {
        // 积分支付
        return true
      } else if (this.isHuanBean === ENUM.HUANTYPE.BEAN && this.hhpay.use_token_need_money > 0) {
        // 积分支付
        return true
      } else {
        return false
      }
    }
  },
  created: function() {
    if (this.wxApi.isweixin()) {
      // 是否微信授权回调
      if (!this.openId) {
        const code = $cookie.get('wx_code')
        if (code) {
          $cookie.remove('wx_code')
          this.getOpenid(code)
        } else {
          const appId = wechat.appId
          const redirect_uri = encodeURIComponent(location.href)
          location.href = `https://open.weixin.qq.com/connect/oauth2/authorize?appid=${appId}&redirect_uri=${redirect_uri}&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect`
        }
      }
    }

    // itz剩余可兑换债权
    bondGet().then(res => {
      this.saveCurrentBondState(res)
    })

    // 判断版本号是否大于0.1.0
    this.judgeVersion0()

    let id = this.$route.query.order
    let parent_id = this.$route.query.parent_order
    const p1 = this.getOrderMsg(id, parent_id)
    const p2 = this.getPaymentTypesList()

    Indicator.open()
    Promise.all([p1, p2]).then(() => {
      Indicator.close()
      this.isLoading = false

      // 初始化支付方式
      if (this.hhpay.need_money > 0) {
        this.code = this.payList[0].code
      } else {
        this.code = 'balance'
      }
      // 代付积分 默认第一个代付公司
      this.selectCompany = this.companyList.length ? this.companyList[0] : {}
      // 添加默认积分支付 默认积分
      if (!this.isHbUser) {
        // 非A类用户 默认积分
        this.isHuanBean = ENUM.HUANTYPE.BEAN
      } else {
        // A类用户
        if (ENUM.HUANTYPE.BEAN === this.hhpay.token_type) {
          this.isHuanBean = ENUM.HUANTYPE.BEAN
        } else if (ENUM.HUANTYPE.COIN === this.hhpay.token_type) {
          this.isHuanBean = ENUM.HUANTYPE.COIN
        } else {
          // token_type为0
          this.isHuanBean = ENUM.HUANTYPE.COIN
        }
      }
    })
  },
  mounted() {
    window.onPayResult = function(type, result) {
      if (
        // 微信支付
        (type == 1 && result.code != 0) ||
        // 支付宝
        (type == 2 && result.resultStatus != 9000)
      ) {
        // 用户取消或支付失败
        return
      }
      window.location.href = window.redirect_url
      window.redirect_url = ''
    }
  },
  methods: {
    ...mapMutations({
      saveFriendPayFlag: 'saveFriendPayFlag',
      savePaymentState: 'savePaymentState',
      initPaymentSate: 'initPaymentSate',
      changeStatus: 'changeStatus',
      saveOrder: 'saveOrder',
      saveOpenid: 'saveOpenid',
      saveHhpay: 'saveHhpay',
      itzAuthGuide: 'itzAuthGuide',
      saveCurrentBondState: 'saveCurrentBondState'
    }),
    ...mapActions({
      fetchItzBondAuthCheck: 'fetchItzBondAuthCheck'
    }),
    // 判断版本号是否大于0.1.0
    judgeVersion0() {
      let version = '0.1.0'
      if (this.isHHApp && this.hhApp.getAppVersion()) {
        version = this.hhApp.getAppVersion()
      }

      if (Number(version.replace(/\./g, '')) >= Number('020')) {
        this.version0 = true
      }
    },
    getPaymentTypesList() {
      // 判断支付环境 hhppp /wechat /h5 获取支付方式
      let payenv = 'wap'
      if (this.isHHApp && this.version0) {
        payenv = 'app'
      }
      if (this.wxApi.isweixin()) {
        payenv = 'wechat'
      }
      return new Promise((resolve, reject) => {
        paymentTypesList(payenv).then(
          res => {
            this.payList = [...res]
            resolve()
          },
          error => {
            Toast(error.errorMsg)
            reject()
          }
        )
      })
    },
    getOrderMsg(orderId, parentOrderId) {
      return new Promise((resolve, reject) => {
        this.canPay = false
        orderGet(orderId, this.isInstalment, parentOrderId).then(res => {
          // 只取total、mlm_id、created_at、hhpay、goods
          this.canPay = true
          this.orderCreateTime = res.created_at
          this.canceledTime = res.canceled_at
          this.orderList = res.order
          this.saveHhpay({ ...res.hhpay })
          this.saveOrder(res)
          if (res.goods[0].is_borrow) {
            this.goods_id = res.goods[0].id
          }

          this.isMlmPay = res.mlm_id > 0

          if (this.hhpay.need_surplus === undefined) {
            Toast('订单已失效')
            setTimeout(() => {
              this.$_goBack()
            }, 1000)
          }

          resolve()
        })
      })
    },
    leftClick() {
      if (this.canceledTime) {
        // 订单是否有失效时间；et: 分期订单后续单子 没有 失效时间
        this.showflag = true
      } else {
        this.$_goBack()
      }
    },
    getShowFlag(value) {
      this.showflag = value
    },
    leavePay(value) {
      this.showflag = value
      this.changeStatus(ENUM.ORDER_STATUS.CREATED)
      this.$router.push({ name: 'order', params: { order: ENUM.ORDER_STATUS.CREATED } })
    },

    checkOrder(value) {
      this.isShowPayPop = value
      this.changeStatus(ENUM.ORDER_STATUS.CREATED)
      this.$router.push({ name: 'order', params: { order: ENUM.ORDER_STATUS.CREATED } })
    },
    payAgain(value) {
      this.isShowPayPop = value
      let id = this.$route.query.order
      let parent_id = this.$route.query.parent_order
      this.getOrderMsg(id, parent_id)
    },
    /*
     *获取支付方式
     */
    payType(code, index) {
      if (code) {
        this.code = code
      }
    },
    /*
     * 选择 积分or积分 支付
     */
    setHuanBeanType(code) {
      if (this.hhpay.surplus_paid > 0) return //已支付积分or积分 选择框不能操作
      if (code === this.isHuanBean) {
        // this.isHuanBean = 0
        return //取消 可取消选择积分
      } else {
        if (code === ENUM.HUANTYPE.BEAN) {
          this.openPopup()
        } else {
          this.isHuanBean = code
        }
      }
    },
    /*
     * 选择 积分种类
     */
    setBeanType(code) {
      this.beanType = code
    },
    openCompanyList() {
      this.beanType = ENUM.BEANTYPE.COM
      this.isShowCompany = !this.isShowCompany
      this.$nextTick(() => {
        let body = this.$refs.popup_body
        body.scrollTop = body.scrollHeight
      })
    },
    selectCompanyItem(item) {
      this.selectCompany = item
      this.selectCompanyId = item.partner_id
      this.isShowCompany = !this.isShowCompany
    },
    openPopup() {
      this.isPopupShow = true
    },
    close() {
      this.isPopupShow = false
    },
    confirm() {
      this.close()
      if (this.beanType != -1) {
        this.isHuanBean = ENUM.HUANTYPE.BEAN
      }
    },
    /*
     *支付
     */
    pay() {
      if (!this.canPay) {
        return
      }

      // TODO 防重复提交
      if (this.isPending) {
        return
      }

      let code = this.code
      let need_money = this.hhpay.need_money
      let type = this.type
      // let orderid = this.$route.query.order
      let referer = null
      let channel = null
      let openid = null
      let share_type = this.isFriendPay ? this.share_type : 0
      let token_type = this.hhpay.token_type ? this.hhpay.token_type : this.isHuanBean
      let partner = 0
      if (this.hhpay.surplus_paid > 0) {
        // 已支付积分 且 使用的是积分支付 取hhpay.is_use
        if (this.hhpay.token_type === ENUM.HUANTYPE.BEAN && this.hhpay.is_use) partner = this.hhpay.is_use
      } else {
        // 未支付 已选择代付公司
        if (this.beanType === ENUM.BEANTYPE.COM && this.selectCompany.partner_id)
          partner = this.selectCompany.partner_id
      }

      let data = {}
      data['id'] = this.orderList
      data['code'] = code
      data['share_type'] = share_type
      data['token_type'] = token_type
      data['partner'] = token_type === ENUM.HUANTYPE.BEAN ? partner : 0

      if (need_money > 0 && !code) {
        return Toast('请选择支付方式')
      }

      // 若 未抵扣积分 && 有可抵扣的积分 那么必选选择一种
      if (
        0 == Number(this.hhpay.surplus_paid) &&
        0 == token_type &&
        (Number(this.hhpay.user_money) > 0 || this.hasBean)
      ) {
        return Toast('请选择您的积分抵扣方式')
      }

      /*if (!orderid) {
        return Toast('无该笔订单')
      }*/

      if (need_money > 0 || this.isFriendPayAndSurplusTotal) {
        // 有现金的支付（需要第三方支付）
        this.isPending = true
        if (code == ENUM.CASH_PAY_TYPE.WXPAY_JS && !this.isFriendPayAndSurplusTotal) {
          // 微信内支付
          getWxConfig().then(res => {
            wx.config({
              debug: false, // 开启调试模式
              appId: res.app_id, // 必填，公众号的唯一标识
              timestamp: res.timestamp, // 必填，生成签名的时间戳
              nonceStr: res.nonceStr, // 必填，生成签名的随机串
              signature: res.signature, // 必填，签名，见附录1
              jsApiList: ['chooseWXPay'] // 调用微信的支付接口
            })

            wx.ready(() => {
              data['openid'] = this.openId
              paymentPay(this.isInstalment, data).then(res => {
                this.savePaymentState({ status: ENUM.ORDER_PAY_STATUS.WAITTING, created_at: this.orderCreateTime })
                this.wxPaymentPay(res)
                this.isPending = false
              })
            })
          })
        } else {
          // 其他支付
          paymentPay(this.isInstalment, data)
            .then(
              res => {
                if (this.isFriendPayAndSurplusTotal) {
                  this.sharePay(res)
                } else {
                  this.savePaymentState({ status: ENUM.ORDER_PAY_STATUS.WAITTING, created_at: this.orderCreateTime })
                  if (code != 'balance') {
                    this.isShowPayPop = true
                    if (code == ENUM.CASH_PAY_TYPE.ALIPAY_H5 || code == ENUM.CASH_PAY_TYPE.WXPAY_H5) {
                      if (res.result) {
                        window.location.href = res.result
                      }
                    } else if (code == ENUM.CASH_PAY_TYPE.ALIPAY_APP || code == ENUM.CASH_PAY_TYPE.WXPAY_APP) {
                      // app版本小于0.2.0则调用h5支付
                      if (this.version0) {
                        window.redirect_url = res.redirect_url
                        this.payInApp(code, res.result)
                      } else {
                        window.location.href = res.result
                      }
                    }
                  }
                }
              },
              error => {
                this.savePaymentState({
                  status: ENUM.ORDER_PAY_STATUS.FAILED,
                  message: error.errorMsg,
                  order: this.order.sn
                })
                this.goPayResult()
                Toast(error.errorMsg)
              }
            )
            .finally(() => {
              this.isPending = false
              Indicator.close()
            })
        }
      } else {
        // 纯积分支付（无需第三方支付）
        this.isPending = true
        paymentPay(this.isInstalment, data)
          .then(
            res => {
              this.savePaymentState({ status: ENUM.ORDER_PAY_STATUS.SUCCEED, message: '支付成功' })
              this.goPayResult()
            },
            error => {
              Toast(error.errorMsg)
            }
          )
          .finally(() => {
            this.isPending = false
            Indicator.close()
          })
      }
    },
    payInApp(code, result) {
      // app内支付
      let type = '0'
      if (code == ENUM.CASH_PAY_TYPE.WXPAY_APP) {
        type = '1'
      } else if (code == ENUM.CASH_PAY_TYPE.ALIPAY_APP) {
        type = '2'
      }
      if (type) {
        console.log('即将调用第三方app')
        // debugger
        if (type == '1') {
          // json先压缩后encode处理
          result = JSON.stringify(result)
        }
        result = encodeURIComponent(result)
        this.hhApp.openPay(type, result)
      }
    },

    async goExchange(need) {
      let res = null
      try {
        res = await this.fetchItzBondAuthCheck(true)
      } catch (e) {}
      // 授权认证（爱投资平台用户 && 可兑换债权>0 && 未授权）
      // if (!this.authStatus && 1 == this.platform && res.userBondInfo.amount > 0) {
      //   return this.itzAuthGuide({ isHHApp: this.isHHApp })
      // }

      let isExchangeAndPay = !!need
      let bond = isExchangeAndPay ? need : this.hhpay.user_debt
      const params = { need: bond }
      if (!isExchangeAndPay) {
        params.canPartial = true
      }
      params.order = this.$route.query.order
      if (this.goods_id) {
        params.product = this.goods_id
      }
      this.$router.push({ name: 'bondDebt', params: params })
    },

    goPayResult(isOnline) {
      this.savePaymentState({ order_id: this.order.id })
      let order = this.$route.query.order
      const params = { order: order }
      if (isOnline) {
        params.isOnline = 1
      }
      params.isInstalment = this.isInstalment
      this.$router.replace({ name: 'payResult', query: params })
    },

    goPayFailed(msg) {
      this.savePaymentState({ status: ENUM.ORDER_PAY_STATUS.FAILED, message: msg, order: this.order.sn })
      this.goPayResult()
    },

    sharePay(res) {
      this.share_pay_info = { ...res.share_pay }

      this.savePaymentState({
        status: ENUM.ORDER_PAY_STATUS.SUCCEED_FRIEND,
        order: this.order.sn,
        share_pay: this.share_pay_info,
        message: '待支付积分部分'
      })
      this.goPayResult()
    },

    getOpenid(code) {
      authWeb(ENUM.AUTH_VENDOR.WEIXIN, 'snsapi_base', code).then(res => {
        this.saveOpenid(res.openid)
      })
    },

    wxPaymentPay(response) {
      const _this = this
      wx.chooseWXPay({
        timestamp: response.result.timeStamp, // 支付签名时间戳，注意微信jssdk中的所有使用timestamp字段均为小写。但最新版的支付后台生成签名使用的timeStamp字段名需大写其中的S字符
        nonceStr: response.result.nonceStr, // 支付签名随机串，不长于 32 位
        package: response.result.package, // 统一支付接口返回的prepay_id参数值，提交格式如：prepay_id=***）
        signType: response.result.signType, // 签名方式，默认为'SHA1'，使用新版支付需传入'MD5'
        paySign: response.result.paySign, // 支付签名
        success: res => {
          if (res.errMsg == 'chooseWXPay:ok') {
            window.location.href = `${window.location.origin}${window.location.pathname}#/payResult?isOnline=1&order=${_this.order.sn}&isInstalment=${_this.$route.query.isInstalment}`
          } else {
            window.location.href = `${window.location.origin}${window.location.pathname}#/payResult?isOnline=1&order=${_this.order.sn}&isInstalment=${_this.$route.query.isInstalment}`
          }

          // 支付成功后的回调函数
          return true
        },
        cancel: res => {
          Toast('支付已取消')
        },
        // 支付失败回调函数
        fail: function(res) {
          Toast(res.errMsg || res)
          window.location.href = `${window.location.origin}${window.location.pathname}#/payResult?isOnline=1&order=${_this.order.sn}&isInstalment=${_this.$route.query.isInstalment}`
        }
      })
    }
  },
  beforeRouteLeave(to, from, next) {
    this.initPaymentSate()
    next()
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: $mainbgColor;
}
.header {
  @include header;
  border-bottom: 1px solid $lineColor;
}
.top-wrapper {
  height: 44px;
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  padding: 0 15px;
  font-size: 14px;
  color: $markColor;
}
.pay-msg-wrapper {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  padding: 0 15px 120px;
  overflow: hidden;
}
.pay-wrapper {
  margin-bottom: 10px;
  background-color: #fff;
  border-radius: 2px;
  overflow: hidden;
  &.surplus-container {
    margin-bottom: 25px;
    .is-friend-pay {
      position: relative;
      &:before {
        content: '';
        display: block;
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        z-index: 1;
        background: rgba(255, 255, 255, 0.8);
      }
    }
    .only-cash-pay-wrapper {
      color: #999999;
      font-size: 13px;
      padding: 0 15px;
      height: 50px;
      line-height: 50px;
      margin-top: -25px;
      position: relative;
      z-index: 2;
    }
  }
  .pay-container {
    padding: 0 15px;
    display: flex;
    background: #ffffff;
    justify-content: space-between;
    align-items: center;
    &:not(:last-child) {
      @include thin-border(#f4f4f4, 0, 0);
    }
    &.pay-wrapper-title {
      color: $baseColor;
      height: 50px;
      .pay-name {
        font-size: 16px;
        color: #404040;
        font-weight: 400;
      }
      .pay-price {
        font-size: 16px;
        font-weight: bold;
        color: #404040;
      }
      .wrapper-price {
        display: flex;
        flex-direction: row;
        align-items: baseline;
      }
      .surplus-paid {
        display: inline-block;
        color: #ff3950;
        font-size: 13px;
        line-height: 20px;
        padding: 0 6px;
      }
      .surplus-icon {
        width: 11px;
        height: 11px;
        margin-right: 2px;
      }
      .price-unit {
        line-height: 1;
        @include sc(10px, #404040, center bottom);
        font-weight: 600;
      }
    }
    &.pay-wrapper-exchange {
      height: 50px;
      font-size: 13px;
      font-weight: 300;
      @include thin-border(#f4f4f4, 0, 0);
      .can-use {
        color: #c6c6c6;
        flex: 1;
      }
      .go-exchange {
        color: #562f21;
        display: flex;
        align-items: center;
        img {
          width: 6px;
          margin-left: 3px;
        }
      }
    }
    &.pay-wrapper-rules {
      min-height: 50px;
      text-align: center;
      flex-direction: column;
      .rules-content {
        text-align: left;
        font-size: 14px;
        color: $baseColor;
      }
      .rules-title {
        color: #b5b6b6;
        line-height: 45px;
      }
      .show-rules {
        height: 50px;
        color: #552e20;
        line-height: 50px;
        font-size: 14px;
      }
      img {
        width: 13px;
        height: 13px;
        transform: rotate(90deg);
        &.is-show {
          transform: rotate(-90deg) translateX(-2px);
        }
      }
    }
    //
    .pay-check {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      span {
        margin-right: 20px;
        font-size: 13px;
        font-weight: 300;
        color: #c0c0c0;
        line-height: 18px;
      }
      .check-radio {
        display: flex;
        align-items: center;
        input {
          display: none;
        }
        label {
          display: inline-block;
          width: 22px;
          height: 22px;
          background: url('../../assets/image/hh-icon/icon-checkbox.png') no-repeat;
          background-size: 22px 22px;
          &.active {
            background-image: url('../../assets/image/hh-icon/icon-checkbox-active.png');
          }
        }
      }
      .go-exchange {
        font-size: 13px;
        font-weight: 300;
        line-height: 18px;
        color: #562f21;
        margin-right: 20px;

        display: flex;
        align-items: center;
        justify-content: flex-end;
        img {
          width: 6px;
          margin-left: 5px;
        }
      }
    }
  }
  .item-wrapper {
    .item-choose-wrapper {
      &:not(:last-child) {
        @include thin-border(#f4f4f4, 0, 0);
      }
      padding: 0 15px;
      height: 50px;
      display: flex;
      align-items: center;
      flex-direction: row;
      justify-content: space-between;
    }
    .sel-radius {
      @include wh(20px, 20px);
      background-size: 100%;
      background-repeat: no-repeat;
      background-position: center;
      background-image: url('../../assets/image/hh-icon/icon-checkbox.png');
    }
    .pay-inpput {
      display: none;
      &:checked + .sel-radius {
        background-image: url('../../assets/image/hh-icon/icon-checkbox-active.png');
      }
      &:disabled + .sel-radius {
        visibility: hidden;
      }
    }
    .item-left-wrapper {
      display: flex;
      align-items: center;
      font-size: 14px;
    }
    img {
      width: 24px;
      margin-right: 13px;
    }
  }
  .pay-wrapper-total {
    height: 50px;
    text-align: right;
    justify-content: flex-end;
    align-items: center;
    font-size: 12px;
    .price-wrapper {
      color: $markColor;
      font-size: 20px;
      display: flex;
      align-items: baseline;
      font-weight: bold;
      .price-w-title {
        font-size: 12px;
        color: #404040;
        margin-right: 2px;
        font-weight: 400;
      }
      img {
        width: 11px;
      }
      .price-w-plus {
        font-size: 14px;
        margin: 0 2px;
      }
      .price-w-unit {
        font-size: 12px;
        font-weight: 600;
      }
      .price-w-amount {
        letter-spacing: 0px;
      }
    }
  }
  .share-pay-wrapper {
    padding: 0 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 50px;
    .share-pay-wrapper-title {
      font-size: 14px;
      color: #404040;
    }
  }
}
.row-wrapper {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  .icon {
    width: 14px;
    height: 16px;
    border-radius: 2px;
    margin: 0 12px 0 20px;
  }
}
.title-wrapper {
  height: 58px;
}
.subtitle-wrapper {
  height: 20px;
  // margin-top: 10px;
}
.title {
  font-size: 15px;
  color: #666;
}
.subtitle {
  font-size: 15px;
  color: #666;
  margin-left: 46px;
}
.price {
  font-size: 15px;
  color: $markColor;
}
.action-wrapper {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  .button {
    @include button($margin: 20px 25px 60px, $radius: 2px);
    background-color: $primaryColor !important;
    font-size: 18px;
  }
}
.indicator {
  width: 14px;
  height: 14px;
  margin-left: 5px;
  margin-right: 16px;
}
.payment-switch {
  justify-content: flex-end;
  /deep/ .mint-switch-input:checked + .mint-switch-core:after {
    transform: translateX(18px);
    content: '';
  }
  /deep/ .mint-switch-core:after {
    width: 22px;
    height: 22px;
    content: '';
  }
  /deep/ .mint-switch-core:before {
    width: 40px;
    height: 22px;
    content: '';
  }
  /deep/ .mint-switch-core {
    width: 42px;
    height: 24px;
  }
  /deep/ .mint-switch-input:checked + .mint-switch-core {
    background-color: #4cd964;
    border-color: #4cd964;
  }
}
.mint-popup-bottom {
  height: 400px;
  border-radius: 12px 12px 0px 0px;
}
// 123
.popup {
  .popup-head {
    padding: 0 15px;
    height: 53px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px dotted rgba(85, 46, 32, 0.2);

    h2 {
      font-size: 17px;
      font-weight: 500;
      color: #404040;
      line-height: 24px;
    }
    div {
      @include wh(14px, 14px);
      background: url('../../assets/image/hh-icon/icon-close.png') no-repeat;
      background-size: 14px 14px;
    }
  }
  .popup-body {
    padding: 0 15px;
    height: 242px;
    overflow: auto;
    .b-line {
      // @include thin-border(rgba(85, 46, 32, 0.2));
      border-bottom: 1px dotted rgba(85, 46, 32, 0.2);
    }
    .body-item {
      padding: 21px 0;
      display: flex;
      justify-content: space-between;
      .left {
        .title {
          height: 22px;
          margin-bottom: 7px;
          span {
            font-size: 16px;
            font-weight: 400;
            color: #404040;
            line-height: 22px;
            margin-right: 13px;
          }
          label {
            font-size: 14px;
            font-weight: 400;
            color: #999;
            line-height: 20px;
          }
        }
        .line {
          font-size: 12px;
          font-weight: 400;
          color: #999;
          line-height: 17px;
        }
      }
      .right {
        @include wh(22px, 22px);
        background: url('../../assets/image/hh-icon/icon-checkbox.png') no-repeat;
        background-size: 22px 22px;
        &.active {
          background-image: url('../../assets/image/hh-icon/icon-checkbox-active.png');
        }
      }
    }
    .select-box {
      width: 100%;
      .select-wrapper {
        padding: 14px 12px;
      }
      .select-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px dotted rgba(85, 46, 32, 0.3);
        p {
          font-size: 14px;
          font-weight: 400;
          color: #404040;
          line-height: 20px;
        }
        div {
          @include wh(7px, 7px);
          background: url('../../assets/image/hh-icon/b0-home/icon-search-箭头-down.png') no-repeat;
          background-size: 7px 7px;
        }
      }
      .select-body {
        background-color: #f9f9f9;
        max-height: 182px;
        overflow: auto;
        .select-body-item {
          display: flex;
          align-items: center;
          justify-content: space-between;
          p {
            font-size: 14px;
            font-weight: 400;
            color: #404040;
            line-height: 20px;
          }
          div {
            @include wh(22px, 22px);
            background: url('../../assets/image/hh-icon/icon-checkbox.png') no-repeat;
            background-size: 22px 22px;
            &.active {
              background-image: url('../../assets/image/hh-icon/icon-checkbox-active.png');
            }
          }
        }
      }
    }
  }
  .popup-bottom {
    padding: 32px 0 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    button {
      @include wh(327px, 46px);
      background: rgba(119, 37, 8, 1);
      border-radius: 2px;
      span {
        font-size: 18px;
        font-weight: 400;
        color: #fff;
        line-height: 46px;
      }
      &.undeep {
        opacity: 0.3;
      }
    }
  }
}
</style>
