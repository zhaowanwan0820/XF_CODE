<!-- footer.vue -->
<template>
  <div
    class="ui-detail-footer"
    v-if="detailInfo"
    v-bind:class="{ 'hidden-cart-footer': ispromotion, 'show-cart-footer': !ispromotion }"
  >
    <!-- 国庆banner -->
    <!-- <rolling-line></rolling-line> -->
    <div class="footer-flex">
      <!-- 联系方式 -->
      <div class="footer-item service" @click="showServicePopup" v-stat="{ id: 'infos_btn_customerService' }"></div>

      <!-- 收藏 -->
      <!-- <div class="footer-item" v-stat="{ id: 'infos_btn_shopsc' }">
        <img v-if="detailInfo.is_liked" src="../../assets/image/change-icon/collect_sel.png" @click="productUnlike" />
        <img v-else src="../../assets/image/change-icon/collect.png" @click="productLike" />
      </div> -->

      <!-- 购物车 -->
      <div class="footer-item cart" @click="goCart" v-stat="{ id: 'infos_btn_gotocart' }">
        <span class="icon" v-if="cartNumber > 0">{{ getCarCount }}</span>
      </div>

      <template v-if="detailInfo.good_stock > 0">
        <div
          class="footer-item buy-now"
          :class="{ 'pre-sale': isPreSale }"
          @click="checkout(true, false)"
          v-stat="{ id: 'infos_btn_buynow' }"
        >
          {{ buyTxt }}
        </div>
      </template>
      <template v-else>
        <div class="footer-item disabled-buy">领光了</div>
      </template>
    </div>

    <!-- 选择商品规格 暂未考虑积分的情况，如需要 再修改 -->
    <shopping v-if="isShowcartInfo" :isShowcartInfo="isShowcartInfo">
      <div class="footer-flex">
        <template v-if="detailInfo.good_stock > 0">
          <div class="footer-item buy-now" :class="{ 'pre-sale': isPreSale }" @click="checkout(true, true)">
            {{ buyTxt }}
          </div>
        </template>
        <template v-if="detailInfo.good_stock <= 0">
          <div class="footer-item disabled-buy">领光了</div>
        </template>
      </div>
    </shopping>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { MessageBox, Toast, Button, Popup, Indicator } from 'mint-ui'
import { productLike, productUnlike, trainBill } from '../../api/product'
import { cartAdd } from '../../api/cart'
import shopping from './child/Shopping'
import RollingLine from '../../components/common/RollingLine'
import { checkRead } from './mustRead/main'

