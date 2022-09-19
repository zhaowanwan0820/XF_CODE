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
      <div class="footer-item service" @click="isShowService = true" v-stat="{ id: 'infos_btn_customerService' }"></div>
      <div class="footer-item" v-stat="{ id: 'infos_btn_shopsc' }">
        <img
          src="../../assets/image/change-icon/collect_sel.png"
          v-on:click="productUnlike"
          class="like"
          v-if="detailInfo.is_liked"
        />
        <img src="../../assets/image/change-icon/collect.png" v-on:click="productLike" v-else />
      </div>
      <div class="footer-item cart" @click="goCart" v-stat="{ id: 'infos_btn_gotocart' }">
        <span class="icon" v-if="cartNumber > 0">{{ getCarCount }}</span>
      </div>
      <div class="footer-item add-cart" @click="goProductDetail" v-stat="{ id: 'infos_btn_putincart' }">
        原价购买
      </div>
      <div
        class="footer-item buy-now"
        :class="{ 'disabled-buy': !canbuy }"
        @click="checkout(true, false)"
        v-stat="{ id: 'infos_btn_buynow' }"
      >
        {{ buyTxt }}
      </div>
    </div>
    <!-- <div class="footer-flex" v-if="this.isExchange">
      <div class="right">
        <gk-butto
          type="secondary"
          class="button"
          v-on:click="checkout(true)"
          v-if="detailInfo.good_stock > 0 && this.currentScore >= detailInfo.exchange_score"
          >立即兑换</gk-butto
        >
        <gk-butto type="primary" class="button disabled-cart" v-else-if="this.currentScore < detailInfo.exchange_score"
          >立即兑换</gk-butto
        >
      </div>
    </div> -->

    <!-- <p class="good-stock-none" v-else-if="this.isExchange && this.currentScore < detailInfo.exchange_score">积分不足</p> -->

    <!-- 加入购物车显示动画 -->
    <div class="ui-cart-animation" v-if="isAnimation">
      <mt-spinner type="snake" color="#FD9F21"></mt-spinner>
    </div>

    <!-- 服务联系方式 -->
    <mt-popup v-model="isShowService" position="bottom">
      <div class="pop-container">
        <div class="title">
          <p>联系方式</p>
          <img src="../../assets/image/hh-icon/detail/icon-close@3x.png" @click="isShowService = false" alt="" />
        </div>
        <div class="content">
          <a
            :href="'tel:' + (isIos ? '//' : '') + detailInfo.supplier.service_tel"
            v-if="detailInfo.supplier.service_tel"
            class="serviceType-wrapper"
          >
            <div class="content-line">
              <div class="content-left">
                <p class="content-title">客服电话</p>
                <p class="content-num">{{ detailInfo.supplier.service_tel }}</p>
              </div>
              <div class="content-right">
                <img src="../../assets/image/hh-icon/detail/icon-tel@3x.png" alt="" />
              </div>
            </div>
          </a>

          <a
            :href="'mqq://im/chat?chat_type=wpa&uin=' + detailInfo.supplier.service_qq + '&version=1&src_type=web'"
            class="serviceType-wrapper"
          >
            <div class="content-line" v-if="detailInfo.supplier.service_qq">
              <div class="content-left">
                <p class="content-title">客服QQ</p>
                <p class="content-num">{{ detailInfo.supplier.service_qq }}</p>
              </div>
              <div class="content-right">
                <img src="../../assets/image/hh-icon/detail/icon-qq@3x.png" alt="" />
              </div>
            </div>
          </a>

          <div class="content-none" v-if="!detailInfo.supplier.service_tel && !detailInfo.supplier.service_qq">
            <p>该商家未提供客服联系方式</p>
          </div>
        </div>
      </div>
    </mt-popup>

    <!-- 选择商品规格 暂未考虑积分的情况，如需要 再修改 -->
    <shopping v-if="isShowcartInfo" :isShowcartInfo="isShowcartInfo">
      <div class="footer-flex">
        <template v-if="detailInfo.secbuy.secbuy_quantity - detailInfo.secbuy.secbuy_sale > 0">
          <!-- 商品规则选择 浮层的来源（1-点击【商品规则选择】，2-点击加入购物车，3-点击立即购买） -->
          <div class="footer-item buy-now" @click="checkoutYes()">
            确定
          </div>
        </template>
        <template v-else>
          <div class="footer-item disabled-buy">已售罄</div>
        </template>
      </div>
    </shopping>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { Toast, Button, Popup, Indicator } from 'mint-ui'
