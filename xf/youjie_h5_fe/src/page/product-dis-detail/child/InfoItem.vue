<template>
  <div class="ui-detail-info clearfix" v-if="detailInfo">
    <!-- 原价 专享价 -->
    <div class="price">
      <span class="price-unit">￥</span>
      <span class="current-price">{{ utils.formatFloat(detailInfo.seller.current_price) }}</span>
      <div class="o-p-con">
        <span class="old-price">￥{{ utils.formatFloat(detailInfo.market_price) }}</span>
        <span class="save-price">可省￥{{ utils.formatFloat(detailInfo.seller.save_price || 0) }}</span>
      </div>
    </div>

    <!-- 商品名称 -->
    <div class="prod-name">{{ detailInfo.name }}</div>
  </div>
</template>

<script>
import { mapState, mapMutations, mapGetters } from 'vuex'

export default {
  data() {
    return {}
  },
  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    }),
    hideOldPrice() {
      return this.utils.formatFloat(this.detailInfo.market_price) <= this.utils.formatFloat(this.detailInfo.shop_price)
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-detail-info {
  background: #fff;
}

.ui-flex {
  display: flex;
  justify-content: space-between;
  align-content: center;
  align-items: center;
  flex-basis: 100%;
  width: auto;
}

.price {
  display: block;
  height: 57px;
  box-sizing: border-box;
  background: url('../../../assets/image/hh-icon/mlm/price-background@3x.png') no-repeat 0 50%;
  background-size: cover;
  font-size: 0;
  padding-left: 11px;
  padding-top: 4px;

  .price-unit {
    font-size: 22px;
    font-weight: bold;
    color: rgba(119, 37, 8, 1);
    line-height: 26px;
    display: inline-block;
    vertical-align: baseline;
  }
  .current-price {
    font-size: 37px;
    font-weight: bold;
    color: rgba(119, 37, 8, 1);
    line-height: 43px;
    display: inline-block;
    vertical-align: baseline;
    margin-left: 2px;
  }
  .o-p-con {
    vertical-align: text-bottom;
    margin-left: 8px;
    display: inline-block;
    font-size: 0;

    .old-price {
      display: block;
      font-size: 13px;
      color: rgba(188, 167, 146, 1);
      line-height: 18px;
      text-decoration: line-through;
      margin-bottom: 1px;
    }
    .save-price {
      display: inline-block;
      line-height: 14px;
      background: rgba(155, 33, 11, 1);
      border-radius: 7px;
      padding: 0 5px;
      font-size: 11px;
      color: rgba(255, 255, 255, 1);
    }
  }
}

.prod-name {
  font-size: 14px;
  font-weight: 500;
  color: rgba(102, 102, 102, 1);
  line-height: 21px;
  padding: 0 15px;
  margin: 15px 0 15px;
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
  margin: 14px 0 15px;
  padding: 0 15px;
  background: #fff;

  span {
    display: inline-block;
    line-height: 17px;
  }
}

.visibleHide {
  visibility: hidden;
}
</style>
