<!-- 小店看板—佣金结算 -->
<template>
  <div class="dashboard-wrapper">
    <shop-dashboard-title
      :icon="require('../../../assets/image/hh-icon/f0-shop/icon-brokerage.png')"
      :title="'佣金结算'"
      :subtitle="'我的账户'"
      :path="{ name: 'HuankeAccount' }"
    ></shop-dashboard-title>
    <div class="dashboard-body">
      <div class="title">
        账户佣金
        <span @click="visibleToggle">
          <img v-if="countVisible" src="../../../assets/image/hh-icon/f0-shop/icon-visible.png" alt="" />
          <img v-else src="../../../assets/image/hh-icon/f0-shop/icon-hidden.png" alt="" />
        </span>
      </div>
      <div class="title-count">
        <template v-if="countVisible">
          <div class="unit">￥</div>
          <div class="count">{{ utils.formatMoney(money.account_money) }}</div>
        </template>
        <template v-else>
          ****
        </template>
      </div>
      <div class="data-wrapper">
        <div class="data-wrapper-item">
          <div class="title">可提现</div>
          <div class="content">
            <div class="unit">￥</div>
            <div class="count">{{ utils.formatMoney(money.available_money) }}</div>
          </div>
        </div>
        <div class="data-wrapper-item">
          <div class="title">待结算</div>
          <div class="content">
            <div class="unit">￥</div>
            <div class="count">{{ utils.formatMoney(money.frozen_money) }}</div>
          </div>
        </div>
        <div class="data-wrapper-item">
          <div class="title">累计提现</div>
          <div class="content">
            <div class="unit">￥</div>
            <div class="count">{{ utils.formatMoney(money.withdraw_money) }}</div>
          </div>
        </div>
        <div class="data-wrapper-item">
          <div class="title">累计获得</div>
          <div class="content">
            <div class="unit">￥</div>
            <div class="count">{{ utils.formatMoney(money.all_get_money) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ShopDashboardTitle from './ShopDashboardTitle'
export default {
  data() {
    return {
      countVisible: true
    }
  },

  props: {
    money: {
      type: Object,
      default() {
        return {
          account_money: '--',
          available_money: '--',
          frozen_money: '--',
          withdraw_money: '--',
          all_get_money: '--'
        }
      }
    }
  },

  components: {
    ShopDashboardTitle
  },

  created() {},

  methods: {
    visibleToggle() {
      this.countVisible = !this.countVisible
    }
  }
}
</script>

<style lang="scss" scoped>
.dashboard-wrapper {
  .dashboard-body {
    padding: 15px 0 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    .title {
      position: relative;
      font-size: 14px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: #404040;
      line-height: 20px;
      span {
        position: absolute;
        width: 20px;
        height: 20px;
        right: -30px;
        top: 0px;
        img {
          width: 100%;
        }
      }
    }
    .title-count {
      margin-top: 10px;
      display: flex;
      align-items: baseline;
      font-size: 26px;
      font-family: DINAlternate-Bold;
      font-weight: bold;
      color: #772508;
      letter-spacing: 1px;
      .unit {
        font-size: 20px;
      }
      .count {
        font-size: 26px;
      }
    }
    .data-wrapper {
      align-self: stretch;
      display: flex;
      justify-content: space-around;
      margin-top: 25px;
      .data-wrapper-item {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      .title {
        font-size: 12px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #999999;
        line-height: 17px;
      }
      .content {
        display: flex;
        align-items: baseline;
        margin-top: 10px;
      }
      .unit {
        @include sc(11px, #404040, right bottom);
      }
      .count {
        font-size: 16px;
        font-family: DINAlternate-Bold;
        font-weight: bold;
        color: #404040;
        line-height: 19px;
      }
    }
  }
}
</style>
