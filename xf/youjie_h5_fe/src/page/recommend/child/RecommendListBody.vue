<template>
  <div class="product-info" @click="productClick">
    <div class="product-good">
      <img class="product-icon" v-lazy="lazy" />
      <span class="icon-sellOut" v-if="item.good_stock == 0"></span>
    </div>
    <div class="product-msg">
      <div class="product-title" ref="text">{{ item.name }}</div>
      <div class="product-price-wrapper">
        <!-- <div class="hb-price">
          <div class="unit">￥</div>
          <div class="num">{{ utils.formatFloat(item.MONEY_SHOW, true) }}</div>
          <img src="../../../assets/image/change-icon/quanyi-price-icon.png" alt="" />
        </div>
        <div class="orig-price">
          <div class="unit">￥</div>
          <div class="num">{{ utils.formatFloat(item.current_price, true) }}</div>
          <img src="../../../assets/image/change-icon/orig-price-icon.png" alt="" />
        </div> -->
        <price-item 
          :cash="item.MONEY_SHOW" 
          :surplus="item.HB_SHOW" 
          class="product-price-wrapper"
        ></price-item>
      </div>
    </div>
  </div>
</template>

<script>
import PriceItem from '../../../components/common/ListItemPrice'
export default {
  name: 'RecommendListBody',
  data() {
    return {
      lazy: {
        src: this.item.thumb,
        error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
        loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
      }
    }
  },
  props: ['item'],
  created() {},
  computed: {},
  methods: {
    productClick: function() {
      this.$router.push({ name: 'product', query: { id: this.item.id } })
    }
  },
  components:{
    PriceItem,
  }
}
</script>

<style lang="scss" scoped>
.product-info {
  box-sizing: border-box;
  // flex: 0 0 33%; /*这里百分25表示项目大小占1/4空间*/
  width: 155px;
  margin-bottom: 9px;
  position: relative;
  overflow: hidden;
  background-color: #ffffff;
  padding-bottom: 10px;
  border-radius: 6px;
  & + .product-info {
    margin-left: 13px;
  }
  .product-good {
    position: relative;
    border-radius: 5px;
    background-color: #f9f9f9;
    width: 155px;
    height: 0;
    margin: 0 auto;
    padding-top: 120px;
    border-radius: 3px;
    overflow: hidden;
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
  .product-msg {
    padding: 0 10px;
  }
  .product-icon {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: 0;
    height: 100%;
  }
  .product-title {
    font-family: PingFangSC-Regular, PingFangSC;
    font-weight: 400;
    color: $baseColor;
    font-size: 12px;
    line-height: 17px;
    margin-top: 7px;
    height: 32px;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    /*! autoprefixer: ignore next */
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }
  .product-price-wrapper {
    margin-top: 8px;
    & > div {
      display: flex;
      align-items: baseline;
    }
    .hb-price {
      color: #b75800;
      line-height: 1;
      .unit {
        font-size: 12px;
        font-family: PingFangSC-Semibold, PingFangSC;
        font-weight: 600;
      }
      .num {
        font-size: 16px;
        font-family: PingFangSC-Medium, PingFangSC;
        font-weight: 500;
      }
      img {
        height: 10px;
        margin-left: 3px;
      }
    }
    .orig-price {
      line-height: 1;
      color: #666666;
      margin-top: 2px;
      .unit {
        display: inline-block;
        @include sc(10px, #666666, center);
        font-family: PingFangSC-Regular, PingFangSC;
      }
      .num {
        font-size: 14px;
      }
      img {
        height: 10px;
      }
    }
  }
  &.border {
    margin-left: 7px;
  }
}
</style>
