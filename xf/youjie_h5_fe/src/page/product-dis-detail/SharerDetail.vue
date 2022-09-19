<template>
  <div class="product-detail-wrapper" v-if="productDetail">
    <share-header
      v-if="!isPreviewPicture"
      :headerBackgroundOpacite="headerBackgroundOpacite"
      :prodDetailOfstHt="prodDetailOfstHt"
    ></share-header>
    <!-- body -->
    <div class="ui-detail-swiper">
      <!-- 轮播图 -->
      <info-goods-swipe></info-goods-swipe>
      <!-- 商品信息 -->
      <mlm-info-item :isMaxEarnFixed="isMaxEarnFixed"></mlm-info-item>

      <!-- 商家服务标签 -->
      <info-serve-tag></info-serve-tag>
      <!-- 商家信息 -->
      <info-supplier-msg></info-supplier-msg>
      <product-desc :prodDetailIsFixed="prodDetailIsFixed" :prodDetailOfstHt="prodDetailOfstHt"></product-desc>
    </div>

    <detail-footer></detail-footer>

    <!-- 预览图片 -->
    <preview-picture v-if="isPreviewPicture" :defaultindex="swipeId" :isshow="isPreviewPicture"></preview-picture>

    <!-- 服务说明popup -->
    <popup-serve-tag v-if="isShowServeTag" :isShowServeTag="isShowServeTag"></popup-serve-tag>

    <!-- 积分说明popup -->
    <h-b-rules-popup v-if="isShowHBRules" :isShowHBRules="isShowHBRules"></h-b-rules-popup>

    <!-- 回到顶部 -->
    <v-back-top v-if="isshowBacktop" :target="target"></v-back-top>
  </div>
</template>

<script>
import ShareHeader from './ShareHeader'
import DetailFooter from './SharerFooter'

import ProductDesc from './child/ProductDesc'
import MlmInfoItem from './child/MlmInfoItem'

import BackTop from '../product-detail/child/DetailBackTop'
import HBRulesPopup from '../product-detail/child/HBRulesPopup'
import InfoServeTag from '../product-detail/child/InfoServeTag'
import PopupServeTag from '../product-detail/child/PopupServeTag'
import PreviewPicture from '../product-detail/child/PreviewPicture'
import InfoGoodsSwipe from '../product-detail/child/InfoGoodsSwipe'
import InfoSupplierMsg from '../product-detail/child/InfoSupplierMsg'

import { mlmProductGet, productLiveGet } from '../../api/mlm'
import { mapState, mapMutations } from 'vuex'

export default {
  data() {
    return {
      product: this.$route.query.id ? this.$route.query.id : '',
      productDetail: {},
      isshowBacktop: false,
      target: null,
      headerBackgroundOpacite: 0,
      // 用来控制详情页二级固定
      prodDetailOfstHt: 0, // 详情页距离父顶的高度
      prodDetailIsFixed: false, // 是否可以fixed
      isMaxEarnFixed: false // 最高赚取是否fixed
    }
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      isPreviewPicture: state => state.detail.isPreviewPicture,
      swipeId: state => state.detail.swipeId,
      isShowHBRules: state => state.detail.isShowHBRules,
      isShowServeTag: state => state.detail.isShowServeTag
    })
  },

  components: {
    InfoGoodsSwipe,
    ProductDesc,
    PreviewPicture,
    MlmInfoItem,
    InfoServeTag,
    InfoSupplierMsg,
    PopupServeTag,
    ShareHeader,
    HBRulesPopup,
    DetailFooter,
    'v-back-top': BackTop
  },

  created() {
    this.getDetail()
    this.getProdLiveList()
  },

  mounted() {
    this.$nextTick(() => {
      const headerHeight = document.querySelector('.ui-detail-header').offsetHeight
      let element = document.querySelector('.ui-detail-swiper')
      let scrollHeight = document.querySelector('.ui-common-swiper').offsetHeight - headerHeight
      const prodDetailEle = document.querySelector('.ui-detail')
      const prodMaxEarnEle = document.querySelector('.info-header')
      this.target = element
      element.addEventListener('scroll', event => {
        let params = {
          top: element.scrollTop,
          height: element.scrollHeight
        }

        // 顶部商品/详情 tabs切换
        this.prodDetailOfstHt = prodDetailEle.offsetTop - headerHeight
        if (params.top >= this.prodDetailOfstHt) {
          this.prodDetailIsFixed = true
          this.changeIndex(1)
        } else {
          this.prodDetailIsFixed = false
          this.changeIndex(0)
        }

        let maxEarnCanFixedHeight = prodMaxEarnEle.offsetTop - headerHeight
        if (params.top >= maxEarnCanFixedHeight) {
          this.isMaxEarnFixed = true
        } else {
          this.isMaxEarnFixed = false
        }

        if (params.top >= scrollHeight) {
          this.isshowBacktop = true
          this.headerBackgroundOpacite = 1
        } else {
          this.isshowBacktop = false
          this.headerBackgroundOpacite = Number((params.top / scrollHeight).toFixed(1))
        }
      })
    })
  },

  methods: {
    ...mapMutations({
      changeIndex: 'changeIndex',
      saveInfo: 'saveDetailInfo',
      saveMlmProduct: 'saveMlmProduct',
      saveProductSharer: 'saveProductSharer'
    }),

    /*
      getDetail: 获取商品详情， 并且存入状态管理
    */
    getDetail() {
      mlmProductGet(this.product).then(
        res => {
          this.productDetail = res
          this.saveInfo(this.productDetail)
          this.saveMlmProduct(this.productDetail)
          let title = this.productDetail.name
          let name = res.name
          let link = res.share_url ? res.share_url : res.share_link
        },
        error => {
          console.log(error)
        }
      )
    },

    /**
     * Gets the product live list.
     * 获取商品分享详情
     */
    getProdLiveList() {
      const params = {
        product: this.product,
        page: 1,
        per_page: 10
      }
      productLiveGet(params).then(res => {
        this.saveProductSharer(res)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-detail-swiper {
  position: absolute;
  width: 100%;
  top: 0;
  bottom: 50px;
  left: 0;
  right: 0;
  overflow-y: auto;
  overflow-x: hidden;
  z-index: 0;
  /* border: 1px solid red; */
  background: rgba(240, 242, 245, 1);
}
.product-detail-wrapper {
  height: 100%;
  width: auto;
}
</style>
