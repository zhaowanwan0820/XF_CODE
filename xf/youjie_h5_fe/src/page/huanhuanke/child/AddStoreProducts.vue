<template>
  <mt-popup v-model="popupVisible" position="center" @click="hideAddShelf">
    <div class="add-supplier">
      <div class="hot-supplier">
        <div class="hot-header">
          <p>免费开店·分销赚钱</p>
          <div class="hh-desc" style="margin-top: 16px">
            Hi，即将一夜暴富的
            <span>{{ shop_name }}</span>
            <br />{{ utils.storeNameForShort }}小店让您久等啦，让我们一起开启赚钱之旅吧！
          </div>
          <img src="../../../assets/image/hh-icon/myStore/recommend-title-card@2x.png" alt />
          <div class="hh-desc" style="margin-top: 13px;">
            在这里，您只要分享商品就能赚钱哦～～
            <br />快看看这些为您挑选的的最热销、最欢迎的商品吧：
          </div>
        </div>
        <div class="hot-content">
          <!-- 商品列表 -->
          <div class="hot-product-wrapper" v-for="product in recommendProductLists" :key="product.id">
            <!-- 图片 -->
            <div class="ui-image-wrapper">
              <img
                class="product-img"
                v-lazy="{
                  src: product.thumb,
                  error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
                  loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
                }"
              />
            </div>
            <!-- 文字 -->
            <div class="ui-words-wrapper">
              <div class="product-title">{{ product.name }}</div>
              <div class="price">
                <span class="icon">￥</span>
                <span class="price-num">{{ utils.formatFloat(product.price) }}</span>
                <div class="price-wrapper-flag">
                  <label class="left"></label>
                  <label class="high-price">
                    <span>最高赚￥{{ utils.formatFloat(product.rebate_price) }}</span>
                  </label>
                  <label class="right"></label>
                </div>
              </div>
              <div class="product-sales">已售{{ product.sales_count }}件</div>
            </div>
          </div>
        </div>
        <div class="hot-footer">
          <gk-button class="alert-button" type="primary-secondary-white" @click="hideAddShelf">自己挑挑</gk-button>
          <gk-button class="alert-button" @click="addBySystem">一键上架</gk-button>
        </div>
      </div>
    </div>
  </mt-popup>
</template>
<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { recommendProduct, lotsOnShelf } from '../../../api/huanhuanke'
export default {
  name: 'AddStoreProducts',
  data() {
    return {
      recommendProductLists: [],
      popupVisible: true
    }
  },
  created() {
    recommendProduct(null).then(res => {
      this.recommendProductLists = res.list
    })
  },
  mounted() {
    let _this = this
    document.querySelector('.v-modal').addEventListener('click', function() {
      _this.hideAddShelf()
    })
  },
  computed: {
    ...mapState({
      addBySelf: state => state.mystore.addBySelf,
      shop_name: state => state.mystore.shop_base_infos.shop_name
    })
  },
  methods: {
    ...mapMutations({
      hideAddShelf: 'hideAddShelf',
      setHasProduct: 'setHasProduct'
    }),
    addBySystem() {
      // 一键上架，直接添加，跳转到首页
      let arr = this.recommendProductLists.map(function(item) {
        return item.id
      })
      lotsOnShelf(arr).then(res => {
        this.hideAddShelf()
        this.setHasProduct(false)
        // 导入成功，调到分类商品列表页，展示到热卖中
      })
    }
  }
}
</script>
<style lang="scss" scoped>
.add-supplier {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);

  .hot-supplier {
    // position: relative;
    width: 300px;
    // height: 71vh;
    height: 573px;
    background: rgba(255, 255, 255, 1);
    border-radius: 6px;

    .hot-header {
      width: 100%;
      padding-top: 19px;
      display: flex;
      flex-direction: column;
      align-items: center;

      p {
        height: 17px;
        font-size: 17px;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        line-height: 17px;
        text-align: center;
      }

      img {
        width: 274px;
        height: 148px;
      }

      .hh-desc {
        height: 34x;
        @include sc(11px, rgba(150, 150, 150, 1));
        font-weight: 400;
        line-height: 17px;

        span {
          font-weight: 500;
          color: rgba(130, 28, 0, 1);
        }
      }
    }

    .hot-content {
      height: calc(100% - 339px);
      overflow: scroll;

      .hot-product-wrapper {
        display: flex;
        margin: 10px 15px;
        width: 270px;
        height: 100px;
        background: rgba(255, 255, 255, 1);
        box-shadow: 0px 0px 6px 0px rgba(208, 90, 90, 0.09);
        border-radius: 2px;

        .ui-image-wrapper {
          .product-img {
            margin: 10px 0 0 10px;
            width: 80px;
            height: 80px;
          }
        }

        .ui-words-wrapper {
          margin-left: 10px;
          overflow: hidden;

          .product-title {
            // 实现多行省略号
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin: 7px 10px 0 0;
            height: 34px;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 12px;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
            line-height: 17px;
          }

          .price {
            display: flex;
            align-items: center;
            margin-top: 11px;

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
              display: flex;
              align-items: center;
              justify-content: center;

              .left {
                background: url('../../../assets/image/hh-icon/mlm/bg-price-left.png') no-repeat;
              }

              .right {
                background: url('../../../assets/image/hh-icon/mlm/bg-price-right.png') no-repeat;
              }

              .left,
              .right {
                width: 12px;
                right: 12px;
                background-size: 12px 12px;
              }

              .high-price {
                width: 60%;
              }

              label {
                display: block;
                height: 12px;
                background: linear-gradient(to right, #eeacac, #d38686);
                display: flex;
                align-items: center;
                justify-content: center;

                span {
                  text-align: center;
                  white-space: nowrap;
                  display: inline-block;
                  @include sc(9px, #fff);
                  font-weight: 400;
                  line-height: 1;
                  text-align: center;
                }
              }
            }
          }

          .product-sales {
            margin-top: 5px;
            height: 14px;
            font-size: 10px;
            font-weight: 400;
            color: rgba(136, 136, 136, 1);
            line-height: 14px;
          }
        }
      }
    }

    .hot-footer {
      position: absolute;
      bottom: 0;
      width: 270px;
      height: 38px;
      padding: 12px 15px;
      z-index: 10;

      .alert-button {
        padding: 8px 26px 8px 27px;
        border: 1px solid rgba(119, 37, 8, 1);
        font-size: 16px;
        font-weight: 400;
      }

      button:last-child {
        background: #772508;
        color: #fff;
        float: right;
      }
    }
  }
}
</style>
