<!-- OrderNav.vue -->
<template>
  <div class="order-wrapepr js-order-list">
    <!-- header -->
    <div class="order-header">
      <ul>
        <li
          class="item"
          v-for="item in navList"
          v-bind:key="item.id"
          v-bind:class="{ active: orderStatus == item.id }"
          v-on:click="setOrderNavActive(item.id)"
        >
          <span class="nav-title">{{ item.name }}</span>
          <label class="number-red" v-if="unpay_count && item.id === 0">
            <span class="val">{{ unpay_count }}</span>
          </label>
          <div class="line"></div>
        </li>
      </ul>
    </div>
    <!-- body -->
    <!-- 无限加载滚动列表 -->
    <!-- <div > -->
    <div
      v-infinite-scroll="getMore"
      infinite-scroll-disabled="loading"
      infinite-scroll-distance="10"
      class="order-body scroll-container-keepAlive"
    >
      <div v-if="orderList.length">
        <div class="list" v-for="(item, index) in orderList" :key="item.id">
          <div class="title">
            <div class="title-left">订单编号：{{ item.sn }}</div>
            <div class="title-right" v-if="item.order_status && item.order_status == 3">
              <span>已过期</span>
            </div>
            <div class="title-right" v-else>
              <span>{{ getOrderStatusBy(item) }}</span>
            </div>
          </div>
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
          <div class="price" v-if="item.status < 5 && item.order_status != 4">
            <div class="price-item">
              <label>返佣金额:</label>
              <!-- <i><img src="../../../assets/image/hh-icon/b0-home/money-icon.png" alt=""/></i> -->
              <i class="marle">￥</i>
              <span>{{ utils.formatFloat(getRebateBack(item.rebate, item.change_rebate)) }}</span>
            </div>
            <!--
            <div class="price-item" v-if="item.status == 0 && isInternal">
              <label>可变现金额:</label>
              <i class="marle">￥</i>
              <span>{{ utils.formatFloat(item.change_rebate) }}</span>
            </div>
            -->
            <div class="price-item" v-if="(item.status == 1 || item.status == 4) && isInternal">
              <label>已变现金额:</label>
              <i class="marle">￥</i>
              <span>{{ utils.formatFloat(item.change_rebate) }}</span>
            </div>
          </div>
          <!--
          <div class="order-list-opratio" v-if="item.status == 0 && item.change_rebate && isInternal">
            <div class="btn" v-if="item.status < 5 && item.order_status != 4">
              <button class="buttonright" v-on:click="payment(item)">积分变现</button>
            </div>
          </div>
          -->
        </div>

        <div class="loading-wrapper">
          <p v-if="!isMore">没有更多了</p>
        </div>
      </div>
      <div v-if="orderList.length <= 0 && !isMore">
        <div class="order-air">
          <img src="../../../assets/image/hh-icon/mlm/content-empty@2x.png" />
          <p>您的{{ orderTxt }}订单为空</p>
          <button class="button" v-on:click="goVisit">
            随便逛逛
          </button>
        </div>
        <recommend-list :params="recommendParams"></recommend-list>
      </div>
    </div>
    <!-- </div> -->
  </div>
</template>

<script>
import { ORDERNAV0, ORDERNAV1, ORDERSTATUS0, ORDERSTATUS1 } from '../static'
import { Toast } from 'mint-ui'

import { ENUM } from '../../../const/enum'
import { huanAccount, huanOrderList } from '../../../api/huanhuanke'
import { Indicator, MessageBox, Popup } from 'mint-ui'
import { PopupShareFriendPay } from '../../../components/common'
import RecommendList from '../../recommend/RecommendList'

