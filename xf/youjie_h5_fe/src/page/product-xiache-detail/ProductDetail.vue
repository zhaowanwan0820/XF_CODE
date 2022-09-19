<!-- 商品详情 -->
<template>
  <div class="product-detail-wrapper">
    <!-- header  -->
    <detail-header
      v-if="!isPreviewPicture"
      :headerBackgroundOpacite="headerBackgroundOpacite"
      :prodDetailOfstHt="prodDetailOfstHt"
    ></detail-header>

    <template v-if="detailInfo.id && !notAvailable">
      <!-- body -->
      <detail-body :prodDetailIsFixed="prodDetailIsFixed" :prodDetailOfstHt="prodDetailOfstHt"></detail-body>

      <!-- footer -->
      <detail-footer></detail-footer>
      <!-- 预览图片 -->
      <preview-picture v-if="isPreviewPicture" :defaultindex="swipeId" :isshow="isPreviewPicture"></preview-picture>
      <!-- 积分说明popup -->
      <h-b-rules-popup v-if="isShowHBRules" :isShowHBRules="isShowHBRules"></h-b-rules-popup>
      <!-- 积分说明popup -->
      <popup-serve-tag v-if="isShowServeTag" :isShowServeTag="isShowServeTag"></popup-serve-tag>
      <!-- 提示秒杀banner -->
      <seckill-banner v-if="detailInfo.is_secbuy" :id="detailInfo.is_secbuy"></seckill-banner>
      <!-- 优惠券popup -->
      <coupon-popup v-if="isShowCouponPopup" :isShowCouponPopup="isShowCouponPopup"></coupon-popup>
      <!-- 直通车弹窗 -->
      <through-train></through-train>
      <!-- 联系方式 -->
      <popup-service v-if="isShowServicePopup" :isShowServicePopup="isShowServicePopup"></popup-service>
      <!-- 回到顶部 -->
      <v-back-top v-if="isshowBacktop" :target="target"></v-back-top>
    </template>

    <div v-if="notAvailable" class="content-empty">
      <img class="content-empty-img" src="../../assets/image/hh-icon/mlm/content-empty@2x.png" alt="商品不可用" />
      <h2 class="content-empty-title">商品已下架</h2>
      <p class="content-empty-des">3s后跳转至{{ utils.storeName }}首页</p>
      <div class="content-empty-toIndex">
        <a href="">进入{{ utils.storeName }}首页</a>
      </div>
    </div>
  </div>
</template>

<script>
import { Indicator } from 'mint-ui'

