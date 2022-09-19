<template>
  <div class="product-info" v-bind:class="{ border: showRightBorder }" @click="productClick">
    <div class="product-food">
      <img
        class="product-icon"
        v-lazy="{
          src: item.thumb,
          error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
          loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
        }"
      />
      <span class="icon-sellOut" v-if="item.good_stock == 0"></span>
    </div>
    <div class="product-title" ref="text">{{ item.name }}</div>
    <div class="product-bottom">
      <p class="product-price">
        <span class="current-price">
          <span class="price-unit">￥</span>
          <label>{{ utils.formatFloat(item.price, true) }}</label>
        </span>
        <span class="orig-price" v-if="item.market_price > 0">
          <span class="price-unit">￥</span><label>{{ utils.formatFloat(item.market_price, true) }}</label>
        </span>
      </p>
    </div>
  </div>
</template>

<script>
import { ENUM } from '../../../const/enum'
export default {
  name: 'HomeProductHotBody',
  data() {
    return {
      showRightBorder: this.index % 3 != 0
    }
  },
  props: ['item', 'index'],
  created() {},
  methods: {
    productClick: function() {
      this.$emit('click')
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
  width: 110px;
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
  .product-food {
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
  }
  .product-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
  }
  .product-title {
    color: rgba(165, 108, 55, 1);
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
  .product-bottom {
    margin-top: 8px;
    .product-price {
      display: flex;
      justify-content: flex-start;
      align-items: baseline;
      font-size: 0;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      .current-price {
        width: auto;
        color: #b75800;
        font-weight: bold;
        margin-right: 1px;
        margin-left: -1px;
        label {
          font-size: 15px;
          letter-spacing: -0.5px;
        }
        .price-unit {
          letter-spacing: 0;
          display: inline-block;
          @include sc(10px, #b75800);
          margin-right: -1px;
          line-height: 1;
        }
      }
      .orig-price {
        display: inline-block;
        position: relative;
        color: rgba(165, 108, 55, 0.6);
        &:before {
          content: '';
          display: block;
          position: absolute;
          left: 3px;
          right: -1px;
          top: 50%;
          height: 1px;
          background-color: rgba(165, 108, 55, 0.6);
        }
        label {
          font-size: 12px;
        }
        .price-unit {
          letter-spacing: -1px;
          display: inline-block;
          line-height: 1;
          @include sc(9px, rgba(165, 108, 55, 0.6), right bottom);
        }
      }
    }
  }
  &.border {
    margin-left: 7px;
  }
}
</style>
