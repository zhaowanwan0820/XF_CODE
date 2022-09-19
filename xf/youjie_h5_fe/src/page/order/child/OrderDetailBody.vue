<!-- OrderDetailBody.vue -->
<template>
  <div class="order-body" v-if="orderDetail.id">
    <div class="order-body-top" v-bind:class="{ ship: orderDetail.status == 1 }">
      <!-- 订单状态 -->
      <div class="image">
        <div class="order-countdown">
          <!-- 代付款 -->
          <template v-if="s0">
            <span class="ms1" v-if="orderDetail.share_sn && pay_status == 1">待好友支付…</span>
            <span class="ms1" v-else>
              <template v-if="particalPay">
                部分支付
              </template>
              <template v-else>待支付…</template>
            </span>
            <span class="ms2">
              <template v-if="isInstalment">{{ instalmentTxt }}</template>
              <template v-else>{{ timeTxt }}</template>
            </span>
          </template>

          <!-- 代发货 -->
          <template v-else-if="s1">
            <span class="ms1">待发货</span>
            <span class="ms2">请耐心等待 商家玩命发货中</span>
          </template>

          <!-- 代收货 -->
          <template v-else-if="s2">
            <span class="ms1">配送中</span>
            <span class="ms2">宝贝正在快马加鞭地配送中</span>
          </template>

          <!-- 代评价 -->
          <template v-else-if="s3">
            <span class="ms1">待评价</span>
            <span class="ms2">收到宝贝了 给个好评吧</span>
          </template>

          <!-- 已完成 -->
          <template v-else-if="s4">
            <span class="ms1">已完成</span>
          </template>

          <!-- 已取消 -->
          <template v-else-if="s5">
            <span class="ms1">已取消</span>
            <span class="ms2">期待再次为您服务</span>
          </template>

          <!-- 配货中 -->
          <template v-else-if="s6">
            <span class="ms1">配货中</span>
            <span class="ms2"></span>
          </template>
        </div>
      </div>

      <!-- 物流信息 ( 暂时没有 ) -->
      <div class="receipt" @click="goOrderrack(orderDetail.id)" v-if="trackList.length >= 1">
        <label>
          <img v-if="s1 || s6" src="../../../assets/image/change-icon/e0_delivery@2x.png" />
          <img v-if="s2" src="../../../assets/image/change-icon/icon_car@2x.png" />
          <img src="../../../assets/image/change-icon/e0_delivery@2x.png" />
          <span>{{ trackList[0].content }}</span>
        </label>
        <img class="arrow" src="../../../assets/image/change-icon/enter@2x.png" />
      </div>

      <!-- 地址信息 -->
      <div class="address">
        <div>
          <span>{{ orderDetail.consignee.name }}</span>
          <span class="mobile">{{ orderDetail.consignee.mobile }}</span>
        </div>
        <p style="-webkit-box-orient: vertical; -webkit-line-clamp: 2;">
          {{ orderDetail.consignee.regions }} {{ orderDetail.consignee.address }}
        </p>
      </div>

      <!-- 商品信息 -->
      <div class="container container-good-info">
        <p class="good-title">商品信息</p>
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
        <div
          class="refund-wrapper"
          v-if="!orderDetail.share_sn && !orderDetail.share_detail && orderDetail.mlm_id == 0 && !user.suppliers_id"
        >
          <template v-if="orderDetail.payment && [1, 3].indexOf(orderDetail.payment.refund_status) > -1">
            <span class="button-refund">{{ orderDetail.payment.refund_status == 1 ? '退款成功' : '退款中' }}</span>
          </template>
          <template v-else-if="orderDetail.shipping.status === 0 && s1 && !isInstalment">
            <span class="button-refund" @click.stop="goRefund(orderDetail.id)">退款</span>
          </template>
          <template v-if="isShowASbtn">
            <span class="button-refund" @click.stop="goWorkOrder(orderDetail.sn)">申请售后</span>
            <label class="work-msg" v-if="isMsg === 'OK'"></label>
          </template>
        </div>
      </div>

      <!-- <div class="onClick" v-if="orderDetail.goods.length > 3 && !isShow">
        <p v-on:click="getNumber">还有 {{ orderDetail.goods.length - 3 }} 件</p>
      </div>-->

      <!-- 价格信息 -->
      <div class="desc section-header section-footer" v-bind:class="{ ship: s1 }">
        <div class="price-info">
          <checkout-desc class="desc-item" title="商品总额" :subtitle="getOrderProductPrice"></checkout-desc>
          <checkout-desc class="desc-item" title="运费" :subtitle="getOrderShippingPrice"></checkout-desc>
          <checkout-desc
            class="desc-item"
            v-for="(item, index) in getPromos"
            :key="index"
            v-if="s0 || item.price > 0"
            :title="getPromoTitle(item)"
            :subtitle="getOrderDiscountPrice(item)"
          ></checkout-desc>
          <template v-if="orderDetail.instalment.length === 0">
            <checkout-desc
              class="desc-item"
              title="已支付"
              :subtitle="getOrderHadPay"
              v-if="orderDetail.share_sn && (s0 || s5) && money_paid"
            ></checkout-desc>
            <template v-if="orderDetail.order_type !== 5">
              <!-- 直通车订单不展示已支付部分 -->
              <checkout-desc
                class="desc-item"
                title="已支付"
                :subtitle="getOrderHadPayNormal"
                v-if="!orderDetail.share_sn && (money_paid || surplus_paid) && (s0 || s5)"
              ></checkout-desc>
            </template>
          </template>
          <checkout-desc
            class="desc-item"
            title="好友支付"
            :isIcon="isIcon"
            :subtitle="getOrderFriendPaySur"
            v-if="orderDetail.share_sn && surplus_paid"
          ></checkout-desc>
          <checkout-desc
            class="desc-item"
            title="优惠券"
            :subtitle="bonus"
            v-if="orderDetail.bonus > 0"
          ></checkout-desc>
        </div>
        <!-- <checkout-desc class="desc-item" title="积分" :subtitle="getBalance" :warn='showWarn' :status='orderDetail.status'>
        </checkout-desc>
        <checkout-desc class="desc-item" v-for="(item, index) in getPromos" :key="index" :title=" getPromoTitle(item)" :subtitle="'-' + getOrderDiscountPrice(item)">
        </checkout-desc>
        <checkout-desc class="desc-item total-price" title="订单总价" :subtitle="getOrderTotalPrice">
        </checkout-desc>-->

        <order-instalment v-if="isInstalment" :order="orderDetail"></order-instalment>

        <!-- 分期付款走这里 -->
        <div class="amount-istlmt-wrapper" v-if="isInstalment && s0">
          <label class="amount paid-amount">
            <span class="amount-title">已支付:</span>
            <label class="cash">
              <span class="cash-icon">￥</span>
              <span class="cash-num">{{
                utils.formatFloat(parseFloat(orderDetail.total) - parseFloat(getOrderTotalPrice))
              }}</span>
            </label>
          </label>
          <label class="amount no-pay-amount">
            <span class="amount-title">剩余未付:</span>
            <label class="cash">
              <span class="cash-icon">￥</span>
              <span class="cash-num">{{ getOrderTotalPrice }}</span>
            </label>
          </label>
        </div>
        <div class="amount-wrapper" v-else>
          <label class="amount" v-if="!s5">
            <span class="amount-title" v-if="orderDetail.share_sn && s0 && pay_status == 1">待好友支付:</span>
            <span class="amount-title" v-if="s0 && pay_status !== 2">待支付:</span>
            <span class="amount-title" v-if="!s0">实际支付:</span>
            <!-- 积分部分 -->
            <label class="surplus" v-if="orderDetail.share_sn && s0 && pay_status == 1">
              <img src="../../../assets/image/hh-icon/b0-home/money-icon.png" />
              <span class="surplus-num">{{ getOrderTotalPrice }}</span>
            </label>
            <label class="cash" v-else>
              <!-- <span class="cash-surplus">+</span> -->
              <span class="cash-icon">￥</span>
              <span class="cash-num">{{ getOrderTotalPrice }}</span>
            </label>
          </label>
          <label class="cancel" v-if="s5 && (money_paid || surplus_paid)">
            <span>订单已取消，已支付的部分将会按照原支付方式退回</span>
          </label>
        </div>

        <div class="instalment-refund-tips" v-if="isInstalment && s0">
          <span>如需退款请联系商家</span>
        </div>
      </div>

      <!-- 其他信息 -->
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
          <div v-if="orderDetail.status > 1 && !s5">
            <label>
              <span class="order-title">配送方式：</span>
              <span class="order-sn">{{ orderDetail.shipping.name }}</span>
            </label>
          </div>
          <div v-if="orderDetail.take_time > 0">
            <label>
              <span class="order-title">收货时间：</span>
              <span class="order-sn">{{ utils.formatDate('YYYY-MM-DD HH:mm:ss', orderDetail.take_time) }}</span>
            </label>
          </div>
        </div>
        <!-- <div class="pay" v-if="orderDetail.status !== 0">
          <p v-if="orderDetail.extension_code == isExchangeGood">支付方式：积分兑换</p>
          <p v-else>支付方式：{{orderDetail.payment.name}}</p>
        </div>-->
        <div class="mall-phone">
          <div>
            <img src="../../../assets/image/hh-icon/e5-orderDetail/icon-phone.png" />
            <span @click="isShowService = !isShowService">联系商家</span>
          </div>
          <div>
            <img src="../../../assets/image/hh-icon/e5-orderDetail/icon-huan.png" />
            <span @click="isShowHuan = !isShowHuan">联系{{ utils.storeNameForShort }}</span>
          </div>
        </div>
      </div>

      <recommend-list :params="recommendParams"></recommend-list>
    </div>

    <!-- 待付款按钮 -->
    <div class="btn" v-if="(s0 && !isFailure) || (s0 && particalPay)">
      <template v-if="showCancelBtn">
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
      </template>
      <template v-if="showConfirmBtn">
        <button class="buttonbottom" v-on:click="confirm(orderDetail.id, index)">确认收货</button>
      </template>
      <template v-if="showContinueBtn">
        <button class="buttonbottom" v-on:click="sharePay" v-if="orderDetail.share_sn && pay_status == 1">
          好友支付
        </button>
        <button class="buttonbottom" v-on:click="payment" v-else>继续支付</button>
      </template>
      <template v-if="particalPay">
        <button class="buttonbottom" @click="payAll">全部支付</button>
      </template>
      <template v-if="isInstalment">
        <button class="buttonbottom" v-on:click="instalmentDetail(orderDetail.id)">分期明细</button>
      </template>
    </div>

    <!-- 待发货按钮 -->
    <div class="btn" v-if="s1 ? '' : checkState"></div>

    <!-- 待收货按钮 -->
    <div class="btn" v-if="s2">
      <button v-on:click="payDetail(orderDetail.id)" v-if="!isInstalment">支付明细</button>
      <button class="buttonbottom" v-on:click="confirm(orderDetail.id, index)">确认收货</button>
      <button v-if="isInstalment" class="buttonbottom" v-on:click="instalmentDetail(orderDetail.id)">分期明细</button>
    </div>

    <!-- 支付明细 -->
    <div class="btn" v-if="s1 || s3">
      <button v-on:click="payDetail(orderDetail.id)" v-if="!isInstalment">支付明细</button>
      <button v-if="isInstalment" class="buttonbottom" v-on:click="instalmentDetail(orderDetail.id)">分期明细</button>
    </div>

    <!-- 已完成 -->
    <div class="btn" v-if="s4 && isInstalment">
      <button class="buttonbottom" v-on:click="instalmentDetail(orderDetail.id)">分期明细</button>
    </div>

    <!-- 配货中 -->
    <div class="btn" v-if="s6">
      <button class="buttonbottom" v-on:click="confirm(orderDetail.id, index)">确认收货</button>
      <button v-if="isInstalment" class="buttonbottom" v-on:click="instalmentDetail(orderDetail.id)">分期明细</button>
    </div>

    <!-- 已取消 -->
    <div class="btn" v-if="s5 && isInstalment">
      <button class="buttonbottom" v-on:click="instalmentDetail(orderDetail.id)">分期明细</button>
    </div>

    <popup-share-friend-pay
      ref="shareFriendPayPop"
      v-model="friendPayFlag"
      :options="share_option"
      :sharePayInfo="share_pay_info"
    ></popup-share-friend-pay>

    <!-- 服务联系方式 -->
    <mt-popup v-model="isShowService" position="bottom">
      <div class="pop-container">
        <div class="title">
          <p>联系方式</p>
          <img src="../../../assets/image/hh-icon/detail/icon-close@3x.png" @click="isShowService = false" alt="" />
        </div>
        <div class="content">
          <div class="content-line" v-if="orderDetail.supplier.service_tel">
            <div class="content-title">客服电话</div>
            <div class="content-item">
              <div class="content-num">{{ orderDetail.supplier.service_tel }}</div>
              <a :href="`tel:${isIos ? '//' : ''}${orderDetail.supplier.service_tel}`">
                <img src="../../../assets/image/hh-icon/detail/icon-tel@3x.png" alt="" />
              </a>
            </div>
          </div>
          <div class="content-line" v-if="orderDetail.supplier.service_qq">
            <div class="content-title">客服QQ</div>
            <div class="content-item">
              <div class="content-num">{{ orderDetail.supplier.service_qq }}</div>
              <a :href="`mqq://im/chat?chat_type=wpa&uin=${orderDetail.supplier.service_qq}&version=1&src_type=web`">
                <img src="../../../assets/image/hh-icon/detail/icon-qq@3x.png" alt />
              </a>
            </div>
          </div>

          <div class="content-none" v-if="!orderDetail.supplier.service_tel && !orderDetail.supplier.service_qq">
            <p>该商家未提供客服联系方式</p>
          </div>
        </div>
      </div>
    </mt-popup>

    <mt-popup v-model="isShowHuan" position="bottom">
      <div class="pop-container">
        <div class="title">
          <p>联系方式</p>
          <img src="../../../assets/image/hh-icon/detail/icon-close@3x.png" @click="isShowHuan = false" alt />
        </div>
        <div class="content">
          <div class="content-line">
            <p class="content-title">客服电话</p>
            <div class="content-item">
              <div class="content-num">4006960099</div>
              <a :href="`tel:${isIos ? '//' : ''}4006960099`">
                <img src="../../../assets/image/hh-icon/detail/icon-tel@3x.png" alt />
              </a>
            </div>
            <!-- <div class="content-item">
              <div class="content-num">010-89929967</div>
              <a :href="`tel:${isIos ? '//' : ''}010-89929967`">
                <img src="../../../assets/image/hh-icon/detail/icon-tel@3x.png" alt />
              </a>
            </div>
            <div class="content-item">
              <div class="content-num">010-89929968</div>
              <a :href="`tel:${isIos ? '//' : ''}010-89929968`">
                <img src="../../../assets/image/hh-icon/detail/icon-tel@3x.png" alt />
              </a>
            </div> -->
          </div>

          <!-- href="mqqapi://card/show_pslcard?src_type=internal&version=1&uin=994056656&card_type=group&source=external" -->
          <!-- <a href="javascript:void(0)" class="serviceType-wrapper">
            <div class="content-line">
              <div class="content-left">
                <p class="content-title">客服QQ群</p>
                <p class="content-num">&nbsp;</p>
              </div>
              <div class="content-right">
                <img src="../../../assets/image/hh-icon/detail/icon-qq@3x.png" alt />
              </div>
            </div>
          </a> -->

          <div class="content-line">
            <div class="content-title">服务时间</div>
            <div class="content-item">
              <span>工作日：09:30-18:30</span>
              <span>节假日：09:30-18:30</span>
            </div>
          </div>
        </div>
      </div>
    </mt-popup>

    <!-- 买家已付款后的退款pop -->
    <template v-if="orderDetail.shipping.status === 0 && s1">
      <mt-popup v-model="refundPopupFlag" position="bottom" class="refund-pop" style="height: 81%;">
        <div class="refund">
          <div class="title-wrapper">
            <span></span>
            <span class="r-p-title">退款</span>
            <img src="../../../assets/image/hh-icon/detail/icon-close@3x.png" @click="refundPopupFlag = false" alt />
          </div>
          <p class="content-title">请选择退款原因</p>
          <div class="reason">
            <div class="list" v-for="item in reasonList" :key="item.id">
              <label :for="'refund' + item.id" class="item-title item-left-wrapper">{{ item.name }}</label>
              <input
                type="radio"
                :id="'refund' + item.id"
                class="item-input"
                @change="checkReason(item.id)"
                name="reason"
              />
              <label class="input-radius" placeholder="v" :for="'refund' + item.id"></label>
            </div>
          </div>
          <div class="content-tips">
            <div class="tips-title">温馨提示</div>
            <div class="tips-body">
              <p>1.订单一旦取消，限时特价、购买资格等购买优惠可能一并取消，不可恢复；</p>
              <p>2.订单所使用红包、优惠券不支持退回，为避免过期请尽快使用；</p>
              <p>3.若您支付未成功银行卡已扣款，钱款将于4个工作日内退回银行卡。</p>
            </div>
          </div>
          <div class="refund-comfirm">
            <gk-button v-if="refundReasonId" class="button" type="primary-secondary" v-on:click="submitRefunReason"
              >提交</gk-button
            >
            <gk-button v-else class="button disable" type="primary-secondary">提交</gk-button>
          </div>
        </div>
      </mt-popup>
    </template>
  </div>
