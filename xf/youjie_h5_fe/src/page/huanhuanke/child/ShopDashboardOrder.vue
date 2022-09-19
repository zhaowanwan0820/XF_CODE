<!-- 小店看板—订单数据 -->
<template>
  <div class="dashboard-wrapper">
    <shop-dashboard-title
      :icon="require('../../../assets/image/hh-icon/f0-shop/icon-order.png')"
      :title="'订单数据'"
      :subtitle="'全部订单明细'"
      :path="{ name: 'HuankeOrder', params: { order: ORDER_STATUS.ALL } }"
    ></shop-dashboard-title>
    <div class="dashboard-body">
      <div class="toggle-bar">
        <template v-for="item in CHART_STATUS">
          <div class="toggle-bar-item" :class="{ active: item.id == defailtItem.id }" @click="changeIndex(item)">
            {{ item.name }}
          </div>
        </template>
      </div>
      <div class="toggle-body">
        <div class="left">
          <div class="toggle-body-item">
            <div class="title">付款人数</div>
            <div class="content">{{ pay_user_num }}</div>
          </div>
          <div class="toggle-body-item">
            <div class="title">销售金额</div>
            <div class="content">
              <div class="unit">￥</div>
              <div class="count">{{ mlm_order_money }}</div>
            </div>
          </div>
        </div>
        <div class="right">
          <div class="toggle-body-item">
            <div class="title">付款笔数</div>
            <div class="content">{{ pay_item_num }}</div>
          </div>
          <div class="toggle-body-item">
            <div class="title">预计收益</div>
            <div class="content">
              <div class="unit">￥</div>
              <div class="count">{{ mlm_order_rebate }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ENUM } from '../../../const/enum'
import { CHART_STATUS } from '../static.js'
import ShopDashboardTitle from './ShopDashboardTitle'
export default {
  data() {
    return {
      CHART_STATUS,
      ORDER_STATUS: ENUM.ORDER_STATUS,
      defailtItem: CHART_STATUS[1]
    }
  },

  props: {
    order_info: {
      type: Object,
      default() {
        return {
          today: {
            pay_user_num: '--',
            pay_item_num: '--',
            mlm_order_money: '--',
            mlm_order_rebate: '--'
          },
          yesterday: {
            pay_user_num: '--',
            pay_item_num: '--',
            mlm_order_money: '--',
            mlm_order_rebate: '--'
          },
          all: {
            pay_user_num: '--',
            pay_item_num: '--',
            mlm_order_money: '--',
            mlm_order_rebate: '--'
          }
        }
      }
    }
  },

  computed: {
    pay_user_num() {
      return this.order_info[this.defailtItem.e_name].pay_user_num
    },
    pay_item_num() {
      return this.order_info[this.defailtItem.e_name].pay_item_num
    },
    mlm_order_money() {
      return this.order_info[this.defailtItem.e_name].mlm_order_money
    },
    mlm_order_rebate() {
      return this.order_info[this.defailtItem.e_name].mlm_order_rebate
    }
  },

  components: {
    ShopDashboardTitle
  },

  methods: {
    changeIndex(item) {
      this.defailtItem = { ...item }
    }
  }
}
</script>

<style lang="scss" scoped>
.dashboard-wrapper {
  .dashboard-body {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px 0 0;
    .toggle-bar {
      display: flex;
      box-sizing: border-box;
      border: 1px solid #772508;
      height: 26px;
      align-items: center;
      .toggle-bar-item {
        width: 50px;
        line-height: 26px;
        font-size: 12px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #772508;
        text-align: center;
        &.active {
          background-color: #772508;
          color: #ffffff;
        }
        & + div {
          border-left: 1px solid #772508;
        }
      }
    }
    .toggle-body {
      align-self: stretch;
      display: flex;
      justify-content: center;
      margin-top: 25px;
      .right {
        margin-left: 98px;
      }
      .toggle-body-item {
        margin-bottom: 20px;
      }
      .title {
        height: 17px;
        font-size: 12px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #999999;
        line-height: 17px;
      }
      .content {
        font-size: 16px;
        font-family: DINAlternate-Bold;
        font-weight: bold;
        color: #404040;
        line-height: 19px;
        display: flex;
        margin-top: 5px;
        align-items: baseline;
        .unit {
          font-weight: 400;
          @include sc(11px, #404040, center bottom);
        }
      }
    }
  }
}
</style>