// import OrderNav from './OrderNav'
import { mapState, mapMutations } from 'vuex'
export default {
  name: 'page-navbar',
  data() {
    return {
      ORDERNAV0: ORDERNAV0,
      ORDERNAV1: ORDERNAV1,
      ORDERSTATUS0: ORDERSTATUS0,
      ORDERSTATUS1: ORDERSTATUS1,
      orderListParams: { page: 0, per_page: 10, status: '' },
      orderList: [],
      loading: false,
      isMore: true,
      message: '',
      checkState: '',
      id: -1,
      currentIndex: -1,
      unpay_count: 0, //未支付订单数

      recommendParams: {} // 猜你喜欢额外的请求参数
    }
  },
  // watch: {
  //   $route(to, from) {
  //     if (to.name == 'HuankeOrder' && from.name != 'HuankeOrderDetail') {
  //       this.setOrderNavActive(this.orderStatus)
  //     }
  //   }
  // },
  created() {
    this.getUrlParams()
    this.getHuanMessageCount()
  },
  components: {
    RecommendList
  },
  computed: {
    ...mapState({
      isInternal: state => state.mlm.isInternal,
      orderStatus: state => state.order.orderStatus,
      orderItem: state => state.order.orderItem
    }),
    orderTxt() {
      let item = this.navList.filter(val => {
        return val.id == this.orderStatus
      })
      return item[0].name
    },
    navList() {
      return this.isInternal ? this.ORDERNAV1 : this.ORDERNAV0
    }
  },
  methods: {
    ...mapMutations({
      changeStatus: 'changeStatus',
      changeItem: 'changeItem'
    }),
    getUrlParams() {
      let status = this.$route.params.order
      let index = this.orderStatus
      if (status) {
        this.changeStatus(status)
      } else {
        this.changeStatus(index)
      }
    },
    // 去订单详情
    goOrderDetail(id) {
      this.$router.push({ name: 'HuankeOrderDetail', query: { id: id } })
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
      huanOrderList(data.status, data.page, data.per_page).then(
        res => {
          if (ispush) {
            this.orderList = [...this.orderList, ...res.list]
          } else {
            this.orderList = res.list
          }
          this.isMore = res.paged.more == 1 ? true : false
          // this.loading = true;
          Indicator.close()
        },
        error => {
          console.log(error)
        }
      )
    },

    payDetail(id) {
      this.$router.push({ name: 'HuanKeOrderPayDetail', query: { id: id } })
    },

    // 根据订单状态值获取对应的状态
    getOrderStatusBy(order) {
      if (order.order_status == 4) {
        return '已取消'
      } else {
        let data = this.isInternal ? this.ORDERSTATUS1 : this.ORDERSTATUS0
        for (let i = 0, len = data.length; i <= len - 1; i++) {
          if (data[i].id == order.status) {
            return data[i].name
          }
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
    // 获取未支付订单数
    getHuanMessageCount() {
      huanAccount(ENUM.HUANKE_STATUS.UNPAY).then(
        res => {
          this.unpay_count = res.unpay_count
        },
        error => {
          console.log(error)
        }
      )
    },
    getRebateBack(val1, val2) {
      return val1 - val2 > 0 ? val1 - val2 : 0
    },
    // 继续支付
    payment(order) {
      // 积分变现已暂停
      this.$router.push({ name: 'HuanKeConfirm', params: { id: order.id } })
    },
    // 随便逛逛
    goVisit() {
      this.$router.push('/home')
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
        position: relative;
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
        .nav-title {
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
          color: #562f21;
          .line {
            background: #562f21;
          }
        }
        .number-red {
          min-width: 13px;
          height: 13px;
          line-height: 13px;
          background: #ff3950;
          border-radius: 7px;
          text-align: center;
          font-weight: normal;
          position: absolute;
          top: 9px;
          left: 43px;
          .val {
            display: block;
            margin: 0 3.25px;
            @include sc(10px, #fff);
          }
        }
      }
    }
  }
  .order-body {
    width: 100%;
    flex: 1;
    overflow: auto;

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
        }
        .title-right span {
          color: #552e20;
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
            display: -webkit-box;
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
        flex-direction: column;
        // justify-content: flex-end;
        align-items: flex-end;
        padding: 12px 0 10px;
        margin: 0 15px;
        @include thin-border(#f4f4f4);
        label {
          font-size: 14px;
          color: #404040;
          line-height: 16px;
        }
        i {
          @include sc(11px, #772508);
          font-style: normal;
          line-height: 14px;
          font-weight: 600;
          margin-bottom: -0.9%;
          img {
            width: 11px;
            height: 11px;
            margin-left: 5px;
            margin-right: 2px;
          }
        }
        span {
          display: inline-block;
          font-size: 16px;
          line-height: 19px;
          color: #772508;
          font-weight: bold;
        }
        .price-item {
          margin-bottom: 7px;
          display: flex;
          justify-content: center;
          align-items: center;
          &:last-of-type {
            margin-bottom: 0;
          }
          .marle {
            margin-left: 6px;
          }
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
          color: #772508;
          margin-left: 15px;
          background: #fff;
          border: 1px solid #772508;
        }
        .buttonright {
          color: #fff;
          background: #772508;
        }
      }
    }
    .loading-wrapper {
      text-align: center;
      p {
        color: #b5b6b6;
        font-size: 13px;
        font-weight: 'Regular';
        margin: 10px auto;
      }
    }
  }
  .order-air {
    width: 100%;
    background: #fff;
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    img {
      width: 135px;
      height: 135px;
      padding: 64px 0 11px;
    }
    p {
      font-size: 18px;
      font-weight: 500;
      color: #666;
      line-height: 25px;
    }
    .button {
      width: 140px;
      height: 36px;
      border-radius: 2px;
      border: 1px solid #552e20;
      background: #fff;
      margin: 41px 0 71px;

      font-size: 14px;
      font-weight: 400;
      color: #552e20;
      line-height: 36px;
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
          color: red;
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
