<template>
  <div class="container">
    <div class="header-wrapper" :style="{ backgroundImage: `url(${shop_base_infos.shop_banner || baseImg})` }">
      <div @click="setShopInfo">
        <div class="img-wrapper">
          <div class="user_head" :style="{ backgroundImage: `url(${myShopIcon})` }"></div>
          <!-- <img src="../../assets/image/hh-icon/myStore/head@2x.png" v-if="!shop_base_infos.shop_icon" alt />
          <img :src="shop_base_infos.shop_icon" v-else alt /> -->
        </div>
        <div class="baseset-wrapper">
          <p class="store-title">{{ shop_base_infos.shop_name || '****' }}的小店</p>
          <p class="store-desc">
            {{ shop_base_infos.shop_desc || '欢迎光临我的小店' }}
          </p>
        </div>
      </div>
      <div class="share-wrapper" v-if="isHHApp">
        <!-- <div class="share-wrapper"> -->
        <img src="../../assets/image/hh-icon/myStore/icon-preview@2x.png" @click="previewStore" alt />
        <img
          src="../../assets/image/hh-icon/myStore/icon-share@2x.png"
          @click="shareMyShop"
          v-stat="{ id: 'myshop_share' }"
          alt
        />
      </div>
      <div class="statistics-wrapper" @click="detailInfos">
        <div class="left-border" v-stat="{ id: 'myshop_fans' }">
          <p>{{ shop_base_infos.fans_count }}</p>
          <p>粉丝数</p>
        </div>
        <div v-stat="{ id: 'myshop_paycount' }">
          <p>{{ shop_base_infos.pay_count }}</p>
          <p>付款笔数</p>
        </div>
        <div class="right-border" v-stat="{ id: 'myshop_money' }">
          <p>{{ utils.formatFloat(shop_base_infos.money_all) }}</p>
          <p>获取佣金</p>
        </div>
        <div class="right-border" @click.stop="toCshop" v-if="shop_base_infos.is_personal_supplier">
          <p>{{ shop_base_infos.personal_goods_count }}</p>
          <p>自有商品</p>
        </div>
      </div>
    </div>
    <!-- 列表弹框 -->
    <v-add-pro v-if="addBySelf"></v-add-pro>
    <!-- 未导入 -->
    <div class="content-wrapper" v-if="hasProduct">
      <div class="cart-list-empty">
        <img src="../../assets/image/hh-icon/empty-list-icon.png" />
        <p>店铺里空荡荡的</p>
        <gk-button class="button" type="primary-secondary-white" @click="addSupplier">去添加</gk-button>
      </div>
    </div>
    <!-- 导入 -->
    <v-pro-list v-else></v-pro-list>
    <!-- 新手引导 -->
    <new-owner-guide v-if="showGuide"></new-owner-guide>
    <mt-actionsheet :actions="storeShareActions" v-model="some_operate"></mt-actionsheet>
    <template v-if="shareUrl">
      <popup-photo-share
        ref="storeShopShare"
        v-model="mlmShare"
        :options="options"
        :share_options="share_options"
      ></popup-photo-share>
    </template>
  </div>