import { productLike, productUnlike } from '../../api/product'
import shopping from './child/Shopping'
import RollingLine from '../../components/common/RollingLine'
import ContactSupplier from '../../components/common/ContactSupplier'
import { checkRead } from './mustRead/main'

export default {
  data() {
    return {
      isAnimation: false, //加入购物车成功之后是否显示动画
      isShowService: false,
      isIos: false
    }
  },

  components: {
    shopping,
    RollingLine,
    ContactSupplier
  },

  props: {
    ishidefooter: {
      type: Boolean,
      default: false
    }
  },

  computed: {
    ...mapState({
      isShowcartInfo: state => state.detail.isShowcartInfo, // 商品规格选择popup
      detailInfo: state => state.detail.detailInfo,
      isOnline: state => state.auth.isOnline,
      platform: state => state.auth.platform,
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
      seckillStatus: state => state.seckillList.seckillStatus
    }),
    ...mapGetters({
      getDetailUsableList: 'getDetailUsableList',
      getUsableList: 'getUsableList',
      hasSelectProperty: 'hasSelectProperty',
      hasEnoughStock: 'hasEnoughStock',
      shoppingPrice: 'getShoppingPrice',
      stockLimit: 'getStockLimit'
    }),
    getCarCount() {
      if (this.cartNumber > 0 && this.cartNumber < 100) {
        return this.cartNumber
      } else if (this.cartNumber >= 100) {
        return '99+'
      }
    },
    buyTxt() {
      let txt = '立即购买'
      if (
        !this.detailInfo.secbuy.is_on_sale ||
        this.detailInfo.secbuy.secbuy_quantity - this.detailInfo.secbuy.secbuy_sale <= 0
      ) {
        txt = '已售罄'
      } else if (this.seckillStatus === 0) {
        txt = '暂未开售'
      } else if (this.seckillStatus === 1) {
        txt = '立即购买'
      } else if (this.seckillStatus === 2) {
        txt = '秒杀已结束'
      }
      return txt
    },
    canbuy() {
      if (
        this.seckillStatus != 1 ||
        !this.detailInfo.secbuy.is_on_sale ||
        this.detailInfo.secbuy.secbuy_quantity - this.detailInfo.secbuy.secbuy_sale <= 0
      ) {
        // 不在秒杀时间内 or 已下架 or 库存为0
        return false
      } else {
        return true
      }
    }
  },

  created() {
    this.$on('start-addcart-animation', () => {
      this.isAnimation = true
    })
    this.$on('end-addcart-animation', () => {
      this.isAnimation = false
      this.saveCartState(false)
    })

    this.isIos = 1 == this.utils.getOpenBrowser() ? true : false
  },

  methods: {
    ...mapMutations({
      saveCartState: 'saveCartState',
      saveNumber: 'saveNumber',
      changeType: 'changeType',
      saveSelectedCartGoods: 'saveSelectedCartGoods',
      // saveExchangeScoreState: 'saveExchangeScoreState',
      savePrice: 'savePrice',
      saveShowFromAct: 'saveShowFromAct',
      saveSeckillToken: 'saveSeckillToken',
      saveIsLike: 'saveIsLike'
    }),
    ...mapActions({
      helperItzAuthCheck: 'helperItzAuthCheck',
      fetchCartNumber: 'fetchCartNumber'
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
      // 3 == this.showFromAct ? this.checkout(true, true) : this.checkout(false, true)
      this.checkout(true, true)
    },

    /**
     * 加入购物车 或 立即购买
     *
     * @param      {boolean}  immediately     true-立即购买 | false-加入购物车
     * @param      {boolean}  isFromShopping  true-规格蒙层点击的 | false-详情页底部点击的
     * @return     {undefined}   无
     */
    async checkout(immediately, isFromShopping) {
      // 预售商品不可购买
      if (immediately && this.isPreSale) {
        return
      }

      if (!this.isOnline) {
        this.$router.push({ name: 'login' })
        return
      }

      // 未在指定秒杀时间 不可购买
      if (this.seckillStatus != 1) return

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
      // 如果商品需要分期  且  点击的是详情页的立即购买  则弹出 选择分期弹出
      if (this.detailInfo.instalment && this.detailInfo.instalment.length > 0) {
        if (!isFromShopping) {
          // 记录 触发 商品规格选择浮层 动作来源
          this.saveShowFromAct(immediately ? 3 : 2)
          this.saveCartState(true)
          return
        }
        if (this.instalmentWay === undefined) {
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

      // 秒杀限流-JAVA
      // Indicator.open()
      // let res = await this.getLimitInformation()
      // Indicator.close()
      // if (res.status === 200 && res.allow) {
      let data = {}
      data['id'] = this.detailInfo.secbuy.id
      data['token'] = '22222222222222222222222'
      this.saveSeckillToken(data)
      // } else {
      //   Toast('当前网络繁忙，请稍后重试')
      //   return
      // }

      this.checkoutGood()
    },

    checkoutGood() {
      let cartGood = {
        goods_id: this.detailInfo.id,
        goods: this.detailInfo,
        property: '',
        attrs: JSON.stringify(this.chooseinfo.ids),
        attr_stock: this.detailInfo.secbuy.secbuy_quantity - this.detailInfo.secbuy.secbuy_sale,
        num: this.number ? this.number : 1,
        amount: this.number ? this.number : 1,
        chooseinfo: this.chooseinfo,
        price: Number(this.shoppingPrice.current_price) + Number(this.shoppingPrice.money_line),
        instalment_id: this.instalmentWay
      }

      if (this.chooseinfo.specification.length > 0) {
        let attrs = this.chooseinfo.specification
        for (let i = 0; i <= attrs.length - 1; i++) {
          cartGood.property = cartGood.property + '' + attrs[i]
        }
      }

      let cartGoods = [cartGood]

      this.saveSelectedCartGoods({ cartGoods: cartGoods })
      this.savePrice(cartGood.price)

      this.$router.push({ name: 'checkout' })
    },

    // 查看 购物车
    goCart() {
      if (this.isOnline) {
        this.$router.push({ name: 'cart', params: { type: 0 } })
      } else {
        this.$router.push({ name: 'login' })
      }
    },
    goProductDetail() {
      this.$router.push({ name: 'product', query: { id: this.detailInfo.id } })
    },
    getLimitInformation() {
      return new Promise((resolve, reject) => {
        this.$api.get_limit(
          'order/' + this.detailInfo.secbuy.id,
          null,
          res => {
            resolve(res)
          },
          error => {
            reject(error)
          }
        )
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-detail-footer {
  position: fixed;
  background: rgba(255, 255, 255, 1);
  border-top: 0.5px solid #e8eaed;
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
      border-top: 0.5px solid #e8eaed;
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
        // flex: 1;
        width: 120px;
        color: #552e20;
        &:hover {
          background-color: #f4f4f4;
        }
      }
      &.buy-now {
        background-color: #772508;
        color: #ffffff;
        width: 120px;
        flex: 1;
        // &:hover {
        //   background-color: #672108;
        // }
      }
      &.disabled-buy {
        flex: 1;
        background-color: #c0c0c0;
        color: #fff;
        pointer-events: none;
      }
      &.pre-sale {
        background-color: #d0b482;
        color: #ffffff;
        flex: 1;
        &:hover {
          background-color: #d0b482;
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
.ui-cart-animation {
  position: fixed;
  top: 50%;
  left: 50%;
}
.mint-popup-bottom {
  height: 440px;
  .pop-container {
    // padding: 0 15px;
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
        height: 85px;
        border-bottom: 1px dotted #d8d8d8;
        display: flex;
        justify-content: space-between;
        align-items: center;
        p {
          font-size: 13px;
          line-height: 20px;
        }
        .content-title {
          color: #999;
          margin-bottom: 5px;
        }
        .content-num {
          color: #333;
          margin: 0;
        }
        img {
          width: 30px;
          height: 30px;
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
</style>
