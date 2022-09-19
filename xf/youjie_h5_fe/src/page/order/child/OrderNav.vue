<!-- OrderNav.vue -->
<template>
  <div class="order-wrapepr js-order-list">
    <!-- header -->
    <div class="order-header">
      <ul>
        <li
          class="item"
          v-for="item in ORDERNAV"
          v-bind:key="item.id"
          v-bind:class="{ active: orderStatus == item.id }"
          v-on:click="setOrderNavActive(item.id)"
        >
          <span>{{ item.name }}</span>
          <div class="line"></div>
        </li>
      </ul>
    </div>
    <!-- body -->
    <!-- 无限加载滚动列表 -->
    <!-- <div > -->
    <div class="order-body scroll-container-keepAlive">
      <div v-infinite-scroll="getMore" infinite-scroll-disabled="loading" infinite-scroll-distance="10">
        <template v-if="orderList.length">
          <div class="list" v-for="(item, index) in orderList" :key="item.id">
            <!-- <h3 class="title" v-if="item.status != 4">{{ getOrderStatusBy(item.status) }}</h3> -->
            <div class="title">
              <!-- <div class="title-left">订单编号：{{ item.sn }}</div> -->
              <div class="title-left" @click="goSupplier(item.supplier.sn)">
                <img class="shop-icon" src="../../../assets/image/change-icon/shop-icon@2x.png" alt="" />
                <span>{{ item.supplier.shop_name }}</span>
                <img class="icon-tip" src="../../../assets/image/hh-icon/supplier/icon-tip.png" alt="" />
              </div>
              <div class="title-right">{{ getOrderStatusBy(item) }}</div>
            </div>
            <!-- <h3 v-if="item.status == 4">
              <img src="../../../assets/image/change-icon/e3_seal@2x.png" />
            </h3>-->
            <div class="order-image" v-if="item.goods.length" @click="goOrderDetail(item.id)">
              <div class="order-image-item">
                <template v-for="(image, ix) in item.goods">
                  <img
                    :src="image.thumb"
                    data-src="../../../assets/image/change-icon/default_image_02@2x.png"
                    :key="ix"
                    v-if="image.thumb"
                  />
                  <img src="../../../assets/image/change-icon/default_image_02@2x.png" :key="ix" v-else />
                </template>
              </div>
              <div class="order-detail">
                <div class="order-title">{{ item.goods[0].name }}</div>
                <div class="order-info">
                  <label>{{ item.goods[0].property }}</label>
                </div>
                <p class="order-price">
                  <label>
                    <span class="price-unit">￥</span>
                    <span>{{ utils.formatFloat(item.goods[0].product_price) }}</span>
                  </label>
                  <span class="count">x{{ item.goods[0].total_amount }}</span>
                </p>
              </div>
            </div>
            <div class="price" v-if="item.goods.length">
              <label>合计:</label>
              <i>￥</i>
              <span>{{ item.total }}</span>
            </div>
            <div class="order-list-opratio">
              <!-- 待付款 -->
              <div class="btn" v-if="item.status == 0">
                <template>
                  <button
                    v-if="!user.suppliers_id && !(item.instalment_id && item.instalment_pay_status == 2)"
                    v-on:click="cancel(item.id, index)"
                  >
                    取消订单
                  </button>
                  <button
                    class="buttonright"
                    v-on:click="sharePay(item.share_sn, item.goods[0].thumb)"
                    v-if="item.share_sn && item.pay_status == 1"
                  >
                    好友支付
                  </button>
                  <button class="buttonright" v-on:click="payment(item)" v-else>
                    {{ item.instalment_id && item.instalment_pay_status == 2 ? '支付下期' : '继续支付' }}
                  </button>
                  <button
                    class="buttonright"
                    v-if="item.instalment_id && item.instalment_pay_status == 2"
                    @click="payAll(item)"
                  >
                    全部支付
                  </button>
                </template>
              </div>
              <!-- 待发货 -->
              <div class="btn" v-if="item.status == 1 ? '' : checkState"></div>
              <!-- 发货中 -->
              <div class="btn" v-if="item.status == 2">
                <button v-on:click="track(item.id)" v-if="!item.instalment_id">查看物流</button>
                <button class="buttonright" v-on:click="confirm(item, index)">
                  确认收货
                </button>
              </div>
              <!-- 待评价 -->
              <div class="btn" v-if="item.status == 3">
                <button v-on:click="track(item.id)" v-if="!item.instalment_id">查看物流</button>
                <button v-on:click="goComment(item)">评价商品</button>
              </div>
              <!-- 已完成 -->
              <div class="btn" v-if="item.status == 4 && !item.instalment_id">
                <button v-on:click="track(item.id)">查看物流</button>
                <button v-on:click="goComment(item, true)" v-if="!item.is_comment_append">追加评价</button>
              </div>
              <!-- 配货中 -->
              <div class="btn" v-if="item.status == 6">
                <button v-on:click="track(item.id)" v-if="!item.instalment_id">查看物流</button>
                <button class="buttonright" v-on:click="confirm(item, index)">
                  确认收货
                </button>
              </div>
            </div>
          </div>

          <mt-popup v-model="popupVisible" position="bottom" class="mint-popup">
            <div class="cancels">
              <div class="cancelInfo">
                <span class="cancel" v-on:click="cancelInfo">取消</span>
                <span class="success" v-on:click="complete">完成</span>
              </div>
              <div class="reason">
                <p
                  v-for="(item, list) in reasonList"
                  :key="list"
                  v-on:click="getReasonItem(item)"
                  :class="{ red: reasonId == item.id }"
                >
                  {{ item.name }}
                </p>
              </div>
            </div>
          </mt-popup>

          <popup-share-friend-pay
            ref="shareFriendPayPop"
            v-model="friendPayFlag"
            :options="share_option"
            :sharePayInfo="share_pay_info"
          ></popup-share-friend-pay>

          <!-- <div v-if="!isMore" class="loading-wrapper">没有更多了</div> -->
        </template>
      </div>
      <div class="order-air" v-if="orderList.length <= 0 && !isMore">
        <img src="../../../assets/image/hh-icon/l0-list-icon/order-list.png" />
        <p>您的订单为空</p>
        <gk-button class="button" type="primary-secondary-white" v-on:click="goVisit">随便逛逛</gk-button>
      </div>
      <recommend-list v-if="!isMore && orderStatus != ENUM.ORDER_STATUS.ALL" :params="recommendParams"></recommend-list>
    </div>
    <!-- </div> -->
    <v-back-top v-if="orderList.length > 0 || !isMore" :target="target"></v-back-top>
  </div>
