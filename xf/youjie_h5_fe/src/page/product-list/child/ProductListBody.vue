<!-- ProductListBody.vue -->
<template>
  <div class="ui-product-body">
    <div class="list" v-on:click="goDetail">
      <div class="ui-image-wrapper">
        <img class="product-img" v-lazy="lazy" />
        <span class="saled" v-if="item.good_stock == 0"></span>
        <span class="only-have" v-if="item.good_stock > 0 && item.good_stock <= 10">
          <span>仅剩{{ item.good_stock }}件</span>
        </span>
        <!-- <activity-icon-on-product
          v-if="item.act_616_type"
          :offset="true"
          :typeCode="item.act_616_type"
        ></activity-icon-on-product> -->
      </div>

      <div class="flex-right">
        <div class="row1">
          <div class="product-header">
            <div class="title clear-bottom">
              <label class="title-content">
                <template v-if="item.supplier">
                  <!-- 新接口将商家type移到商家对象中 -->
                  <label v-if="item.supplier.type == 1" class="title-header head1">
                    <span>&nbsp;专区商品&nbsp;</span>
                  </label>
                  <label v-if="item.supplier.type == 3" class="title-header head3">
                    <span>&nbsp;专区商品&nbsp;</span>
                  </label>
                  <label v-if="item.supplier.type == 5" class="title-header head5">
                    <span>&nbsp;个人商家&nbsp;</span>
                  </label>
                </template>
                {{ item.name }}
              </label>
            </div>
          </div>

          <!-- 汽车类商品 分期展示效果 -->
          <div class="hb-discount-title" v-if="item.instalment && item.instalment.length > 0">
            <span class="hb-price-desc">
              <img src="../../../assets/image/hh-icon/b0-home/money-icon.png" class="sur-icon" />
              <span class="hb-price-desc-txt">
                {{
                  `${utils.formatMoney(item.instalment[item.instalment.length - 1].surplus)}积分+￥${utils.formatMoney(
                    item.instalment[item.instalment.length - 1].cash
                  )}`
                }}
              </span>
              <span class="fq-length"
                ><span>{{ item.instalment[item.instalment.length - 1].num || item.instalment.length }}期</span></span
              >
            </span>
          </div>
          <div class="price" v-else>
            <!-- <span class="orig-price"
              ><span class="price-unit">￥</span><label>{{ utils.formatFloat(item.current_price, true) }}</label></span
            >
            <span class="hb-price" v-if="item.HB_SHOW > 0"
              ><span class="price-unit">￥</span><label>{{ utils.formatFloat(item.MONEY_SHOW, true) }}</label
              ><img src="../../../assets/image/change-icon/quanyi-price-icon.png" alt=""
            /></span> -->
            <price-item :cash="item.MONEY_SHOW" :surplus="item.HB_SHOW" class="product-price-wrapper" classType="price-style-one"></price-item>
          </div>

          <div class="promotions">
            <template v-if="item.promos && item.promos.length > 0">
              <template v-for="(promo, index) in item.promos">
                <span v-if="promo.status == 2 && promo.type == 1 && !promo.is_over" :key="index"
                  ><span>满{{ promo.detail.limit }}减{{ promo.detail.reduce }}</span></span
                >
                <label class="none" v-if="promo.status == 2 && promo.type == 2 && !promo.is_over" :key="index"
                  ><span>{{ utils.activityNameTmp }}</span></label
                >
                <label class="none" v-if="promo.status == 2 && promo.type == 3 && !promo.is_over" :key="index"
                  ><span>{{ utils.activityNameTmp }}</span></label
                >
              </template>
            </template>
          </div>
        </div>
        <div class="sendway" @click.stop="goSupplier(item.supplier.sn)">
          <span v-if="item.supplier" class="self-support">
            {{ item.supplier.shop_name }}
          </span>
          <span v-else class="self-support"></span>
          <span>销量{{ item.sales_count }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { PRODUCT_SHOW_SHOUQI } from '../../product-detail/static.js'
import { mapState } from 'vuex'
import PriceItem from '../../../components/common/ListItemPrice'

export default {
  data() {
    return {
      lazy: {
        src: this.item.thumb,
        error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
        loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
      }
    }
  },
  props: ['item', 'productId', 'requestparams'],

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    })
  },

  methods: {
    goDetail() {
      let data = Object.assign({}, { id: this.productId }, this.requestparams)
      this.$router.push({ name: 'product', query: { id: this.productId } })
    },
    goSupplier(id) {
      this.$router.push({ name: 'Supplier', query: { id: id } })
    }
  },
  components:{
    PriceItem,
  }
}
</script>

