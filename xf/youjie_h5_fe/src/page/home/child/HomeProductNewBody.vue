<template>
  <div class="product-info" @click="productClick">
    <div class="product-food">
      <img class="product-icon" v-lazy="lazy" />
      <!-- <activity-icon-on-product
        v-if="item.act_616_type"
        :offset="true"
        :typeCode="item.act_616_type"
      ></activity-icon-on-product> -->
    </div>
    <div class="product-title">
      <span>{{ item.name }}</span>
    </div>
    <div class="product-bottom">
      <p class="product-price">
        <span class="orig-price">
          <span class="price-unit">￥</span>
          <label>{{ utils.formatFloat(item.MONEY_SHOW, true) }}</label>
          <img src="../../../assets/image/change-icon/quanyi-price-icon.png" alt="" />
        </span>
      </p>
      <p class="product-price">
        <span class="hb-price" v-if="item.HB_SHOW > 0">
          <span class="price-unit">￥</span>
          <label>{{ utils.formatFloat(item.current_price, true) }}</label>
        </span>
      </p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HomeProductHotBody',
  data() {
    return {
      lazy: {
        src: this.item.thumb,
        error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
        loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
      }
    }
  },
  props: ['item', 'index'],
  methods: {
    productClick: function() {
      this.$router.push({ name: 'product', query: { id: this.item.id } })
    }
  }
}
</script>

<style lang="scss" scoped>
.product-info {
  box-sizing: border-box;
  border-radius: 2px;
  width: 104px;
  margin-bottom: 10px;
  padding-bottom: 10px;
  background: linear-gradient(180deg, rgba(249, 249, 249, 1) 0%, rgba(253, 251, 251, 1) 100%);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  align-items: center;
  &:before {
    content: '';
    display: block;
    position: absolute;
    top: 0;
    right: 0;
    width: 58px;
    height: 15px;
    background-image: url('../../../assets/image/hh-icon/b0-home/prod-new-bg.png');
    background-size: 58px;
  }
  .product-food {
    position: relative;
    width: 70px;
    height: 70px;
    margin-top: 15px;
    overflow: hidden;
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
    font-size: 13px;
    width: 101.5px;
    line-height: 14px;
    height: 12px;
    display: flex;
    justify-content: center;
    margin: 7px 5px 0;
    overflow: hidden;
    span {
      flex-grow: 1;
      display: inline-block;
      @include sc(11px, #404040, center);
      text-align: left;
      word-break: break-all;
      overflow: hidden;
    }
  }
  .product-bottom {
    align-self: stretch;
    padding: 0 6px;
    margin-top: 2px;
    .product-price {
      flex-grow: 1;
      display: flex;
      justify-content: flex-start;
      align-items: baseline;
      font-size: 0;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      margin-top: 3px;
      .orig-price {
        margin-left: -1px;
        label {
          display: inline-block;
          line-height: 1;
          font-size: 12px;
          color: #b75800;
          font-weight: bold;
          letter-spacing: -0.5px;
        }
        .price-unit {
          letter-spacing: 0;
          display: inline-block;
          @include sc(8px, #b75800, left 10px);
          font-weight: bold;
          margin-right: -5px;
          line-height: 1;
        }
        img {
          margin-left: 3px;
          width: 23px;
        }
      }
      .hb-price {
        width: auto;
        color: #404040;
        font-weight: 400;
        margin-left: -1px;
        font-family: PingFangSC-Medium;
        label {
          font-size: 13px;
          letter-spacing: -0.6px;
          @include sc(11px, #404040, left bottom);
          line-height: 1;
          display: inline-block;
        }
        .price-unit {
          letter-spacing: 0;
          display: inline-block;
          @include sc(8px, #404040, left 10px);
          margin-right: -5px;
          line-height: 1;
        }
      }
    }
    .product-buy {
      color: #b5b6b6;
      margin-top: 5px;
      font-size: 10px;
      line-height: 18px;
      white-space: nowrap;
      margin-left: 5px;
      text-align: right;
    }
  }
}
</style>
