<template>
  <div class="product-list">
    <div class="btn-header">
      <gk-button>
        商品渠道/分销返佣
        <!-- <template v-if="type === 1">分销返佣</template>
        <template v-else>自营渠道</template>-->
      </gk-button>
      <gk-button type="primary-secondary-white" @click="continueAdd" v-stat="{ id: 'myshop_continueadd' }"
        >继续添加</gk-button
      >
    </div>
    <div class="product-content">
      <div class="category-flex">
        <div class="category-sidebar scroll-container-keepAlive">
          <ul>
            <li
              v-for="item in categorylists"
              :key="item.cat_id"
              @click="setCurrentCat(item)"
              :class="{
                sidbaractive: currentCate && item.cat_id == currentCate.cat_id,
                noActive: currentCate && item.cat_id != currentCate.cat_id
              }"
            >
              <a>{{ item.cat_name }}</a>
            </li>
          </ul>
        </div>
        <div
          class="category-content scroll-container-keepAlive"
          v-if="currentCate && productlists"
          v-infinite-scroll="getMore"
          infinite-scroll-distance="10"
        >
          <div class="product-wrapper" v-for="item in productlists" :key="item.id" @click="goDetail(item.id)">
            <div class="maininfo-wrapper">
              <div class="image-wrapper">
                <img
                  class="product-img"
                  v-lazy="{
                    src: item.thumb,
                    error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
                    loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
                  }"
                />
                <img
                  class="sticky-top"
                  src="../../../assets/image/hh-icon/myStore/stickytop@2x.png"
                  v-if="item.is_hot"
                />
                <img
                  class="reduce-price"
                  src="../../../assets/image/hh-icon/myStore/reduce-price@2x.png"
                  v-if="item.is_reduced"
                />
              </div>
              <div class="word-wrapper">
                <div>{{ item.name }}</div>
                <div class="price">
                  <span class="icon">￥</span>
                  <span class="price-num">{{ utils.formatFloat(item.price) }}</span>
                  <div class="price-wrapper-flag">
                    <label class="left"></label>
                    <label class="high-price">
                      <span>最高赚￥{{ utils.formatFloat(item.total_price) }}</span>
                    </label>
                    <label class="right"></label>
                  </div>
                </div>
              </div>
            </div>
            <div class="price-wrapper">
              <span>销量{{ item.sales_count }}</span>
              <div class="btn-more" @click.stop="someOperate(item.id, item.mlm_id, item.is_hot, item.cat_id)">
                <span></span>
                <span></span>
                <span></span>
              </div>
              <button class="btn-style" @click.stop="retailSimple(item)">分销单品</button>
            </div>
          </div>
          <!-- 商品列表底部提示 -->
          <div class="loading-wrapper">
            <p v-if="!isMore && productlists.length > 0">没有更多了</p>
            <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
          </div>
          <div class="wrapper-list-empty" v-if="productlists.length <= 0 && !isMore">
            <div>
              <img src="../../../assets/image/hh-icon/empty-list-icon.png" />
              <p>暂无任何商品</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- 底部弹出层 -->
    <mt-popup class="bottomAlert" v-model="popupVisible" position="bottom">
      <div class="footer-bg">
        <div class="footer-wrapper">
          <p class="footer-title">选择查看您的商品渠道</p>
          <img src="../../../assets/image/change-icon/close@2x.png" alt @click="popupVisible = false" />
          <div>
            <div @click="getCategoryList(1)">
              <p>分销返佣</p>
              <p>(0)</p>
            </div>
            <!-- <div @click="getCategoryList(2)">
              <p>自营渠道</p>
              <p>(0)</p>
            </div>-->
            <div>
              <p>自营渠道</p>
              <p>
                <a :href="'tel:' + service_tel">{{ service_tel }}</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </mt-popup>
    <mt-actionsheet :actions="operates" v-model="some_operate"></mt-actionsheet>
    <mt-actionsheet :actions="mlmProdShareActions" v-model="mlmProShareOperation"></mt-actionsheet>
    <template v-if="mlm_share_prod">
      <popup-photo-share
        ref="photoSharePopup"
        v-model="photoShareFlag"
        :options="photoSharePlantform"
        :share_options="photoShareOptions"
      ></popup-photo-share>
    </template>
  </div>
