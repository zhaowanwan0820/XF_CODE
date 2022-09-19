<template>
  <div class="product-info" @click="productClick">
    <div class="product-good">
      <img class="product-icon" v-if="item.thumb" :src="item.thumb" v-lazy="item.thumb" />
      <img class="product-icon" src="../../../assets/image/change-icon/default_image_02@2x.png" v-else />
      <span class="icon-sellOut" v-if="item.good_stock == 0"></span>
      <span class="icon-rebate" v-if="(userBondInfo.amount > 0 || userBondInfo.balance > 0) && item.rebate > 0"
        ><span>成单可获得返佣￥{{ utils.formatFloat(item.rebate) }}</span></span
      >
    </div>
    <div class="product-msg">
      <div class="product-title" ref="text">{{ item.name }}</div>
      <div class="product-price-wrapper">
        <p class="product-price">
          <span class="current-price">
            <span class="price-unit">￥</span>
            <label>{{ utils.formatFloat(item.price, true) }}</label>
          </span>
          <span class="orig-price" v-if="item.market_price >= item.price">
            <span class="price-unit">￥</span><label>{{ utils.formatFloat(item.market_price, true) }}</label>
          </span>
        </p>
      </div>
      <div class="share-count">{{ item.live_total }} 名{{ utils.mlmUserName }}正在推荐</div>
    </div>
  </div>
</template>

<script>
import { ENUM } from '../../../const/enum'
import { mapState } from 'vuex'
export default {
  name: 'HomeProductHotBody',
  data() {
    return {
      showRightBorder: this.index % 3 != 0
    }
  },
  props: ['item', 'productId'],
  created() {},
  computed: {
    ...mapState({
      userBondInfo: state => state.auth.userBondInfo
    })
  },
  methods: {
    productClick: function() {
      this.$router.push({ name: 'sharerDetail', query: { id: this.item.id } })
    }
  }
}
</script>

<style lang="scss" scoped>
// .border {
//     border-right: 1px solid #F5F5F5;
// }
.product-info {
  box-sizing: border-box;
  // flex: 0 0 33%; /*这里百分25表示项目大小占1/4空间*/
  width: 165px;
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
  .product-good {
    position: relative;
    width: 100%;
    height: 0;
    padding-top: 100%;
    border-radius: 3px;
    overflow: hidden;
    background: #f9f9f9;
    .icon-sellOut {
      position: absolute;
      top: -1px;
      right: -1px;
      width: 38px;
      height: 38px;
      background: url('../../../assets/image/hh-icon/b0-home/sellout-icon.png') no-repeat;
      background-size: 100%;
    }
    .icon-rebate {
      position: absolute;
      bottom: 0;
      width: 100%;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f1ece2;
      span {
        white-space: nowrap;
        @include sc(9px, #7b4b2d);
      }
    }
  }
  .product-msg {
    padding: 0 5px;
  }
  .product-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
  }
  .product-title {
    color: $baseColor;
    font-size: 12px;
    line-height: 15px;
    margin-top: 10px;
    height: 29px;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: normal;
    display: -webkit-box;
    /*! autoprefixer: ignore next */
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }
  .product-price-wrapper {
    margin-top: 8px;
    .product-price {
      display: flex;
      justify-content: flex-start;
      align-items: baseline;
      font-size: 0;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      line-height: 1;
      .current-price {
        width: auto;
        color: #772508;
        font-weight: bold;
        margin-right: 1px;
        margin-left: -1px;
        label {
          font-size: 16px;
          letter-spacing: -0.5px;
        }
        .price-unit {
          letter-spacing: 0;
          display: inline-block;
          @include sc(11px, #772508);
          margin-right: -1px;
          line-height: 1;
        }
      }
      .orig-price {
        display: inline-block;
        position: relative;
        color: #999999;
        &:before {
          content: '';
          display: block;
          position: absolute;
          left: 3px;
          right: -1px;
          top: 50%;
          height: 1px;
          background-color: #999999;
        }
        label {
          font-size: 12px;
        }
        .price-unit {
          letter-spacing: -1px;
          display: inline-block;
          line-height: 1;
          @include sc(9px, #999999, right bottom);
        }
      }
    }
  }
  .share-count {
    display: inline-block;
    line-height: 1;
    @include sc(9px, #999999, left center);
  }
  &.border {
    margin-left: 7px;
  }
}
</style>
