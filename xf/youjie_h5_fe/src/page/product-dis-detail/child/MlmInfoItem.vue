<template>
  <div class="mlm-detail-info clearfix" v-if="detailInfo">
    <div class="info-header ui-flex">
      <div class="price info-price">
        <span class="price-unit">￥</span>
        <span>{{ utils.formatFloat(detailInfo.price) }}</span>
        <label class="old-price" v-if="detailInfo.origin_price && detailInfo.origin_price.price >= detailInfo.price"
          >￥{{ utils.formatFloat(detailInfo.origin_price.price) }}</label
        >
      </div>
      <div class="max-earn-wrapper" :class="{ 'is-fixed': isMaxEarnFixed }">
        <span>最高赚￥{{ utils.formatFloat(detailInfo.max_earn, true) }}</span>
      </div>
    </div>

    <!-- 积分抵扣部分 -->
    <!-- 后期可能会区分用户显示（爱投资/其他） -->
    <div class="hb-iscount" v-if="detailInfo.id && detailInfo.origin_price" @click="showHBRules">
      <div class="hb-discount-title">
        <!-- <span class="hn-price">
          <span class="price-unit">￥</span>
          <span class="price-count">{{ utils.formatFloat(detailInfo.MONEY_SHOW) }}</span>
        </span> -->
        <span class="hb-price-desc">
          <!-- <img src="../../../assets/image/hh-icon/b0-home/money-icon.png" class="sur-icon" /> -->
          <!-- <span class="hb-price-desc-txt">
            {{
              `${utils.formatFloat(detailInfo.origin_price.HB_SHOW)}积分+￥${utils.formatFloat(
                detailInfo.origin_price.MONEY_SHOW
              )}`
            }}
          </span> -->
          <span class="hb-price-desc-txt">
            {{ `￥${utils.formatFloat(detailInfo.origin_price.MONEY_SHOW)}` }}
          </span>
          <!-- <img src="../../../assets/image/hh-icon/b0-home/debt-left-angle.png" class="sur-img" /> -->
          <div class="quanyi-wrapper">
            <img src="../../../assets/image/hh-icon/l0-list-icon/debt-left-angle.png" />
            <div>
              <label class="debt-style">{{ `积分抵扣￥${utils.formatFloat(detailInfo.origin_price.HB_SHOW)}` }}</label>
            </div>
          </div>
        </span>
      </div>
      <img src="../../../assets/image/change-icon/icon_more.png" />
    </div>

    <div class="product-live" v-if="productSharer.length > 0">
      <div class="product-live-header" @click="showMoreSharer">
        <span class="title">{{ sharerTotal }}名店主正在分销返佣</span>
        <span class="more" v-if="productSharer.length > 2"
          >查看更多<img src="../../../assets/image/change-icon/icon_more.png"
        /></span>
      </div>
      <div class="product-live-body">
        <template v-for="(item, index) in productSharer" v-if="index < 2">
          <div class="product-live-item">
            <img v-if="item.avatar" :src="item.avatar" alt="用户头像" />
            <img v-else src="../../../assets/image/hh-icon/mlm/mlm-buyer-avator@3x.png" alt="默认头像" />
            <span>{{ item.nickname }}</span>
          </div>
        </template>
      </div>
    </div>

    <!-- 商品名称 -->
    <div class="prod-name">{{ detailInfo.name }}</div>

    <div class="detailinfo-sub ui-flex">
      <span>{{ shipTxt }}</span>
      <span>销量 {{ $accounting.formatNumber(detailInfo.sales_count) }}</span>
      <span>库存 {{ $accounting.formatNumber(detailInfo.good_stock) }}</span>
    </div>
    <!-- 商家 -->
    <info-suppliers></info-suppliers>
  </div>
</template>

<script>
import { mapState, mapMutations, mapGetters } from 'vuex'
import { MessageBox } from 'mint-ui'
import InfoSuppliers from './InfoSuppliers'
import { productLiveGet } from '../../../api/mlm'

export default {
  data() {
    return {
      isShowSharer: false,
      title: '这些${this.utils.mlmUserName}正在推荐该商品',
      defaultImg: require('../../../assets/image/hh-icon/mlm/mlm-buyer-avator@3x.png')
    }
  },

  props: {
    isMaxEarnFixed: {
      type: Boolean,
      default: false
    }
  },

  components: {
    InfoSuppliers
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo,
      productSharer: state => state.detail.productSharer,
      sharerTotal: state => state.detail.sharerTotal
    }),
    shipping() {
      return this.detailInfo.format_shipping_price
    },
    shipTxt() {
      let txt
      if (this.shipping.head_fee) {
        switch (this.shipping.free_type) {
          case 1:
            txt = '满' + this.shipping.free_sum + '件包邮'
            break
          case 2:
            txt = '满' + this.utils.formatFloat(this.shipping.free_fee) + '元包邮'
            break
          case 3:
            txt = '满' + this.shipping.free_sum + '件或满' + this.utils.formatFloat(this.shipping.free_fee) + '元包邮'
            break
          default:
            txt = '运费￥' + this.utils.formatFloat(this.shipping.head_fee)
        }
      } else {
        txt = '包邮'
      }
      return txt
    }
  },
  methods: {
    ...mapMutations({
      saveHBRulesPopupState: 'saveHBRulesPopupState'
    }),
    showMoreSharer() {
      if (this.productSharer.length < 3) {
        return
      }
      let msg = ''
      msg += '<div calss="sharer-popup">'
      for (let item in this.productSharer) {
        msg += `<div class="product-live-item">
                  <img src="${this.productSharer[item].avatar || this.defaultImg}" alt="用户头像" />
                  <span>${this.productSharer[item].nickname}</span>
                </div>`
      }
      if (this.productSharer.length == 10) {
        msg += '<p class="product-live-p">仅显示最近10个${this.utils.mlmUserName}</p></div>'
      }
      MessageBox({
        title: `这些${this.utils.mlmUserName}正在推荐该商品`,
        message: msg,
        confirmButtonText: '知道了'
      })
    },
    /*
        showHBRules: 显示积分说明
       */
    showHBRules() {
      this.saveHBRulesPopupState(true)
    }
  }
}
</script>