</template>

<script>
import { ORDEREFFRCTTIME, SECKILLORDEREFFRCTTIME, AFTERSALEDAYS } from '../static'
import OrderPrice from './OrderPrice'
import ContactSupplier from '../../../components/common/ContactSupplier'
import { Indicator, MessageBox, Popup } from 'mint-ui'
import CheckoutDesc from './CheckoutDesc'
import { PopupShareFriendPay } from '../../../components/common'
import Promos from '../../checkout/Promos'
import OrderInstalment from './OrderInstalment'
import { ENUM } from '../../../const/enum'

import { orderGet, orderReasonList, orderCancel, orderConfirm } from '../../../api/order'
import { Toast } from 'mint-ui'
import Clipboard from 'clipboard'
import { mapState, mapMutations } from 'vuex'
import RecommendList from '../../recommend/RecommendList'
export default {
  mixins: [Promos],
  data() {
    return {
      isShowHuan: false,
      isShowService: false,
      isIos: false,

      orderDetail: {},
      popupVisible: false,
      reasonList: [],
      orderCancel: [],
      checkState: '',
      currentNAVId: '',
      orderListParams: { page: 0, per_page: 10, status: '' },
      index: '',
      total_price: [],
      orderIndex: 2,
      isShow: false,
      trackList: [], // 物流信息
      // isExchangeGood: ENUM.ORDER_EXTENSION_CODE.EXCHANGE_GOODS,
      orderTime: 0,
      timeTxt: '',
      reasonId: '',
      isFailure: false, // 订单是否失效

      share_pay_info: {
        sn: 0,
        need_money: 0,
        need_surplus: 0,
        thumb: ''
      },
      isIcon: true,
      money_paid: 0,
      pay_status: 0, //支付状态 0未支付 1支付中(已支付积分未支付现金) 2支付完成
      surplus_paid: 0,
      friendPayFlag: false,
      share_sn: '',
      share_option: ['WechatSession'],

      refundPopupFlag: false, // 退款popup
      refundReasonId: null, // 退款原因id

      recommendParams: {}, // 推荐额外参数
      share_option: ['WechatSession'],

      refundPopupFlag: false, // 退款popup
      refundReasonId: null, // 退款原因id

      isMsg: 'FAIL', //申请售后按钮 未读消息红点 FAIL无未读，OK有未读
      workOrderFlag: 0, // 【0】没有正在进行的工单 已进行的已超过14天 【1】工单关闭未超过14天 【2】有一个工单未关闭
      service_tel_arr: ENUM.SERVICE.MASTER_TEL_ARR,
      isShowText: '' //判断appoint_debt，是否属于0元购订单
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
    ContactSupplier,
    RecommendList,
    OrderInstalment
  },

  created() {
    let id = this.$route.query.id ? this.$route.query.id : null
    this.orderInfo(id)
    this.orderReasonList()

    this.isIos = 1 == this.utils.getOpenBrowser() ? true : false
  },
  methods: {
    ...mapMutations({
      changeItem: 'changeItem',
      saveRefundStatus: 'saveRefundStatus'
    }),

    // 获取订单详情数据
    orderInfo(id) {
      orderGet(id).then(res => {
        console.log(res, 'orderGet')
        this.$emit('onloaded', res)
        this.orderDetail = res
        this.total_price = res.goods
        this.orderTime = res.created_at
        this.pay_status = res.pay_status
        this.money_paid = this.utils.formatFloat(res.hhpay.money_paid) * 1
        this.surplus_paid = this.utils.formatFloat(res.hhpay.surplus_paid) * 1
        this.share_sn = res.share_sn ? res.share_sn : ''
        if (res.share_pay && res.share_pay.sn) {
          this.share_pay_info = res.share_pay
        }
        this.share_pay_info.thumb = res.goods[0].thumb
        this.getRestTime(this.orderTime)
        this.isShowText = res.appoint_debt
        // 获取售后未读消息
        // this.getWorkMsg(res.sn)
      })
    },

    // 获取订单倒计时
    getRestTime(orderTime) {
      var RestTime = this.orderRestTime - (Math.floor(new Date().getTime() / 1000) - orderTime)
      if (RestTime > 0 && RestTime < this.orderRestTime) {
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
      let is_exchanged_debt = this.orderDetail.debt_purchase
      if (is_exchanged_debt) {
        const params = {
          title: '风险提示',
          message:
            '您已用于购买“一对一专区商品”商品的积分，在取消订单后仅会回退至积分余额，无法回退为指定债权。 <p class="cancel-order-tips-para">是否取消订单？</p>',
          confirmButtonText: '取消订单',
          showCancelButton: true,
          cancelButtonText: '关闭'
        }
        MessageBox(params).then(
          action => {
            if (action == 'confirm') {
              this.popupVisible = false
              this.getordersuccess(id, index)
            }
          },
          cancel => {
            this.popupVisible = false
          }
        )
      } else {
        this.popupVisible = false
        this.getordersuccess(id, index)
      }
    },

    // 去支付
    payment() {
      let order = this.orderDetail
      let id
      if (this.isInstalment) {
        order.instalment.forEach((item, index) => {
          if (id) {
            return
          }
          if (item.pay_status != 2) {
            id = item.id
          }
        })
      } else {
        id = order.id
      }
      if (order.id) {
        if (this.isInstalment) {
          this.$router.push({
            name: 'paymentHuan',
            query: {
              order: id,
              parent_order: '',
              total: order.total,
              isInstalment: 1
            }
          })
        } else {
          this.$router.push({
            name: 'payment',
            query: {
              order: JSON.stringify([id]),
              total: order.hhpay.need_money,
              isInstalment: 0,
              canceled_at: order.canceled_at
            }
          })
        }
      }
    },

    // 支付全部
    payAll() {
      this.$router.push({
        name: 'paymentHuan',
        query: {
          order: '',
          parent_order: this.orderDetail.id,
          total: this.orderDetail.total,
          isInstalment: 1
        }
      })
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

    // 查看物流
    track(id) {
      this.$router.push({ name: 'orderTrack', params: { orderTrack: id } })
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
    instalmentDetail(id) {
      this.$router.push({ name: 'orderInstalDetail', query: { id: id, pay_status: this.orderDetail.pay_status } })
    },

    // 获取确认收货数据
    orderConfirms(id, index) {
      orderConfirm(id).then(res => {
        // 如果是分期，确认收货，则不更改页面状态

        let that = this.orderDetail.instalment
        this.orderDetail.take_time = res.take_time
        if (this.s0 && this.notPayAll) {
          return
        }
        this.orderDetail.status = res.status
      })
    },

    // 晒单评价
    goComment(data) {
      this.changeItem(data)
      this.$router.push({ name: 'orderComment', query: { order: data.id } })
    },

    getOrderDiscountPrice(item) {
      return '-￥ ' + (item.price ? this.utils.formatFloat(item.price) : 0)
    },

    getFormatPrice(key) {
      let price = this.getPriceByKey(key)
      let priceStr = '￥' + (price ? this.utils.formatFloat(price) : '0')
      return priceStr
    },

    getPriceByKey(key) {
      let total = ''
      let order = this.orderDetail
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

    // 弹窗退款原因弹窗
    goRefund() {
      if (this.isShowText == 1) {
        MessageBox.confirm('', {
          title: '确定退款',
          message: '该商品是0元购活动商品，退款后的积分无法用于再次购买0元购活动商品'
        }).then(action => {
          this.refundPopupFlag = true
        })
      } else {
        this.refundPopupFlag = true
      }
    },
    // 选择退款原因
    checkReason(id) {
      this.refundReasonId = id
    },
    // 提交退款原因
    async submitRefunReason() {
      this.refundPopupFlag = false
      let res
      if (this.orderDetail.hhpay.surplus_paid == 0) {
        res = 'confirm'
      } else {
        const params = {
          title: '风险提示',
          message: '订单取消后，原债权仅退回到积分，无法退回到债权',
          confirmButtonText: '确定',
          showCancelButton: true,
          cancelButtonText: '取消'
        }
        res = await MessageBox(params)
      }
      if (res == 'confirm') {
        orderCancel(this.orderDetail.id, this.refundReasonId).then(
          res => {
            this.saveRefundStatus(1)
            this.$router.push({ name: 'refundResult' })
          },
          error => {
            this.saveRefundStatus(2)
            this.$router.push({ name: 'refundResult' })
          }
        )
      } else {
        this.refundPopupFlag = true
      }
    },
    // getWorkMsg(id) {
    //   this.$api.get('workOrderMessage/' + id, null, res => {
    //     this.isMsg = res.unReadMessage
    //     this.workOrderFlag = res.workOrderFlag
    //   })
    // },
    goWorkOrder(id) {
      if (this.workOrderFlag == 2) {
        this.$router.push({ name: 'WorkorderList', params: { id: id } })
      } else {
        this.$router.push({ name: 'AddWorkorder', params: { id: id } })
      }
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
      if (this.orderDetail.share_sn) {
        // 好友代付订单
        if (this.s0) {
          if (this.money_paid) {
            // 代付订单已支付现金  待好友支付
            return this.utils.formatFloat(this.orderDetail.hhpay.friend_high_surplus)
          } else {
            // 代付订单未支付
            return this.orderDetail.total
          }
        } else {
          // 实际支付
          return this.orderDetail.hhpay.money_paid
        }
      } else if (this.orderDetail.mlm_id) {
        return this.orderDetail.total
      } else {
        // 正常订单
        if (!this.s0) {
          // 实际支付
          return this.utils.formatFloat(this.money_paid + this.surplus_paid)
        } else {
          // 待支付
          return this.utils.formatFloat(this.orderDetail.total - this.money_paid - this.surplus_paid)
        }
      }
    },

    getOrderProductPrice: function() {
      return '￥' + this.goodsTotalPrice()
    },

    getOrderTaxPrice: function() {
      return this.getFormatPrice('tax')
    },

    getOrderHadPay: function() {
      return '-￥ ' + this.utils.formatFloat(this.orderDetail.hhpay.money_paid)
    },

    getOrderHadPayNormal: function() {
      return '-￥ ' + this.utils.formatFloat(this.money_paid + this.surplus_paid)
    },

    getOrderFriendPaySur: function() {
      return this.utils.formatFloat(this.orderDetail.hhpay.surplus_paid) //代付方支付的积分
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
    },
    isShowASbtn() {
      return false

      let isShow = true
      // 未付款 已取消 不展示
      if (this.s0 || this.s5) {
        isShow = false
      }
      // 待发货&未出库 不展示
      if (this.s1 && !this.orderDetail.shipping.status) {
        isShow = false
      }
      if (this.isInstalment) isShow = false
      // 确认收货超过14天 且 workOrderFlag==0 不展示
      if (this.s3 || this.s4) {
        if (this.orderDetail.confirm_time) {
          let restdays = Math.floor((new Date().getTime() / 1000 - this.orderDetail.confirm_time) / 86400)
          if (restdays > AFTERSALEDAYS && this.workOrderFlag == 0) {
            isShow = false
          }
        }
      }
      return isShow
    },

    instalmentTxt() {
      let paidLength = 0,
        totalNum = this.orderDetail.instalment.length

      this.orderDetail.instalment.map((item, index) => {
        if (item.pay_status == 2) {
          paidLength += 1
        }
      })

      return `当前已支付期数 ${paidLength}/${totalNum} `
    },
    orderRestTime() {
      return this.orderDetail.order_type === ENUM.ORDERTYPE.SECKILL ? SECKILLORDEREFFRCTTIME : ORDEREFFRCTTIME
    },
    // 因DOM渲染使用orderDetail.status过于频繁，现将status的判断运算提取成计算属性
    s0() {
      return this.orderDetail.status == 0
    },
    s1() {
      return this.orderDetail.status == 1
    },
    s2() {
      return this.orderDetail.status == 2
    },
    s3() {
      return this.orderDetail.status == 3
    },
    s4() {
      return this.orderDetail.status == 4
    },
    s5() {
      return this.orderDetail.status == 5
    },
    s6() {
      return this.orderDetail.status == 6
    },
    isInstalment() {
      // 是否是分期订单
      let that = this.orderDetail.instalment
      return that && that.length
    },
    showCancelBtn() {
      // 显示【取消订单】，当非分期订单，或者分期订单未支付首期
      let that = this.orderDetail.instalment
      return !that.length || (this.isInstalment && that[0].pay_status != 2)
    },
    notPayAll() {
      // 显示【继续支付】，者未支付完成的分期订单
      let that = this.orderDetail.instalment
      return this.isInstalment && that[that.length - 1].pay_status != 2
    },
    showContinueBtn() {
      // 显示【继续支付】，未支付的订单或者未支付完成的分期订单
      return !this.orderDetail.instalment.length || this.notPayAll
    },
    particalPay() {
      // 部分支付
      let that = this.orderDetail.instalment
      return this.isInstalment && that[0].pay_status == 2
    },
    showConfirmBtn() {
      // 显示【确认收货】
      return this.particalPay && this.orderDetail.take_time == 0
    },
    bonus() {
      // 返回优惠券金额
      return '-￥' + this.utils.formatFloat(this.orderDetail.bonus)
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
  background: linear-gradient(59deg, rgba(252, 150, 85, 1) 0%, rgba(254, 112, 99, 1) 100%);
  height: 108px;
  .order-countdown {
    height: 73px;
    padding-left: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    span {
      // display: block;
      color: #ffffff;
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
      color: #ffffff;
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
  &.container-good-info {
    @include thin-border(#f4f4f4, 15px);
  }
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
    padding: 10px 15px;
  }
  .refund-wrapper {
    padding: 0 15px 10px;
    display: flex;
    justify-content: flex-end;
    position: relative;
    .button-refund {
      box-sizing: border-box;
      border: 1px solid rgba(183, 88, 0, 0.5);
      border-radius: 2px;
      padding: 0 15px;
      height: 20px;
      font-size: 12px;
      color: $markColor;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .work-msg {
      position: absolute;
      top: -4px;
      right: 11px;

      width: 10px;
      height: 10px;
      background: #ff3950;
      border-radius: 50%;
    }
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
    padding: 14px 18px 17px 15px;
    font-size: 13px;
    line-height: 18px;
    color: #888;
    font-weight: 300;

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
        @include sc(11px, $markColor);
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
      color: $markColor;
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
  .amount-istlmt-wrapper {
    padding: 15px;
    overflow: hidden;
    display: flex;
    justify-content: flex-end;
    .amount + .amount {
      margin-left: 12px;
    }
    .amount.no-pay-amount {
      span {
        color: $primaryColor;
      }
      .cash-surplus {
        line-height: 12px;
        @include sc(11px, $primaryColor);
        font-weight: bold;
      }
      .cash-icon {
        line-height: 14px;
        @include sc(10px, $primaryColor);
        font-weight: bold;
        padding-top: 4px;
      }
      .cash-num {
        font-size: 16px;
        line-height: 19px;
      }
    }
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
        @include sc(11px, $markColor);
        font-weight: bold;
      }
      .cash-icon {
        line-height: 14px;
        @include sc(10px, $markColor);
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
      color: $markColor;
    }
  }
  .cancel {
    display: flex;
    justify-content: flex-end;
    margin-top: 7px;
    margin-right: -7%;
    span {
      @include sc(10px, $markColor);
      line-height: 14px;
    }
  }
  .instalment-refund-tips {
    text-align: right;
    padding: 0 15px 10px;
    font-size: 0;
    span {
      display: inline-block;
      font-size: 11px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: rgba(85, 46, 32, 1);
      line-height: 1;
      @include sc(11px, rgba(85, 46, 32, 0.6), right top);
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
    margin-right: 8px;
    background-color: #fff;
    color: $primaryColor;
    border-radius: 2px;
    border: 1px solid $primaryColor;
  }
  .buttonbottom {
    background-color: $primaryColor;
    color: #fff;
    border: none;
  }
  .buttondetail {
    border-radius: 2px;
    background: $primaryColor;
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
.mint-popup-bottom {
  height: 440px;
  .pop-container {
    .title {
      height: 50px;
      padding: 0 15px;
      border-bottom: 1px dotted #d8d8d8;
      display: flex;
      align-items: center;
      justify-content: space-between;
      p {
        font-size: 14px;
        line-height: 20px;
        color: #404040;
        margin: 0;
      }
      img {
        width: 14px;
        height: 14px;
      }
    }
    .content {
      padding: 0 15px;

      .serviceType-wrapper {
        display: block;
        text-decoration: none;
      }

      .content-line {
        padding: 20px 0;
        border-bottom: 1px dotted #d8d8d8;
        .content-title {
          color: #999;
          font-size: 13px;
          margin-bottom: 15px;
          line-height: 20px;
        }
        .content-item {
          display: flex;
          justify-content: space-between;
          align-items: center;
          & + .content-item {
            margin-top: 25px;
          }
        }
        .content-num {
          color: #333;
          margin: 0;
          font-size: 13px;
        }
        a {
          font-size: 0;
        }
        img {
          width: 26px;
          height: 26px;
        }
        span {
          font-size: 13px;
          color: #333333;
        }
      }
      .content-none {
        height: 85px;
        display: flex;
        justify-content: space-between;
        align-items: center;

        p {
          margin: 0;
        }
      }
    }
  }
}
.ship {
  margin-bottom: 0;
}
.refund-pop {
  .refund {
    padding-left: 15px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .title-wrapper {
    line-height: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 15px 20px 0;
    span {
      font-size: 16px;
      color: #333333;
    }
    img {
      width: 14px;
      height: 14px;
    }
  }
  .content-title {
    @include sc(14px, #888888);
    line-height: 20px;
    margin-top: 10px;
  }
  .reason {
    margin-top: 5px;
    flex: 1;
    overflow-y: auto;
  }
  .list {
    height: 50px;
    display: flex;
    align-items: center;
    @include thin-border();
    padding-right: 15px;
    justify-content: space-between;
    @include sc(14px, #404040);
    .list-item {
      // flex: 1;
      // display: flex;
      // align-items: center;
      // flex-direction: row;
      // justify-content: space-between;
      // @include sc(14px, #404040);
    }
    .item-input {
      display: none;
      &:checked + .input-radius {
        background-image: url('../../../assets/image/hh-icon/icon-checkbox-active.png');
      }
      &:disabled + .input-radius {
        visibility: hidden;
      }
    }
    .input-radius {
      @include wh(22px, 22px);
      background-size: 95%;
      background-repeat: no-repeat;
      background-position: center;
      background-image: url('../../../assets/image/hh-icon/icon-checkbox.png');
      display: inline-block;
    }
  }
  .content-tips {
    font-weight: 300;
    margin-right: 15px;
    border-radius: 2px;
    background-color: #f9f9f9;
    padding: 10px;
    .tips-title {
      @include sc(12px, #888888);
    }
    .tips-body {
      margin-top: 8px;
      p {
        @include sc(11px, #888888, left center);
        margin-right: -35px;
        line-height: 19px;
      }
    }
  }
  .refund-comfirm {
    padding: 0 25px 15px 10px;
    margin-top: 25px;
    .button {
      width: 100%;
      font-size: 18px;
      @include button($margin: 0, $radius: 2px, $spacing: 2px);
      & + .button {
        margin-top: 25px;
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