<style lang="scss" scoped>
.ui-product-body {
  .list {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0 0 15px;
    position: relative;
    div.ui-image-wrapper {
      width: 110px;
      height: 110px;
      position: relative;
      border-radius: 3px;
      overflow: hidden;
      margin-right: 20px;
      margin-bottom: 10px;

      display: flex;
      justify-content: center;
      align-content: center;
      align-items: center;
      flex-basis: 110px;
      flex-shrink: 0;

      background-color: #f9f9f9;

      .product-img {
        width: 100%;
      }
      span {
        position: absolute;
        font-size: 0;
        &.only-have {
          display: flex;
          align-items: center;
          justify-content: center;
          line-height: 20px;
          text-align: center;
          width: 100%;
          height: 13px;
          background: rgba(210, 185, 120, 0.3);
          border-radius: 0 0 4px 4px;
          bottom: 0;
          left: 0;
          span {
            position: relative;
            display: inline-block;
            @include sc(8px, #563f19);
          }
        }
        &.saled {
          position: absolute;
          left: auto;
          top: -1px;
          right: -1px;
          width: 38px;
          height: 38px;
          background: url('../../../assets/image/hh-icon/b0-home/sellout-icon.png') no-repeat;
          background-size: 100%;
        }
      }
    }

    span.promos {
      position: absolute;
      background: url('../../../assets/image/change-icon/label@2x.png') no-repeat;
      width: 36px;
      height: 19px;
      color: #fff;
      font-size: 10px;
      top: 0;
      left: 0;
      background-size: cover;
      font-weight: 100;
      line-height: 19px;
      text-align: left;
      padding-left: 5px;
    }
    .flex-right {
      flex: 1;
      padding: 0 15px 10px 0;
      min-height: 120px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow-x: hidden;

      @include thin-border(rgba(85, 46, 32, 0.2), 0, auto, true);
      .title {
        color: $baseColor;
        font-size: 14px;
        font-weight: normal;
        line-height: 20px;

        max-height: 40px;
        overflow: hidden;
        text-overflow: ellipsis;

        display: -webkit-box;
        display: flex;

        -webkit-line-clamp: 2;

        box-orient: vertical;
        -webkit-box-orient: vertical;

        margin-bottom: 8px;

        .title-content {
          // display: inline-block;
          max-height: 60px;
        }
        &.clear-bottom {
          margin-bottom: 0;
        }
        .title-header {
          display: inline-block;
          height: 18px;
          border-radius: 10px;
          line-height: 18px;
          padding: 0 4px;
          margin-right: 3px;
          span {
            display: inline-block;
            @include sc(10px, #fff);
          }
        }
        .head1 {
          background-color: #d8aab7;
        }
        .head2 {
          background-color: #c2b5cf;
        }
        .head3 {
          background-color: #d8aab7;
        }
        .head5 {
          background-color: #b5c884;
        }
      }

      .product-header {
        display: flex;
      }
      .price {
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 0;
        display: flex;
        align-items: baseline;
        margin-top: 5px;
        .orig-price {
          width: auto;
          color: #b75800;
          font-weight: bold;
          margin-right: 2px;
          margin-left: -1px;
          img {
            width: 12px;
            height: 12px;
            margin-right: 1px;
            transform: translateY(0.5px);
          }
          label {
            font-size: 18px;
            letter-spacing: -0.5px;
          }
          .price-unit {
            letter-spacing: 0;
            display: inline-block;
            @include sc(10px, #b75800);
            margin-right: -1px;
            line-height: 1;
          }
          .product-money-icon {
            width: 10px;
            height: 10px;
            transform: translateY(0.5px);
            margin-right: 1px;
          }
        }
        .hb-price {
          width: auto;
          color: #404040;
          font-weight: 400;
          label {
            font-size: 14px;
            letter-spacing: -0.6px;
            font-weight: 600;
          }
          .price-unit {
            letter-spacing: 0;
            display: inline-block;
            @include sc(9px, #404040);
            margin-right: -2px;
            line-height: 1;
          }
          img {
            width: 27px;
          }
        }
      }
      .promotions {
        min-height: 22px;
        padding: 9px 0 9px;
        overflow: hidden;

        & > span,
        & > label {
          white-space: nowrap;
          padding: 0 5px;
          height: 16px;
          border: 1px solid #b75800;
          border-radius: 50px;
          font-size: 0;
          display: inline-block;
          & + span {
            margin-left: 7px;
          }
          span {
            display: inline-block;
            @include sc(10px, #b75800);
          }
        }
        label {
          display: none;
          background-color: transparent;
          border-color: #b75800;
          &:nth-of-type(1) {
            display: inline-block;
          }
          span {
            color: #b75800;
          }
        }
      }
      .sendway {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        font-weight: 400;

        span {
          line-height: 16px;
          white-space: nowrap;
          @include sc(10px, #999999, right center);
          &.self-support {
            max-width: 175px;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 10px;
            @include sc(10px, #999999, left center);

            &::after {
              content: '';
              position: absolute;
              top: 0;
              right: 0;
              width: 8px;
              height: 16px;
              background: url('../../../assets/image/hh-icon/supplier/icon-tip.png') no-repeat right center;
            }
            img {
              width: 5px;
              height: 8px;
              margin-left: 2px;
            }
          }
        }
        div {
          width: 22px;
          height: 20px;
          position: relative;
          img {
            position: absolute;
            right: 0;
            bottom: 0;
          }
        }
      }
    }
  }
}

/* 汽车分期价格展示 */
.hb-discount-title {
  display: flex;
  align-items: center;
  margin-top: 12px;

  .hb-price-desc {
    display: flex;
    align-items: center;
    line-height: 1;
    .sur-icon {
      width: 16px;
    }
    .hb-price-desc-txt {
      font-size: 16px;
      font-weight: 700;
      color: #b75800;
      transform: none;
      margin-left: 1px;
    }
    .fq-length {
      background: #634903;
      border-radius: 6px;
      font-weight: 500;
      line-height: 12px;
      padding: 0 4px;
      margin-left: 2px;
      display: inline-block;

      span {
        display: inline-block;
        @include sc(9.5px, #fff);
      }
    }
  }
}
</style>
