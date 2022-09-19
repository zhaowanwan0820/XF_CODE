<template>
  <div class="token-wrapper" v-show="huanTxt">
    <div class="token-item">
      <div class="left">
        <span class="token-name">积分：</span>
        <span class="token">{{ huanTxt }}</span>
      </div>
      <label>{{ utils.formatFloat(token) }}可用</label>
    </div>
  </div>
</template>

<script>
import { ENUM } from '../../../const/enum'
import { mapState } from 'vuex'
import { goExchange } from '../util'

export default {
  name: 'TokenItem',
  data() {
    return {}
  },
  props: {
    price: Object
  },
  computed: {
    ...mapState({
      cartGoods: state => state.checkout.cartGoods,
      authStatus: state => state.itouzi.authStatus,
      platform: state => state.auth.platform,
      currentBond: state => state.bond.currentBond,
      currentBalance: state => state.balance.currentBalance,
      user: state => state.auth.user
    }),
    use_surplus() {
      // surplus_pay token_pay 当前订单 积分or积分 可支付金额（不能同时大于0）
      return this.price && this.price.use_surplus ? this.price.use_surplus : {}
    },
    token() {
      // 判断逻辑统一处理为 支持积分支付展示积分 否则（默认）展示为积分
      return this.use_surplus.surplus_limit > 0 && this.use_surplus.surplus_pay > 0
        ? this.use_surplus.surplus_pay
        : this.use_surplus.token_pay
    },
    huanTxt() {
      let huantext = ''
      // 直通车商品不展示积分部分
      if (!this.cartGoods[0].train_sn && (this.use_surplus.surplus_limit > 0 || this.use_surplus.token_limit > 0)) {
        // 支持积分支付 积分展示为积分 否则默认为积分
        if (this.use_surplus.surplus_limit > 0 && this.use_surplus.surplus_pay > 0) {
          huantext = '积分'
        } else {
          huantext = '积分'
        }
      }
      return huantext
    }
  }
}
</script>

<style lang="scss" scoped>
.token-wrapper {
  padding: 14px 15px;
  background-color: #fff;
  @include thin-border(#f4f4f4, 15px);
  .token-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    .left {
      font-size: 0;
      span {
        font-size: 13px;
        font-weight: 300;
        color: #404040;
        line-height: 18px;
      }
      .token {
        margin-left: 5px;
        color: #666;
      }
    }
    label {
      font-size: 13px;
      font-weight: 300;
      color: #404040;
      line-height: 18px;
    }
  }
  .change {
    margin-top: 9px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    span {
      font-size: 12px;
      font-weight: 300;
      color: #b75800;
      line-height: 17px;
    }
    img {
      margin-left: 7px;
      @include wh(7px, 11px);
    }
  }
}
</style>