</template>
<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import {
  setFirstInList,
  cancelFirstList,
  getStoreProductList,
  removeFromShop,
  refreshProduct,
  getProdSharePhoto
} from '../../../api/huanhuanke'
import { PopupPhotoShare } from '../../../components/common'
import { MessageBox, Indicator, Toast } from 'mint-ui'
import { ENUM } from '../../../const/enum'
import { MYSTORE_SHARE } from '../static'
const SHARE_SAY_PLANS = [
  '我发现了一件物美价廉的商品，喜欢的话就出手吧！',
  '亲身体验，物美价廉，性价比超高！',
  '这里的价格本来就比X东X猫便宜，下单确认收货后，还能找领红包呦~~'
]
export default {
  name: 'ProductList',
  data() {
    return {
      SHARE_SAY_PLANS,
      JumpOutOperate: {
        id: '',
        action: ''
      },
      productlists: [], // 商品列表，将商品获取保存到当前页面
      isMore: true, // 是否具有更多商品
      loading: true, // 是否在加载数据
      page: 1,
      product_id: '',
      mlm_id: '',
      current_cat_id: '',
      popupVisible: false,
      some_operate: false,
      type: 1,
      operates: [],
      operates_a: [
        {
          name: '移出小店',
          method: this.removeProduct
        },
        {
          name: '调整价格',
          method: this.exitPrice
        }
      ],
      service_tel: ENUM.SERVICE.MASTER_TEL,

      // 单品分享相关
      photoShareFlag: false,
      photoSharePlantform: ['WechatSession', 'WechatTimeline'],
      photoShareOptions: {
        info: '',
        thumb: '',
        actName: '',
        description: ''
      },
      mlm_share_prod: '',
      mlmProdShareActions: [
        {
          name: '分享商品海报',
          method: this.shareProdPoster
        },
        {
          name: '分享商品链接',
          method: this.shareProdLink
        }
      ],
      mlmProShareOperation: false
    }
  },
  created() {
    this.getCategoryList(1)
    // actions中的shop_sn参数在此之前得到，所以赋值给this.shop_sn,再传过去获取不到
  },
  activated() {
    if (this.JumpOutOperate.action === 'editPrice') {
      // let params = { ...this.product_params }
      // params.productID = this.JumpOutOperate.id
      refreshProduct(this.product_params).then(res => {
        for (let i = 0; i < this.productlists.length; i++) {
          if (this.productlists[i].id === this.JumpOutOperate.id) {
            this.productlists.splice(i, 1, res.list[0])
            break
          }
        }
      })
    } else if (this.JumpOutOperate.action === 'continueAdd') {
      this.getCategoryList(1)
    }
  },
  computed: {
    ...mapState({
      categorylists: state => state.mystore.categorylists,
      currentCate: state => state.mystore.currentCate,
      // productlists: state => state.mystore.productlists,
      shop_sn: state => state.mystore.shop_base_infos.id,
      shop_base_infos: state => state.mystore.shop_base_infos,
      counterForTabRefresh: state => state.app.counterForTabRefresh
    }),
    product_params() {
      return {
        cat_id: this.currentCate.cat_id,
        productID: this.JumpOutOperate.id,
        page: this.page,
        per_page: 10,
        type: 1
      }
    }
  },
  watch: {
    currentCate: {
      handler() {
        this.productlists = []
      },
      deep: true
    },
    counterForTabRefresh(value) {
      this.getCategoryList(1)
    }
  },
  methods: {
    ...mapMutations({
      setCategoryLists: 'setCategoryLists',
      saveCurrentProductCate: 'saveCurrentProductCate'
    }),
    ...mapActions({
      fetchMlmCategoryList: 'fetchMlmCategoryList'
    }),
    // 获取类
    getCategoryList(n) {
      this.type = n

      if (n === 1) {
        this.fetchMlmCategoryList().then(res => {
          this.getProductList()
        })
      }
    },
    // 获取商品列表
    getProductList(isGetMore) {
      Indicator.open()
      // this.product_params.cat_id = this.currentCate.cat_id
      !isGetMore ? (this.page = 1) : void 0
      getStoreProductList(this.product_params).then(res => {
        if (this.productlists.length && isGetMore) {
          this.productlists = [...this.productlists, ...res.list]
        } else {
          this.productlists = res.list
        }
        this.isMore = res.paged && res.paged.more === 1 ? true : false
        this.loading = false
        Indicator.close()
      })
    },
    getMore() {
      if (this.loading) return
      if (this.isMore) {
        this.page++
        this.loading = true
        this.getProductList(true)
      }
    },
    // 设置当前类
    setCurrentCat(item) {
      if (item.cat_id != this.currentCate.cat_id) {
        this.saveCurrentProductCate(item)
        this.getProductList()
      }
    },
    // 操作商品
    someOperate(id, mlm_id, hot, cat_id) {
      // MessageBox.confirm()
      this.product_id = id
      this.mlm_id = mlm_id
      if (!hot) {
        this.operates = this.operates_a.concat([
          {
            name: '商品置顶',
            method: this.stickyTop
          }
        ])
      } else {
        this.operates = this.operates_a.concat([
          {
            name: '取消置顶',
            method: this.cancelTop
          }
        ])
      }
      this.current_cat_id = cat_id
      this.some_operate = true
    },
    // 移除商店
    removeProduct() {
      Indicator.open()
      // 调用移除商品接口
      removeFromShop(this.product_id).then(res => {
        // 刷新列表
        this.getProductList()
        Indicator.close()
        Toast('该商品已成功移出小店')
      })
    },
    stickyTop() {
      let count = 0
      this.productlists.forEach(function(item) {
        if (item.is_hot === 1) {
          count++
        }
      })
      if (count >= 2) {
        Toast('每个分类最多置顶两个商品')
        return
      }
      setFirstInList(this.product_id).then(res => {
        this.getProductList()
      })
    },
    cancelTop() {
      cancelFirstList(this.product_id).then(res => {
        this.getProductList()
      })
    },
    // 调整价格
    exitPrice() {
      this.JumpOutOperate.id = this.product_id
      this.JumpOutOperate.action = 'editPrice'
      this.$router.push({
        name: 'huankeShareCheckout',
        query: { id: this.product_id, mlm_id: this.mlm_id, isShop: '1' }
      })
    },
    // 同步商品
    importProducts() {
      // 先进行弹框提示
      MessageBox.confirm('确定将自己的商品加入到我的小店？').then(action => {
        // 调用接口，执行同步操作
      })
    },
    continueAdd() {
      // 继续添加商品
      this.JumpOutOperate.action = 'continueAdd'
      this.$router.push({ name: 'pickGoods' })
    },
    // 查看商品详情
    goDetail(id) {
      this.$router.push({ name: 'sharerDetail', query: { id: id } })
    },

    // 分销单品
    retailSimple(item) {
      this.mlm_share_prod = item
      this.mlmProShareOperation = true
    },
    // app分享商品链接
    shareProdLink() {
      this.mlmProShareOperation = false
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
        this.mlm_share_prod.name,
        encodeURIComponent(`${location.origin}${location.pathname}#/buyerProduct/${this.mlm_share_prod.mlm_id}`),
        MYSTORE_SHARE.title
      )
    },
    // app分享商品海报
    shareProdPoster() {
      Indicator.open()
      getProdSharePhoto({
        mlm_id: this.mlm_share_prod.mlm_id
      }).then(
        res => {
          Indicator.close()
          this.mlmProShareOperation = false
          const shareUrl = res[0]
          this.photoShareOptions = {
            info: shareUrl.large,
            thumb: shareUrl.small,
            actName: '商品分享',
            description: `小店${this.shop_base_infos.shopname}店主对自己商品${this.mlm_share_prod.mlm_id}的分享`
          }
          setTimeout(() => {
            this.$refs.photoSharePopup.open()
          }, 10)
        },
        err => {
          Indicator.close()
          Toast(err.errorMsg)
        }
      )
    }
  }
}
</script>
<style lang="scss" scoped>
.product-list {
  position: absolute;
  width: 100%;
  top: 185px;
  bottom: 0;
  background-color: #fff;
  border-radius: 8px 8px 0px 0px;

  .btn-header {
    height: 60px;
    font-weight: 400;

    button:first-child {
      width: 137px;
      height: 20px;
      margin-top: 25px;
      margin-left: 11px;
      font-size: 14px;
      color: rgba(64, 64, 64, 1);
      line-height: 20px;
    }

    button.sync-product {
      float: right;
      margin-right: 15px;
      margin-top: 20px;
      font-size: 13px;
      width: 50px;
      color: rgba(85, 46, 32, 1);
      height: 30px;
      background: rgba(255, 255, 255, 1);
      border-radius: 2px;
      border: 1px solid rgba(85, 46, 32, 1);
    }

    button:last-child {
      float: right;
      margin-right: 15px;
      margin-top: 20px;
      width: 84px;
      font-size: 13px;
      color: rgba(85, 46, 32, 1);
      height: 30px;
      background: rgba(255, 255, 255, 1);
      border-radius: 2px;
      border: 1px solid rgba(85, 46, 32, 1);
    }
  }

  .product-content {
    position: absolute;
    width: 100%;
    top: 60px;
    bottom: 0;

    .category-flex {
      display: flex;
      width: 100%;
      position: absolute;
      bottom: 0;
      width: 100%;
      top: 0;

      .category-sidebar {
        flex: 0 0 85px;
        background-color: $mainbgColor;
        overflow-y: auto;

        ul {
          li {
            display: block;
            padding: 15px 0;
            text-align: center;

            a {
              display: block;
              color: #888888;
              overflow: hidden;
              font-size: 14px;
            }
          }

          li.noActive {
            background-color: $mainbgColor;
            border-left: 2px solid transparent;
          }

          li.sidbaractive {
            background-color: #fff;

            a {
              color: #552e20;
              border-left: 3px solid #552e20;
            }
          }
        }
      }

      .category-content {
        flex: 1 0 0;
        overflow-y: auto;
        padding: 0 15px 10px 10px;

        .product-wrapper {
          // position: relative;
          width: 100%;
          padding: 15px 0 10px 0;
          //   height: 150px;
          border-bottom: 1px dotted rgba(85, 46, 32, 0.2);
          background-color: #fff;

          .maininfo-wrapper {
            display: flex;

            .image-wrapper {
              position: relative;
              width: 85px;
              height: 85px;

              img {
                width: 100%;
                height: 100%;
              }

              img.sticky-top {
                position: absolute;
                top: 0;
                width: 30px;
                height: 30px;
              }

              img.reduce-price {
                position: absolute;
                bottom: 0;
                right: 0;
                width: 32px;
                height: 12px;
              }
            }

            .word-wrapper {
              width: 170px;
              font-size: 13px;
              font-weight: 400;
              color: rgba(64, 64, 64, 1);
              line-height: 18px;
              margin-left: 10px;

              > div:first-child {
                overflow: hidden;
                height: 35px;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                margin-bottom: 17px;
              }

              .price {
                display: flex;
                align-items: center;

                .icon {
                  @include sc(10px, #772508);
                  font-weight: bold;
                  line-height: 11px;
                  padding-top: 4px;
                }

                .price-num {
                  font-size: 18px;
                  font-weight: bold;
                  color: #772508;
                  line-height: 21px;
                  padding-right: 8px;
                }

                .price-wrapper-flag {
                  @include price-wrapper-flag(
                    '../../../assets/image/hh-icon/mlm/bg-price-left.png',
                    '../../../assets/image/hh-icon/mlm/bg-price-right.png'
                  );
                }
              }
            }
          }

          .price-wrapper {
            padding-top: 10px;
            overflow: hidden;
            font-weight: 400;

            span {
              padding-top: 4px;
              font-size: 11px;
              color: rgba(136, 136, 136, 1);
              line-height: 16px;
            }

            .btn-style {
              background: none;
              border: 1px solid rgba(85, 46, 32, 1);
              padding: 4px 7px;
              font-size: 11px;
              color: rgba(85, 46, 32, 1);
              line-height: 16px;
              border-radius: 2px;
              float: right;
              margin-left: 15px;
            }

            .btn-more {
              background: transparent;
              display: flex;
              justify-content: space-between;
              align-items: center;
              height: 26px;
              width: 14px;
              float: right;
              margin-left: 20px;

              span {
                display: inline-block;
                width: 2px;
                height: 2px;
                background-color: #552e20;
                margin: 0;
                padding: 0;
              }
            }
          }
        }
      }
    }
  }

  .bottomAlert {
    height: auto !important;
  }

  .footer-bg {
    /* position: fixed;
    bottom: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 100; */
    .footer-wrapper {
      //   position: absolute;
      bottom: 0;
      width: 100%;
      height: 236px;
      background: rgba(255, 255, 255, 1);
      font-size: 16px;
      font-weight: 400;

      img {
        width: 14px;
        height: 14px;
        position: absolute;
        top: 19px;
        right: 15px;
      }

      p {
        text-align: center;
      }

      > div {
        margin-top: 30px;
        display: flex;
        justify-content: space-between;

        div {
          display: flex;
          justify-content: center;
          flex-direction: column;
          width: 119px;
          height: 119px;
          border-radius: 2px;
        }

        div:first-child {
          background: rgba(119, 37, 8, 1);
          color: rgba(255, 255, 255, 1);
          margin-left: 46px;
        }

        div:last-child {
          width: 118px;
          height: 118px;
          border: 0.5px solid rgba(85, 46, 32, 1);
          color: rgba(85, 46, 32, 1);
          margin-right: 44px;
        }
      }

      .footer-title {
        color: rgba(51, 51, 51, 1);
        padding-top: 15px;
      }
    }
  }
}

.loading-wrapper {
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 44px;

  p {
    color: #7c7f88;
    font-size: 12px;
    font-weight: 'Regular';
    padding: 0;
    margin: 0;
  }

  span {
    display: inline-block;
  }

  /deep/ .mint-spinner-triple-bounce-bounce1,
  /deep/ .mint-spinner-triple-bounce-bounce2,
  /deep/ .mint-spinner-triple-bounce-bounce3 {
    background-color: #f0f0f0 !important;
  }
}

.wrapper-list-empty {
  display: flex;
  justify-content: center;
  align-content: center;
  align-items: center;
  padding-top: 45%;

  div {
    display: flex;
    flex-direction: column;
    align-items: center;

    img {
      width: 75px;
      height: 75px;
    }

    p {
      text-align: center;
      margin-top: 11px;
      color: #a4aab3;
    }
  }
}
</style>
