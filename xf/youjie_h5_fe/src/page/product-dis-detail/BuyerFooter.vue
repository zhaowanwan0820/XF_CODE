<template>
  <div class="ui-detail-footer" v-if="detailInfo.id">
    <div class="footer-flex">
      <template v-if="detailInfo.good_stock > 0">
        <div class="footer-item shopIndex" @click="toHHKShop">
          <img src="../../assets/image/hh-icon/shop4buyer/shop@2x.png" alt="小店首页" />
          <span class="btn-txt">小店首页</span>
        </div>
        <div class="footer-item customerService" @click="isShowService = true">
          <img src="../../assets/image/change-icon/service@3x.png" alt="客服" />
          <span class="btn-txt">客服</span>
        </div>
        <div class="footer-item buy-now" @click="checkout(true)">
          <p class="price">
            <span class="price-unit">￥</span
            ><span>{{
              utils.formatFloat(Number(shoppingPrice.current_price) + Number(shoppingPrice.money_line))
            }}</span>
          </p>
          <span class="btn-txt">购买立省￥{{ utils.formatFloat(detailInfo.seller.save_price || 0) }}</span>
        </div>
      </template>
      <template v-if="detailInfo.good_stock <= 0">
        <div class="footer-item disabled-buy stock-none">
          已售罄
        </div>
      </template>
    </div>

    <!-- 选择商品规格 -->
    <shopping v-if="isShowcartInfo" :isShowcartInfo="isShowcartInfo">
      <div class="footer-flex">
        <template v-if="detailInfo.good_stock > 0">
          <div class="footer-item buy-now" @click="checkout(true)">
            <p class="price">
              <span class="price-unit">￥</span
              >{{ utils.formatFloat(Number(shoppingPrice.current_price) + Number(shoppingPrice.money_line)) }}
            </p>
            <span class="btn-txt">专享购买</span>
          </div>
        </template>
        <template v-if="detailInfo.good_stock <= 0">
          <div class="footer-item disabled-buy stock-none">
            已售罄
          </div>
        </template>
      </div>
    </shopping>

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
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { MessageBox, Toast } from 'mint-ui'
import shopping from '../product-detail/child/Shopping'

export default {
  data() {
    return {
      mlmId: this.$route.params.mlmId,
      isShowService: false,
      isIos: false
    }
  },

  computed: {
    ...mapState({
      isShowcartInfo: state => state.detail.isShowcartInfo, // 商品规格选择popup
      detailInfo: state => state.detail.detailInfo,
      platform: state => state.auth.platform,
      isOnline: state => state.auth.isOnline,
      chooseinfo: state => state.detail.chooseinfo,
      number: state => state.detail.number,
      user: state => state.auth.user
    }),
    ...mapGetters({
      hasSelectProperty: 'hasSelectProperty',
      hasEnoughStock: 'hasEnoughStock',
      shoppingPrice: 'getShoppingPrice',
      stockLimit: 'getStockLimit'
    }),
    oldPrice() {
      const mp = this.utils.formatFloat(this.detailInfo.market_price)
      const sp = this.utils.formatFloat(this.detailInfo.shop_price)
      return mp < sp ? sp : mp
    }
  },

  components: {
    shopping
  },

  created() {
    this.isIos = 1 == this.utils.getOpenBrowser() ? true : false
  },

  methods: {
    ...mapMutations({
      saveCartState: 'saveCartState',
      savePrice: 'savePrice',
      changeType: 'changeType',
      saveSelectedCartGoods: 'saveSelectedCartGoods'
      // saveExchangeScoreState: 'saveExchangeScoreState'
    }),
    ...mapActions({
      helperItzAuthCheck: 'helperItzAuthCheck'
    }),

    // 加入购物车 或 立即购买
    checkout(immediately) {
      if (!this.isOnline) {
        this.$router.push({ name: 'login' })
        return
      }

      // 是否选择商品属性
      if (!this.hasSelectProperty) {
        Toast('请选择商品属性')

        this.saveCartState(true)
        return
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

      this.checkoutGood()
    },

    checkoutGood() {
      let cartGood = {
        goods_id: this.detailInfo.id,
        mlmId: this.mlmId,
        goods: this.detailInfo,
        property: '',
        attrs: JSON.stringify(this.chooseinfo.ids),
        attr_stock: this.stockLimit,
        num: this.number ? this.number : 1,
        amount: this.number ? this.number : 1,
        chooseinfo: this.chooseinfo,
        price: Number(this.shoppingPrice.current_price) + Number(this.shoppingPrice.money_line)
      }

      if (this.chooseinfo.specification.length > 0) {
        let attrs = this.chooseinfo.specification
        for (let i = 0; i <= attrs.length - 1; i++) {
          cartGood.property = cartGood.property + '' + attrs[i]
        }
      }

      let cartGoods = [cartGood]

      // 分销商品 纯现金支付，无需判断是否平台用户或者是否授权
      // this.saveExchangeScoreState(0)
      this.saveSelectedCartGoods({ cartGoods: cartGoods })
      this.savePrice(cartGood.price)
      this.$router.push({ name: 'checkout' })
    },
    toHHKShop() {
      this.$router.push({ name: 'shop', params: { id: this.detailInfo.seller.shop_id } })
    }
  }
}
</script>

<style lang="scss" scoped>
p {
  margin: 0;
  padding: 0;
}
.ui-detail-footer {
  background: rgba(255, 255, 255, 1);
  border-top: 0.5px solid #e8eaed;
  width: auto;
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 2;

  .footer-flex {
    flex: 1;

    display: flex;
    justify-content: space-between;
    align-content: center;
    align-items: center;
    height: 50px;
    font-size: 16px;

    .footer-item {
      box-sizing: border-box;
      flex: 1 0 0;
      height: 50px;

      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      background: rgba(119, 37, 8, 1);
      padding-bottom: 4px;

      &.buy-now {
        background-color: #772508;
        color: #fff;
      }
      &.disabled-buy {
        background-color: #c0c0c0;
        color: #fff;
      }
      &.stock-none {
        padding-top: 0;
        justify-content: center;
      }

      .btn-txt {
        font-size: 11px;
        font-weight: 400;
        color: rgba(255, 255, 255, 1);
        line-height: 14px;
        margin-top: 2px;
      }

      &.shopIndex,
      &.customerService {
        background-color: rgba(250, 250, 250, 1);
        flex: 0 0 56.5px;
        padding-top: 0;
        padding-bottom: 0;
        flex-direction: column;
        justify-content: center;

        img {
          width: 23px;
        }
        .btn-txt {
          @include sc(8px, #552e20);
          line-height: 11px;
          margin-top: 5px;
        }
      }
      &.shopIndex {
        border-right: 1px dotted #ccc;
      }
    }
    .price {
      height: 25px;
      line-height: 25px;
      font-size: 21px;
      font-weight: bold;
      color: rgba(255, 255, 255, 1);

      .price-unit {
        font-size: 15px;
        font-weight: 600;
      }
    }
  }
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
