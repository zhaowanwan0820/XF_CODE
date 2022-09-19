<template>
  <div
    class="money-coupon"
    @click="openCouponPopup"
    v-if="detailInfo.id && detailInfo.id == couponInfo.id && couponInfo.list && couponInfo.list.length > 0"
  >
    <div class="money-coupon-title">优惠</div>
    <div class="discount-coupon">
      <div v-for="item in couponInfo.list"><span class="unit">￥</span>{{ item.coupon_price }}</div>
    </div>
    <img src="../../../assets/image/change-icon/icon_more.png" />
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
export default {
  name: '',
  data() {
    return {}
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo,
      couponInfo: state => state.detail.couponInfo
    })
  },

  methods: {
    ...mapMutations({
      saveCouponPopupState: 'saveCouponPopupState'
    }),

    /**
     * Opens a coupon popup. 打开优惠券蒙层
     */
    openCouponPopup() {
      this.saveCouponPopupState(true)
    }
  }
}
</script>

<style lang="scss" scoped="scoped">
.money-coupon {
  padding: 0 15px;
  height: 50px;
  display: flex;
  align-items: center;
  @include thin-border(#dbdbdb, 15px, auto, true);
  .money-coupon-title {
    color: #999999;
    text-align: left;
    font-size: 14px;
  }
  .discount-coupon {
    flex: 1;
    margin-left: 10px;
    padding-right: 55px;
    height: 31px;
    font-size: 0;
    overflow: hidden;
    div {
      min-width: 50px;
      box-sizing: border-box;
      display: inline-block;
      height: 21px;
      line-height: 21px;
      padding: 1px 8px 0;
      background-color: #c9b594;
      position: relative;
      border-radius: 2px;
      font-size: 12px;
      color: #ffffff;
      text-align: center;
      margin-top: 5px;
      & + div {
        margin-left: 10px;
      }
      &:after,
      &:before {
        position: absolute;
        border-radius: 6px;
        display: block;
        content: '';
        width: 6px;
        height: 6px;
        background-color: #ffffff;
        top: 50%;
      }
      &:before {
        left: 0;
        transform: translate(-50%, -50%);
      }
      &:after {
        right: 0;
        transform: translate(50%, -50%);
      }
      .unit {
        letter-spacing: -1px;
        margin-left: -1px;
      }
    }
  }
  .money-coupon-rules {
    display: inline-block;
    @include sc(11px, #999999);
    line-height: 1;
    margin-right: 3px;
  }
  & > img {
    width: 19px;
  }
}
</style>