import DetailHeader from './DetailHeader'
import DetailBody from './DetailBody'
import DetailFooter from './DetailFooter'
import CouponPopup from './child/CouponPopup'
import PreviewPicture from './child/PreviewPicture'
import PopupServeTag from './child/PopupServeTag'
import HBRulesPopup from './child/HBRulesPopup'
import SeckillBanner from './child/SeckillBanner'
import ThroughTrain from './child/ThroughTrain'
import PopupService from './child/PopupService'
import BackTop from './child/DetailBackTop'
import { giftGet } from '../../api/product'
import { balanceGet } from '../../api/balance'
import { getGoodsCouponList } from '../../api/coupon'
import { mapState, mapMutations, mapActions } from 'vuex'
export default {
  name: 'product',
  data() {
    return {
      productId: this.$route.query.id ? this.$route.query.id : '',
      // isExchange: this.$route.query.isExchange ? this.$route.query.isExchange : false,
      hideFooter: false,
      popupVisible: true,
      currentBalance: 0,
      isshowBacktop: false,
      target: null,
      headerBackgroundOpacite: 0,

      // 用来控制详情页二级固定
      prodDetailOfstHt: 0, // 详情页距离父顶的高度
      prodDetailIsFixed: false, // 是否可以fixed
      isLoading: true,
      notAvailable: false
    }
  },

  computed: mapState({
    isOnline: state => state.auth.isOnline,
    detailInfo: state => state.detail.detailInfo,
    isPreviewPicture: state => state.detail.isPreviewPicture,
    swipeId: state => state.detail.swipeId,
    isShowHBRules: state => state.detail.isShowHBRules,
    isShowServeTag: state => state.detail.isShowServeTag,
    isShowCouponPopup: state => state.detail.isShowCouponPopup,
    isShowServicePopup: state => state.detail.isShowServicePopup,
    config: state => state.config.config
  }),

  components: {
    DetailHeader,
    DetailBody,
    DetailFooter,
    HBRulesPopup,
    PreviewPicture,
    PopupServeTag,
    CouponPopup,
    'v-back-top': BackTop,
    SeckillBanner,
    ThroughTrain,
    PopupService
  },
  created() {
    this.getDetail()
    this.saveCartState(false)
    this.getCouponList()

    if (this.isOnline) {
      balanceGet().then(res => {
        this.currentBalance = parseFloat(res.surplus)
        this.saveCurrentBalanceState(this.currentBalance)
      })
    }
  },

  watch: {
    notAvailable: function(newv, oldv) {
      this.$nextTick(() => {
        setTimeout(() => {
          this.$router.push({ name: 'home' })
        }, 3000)
      })
    },
    isLoading: function(newv, oldv) {
      this.$nextTick(() => {
        this.bindScrollEvent()
      })
    }
  },

  methods: {
    ...mapMutations({
      changeIndex: 'changeIndex',
      saveInfo: 'saveDetailInfo',
      saveSaleFlag: 'saveSaleFlag',
      saveCartState: 'saveCartState',
      setCurrentProductId: 'setCurrentProductId',
      savePrice: 'savePrice',
      saveCurrentBalanceState: 'saveCurrentBalanceState',
      saveDetailCouponInfo: 'saveDetailCouponInfo'
    }),

    /*
      getDetail: 获取商品详情， 并且存入状态管理
    */
    getDetail() {
      Indicator.open()

      this.setCurrentProductId(this.productId)
      giftGet(this.productId, this.$route.query.modproductkey)
        .then(
          res => {
            this.saveInfo(res)
            this.saveSaleFlag()
            this.savePrice(res.current_price)
          },
          error => {
            if (400 == error.errorCode) {
              // 商品已下架
              this.notAvailable = true
            }
          }
        )
        .finally(() => {
          Indicator.close()
          this.isLoading = false
        })
    },

    /**
     * 获取商品的优惠券
     */
    getCouponList() {
      getGoodsCouponList(this.productId).then(res => {
        this.saveDetailCouponInfo({
          id: this.productId,
          list: res
        })
      })
    },

    /**
     * 绑定滚动事件
     * 1、顶部透明度控制
     * 2、“回到顶部”按钮显示控制
     * 3、商品详情信息tab吸顶控制
     */
    bindScrollEvent() {
      let element = document.querySelector('.ui-detail-swiper')
      let scrollDOM = document.querySelector('.ui-common-swiper')
      const prodDetailEle = document.querySelector('.ui-detail')
      this.target = element
      if (element) {
        element.addEventListener('scroll', event => {
          let params = {
            top: element.scrollTop,
            height: element.scrollHeight
          }

          this.prodDetailOfstHt = prodDetailEle.offsetTop - 50

          if (params.top >= this.prodDetailOfstHt) {
            this.prodDetailIsFixed = true
            this.changeIndex(1)
          } else {
            this.prodDetailIsFixed = false
            this.changeIndex(0)
          }

          if (scrollDOM) {
            let scrollHeight = scrollDOM.offsetHeight - 50
            if (params.top >= scrollHeight) {
              this.isshowBacktop = true
              this.headerBackgroundOpacite = 1
            } else {
              this.isshowBacktop = false
              this.headerBackgroundOpacite = Number((params.top / scrollHeight).toFixed(1))
            }
          }
        })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.product-detail-wrapper {
  height: 100%;
  width: auto;
}
.content-empty {
  text-align: center;
  padding-top: 30px;

  .content-empty-img {
    width: 135px;
  }
  .content-empty-title {
    font-size: 18px;
    font-weight: 500;
    color: rgba(102, 102, 102, 1);
    line-height: 25px;
    margin-top: 13px;
  }
  .content-empty-des {
    font-size: 15px;
    font-weight: 400;
    color: rgba(153, 153, 153, 1);
    line-height: 21px;
    margin-top: 15px;
  }
  .content-empty-toIndex {
    margin-top: 50px;
    a {
      display: inline-block;
      border-radius: 2px;
      border: 1px solid rgba(85, 46, 32, 1);
      line-height: 36px;
      font-size: 14px;
      font-weight: 400;
      color: rgba(85, 46, 32, 1);
      line-height: 20px;
      padding: 7px 12px;
    }
  }
}
</style>