<style>
.product-live-item {
  height: 34px;
  display: flex;
  justify-content: center;
  align-items: center;
}
.product-live-item img {
  width: 22px;
  height: 22px;
  border-radius: 11px;
  overflow: hidden;
}
.product-live-item span {
  font-size: 12px;
  color: #666666;
  margin-left: 10px;
}
.product-live-p {
  font-size: 12px;
  color: #999999;
  margin-top: 10px;
}
</style>
<style lang="scss" scoped>
.mint-msgbox {
  z-index: 9999;
}
.mlm-detail-info {
  padding-top: 15px;
  background: #fff;
  .ui-flex {
    display: flex;
    justify-content: space-between;
    align-content: center;
    align-items: center;
    flex-basis: 100%;
    width: auto;
  }

  .info-header {
    display: flex;
    justify-content: space-between;
    align-content: center;
    align-items: center;
    flex-basis: 100%;
    width: auto;
    padding: 0 15px;
    justify-content: flex-start;
    height: 32px;
    position: relative;
    .max-earn-wrapper {
      width: 116px;
      height: 32px;
      position: absolute;
      top: 0;
      right: 0;
      color: #ffffff;
      line-height: 32px;
      text-align: center;
      font-size: 13px;
      background-image: url('../../../assets/image/hh-icon/detail/max-earn-bg.png');
      background-color: transparent;
      background-repeat: no-repeat;
      background-position: center center;
      background-size: 100% 100%;
      &.is-fixed {
        position: fixed;
        top: 50px;
        z-index: 1;
      }
    }

    .price {
      display: flex;
      justify-content: space-between;
      align-content: center;
      align-items: center;
      position: relative;
      font-weight: bold;
      font-size: 0;
      display: flex;
      &.info-price {
        display: flex;
        align-items: baseline;
        span {
          font-weight: 600;
        }
      }
      span {
        @include sc(23px, #772508);
      }
      .price-unit {
        @include sc(15px, #772508);
      }
      span {
        display: block;
        font-weight: normal;
      }
      .old-price {
        font-size: 13px;
        font-weight: normal;
        line-height: 18px;
        color: #979797;
        line-height: 16px;
        text-decoration: line-through;
        margin-left: 5px;
        margin-bottom: -7px;
      }
    }
  }

  .hb-iscount {
    padding: 0 15px;
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    align-items: center;
    .hb-discount-title {
      color: #666666;
      font-size: 14px;
      display: flex;
      align-items: center;
      .ltr-spc {
        letter-spacing: 1px;
      }
      .unit {
        font-size: 12px;
        margin-right: -2px;
      }
      .hn-price {
        min-width: 32px;
        color: #707070;
        margin-right: 2px;
        margin-left: -1px;
        line-height: 1;
        .price-unit {
          letter-spacing: 0;
          display: inline-block;
          @include sc(11px, #707070);
          margin-right: -1px;
          line-height: 1;
        }
        .price-count {
          font-size: 16px;
          color: #707070;
        }
      }
      .hb-price-desc {
        display: flex;
        align-items: center;
        line-height: 1;
        .sur-icon {
          width: 13px;
        }
        .sur-img {
          width: 29px;
          height: 10px;
        }
        .quanyi-wrapper {
          @include quanyi-wrapper();
        }
        .hb-price-desc-txt {
          margin-left: -2px;
          display: inline-block;
          @include sc(11px, #999999);
        }
      }
    }
    & > img {
      width: 19px;
    }
  }

  .prod-name {
    font-size: 14px;
    font-weight: 500;
    color: rgba(102, 102, 102, 1);
    line-height: 21px;
    padding: 0 15px;
    margin-top: 15px;
    word-break: break-all;
    .tags-box {
      display: inline-block;
      background-color: #d5b4be;
      border-radius: 10px;
      height: 18px;
      line-height: 18px;
      padding: 0 6px;
      font-size: 0;
      margin-right: 5px;
      span {
        display: inline-block;
        @include sc(10px, #ffffff);
      }
    }
  }

  .detailinfo-sub {
    font-size: 12px;
    color: #808080;
    margin-top: 10px;
    padding: 0 15px;
    background: #fff;
    height: 30px;

    span {
      display: inline-block;
      line-height: 17px;
    }
  }

  .product-live {
    @include thin-border(#dbdbdb, 15px, auto, true);
    .product-live-header {
      display: flex;
      padding: 0 15px;
      justify-content: space-between;
      align-items: center;
      height: 40px;
      span.title {
        color: #404040;
        font-size: 14px;
      }
      span.more {
        display: flex;
        align-items: center;
        @include sc(11px, #999999, right center);
        img {
          width: 19px;
          margin-left: 5px;
        }
      }
    }
    .product-live-body {
      padding: 0 15px 15px;
      .product-live-item {
        height: 34px;
        display: flex;
        justify-content: flex-start;
        align-items: center;
        img {
          width: 22px;
          height: 22px;
          border-radius: 11px;
          overflow: hidden;
        }
        span {
          font-size: 12px;
          color: #666666;
          margin-left: 10px;
        }
      }
    }
  }
}
</style>
