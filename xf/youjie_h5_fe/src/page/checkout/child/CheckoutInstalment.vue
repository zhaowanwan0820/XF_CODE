<template>
  <div class="instalment-wraper" v-if="getInstalmentWay">
    <div class="istlmt-w-item">
      <img :src="instalmentIcon.checkout[getInstalmentWay.method]" alt="" />
      <div class="istlmt-w-num">{{ getInstalmentTimes(getInstalmentWay.num) }}</div>
      <div class="istlmt-w-price">
        <p class="total">
          <span
            >￥{{ utils.formatMoney(getInstalmentWay.total_price, true) }}{{ txtPaymentType(getInstalmentWay) }}</span
          >
        </p>
        <p class="desc">
          <span>现金：{{ utils.formatMoney(getInstalmentWay.cash, true) }}{{ txtPaymentType(getInstalmentWay) }}</span>
          <span
            >积分：{{ utils.formatMoney(getInstalmentWay.surplus, true) }}{{ txtPaymentType(getInstalmentWay) }}</span
          >
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { instalmentIcon, PRODUCT_SHOW_SHOUQI } from '../../product-detail/static.js'

export default {
  data() {
    return {
      instalmentIcon
    }
  },
  props: {
    items: {
      type: Array
    }
  },
  computed: {
    ...mapState({
      cartGoods: state => state.checkout.cartGoods
    }),
    getInstalmentWay() {
      const instalment = this.items[0].goods.instalment
      const instalmentId = this.items[0].instalment_id
      let instalmentWay
      if (instalmentId === undefined) {
        return instalmentWay
      }
      if (!instalment || !instalment.length) {
        return
      }
      if (instalmentId === 0) {
        instalmentWay = {
          method: 0,
          num: 1,
          price: this.utils.formatFloat(this.items[0].goods.MONEY_SHOW * this.items[0].amount, false),
          surplus: this.utils.formatFloat(this.items[0].goods.HB_SHOW * this.items[0].amount, false),
          total_price: this.utils.formatFloat(this.items[0].goods.current_price * this.items[0].amount, false)
        }
      } else {
        instalment.forEach((item, index) => {
          if (instalmentId == item.id) {
            instalmentWay = { ...item }
          }
        })
      }
      return instalmentWay
    }
  },

  methods: {
    getInstalmentTimes(num) {
      if (num <= 1) {
        return '全款付'
      } else {
        return `分${num}期`
      }
    },
    /**
     * 每期 or 首期
     */
    txtPaymentType(item) {
      let ret = '/期'
      if (item.method == 5) {
        ret = ''
      }
      if (this.cartGoods[0] && '9338' == this.cartGoods[0].goods_id) {
        ret = '/首期'
      }
      return ret
    }
  }
}
</script>

<style lang="scss" scoped>
.instalment-wraper {
  background-color: #ffffff;
  padding: 0 15px;
}
.istlmt-w-item {
  border-radius: 2px;
  padding: 12px 10px 12px 0;
  color: #404040;
  border: 1px solid #fafafa;
  background-color: #fafafa;
  display: flex;
  font-size: 13px;
  align-items: center;
  border-radius: 6px;
  position: relative;
  box-sizing: border-box;
  img {
    position: absolute;
    width: 44px;
    left: -1px;
    top: -1px;
  }
}
.istlmt-w-num {
  text-align: right;
  font-family: PingFangSC-Regular;
  font-weight: 400;
  width: 65px;
  flex: 0 0 65px;
}
.istlmt-w-price {
  flex: 1 0 0;
  padding-left: 6px;
  line-height: 18px;
  .total {
    font-size: 14px;
    font-family: PingFangSC-Medium;
    font-weight: 500;
  }
  .desc {
    @include sc(11px, rgba(64, 64, 64, 0.4), left center);
  }
  span + span {
    margin-left: 12px;
  }
}
</style>