</template>
<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { PopupPhotoShare } from '../../components/common'
import AddStoreProducts from './child/AddStoreProducts'
import ProductList from './child/ProductList'
import NewOwnerGuide from './child/NewOwnerGuide'
import { Indicator, MessageBox, Toast } from 'mint-ui'
import { getStoreProductList, getMyShopInfo, getStoreSharePhoto } from '../../api/huanhuanke'
import { MYSTORE_SHARE } from './static'
export default {
  name: 'myStore',
  beforeRouteEnter(to, from, next) {
    next(vm => {
      if (!vm.isOnline) {
        vm.$router.replace({ name: 'login', params: {} })
      }
    })
  },
  data() {
    return {
      baseImg: require('../../assets/image/hh-icon/myStore/icon-mystore@2x.png'),
      headIcon: require('../../assets/image/hh-icon/myStore/head@2x.png'),
      showGuide: false,
      params: {
        cat_id: 0,
        page: 1,
        per_page: 10,
        type: 1
      },

      storeShareActions: [
        {
          name: '分享小店海报',
          method: this.shareShopPoster
        },
        {
          name: '分享小店链接',
          method: this.shareShopLink
        }
      ],
      some_operate: false,

      // 分享相关
      shareUrl: '',
      mlmShare: false,
      options: ['WechatSession', 'WechatTimeline'],
      share_options: {
        info: '',
        thumb: '',
        actName: '',
        description: ''
      }
    }
  },
  components: {
    'v-add-pro': AddStoreProducts,
    'v-pro-list': ProductList,
    NewOwnerGuide
  },
  computed: {
    ...mapState({
      addBySelf: state => state.mystore.addBySelf,
      hasProduct: state => state.mystore.hasProduct,
      isOnline: state => state.auth.isOnline,
      shop_base_infos: state => state.mystore.shop_base_infos,
      counterForTabRefresh: state => state.app.counterForTabRefresh
    }),
    myShopIcon() {
      return this.shop_base_infos.shop_icon || this.headIcon
    }
  },
  created() {
    this.shopInit()
  },
  watch: {
    counterForTabRefresh(value) {
      // 更新数据
      this.shopInit()
    }
  },
  methods: {
    ...mapMutations({
      hideAddShelf: 'hideAddShelf',
      setCategoryLists: 'setCategoryLists',
      setHasProduct: 'setHasProduct',
      setShopBaseInfos: 'setShopBaseInfos'
    }),
    // 设置我的小店基本信息
    setShopInfo() {
      this.$router.push({ name: 'shopInfo' })
    },
    // 预览我的小店
    previewStore() {
      this.$router.push({ name: 'shop', params: { id: this.shop_base_infos.id }, query: { review: 1 } })
    },
    // 点击分享
    shareMyShop() {
      if (this.hasProduct) {
        MessageBox({
          title: '',
          message: '没有商品的小店，不完美哟！</br>快去添加商品吧~',
          showCancelButton: true,
          closeOnClickModal: false,
          cancelButtonText: '取消',
          cancelButtonClass: 'cancel-button',
          confirmButtonClass: 'confirm-button-red',
          confirmButtonText: '去添加'
        }).then(action => {
          if (action === 'confirm') {
            this.$router.push({ name: 'pickGoods' })
          }
        })
      } else {
        this.some_operate = true
      }
    },
    // app分享我的小店链接
    shareShopLink() {
      this.some_operate = false
      /**
       * function: share
       * @params: (text, imgurl, platform, flag, title, url, description)
       */
      // Android当图片地址为null时无法分享
      this.hhApp.share(
        '万物有本则生，事事有道则解',
        this.utils.getShareImage(),
        MYSTORE_SHARE.platform,
        MYSTORE_SHARE.flag,
        `欢迎光临${this.shop_base_infos.shop_name}的小店`,
        encodeURIComponent(`${location.origin}${location.pathname}#/shop/${this.shop_base_infos.id}`),
        MYSTORE_SHARE.title
      )
    },
    // app分享我的小店海报
    shareShopPoster() {
      Indicator.open()
      getStoreSharePhoto({
        shop_sn: this.shop_base_infos.id
      }).then(
        res => {
          Indicator.close()
          this.some_operate = false
          this.shareUrl = res[0]
          this.share_options = {
            info: this.shareUrl.large,
            thumb: this.shareUrl.small,
            actName: '分享小店',
            description: `小店${this.shop_base_infos.shop_name}对自己小店的分享`
          }
          setTimeout(() => {
            this.$refs.storeShopShare.open()
          }, 10)
        },
        err => {
          Indicator.close()
        }
      )
    },
    addSupplier() {
      // 跳转分销商品池
      this.$router.push({ name: 'pickGoods' })
    },
    detailInfos() {
      // 跳转数据看板
      this.$router.push({ name: 'shopDashboard' })
    },

    /**
     * 初始化小店
     */
    shopInit() {
      // 初始化我的小店
      getMyShopInfo().then(res => {
        this.setShopBaseInfos(res)

        // 判断是否阅读新手引导
        if (res.is_first) {
          this.showGuide = true
        } else {
          this.getProductsList()
        }
      })
    },

    /**
     * 获取小店商品列表
     */
    getProductsList() {
      // 判断是否有商品，如果没有，判断是否为商家，如果是，则继续判断是否有自营商品，没有，弹框
      Indicator.open()
      getStoreProductList(this.params).then(res => {
        if (!res.list.length && !res.hotList.length) {
          this.hideAddShelf()
        } else {
          this.setHasProduct(false)
        }
        Indicator.close()
      })
    },
    toCshop() {
      this.$router.push({ name: 'Supplier', query: { id: this.shop_base_infos.is_personal_supplier } })
    }
  }
}
</script>
<style lang="scss" scoped>
.container {
  position: absolute;
  top: 0;
  width: 100%;
  bottom: 0;

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
        height: 18px;
        font-size: 13px;
        color: rgba(64, 64, 64, 1);
        line-height: 18px;
      }

      .store-desc {
        margin-top: 5px;
        width: 189px;
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
    }
  }

  .content-wrapper {
    position: relative;
    top: -11px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 375px;
    // height: 436px;
    min-height: calc(100vh - 234px);
    background: rgba(255, 255, 255, 1);
    border-radius: 1px;

    .cart-list-empty {
      background: #ffffff;
      text-align: center;

      img {
        width: 135px;
        height: 135px;
        // border: 1px dotted rgba(85, 46, 32, 0.6);
      }

      p {
        height: 25px;
        margin-top: 10px;
        font-size: 18px;
        font-weight: 500;
        color: rgba(102, 102, 102, 1);
        line-height: 25px;
      }

      .button {
        @include button($margin: 0 15px, $radius: 2px, $spacing: 2px);
        border: 1px solid rgba(85, 46, 32, 1);
        width: 140px;
        height: 36px;
        font-size: 14px;
        margin-top: 40px;
      }
    }
  }
}
</style>
