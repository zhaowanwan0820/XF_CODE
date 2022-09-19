<template>
  <div class="prod-list-body">
    <div class="list-body" @click="goDetail">
      <div class="ui-image-wrapper">
        <img class="product-img" v-lazy="lazy" />
      </div>
      <div class="flex-right">
        <div class="product-name">
          {{ item.name }}
        </div>
        <div class="prod-price">
          <div class="hb-price">
            <div class="unit">￥</div>
            <div class="num">{{ utils.formatFloat(item.MONEY_SHOW, true) }}</div>
            <img src="../../../assets/image/change-icon/quanyi-price-icon.png" alt="" />
          </div>
          <div class="orig-price">
            <div class="unit">￥</div>
            <div class="num">{{ utils.formatFloat(item.current_price, true) }}</div>
            <img src="../../../assets/image/change-icon/orig-price-icon.png" alt="" />
          </div>
        </div>
      </div>
      <gk-button class="button" type="primary-secondary-white">立即购买</gk-button>
    </div>
  </div>
</template>

<script>
export default {
  name: '',
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

  methods: {
    goDetail() {
      this.$router.push({ name: 'product', query: { id: this.item.id } })
    }
  }
}
</script>

<style lang="scss" scoped="scoped">
.prod-list-body {
  margin-top: 22px;
  .list-body {
    display: flex;
    justify-content: flex-start;
    align-items: stretch;
    position: relative;
  }
  .ui-image-wrapper {
    flex-shrink: 0;
    width: 100px;
    height: 100px;
    border-radius: 2px;
    background: linear-gradient(180deg, rgba(249, 249, 249, 1) 0%, rgba(253, 251, 251, 1) 100%);
    img {
      width: 100%;
    }
  }
  .flex-right {
    margin-left: 10px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    flex: 1;
    .product-name {
      margin-top: 2px;
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
    }
    .prod-price {
      margin-bottom: 5px;
    }
    .hb-price {
      display: flex;
      align-items: baseline;
      color: #b75800;
      line-height: 1;
      .unit {
        font-size: 14px;
        font-family: PingFangSC-Semibold, PingFangSC;
        font-weight: 600;
      }
      .num {
        font-size: 20px;
        font-family: PingFangSC-Medium, PingFangSC;
        font-weight: 500;
      }
      img {
        height: 15px;
        margin-left: 2px;
      }
    }
    .orig-price {
      display: flex;
      align-items: baseline;
      line-height: 1;
      color: #666666;
      margin-top: 2px;
      .unit {
        display: inline-block;
        @include sc(12px, #666666, center);
        font-family: PingFangSC-Regular, PingFangSC;
      }
      .num {
        font-size: 16px;
      }
      img {
        height: 10px;
      }
    }
  }
  .button {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 66px;
    height: 22px;
    border-radius: 11px;
    font-size: 12px;
  }
}
</style>