</template>

<script>
import { ORDERSTATUS, ORDERNAV, ORDEREFFRCTTIME } from '../static'
import { Toast } from 'mint-ui'

import { orderList, orderCancel, orderReasonList, orderConfirm } from '../../../api/order' //订单列表  //取消订单 //获取退货原因 //确认收货 //再次购买
import { Indicator, MessageBox, Popup } from 'mint-ui'
import { PopupShareFriendPay } from '../../../components/common'
import RecommendList from '../../recommend/RecommendList'
import { ENUM } from '../../../const/enum'
import BackTop from '../../../components/common/BackTop'
import { orderGet } from '../../../api/order.js'
import { mapState, mapMutations } from 'vuex'

export default {
  name: 'page-navbar',
  data() {
    return {
      ENUM,
      ORDERSTATUS: ORDERSTATUS,
      ORDERNAV: ORDERNAV,
      orderListParams: { page: 0, per_page: 10, status: '' },
      orderList: [],
      loading: false,
      orderCancel: [],
      isMore: true,
      popupVisible: false,
      reasonList: [],
      success: [],
      reasonId: '',
      message: '',
      checkState: '',
      id: -1,
      currentIndex: -1,

      share_pay_info: {
        sn: undefined,
        need_money: 0,
        need_surplus: 0,
        thumb: ''
      },
      friendPayFlag: false,
      share_sn: '',
      share_option: ['WechatSession'],

      target: null, // 滚动dom，回到顶部用
      recommendParams: {} // 猜你喜欢额外的请求参数
    }
  },
  watch: {
    popupVisible() {
      // hide -> visible
      if (this.popupVisible) {
        this.reasonId = ''
      }
    },
    $route(to, from) {
      if (to.name == 'order' && from.name != 'orderDetail') {
        this.setOrderNavActive(this.orderStatus)
      }
    }
  },
  created() {
    this.getUrlParams()
    this.orderReasonList()
  },
  mounted() {
    this.$nextTick(() => {
      this.target = document.querySelector('.order-body')
    })
  },
  components: {
    RecommendList,
    'v-back-top': BackTop
  },
  computed: {
    ...mapState({
      user: state => state.auth.user,
      orderStatus: state => state.order.orderStatus,
      orderItem: state => state.order.orderItem
    })
  },
  methods: {
    ...mapMutations({
      changeStatus: 'changeStatus',
      changeItem: 'changeItem'
    }),
    getUrlParams() {
      let cur_index = this.$route.params.order
      if (typeof cur_index !== 'undefined') {
        this.changeStatus(cur_index)
      }
    },
    // 去订单详情
    goOrderDetail(id) {
      this.$router.push({ name: 'orderDetail', query: { id: id } })
    },

    setOrderNavActive(index) {
      this.orderListParams.page = 1
      this.orderList = []
      // 重置无限滚动加载 组件
      this.isMore = true
      this.loading = false

      this.changeStatus(index)
      this.getOrderList()
    },

    // 获取订单列表
    getOrderList(ispush) {
      Indicator.open()
      let data = this.orderListParams
      data.status = this.orderStatus
      orderList(data.page, data.per_page, data.status).then(res => {
        if (ispush) {
          this.orderList = [...this.orderList, ...res.list]
        } else {
          this.orderList = res.list
        }
        this.isMore = res.paged.more == 1 ? true : false
        // this.loading = true;
        Indicator.close()
      })
    },

    payDetail(id) {
      this.$router.push({ name: 'orderPayDetail', query: { id: id } })
    },

    // 跳转商家
    goSupplier(id) {
      this.$router.push({ name: 'Supplier', query: { id: id } })
    },

    // 根据订单状态值获取对应的状态
    getOrderStatusBy(item) {
      const status = item.status
      let data = this.ORDERSTATUS
      for (let i = 0, len = data.length; i <= len - 1; i++) {
        if (data[i].id == status) {
          if (status == 0) {
            // 如果是分期订单 且 已经付过一部分了 就是 “部分支付”
            if (item.instalment_id && item.instalment_pay_status > 0) {
              return '部分支付'
            }
          }
          return data[i].name
        }
      }
    },
    //  加载更多数据
    getMore() {
      this.loading = true
      this.orderListParams.page = ++this.orderListParams.page
      if (this.isMore) {
        this.loading = false
        this.getOrderList(true)
      }
    },

    // 取消订单
    cancel(id, index) {
      ;(this.id = id), (this.currentIndex = index), (this.popupVisible = true)
      // this.stop()
    },
    cancelInfo() {
      this.popupVisible = false
      // this.move()
    },
    complete(id, index) {
      if (!this.reasonId) {
        Toast('请选择取消原因')
        return
      }

      let is_exchanged_debt = this.orderList[this.currentIndex].debt_purchase
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
              this.getordersuccess(this.id)
            }
          },
          cancel => {
            this.popupVisible = false
          }
        )
      } else {
        this.popupVisible = false
        this.getordersuccess(this.id)
      }
      // this.move()
    },

    // 查看物流
    track(id) {
      this.$router.push({ name: 'orderTrack', params: { orderTrack: id } })
    },
    // 继续支付
    async payment(order) {
      // 获取订单详情
      Indicator.open()
      const detail = await orderGet(order.id, false)
      Indicator.close()

      // 订单超时失效，分期订单不存在失效问题（支付了首期的）
      if (!(order.instalment_pay_status == 2 && order.instalment_id)) {
        if (Math.floor(new Date().getTime() / 1000) >= detail.canceled_at) {
          MessageBox({
            title: '',
            message: '该订单已失效',
            showCancelButton: true,
            cancelButtonText: '知道了',
            cancelButtonClass: 'cancel-button',
            confirmButtonClass: 'confirm-button-red',
            confirmButtonText: '再去逛逛'
          }).then(action => {
            if (action == 'confirm') {
              this.$router.push({ name: 'home' })
            }
          })
          return
        }
      }
      // 分期订单-首期
      if (order.instalment_pay_status != 2 && order.instalment_id) {
        this.$router.push({
          name: 'paymentHuan',
          query: {
            order: order.instalment_id,
            parent_order: '',
            total: order.total,
            isInstalment: order && order.instalment_id ? 1 : 0
          }
        })
        return
      }
      if (order.instalment_pay_status == 2 && order.next_instalment_id != order.instalment_id) {
        this.$router.push({
          name: 'paymentHuan',
          query: {
            order: order.next_instalment_id,
            parent_order: '',
            total: order.total,
            isInstalment: order && order.instalment_id ? 1 : 0
          }
        })
        return
      }
      // 正常
      this.$router.push({
        name: 'payment',
        query: {
          order: JSON.stringify([order.id]),
          total: detail.hhpay.need_money,
          isInstalment: 0,
          canceled_at: detail.canceled_at
        }
      })
    },
    // 全部支付，针对分期订单
    payAll(order) {
      this.$router.push({
        name: 'paymentHuan',
        query: {
          order: '',
          parent_order: order.id,
          total: order.total,
          isInstalment: 1
        }
      })
    },
    // 随便逛逛
    goVisit() {
      this.$router.push('/home')
    },
    // 确认收货
    confirm(item, index) {
      MessageBox.confirm('是否确认收货？', '确认收货').then(action => {
        this.changeItem(item)
        this.orderConfirms(item.id, index)
        this.$router.push({ name: 'orderTrade', query: { id: item.id } })
      })
    },
    // 获取确认收货数据
    orderConfirms(id, index) {
      orderConfirm(id).then(res => {
        this.orderList[index] = res
      })
    },

    // 晒单评价
    goComment(item, flag = false) {
      this.changeItem(item)
      this.$router.push({ name: 'orderComment', query: { order: item.id, isReview: flag } })
    },

    // 获取退货原因数据
    orderReasonList() {
      orderReasonList().then(res => {
        this.reasonList = Object.assign([], this.reasonList, res)
      })
    },
    // 获取取消订单数据
    getordersuccess(id) {
      orderCancel(id, this.reasonId).then(res => {
        this.orderList.splice(this.currentIndex, 1)
      })
    },
    //
    getReasonItem(item) {
      this.reasonId = item.id
    },
    sharePay(sn, thumb) {
      this.share_pay_info.sn = sn
      this.share_pay_info.thumb = thumb
      this.$refs.shareFriendPayPop.open()
    }
  }
}
</script>

