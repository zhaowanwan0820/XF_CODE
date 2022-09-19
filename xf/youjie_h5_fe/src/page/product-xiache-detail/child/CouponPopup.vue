<!-- Shopping.vue -->
<template>
  <mt-popup v-model="isShowCouponPopup" position="bottom" v-bind:close-on-click-modal="false" style="height: 77%;">
    <div
      class="favorable-container"
      v-if="detailInfo.id && detailInfo.id == couponInfo.id && couponInfo.list.length > 0"
    >
      <div class="favorable-header">
        <span class="title">领券</span>
        <div class="tips">
          <span>限时秒杀、找便宜、我的小店、分期租购暂不支持使用优惠券</span>
        </div>
        <img src="../../../assets/image/hh-icon/icon-关闭.png" @click="closeCouponPopup" />
      </div>
      <div class="favorable-body">
        <template v-for="(item, index) in couponInfo.list" v-if="couponInfo.list.length > 0">
          <coupon-item
            :item="item"
            :key="index"
            @onclick="itemCilck(item, index)"
            :isDetail="true"
            :class="{ 'is-rec': item.is_rec == 2 }"
          ></coupon-item>
        </template>
      </div>
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { Toast, MessageBox, Button } from 'mint-ui'
import CouponItem from './CouponItem'
import { getACoupon } from '../../../api/coupon'

export default {
  data() {
    return {}
  },

  props: {
    isShowCouponPopup: {
      type: Boolean,
      default: false
    }
  },

  components: {
    CouponItem
  },

  created() {},

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      detailInfo: state => state.detail.detailInfo,
      couponInfo: state => state.detail.couponInfo
    })
  },

  mounted() {},

  methods: {
    ...mapMutations({
      saveCouponPopupState: 'saveCouponPopupState',
      updateCouponInfo: 'updateCouponInfo'
    }),

    // 关闭优惠券浮层
    closeCouponPopup() {
      this.saveCouponPopupState(false)
    },

    itemCilck(item, index) {
      if (!this.isOnline) {
        this.$router.push({ name: 'login' })
        return
      }
      getACoupon(item.coupon_id).then(res => {
        Toast({
          message: '领取成功',
          position: 'bottom',
          duration: 1500
        })
        this.updateCouponInfo(index)
      })
    }
  }
}
</script>
<style lang="scss" scoped>
.favorable-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  .favorable-header {
    display: flex;
    justify-content: space-between;
    height: 50px;
    align-items: center;
    padding: 0 15px;
    @include thin-border();
    .title {
      color: #404040;
      font-size: 14px;
      white-space: nowrap;
    }
    .tips {
      margin: 0 5px;
      span {
        text-align: center;
        display: inline-block;
        width: 120%;
        @include sc(10px, #999, left);
        line-height: 21px;
        font-family: PingFangSC;
        font-weight: 300;
      }
    }
    img {
      width: 12px;
    }
  }
  .favorable-body {
    flex: 1;
    overflow: auto;
    padding: 15px;
  }
}
</style>
