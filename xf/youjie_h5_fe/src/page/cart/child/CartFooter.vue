<template>
  <div class="ui-cart-footer">
    <div class="list-checkbox">
      <input
        type="checkbox"
        class="checkbox"
        id="checkbox-all"
        :disabled="selectAllDisabled"
        v-model="isSelectAll"
        @change="allSelect"
      />
      <label for="checkbox-all"></label>
      <!-- <i v-if="isDeleteMode">全选</i> -->
      <i>全选</i>
      <div class="total-price" v-if="!isDeleteMode">
        <i
          >合计<span class="price-unit">￥</span
          ><span class="price-num">{{ utils.formatMoney(total_price, true) }}</span></i
        >
        <i class="discount-price" v-if="promo_price > 0">活动优惠 -￥{{ promo_price }}</i>
      </div>
    </div>
    <span class="cart-footer-btn remove" v-if="isDeleteMode" @click="deleteSelected">删除</span>
    <span
      class="cart-footer-btn checkout"
      v-if="!isDeleteMode && total_amount > 0"
      @click="checkout"
      v-stat="{ id: 'shopcart_btn_settlement' }"
      >结算({{ total_amount }})</span
    >
    <span class="cart-footer-btn disable" v-if="!isDeleteMode && total_amount == 0">结算({{ total_amount }})</span>
  </div>
</template>

<script>
import { Indicator, Toast, MessageBox } from 'mint-ui'
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { cartDelete } from '../../../api/cart'
import { checkRead } from '../../product-detail/mustRead/main'
import { checkGoodsAmoutValid } from './util'

export default {
  data() {
    return {
      isSelectAll: false
    }
  },
  created() {
    // 初始化将total_price置为0
    this.saveTotalPrice(0)
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      mode: state => state.cart.mode, // 结算模式 or 删除模式
      total_price: state => state.cart.total_price, // 已选择 商品的总价
      promo_price: state => state.cart.promo_price // 已选择 商品的活动优惠价格
    }),
    ...mapGetters({
      choosenGoods: 'cart_choosenGoods',
      total_amount: 'cart_choosen_amount',
      isAllChoosen: 'cart_isAllChoosen',
      validGoods: 'cart_valid_goods'
    }),
    choosenGoodsIds() {
      return this.choosenGoods.map(ele => ele.id)
    },
    isDeleteMode() {
      return this.mode == 2
    },
    selectAllDisabled() {
      // 有效商品为空时，结算模式下 全选按钮不可用
      if (1 == this.mode && !this.validGoods.length) {
        return true
      } else {
        return false
      }
    }
  },
  watch: {
    isAllChoosen(val) {
      this.isSelectAll = val
    }
  },
  methods: {
    ...mapMutations({
      selectAll: 'selectALL',
      removeGoods: 'removeGoods',
      setCartIsLoading: 'setCartIsLoading',
      saveSelectedCartGoods: 'saveSelectedCartGoods',
      saveSeckillItems: 'saveSeckillItems',
      saveTotalPrice: 'saveTotalPrice'
    }),
    ...mapActions({
      fetchOrderPrice: 'fetchOrderPrice',
      fetchCartList: 'fetchCartList',
      fetchWxAuthCheck: 'fetchWxAuthCheck'
    }),
    allSelect() {
      this.selectAll(this.isSelectAll)
      if (!this.isDeleteMode) {
        Indicator.open()
        this.fetchOrderPrice().then(() => {
          Indicator.close()
        })
      }
    },
    // 移除商品
    deleteSelected() {
      if (!this.choosenGoods.length) {
        Toast('请先选择需要删除的商品')
        return
      }

      this.setCartIsLoading(true)
      Indicator.open()
      cartDelete(this.choosenGoodsIds).then(res => {
        Indicator.close()
        this.setCartIsLoading(false)
      })

      this.removeGoods(this.choosenGoods)
    },
    // 结算
    async checkout() {
      // 结算商品
      const checkoutGoods = this.choosenGoods[0]
      // 校验 商品库存 | 限购
      const check = checkGoodsAmoutValid(checkoutGoods, checkoutGoods.amount)
      if (!check['valid']) {
        return Toast(check['msg'])
      }

      // 买前须知
      // if (!this.mustReadBeforeBuy()) {
      //   return
      // }
      // 验证身份确权信息
      let hasConfirm = true
      try {
        await this.fetchWxAuthCheck()
      } catch (e) {
        hasConfirm = false
      }
      if (!hasConfirm) return
      // 保存结算商品和活动优惠
      this.saveSelectedCartGoods({ cartGoods: this.choosenGoods })
      this.saveSeckillItems(checkoutGoods.goods.promos || [])
      this.$router.push({ name: 'checkout', params: { isCart: true } })
    },
    // 买前须知
    // mustReadBeforeBuy() {
    //   const detailInfo = this.choosenGoods[0] ? this.choosenGoods[0].goods : {}
    //   const checkRes = checkRead(detailInfo)
    //   if (!checkRes['check']) {
    //     this.$router.push({ name: 'shouldKnownBefore', query: { from: 'cartList', ruleId: checkRes['rule']['id'] } })
    //     return false
    //   } else {
    //     return true
    //   }
    // }
  }
}
</script>

<style lang="scss" scoped>
.ui-cart-footer {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-content: center;
  align-items: center;
  height: 50px;
  background: rgba(255, 255, 255, 1);
  padding-left: 15px;
  width: -webkit-fill-available;
  @include thin-border(#e8eaed, 0, auto, false, false);
  .list-checkbox {
    width: 200px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    margin-right: 5px;
    height: 44px;
    line-height: 44px;
    label {
      @include wh(22px, 22px);
      @include thin-border-2019([top, left, bottom, right], #979797, 50%);
      position: absolute;
      top: 0;
      left: 1px;
      display: inline-block;
    }
    input {
      display: none;
      &:checked + label {
        @include wh(22px, 22px);
        @include thin-border-2019([top, left, bottom, right], $primaryColor, 50%);
        background: url('../../../assets/image/hh-icon/icon-checkbox-active.png') no-repeat;
        background-size: cover;
      }
      &:focus {
        outline-offset: 0;
      }
    }
    i {
      padding-left: 12px;
      font-style: normal;
      font-size: 14px;
      color: rgba(41, 43, 45, 1);
    }
    .total-price {
      width: 120px;
      text-align: right;
      padding-left: 12px;
      font-style: normal;
      font-size: 14px;
      color: rgba(41, 43, 45, 1);
      display: flex;
      justify-content: space-between;
      flex-direction: column;
      i {
        line-height: 1;
        padding-left: 0;
      }
      .discount-price {
        font-size: 10px;
        color: #999999;
        margin-top: 4px;
        padding-left: 0;
      }
      span {
        font-weight: bold;
        color: #B75800;
      }
      span.price-unit {
        width: 9px;
        height: 17px;
        font-size: 12px;
        line-height: 14px;
        // color: $markColor;
      }
      span.price-num {
        height: 14px;
        font-size: 18px;
        line-height: 21px;
      }
    }
  }
  span.cart-footer-btn {
    width: 150px;
    height: 50px;
    display: inline-block;
    font-size: 15px;
    color: rgba(255, 255, 255, 1);
    line-height: 50px;
    text-align: center;
    cursor: pointer;
    font-weight: normal;
  }
  .checkout {
    background: $primaryColor;
  }
  .disable {
    background: #c3c3c3;
  }
  .remove {
    background: $deleteColor;
    &:active {
      background: #d92439;
    }
  }
}
</style>