export default {
  data() {
    return {}
  },

  components: {
    shopping,
    RollingLine
  },

  watch: {
    isConfirmTrough(val) {
      if (!val) return
      this.$router.push({ name: 'checkout' })
    }
  },

  computed: {
    ...mapState({
      isShowcartInfo: state => state.detail.isShowcartInfo, // 商品规格选择popup
      detailInfo: state => state.detail.detailInfo,
      isOnline: state => state.auth.isOnline,
      ispromotion: state => state.detail.ispromotion,
      cartNumber: state => state.tabBar.cartNumber,
      chooseinfo: state => state.detail.chooseinfo,
      number: state => state.detail.number,
      // isExchange: state => state.score.isExchange,
      user: state => state.auth.user,
      // currentScore: state => state.score.currentScore
      isPreSale: state => state.detail.isPreSale,
      showFromAct: state => state.detail.showFromAct, // 商品规则选择 浮层的来源（1-点击【商品规则选择】，2-点击加入购物车，3-点击立即购买）
      instalmentWay: state => state.detail.instalmentWay,
      cartGoods: state => state.checkout.cartGoods,
      isConfirmTrough: state => state.detail.isConfirmTrough, // 直通车商品确认下单
      troughInfo: state => state.detail.troughInfo //直通车商品信息
    }),
    ...mapGetters({
      hasSelectProperty: 'hasSelectProperty',
      hasEnoughStock: 'hasEnoughStock',
      shoppingPrice: 'getShoppingPrice',
      stockLimit: 'getStockLimit'
    }),
    getCarCount() {
      return this.cartNumber >= 100 ? '99+' : this.cartNumber
    },
    buyTxt() {
      let txt = '立即购买'
      if (this.detailInfo && this.detailInfo.instalment && this.detailInfo.instalment.length) {
        txt = '立即租购'
      }
      if (this.isPreSale) {
        txt = '抢购即将开始'
      }
      txt = '立刻领取'
      return txt
    },
    confirmTxt() {
      return (this.detailInfo.through_train || this.isShare) && this.showFromAct === 2 ? '找便宜' : '确定'
    },
    getOrderProducts: function() {
      let cartGoods = this.cartGoods
      let orderProducts = []
      for (let i = 0; i < cartGoods.length; i++) {
        const element = cartGoods[i]
        let goods = {}

        goods.goods_id = element.goods ? element.goods.id : ''

        goods.buy_number = element.amount
        // element.attrs 有四种情况
        // 1. 空的: null
        // 2. Json数组: [123]、[123, 456]
        // 3. 无逗号字串: 123
        // 4. 有逗号字串: 123,456
        goods.property = this.getToArray(element.attrs)

        orderProducts.push(goods)
      }
      return orderProducts
    },
    isShare() {
      return this.$route.query.sharesource && !this.isOnline ? true : false
    }
  },

  methods: {
    ...mapMutations({
      saveCartState: 'saveCartState',
      saveNumber: 'saveNumber',
      saveSelectedCartGoods: 'saveSelectedCartGoods',
      savePrice: 'savePrice',
      saveShowFromAct: 'saveShowFromAct',
      changeShowConfirmTrough: 'changeShowConfirmTrough',
      saveServicePopupState: 'saveServicePopupState',
      setTroughInfo: 'setTroughInfo',
      saveIsLike: 'saveIsLike'
    }),
    ...mapActions({
      fetchCartNumber: 'fetchCartNumber',
      fetchWxAuthCheck: 'fetchWxAuthCheck'
    }),

    /*
     * productLike： 收藏商品
     **/
    productLike() {
      if (this.user) {
        let id = this.detailInfo.id
        productLike(id).then(res => {
          this.saveIsLike(res)
          Toast('收藏成功')
        })
      } else {
        this.$router.push({ name: 'login' })
      }
    },

    /*
     * productUnlike： 取消收藏商品
     **/
    productUnlike() {
      if (this.user) {
        let id = this.detailInfo.id
        productUnlike(id).then(res => {
          this.saveIsLike(res)
          Toast('取消收藏')
        })
      } else {
        this.$router.push({ name: 'login' })
      }
    },

    // 选择规格，点击 确定 按钮
    checkoutYes() {
      3 == this.showFromAct ? this.checkout(true, true) : this.checkout(false, true)
    },

    /**
     * 加入购物车 或 立即购买
     *
     * @param      {boolean}  immediately     true-立即购买 | false-加入购物车
     * @param      {boolean}  isFromShopping  true-规格蒙层点击的 | false-详情页底部点击的
     * @return     {undefined}   无
     */
    checkout(immediately, isFromShopping) {
      // 预售商品不可购买
      if (immediately && this.isPreSale) {
        return
      }

      if (!this.isOnline) {
        this.$router.push({ name: 'login' })
        return
      }

      // 是否选择商品属性
      if (!this.hasSelectProperty) {
        Toast('请选择商品属性')

        // 判断 规则选择浮层 是否已经弹起
        if (this.isShowcartInfo) {
          return
        }
        // 记录 触发 商品规格选择浮层 动作来源
        this.saveShowFromAct(immediately ? 3 : 2)

        this.saveCartState(true)
        return
      }
      // 详情页的立即购买 或 加入购物车  都先弹出选择规格弹出
      if (
        (this.detailInfo.instalment && this.detailInfo.instalment.length > 0) ||
        (this.detailInfo.properties && this.detailInfo.properties.length > 0)
      ) {
        if (!isFromShopping) {
          // 记录 触发 商品规格选择浮层 动作来源
          this.saveShowFromAct(immediately ? 3 : 2)
          this.saveCartState(true)
          return
        }
        if (this.detailInfo.instalment && this.detailInfo.instalment.length > 0 && this.instalmentWay === undefined) {
          Toast('请选择分期方式')
          return
        }
      }

      // 是否限购 TODO 跟当前number比较
      if (this.detailInfo.only_purchase) {
        let can_buy_num = this.detailInfo.only_purchase - this.detailInfo.now_purchase
        if (can_buy_num < 1) {
          let toastConfig = '该商品每个用户每日限购' + this.detailInfo.only_purchase + '件哦'
          Toast(toastConfig)
          return
        }
      }

      // 库存是否足够
      if (!this.hasEnoughStock) {
        Toast('商品库存不足')
        return
      }

      immediately
        ? this.checkoutGood(immediately)
        : this.detailInfo.through_train
        ? this.checkoutGood()
        : this.addShopCart()
    },

    async checkoutGood(immediately) {
      let cartGood = {
        goods_id: this.detailInfo.id,
        goods: this.detailInfo,
        property: '',
        attrs: JSON.stringify(this.chooseinfo.ids),
        attr_stock: this.stockLimit,
        num: this.number ? this.number : 1,
        amount: this.number ? this.number : 1,
        chooseinfo: this.chooseinfo,
        price: Number(this.shoppingPrice.current_price) + Number(this.shoppingPrice.money_line),
        instalment_id: this.instalmentWay,
        gift: true
      }

      if (this.chooseinfo.specification.length > 0) {
        const properties = this.detailInfo.properties
        properties.map((item, index) => {
          const id = this.chooseinfo.ids[index]
          item.attrs.map((attr, i) => {
            if (attr.id == id) {
              cartGood.property += `${item.name}:${attr.attr_name} \n`
            }
          })
        })
      }

      let cartGoods = [cartGood]

      this.saveSelectedCartGoods({ cartGoods: cartGoods })
      this.savePrice(cartGood.price)

      // if (!this.mustReadBeforeBuy()) {
      //   return
      // }

      // // 验证身份确权信息
      // let hasConfirm = true
      // try {
      //   await this.fetchWxAuthCheck()
      // } catch (e) {
      //   hasConfirm = false
      // }
      // if (!hasConfirm) return

      // 直通车商品 && 点击找便宜 && 已确认下单
      if (this.detailInfo.through_train && !immediately) {
        // 打开直通车弹窗
        this.openThroughPopup()
      } else {
        this.$router.push({ name: 'checkout' })
      }
    },

    // 加入购物车
    addShopCart() {
      let good_number = this.number ? this.number : 1
      cartAdd(this.detailInfo.id, this.chooseinfo.ids, good_number).then(
        res => {
          this.saveNumber(good_number)
          this.fetchCartNumber()
          Toast('成功加入购物车')
          this.saveCartState(false)
        },
        error => {
          Toast(error.errorMsg)
        }
      )
    },

    // 买前须知
    // mustReadBeforeBuy() {
    //   const checkRes = checkRead(this.detailInfo)
    //   if (!checkRes['check']) {
    //     this.$router.push({ name: 'shouldKnownBefore', query: { from: 'detail', ruleId: checkRes['rule']['id'] } })
    //     return false
    //   } else {
    //     return true
    //   }
    // },

    // 查看 购物车
    goCart() {
      if (this.isOnline) {
        this.$router.push({ name: 'cart', params: { type: 0 } })
      } else {
        this.$router.push({ name: 'login' })
      }
    },

    // 打开直通车弹窗
    openThroughPopup() {
      Indicator.open()
      trainBill(this.getOrderProducts)
        .then(
          res => {
            this.setTroughInfo(res)
          },
          error => {
            console.log(error)
          }
        )
        .finally(() => {
          Indicator.close()
          // 接口数据回调成功打开
          this.changeShowConfirmTrough(true)
        })
    },
    getToArray: function(str) {
      if (!(str instanceof Array))
        try {
          str = [].concat(JSON.parse(str))
        } catch (e) {
          str = str ? str.split(',').map(Number) : []
        }
      return str
    },

    showServicePopup() {
      this.saveServicePopupState(true)
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-detail-footer {
  position: fixed;
  background: rgba(255, 255, 255, 1);
  width: auto;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 2;

  &.hidden-cart-footer {
    display: none;
  }

  &.show-cart-footer {
    display: block;
  }

  .footer-flex {
    flex: 1;

    display: flex;
    justify-content: flex-start;
    align-content: center;
    align-items: center;
    height: 50px;
    font-size: 16px;
    div.footer-item {
      height: 50px;
      width: 45px;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: space-around;
      background: #fafafa;
      line-height: 50px;
      & + div.footer-item {
        @include thin-left-border(#cccccc, 0, auto);
      }
      img {
        width: 23px;
        height: 23px;
        flex-shrink: 0;
      }
      span.icon {
        position: absolute;
        right: 2px;
        top: 8px;
        @include sc(10px, #fff);
        line-height: 14px;
        width: 18px;
        height: 14px;
        background: #ef3338;
        border-radius: 20px;
        text-align: center;
      }
      &.service {
        background-image: url('../../assets/image/change-icon/service@3x.png');
        background-repeat: no-repeat;
        background-size: 23px 23px;
        background-position: center;
        &:hover {
          background-image: url('../../assets/image/change-icon/service_hover@3x.png');
        }
      }
      &.cart {
        background-image: url('../../assets/image/change-icon/icon-cart.png');
        background-repeat: no-repeat;
        background-size: 23px 23px;
        background-position: center;
        &:hover {
          background-image: url('../../assets/image/change-icon/icon-cart_hover.png');
        }
      }
      &.add-cart {
        flex: 1;
        color: #ffffff;
        background-color: #ffbb7c;
      }
      &.buy-now {
        background-color: #fc7f0c;
        color: #ffffff;
        flex: 1;
      }
      &.disabled-buy {
        flex: 1;
        background-color: #c0c0c0;
        color: #fff;
      }
      &.pre-sale {
        background-color: #d0b482;
        color: #ffffff;
        flex: 1;
        &:hover {
          background-color: #d0b482;
        }
      }
      &.througn-train {
        flex: 1;
        background-color: #d0b482;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        span {
          font-size: 16px;
          font-weight: 400;
          color: #fff;
          line-height: 22px;
        }
        label {
          margin-top: 2px;
          display: inline-block;
          @include sc(10px, rgba(255, 255, 255, 0.8)) font-weight: 400;
          line-height: 14px;
          white-space: nowrap;
        }
      }
    }
  }
  p.good-stock-none {
    width: 100%;
    height: 32px;
    background: #c3c3c3;
    opacity: 0.5;
    font-size: 14px;
    color: #333;
    line-height: 20px;
    position: absolute;
    text-align: center;
    line-height: 32px;
    padding: 0;
    margin: 0;
    bottom: 50px;
  }
  div.right {
    flex: 1;
    height: 50px;
    display: flex;
    flex-direction: row;
    .button {
      flex: 1;
      @include button($margin: 0, $radius: 0);
      font-size: 15px;
      height: 50px;
      line-height: 50px;
    }
  }
}
</style>
