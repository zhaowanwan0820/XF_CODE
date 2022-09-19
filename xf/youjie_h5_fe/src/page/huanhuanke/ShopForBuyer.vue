<template>
  <div class="container scroll-container-keepAlive" ref="container">
    <div class="back-wrapper" v-if="isHHApp">
      <img src="../../assets/image/hh-icon/icon-header-返回.svg" alt="" @click="goBack" />
    </div>
    <div class="share-wrapper" v-if="isReview && isHHApp">
      <img src="../../assets/image/hh-icon/myStore/icon-share@2x.png" @click="shareStore" />
    </div>

    <div class="header-wrapper" :style="{ backgroundImage: `url(${shopInfos.backgroundImage})` }">
      <div class="img-wrapper">
        <div class="user_head" :style="{ backgroundImage: `url(${shopInfos.avatorImage})` }"></div>
      </div>
      <div class="baseset-wrapper">
        <p class="store-title">{{ shopInfos.sname }}的小店</p>
        <p class="store-desc">
          {{ shopInfos.welcome }}
        </p>
      </div>
      <div class="statistics-wrapper">
        <div class="left-border" v-stat="{ id: 'myshop_fans' }">
          <p>{{ shopInfos.fans }}</p>
          <p>粉丝数（人）</p>
        </div>
        <div>
          <p>{{ shopInfos.productSum }}</p>
          <p>商品数（个）</p>
        </div>
        <div class="right-border" @click="toCshop" v-if="shopInfos.is_personal_supplier">
          <p>{{ shopInfos.personal_goods_count }}</p>
          <p>小店精选（个）</p>
        </div>
      </div>
    </div>
    <v-pro-list></v-pro-list>
  </div>
</template>
<script>
import { mapState, mapMutations } from 'vuex'
import { Indicator } from 'mint-ui'
import { getShopInfo } from '../../api/huanhuanke'
import ProductList from './child/ShopProductList'
import { MYSTORE_SHARE } from './static'

export default {
  name: 'shop',
  data() {
    return {
      sn: this.$route.params.id, // 小店sn
      shopInfos: {
        backgroundImage: require('../../assets/image/hh-icon/shop4buyer/wall-default@2x.png'),
        avatorImage: require('../../assets/image/hh-icon/shop4buyer/avator-default@2x.png'),
        sname: '****',
        fans: 0,
        productSum: 0,
        moneySaved: 0,
        welcome: '欢迎光临我的小店',
        is_personal_supplier: '', // 个人商家的商家识别码
        personal_goods_count: 0 // 个人商家的商品数量
      },
      // 是否来自 【预览】
      isReview: this.$route.query.review ? true : false,
      containerScroll: null
    }
  },
  computed: {
    ...mapState({
      containerScrollTop: state => state.shop.containerScrollTop
    })
  },
  watch: {
    containerScrollTop: function(newV) {
      this.utils.scrollTopAni(this.$refs.container, newV, 120)
    }
  },
  components: {
    'v-pro-list': ProductList
  },
  created() {
    // 获取店铺信息
    this.fetchShopInfo(this.isReview)

    // 记录 小店SN
    this.saveInviteCode(this.sn)
  },
  methods: {
    ...mapMutations(['saveInviteCode']),
    goBack() {
      this.$_goBack()
    },
    fetchShopInfo(isReview) {
      getShopInfo({ sn: this.sn }).then(res => {
        const data = res
        data.shop_banner && (this.shopInfos.backgroundImage = data.shop_banner)
        data.shop_icon && (this.shopInfos.avatorImage = data.shop_icon)
        data.shop_desc && (this.shopInfos.welcome = data.shop_desc)
        this.shopInfos.sname = data.shop_name
        this.shopInfos.fans = data.fans_count
        this.shopInfos.productSum = data.goods_total
        this.shopInfos.moneySaved = data.save_total
        this.shopInfos.is_personal_supplier = data.is_personal_supplier
        this.shopInfos.personal_goods_count = data.personal_goods_count

        document.title = this.shopInfos.sname + '的小店'
      })
    },
    shareStore() {
      this.hhApp.share(
        '万物有本则生，事事有道则解',
        this.utils.getShareImage(),
        MYSTORE_SHARE.platform,
        MYSTORE_SHARE.flag,
        this.shopInfos.sname,
        encodeURIComponent(`${location.origin}${location.pathname}#/shop/${this.sn}`),
        MYSTORE_SHARE.title
      )
    },
    toCshop() {
      this.$router.push({ name: 'Supplier', query: { id: this.shopInfos.is_personal_supplier } })
    }
  }
}
</script>
<style lang="scss" scoped>
.container {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 100%;
  overflow-y: auto;

  .header-wrapper {
    position: relative;
    height: 196px;
    background-size: cover;
    background-position: 50%;
    background-repeat: no-repeat;
    background-color: #fff;
    // @include bis('../../assets/image/hh-icon/myStore/icon-mystore@2x.png');

    .img-wrapper {
      position: absolute;
      left: 16px;
      top: 34px;
      width: 40px;
      height: 40px;
      // margin: 34px 0 0 16px;
      border-radius: 50%;
      // opacity: 0.4;
      border: 2px solid rgba(255, 255, 255, 1);

      div.user_head {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-size: cover;
        background-position: 50%;
        background-repeat: no-repeat;
        background-color: #fff;
      }
    }

    .baseset-wrapper {
      position: absolute;
      left: 66px;
      top: 34px;

      p {
        font-weight: 400;
      }

      .store-title {
        font-size: 13px;
        line-height: 18px;
        color: rgba(64, 64, 64, 1);
      }

      .store-desc {
        margin-top: 5px;
        padding-right: 15px;
        height: 32px;
        font-size: 11px;
        color: rgba(85, 46, 32, 1);
        line-height: 16px;
        display: -webkit-box;
        -webkit-line-clamp: 2; // 显示两行
        /*! autoprefixer: ignore next */
        -webkit-box-orient: vertical;
        text-overflow: ellipsis;
        overflow: hidden;
      }
    }

    .share-wrapper {
      position: absolute;
      top: 30px;
      right: 15px;

      img {
        width: 31px;
        height: 31px;
        border-radius: 16px;
        margin-left: 8px;
      }
    }

    .statistics-wrapper {
      position: absolute;
      z-index: 2;
      display: flex;
      align-items: center;
      left: 15px;
      top: 100px;
      width: 345px;
      height: 70px;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0px 0px 6px 0px rgba(8, 8, 8, 0.2);
      border-radius: 6px;

      div {
        text-align: center;
        width: 100%;

        p:first-child {
          font-size: 18px;
          font-weight: bold;
          color: rgba(119, 37, 8, 1);
        }

        p:last-child {
          font-size: 11px;
          font-weight: 400;
          color: rgba(136, 136, 136, 1);
        }
      }

      .left-border {
        border-right: 1px dotted rgba(85, 46, 32, 0.2);
      }

      .right-border {
        border-left: 1px dotted rgba(85, 46, 32, 0.2);
      }
      .right-border .icon {
        font-size: 14px;
      }
    }
  }
}
.share-wrapper {
  position: absolute;
  z-index: 10;
  top: 12px;
  right: 15px;
  font-size: 0;

  img {
    width: 31px;
    height: 31px;
    border-radius: 50%;
  }
}
.back-wrapper {
  position: absolute;
  z-index: 10;
  top: 12px;
  left: 15px;
  img {
    width: 8px;
    height: 16px;
  }
}
</style>