<style lang="scss" scoped>
.order-wrapepr {
  width: 100%;
  flex-grow: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;

  .order-header {
    height: 40px;
    width: 100%;
    @include thin-border(#f4f4f4, 0);

    ul {
      list-style: none;
      width: auto;
      display: flex;
      justify-content: space-around;
      align-content: center;
      align-items: center;
      height: 100%;
      background: rgba(255, 255, 255, 1);
      li {
        font-size: 14px;
        font-family: 'PingFangSC-Regular';
        color: #666;
        height: 100%;
        text-align: center;
        line-height: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        span {
          display: block;
          height: 33px;
        }
        .line {
          width: 67%;
          height: 2px;
          background: rgba(0, 0, 0, 0);
          margin-top: 3px;
        }
        &.active {
          color: $markColor;
          .line {
            background: $markColor;
          }
        }
      }
    }
  }
  .order-body {
    width: 100%;
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;

    display: flex;
    flex-direction: column;
    align-items: stretch;

    .list {
      width: 100%;
      margin-top: 10px;
      background: #fff;
      overflow: hidden;
      .title {
        display: flex;
        justify-content: space-between;
        padding: 11px 15px 9px;
        font-size: 14px;
        @include thin-border(#f4f4f4, 15px);
        .title-left {
          color: #888;
          line-height: 20px;
          display: flex;
          align-items: center;
          img.shop-icon {
            width: 14px;
          }
          span {
            margin-left: 10px;
            font-size: 13px;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
            line-height: 15px;
          }
          img.icon-tip {
            width: 3px;
            margin-left: 9px;
            position: relative;
            top: 1px;
          }
        }
        .title-right {
          color: $markColor;
          line-height: 20px;
        }
      }
      .order-image {
        display: flex;
        justify-content: flex-start;
        padding: 12px 0;
        margin: 0 15px;
        @include thin-border(#f4f4f4);

        .order-image-item {
          width: 85px;
          height: 85px;
          margin-right: 12px;
        }
        img {
          width: 85px;
          height: 85px;
          border-radius: 2px;
        }
        div.order-detail {
          flex: 1;
          .order-title {
            font-size: 13px;
            line-height: 16px;
            height: 32px;
            color: #404040;
            margin-bottom: 4px;

            // overflow: hidden;
            // text-overflow: ellipsis;
            // white-space: nowrap;
            width: 210px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
          }
          .order-info {
            line-height: 18px;
            label {
              display: inline-block;
              @include sc(10px, #888);
              line-height: 18px;
              margin-left: -1%;
            }
          }
          .order-price {
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            label {
              display: flex;
              justify-content: center;
              align-items: center;
              span {
                font-size: 14px;
                line-height: 12px;
                font-weight: bold;
                color: #404040;
                &.price-unit {
                  @include sc(10px, #404040);
                  font-weight: bold;
                  margin-top: 3%;
                }
              }
            }
            .count {
              color: $subbaseColor;
              font-size: 12px;
              line-height: 12px;
            }
          }
        }
      }
      .price {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 12px 0 10px;
        margin: 0 15px;
        @include thin-border(#f4f4f4);
        label {
          font-size: 14px;
          color: #404040;
          line-height: 16px;
          margin-right: 4px;
        }
        i {
          @include sc(11px, $markColor);
          font-style: normal;
          line-height: 14px;
          font-weight: 600;
          margin-bottom: -0.9%;
        }
        span {
          font-size: 16px;
          line-height: 13px;
          color: $markColor;
          font-weight: bold;
        }
      }
      .btn {
        padding: 15px;
        display: flex;
        justify-content: flex-end;
        button {
          width: 84px;
          height: 30px;
          border-radius: 2px;
          font-size: 13px;
          line-height: 30px;
          color: $primaryColor;
          margin-left: 15px;
          background: #fff;
          border: 1px solid $primaryColor;
        }
        .buttonright {
          color: #fff;
          background: $primaryColor;
        }
      }
    }
    .loading-wrapper {
      text-align: center;
      color: #b5b6b6;
      font-size: 13px;
      font-weight: 'Regular';
      margin: 10px auto;
    }
  }
  .order-air {
    flex: 1 0 0;
    width: 100%;
    vertical-align: middle;
    text-align: center;
    background-color: #ffffff;
    padding: 30px 0 70px;
    text-align: center;
    img {
      width: 135px;
      height: 135px;
    }
    p {
      margin-top: 10px;
      font-size: 18px;
      font-weight: 500;
      color: rgba(102, 102, 102, 1);
      line-height: 25px;
    }
    .button {
      @include button($margin: 0, $radius: 2px, $spacing: 2px);
      width: 140px;
      height: 36px;
      font-size: 14px;
      font-family: 'PingFangSC-Regular';
      margin-top: 40px;
    }
    label {
      font-size: 16px;
      color: #fff;
      display: inline-block;
      vertical-align: middle;
      height: 16px;
      line-height: 16px;
    }
  }
  .mint-popup {
    width: 100%;
    height: 235px;
  }
  .cancels {
    height: 100%;
    display: flex;
    flex-direction: column;
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
      flex: 1;
      overflow-y: auto;
      margin-top: 10px;
      p {
        height: 16px;
        line-height: 16px;
        text-align: center;
        padding: 10px;
        &.red {
          color: $markColor;
        }
      }
    }
  }
  .show-product-model {
    background: rgba(0, 0, 0, 0.5);
    overflow: hidden;
    height: 100%;
    position: fixed;
    top: 100px;
    bottom: 0;
    left: 0;
    right: 0;
  }
}
</style>
<style>
.cancel-order-tips-para {
  color: #404040;
  margin-top: 10px;
}
</style>
