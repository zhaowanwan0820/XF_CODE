<!-- Shopping.vue -->
<template>
  <mt-popup v-model="isShowCouponPop" position="bottom" v-bind:close-on-click-modal="false" style="height: 76%;">
    <div class="coupon-container">
      <div class="coupon-header">
        <span>优惠券</span>
        <img src="../../../assets/image/hh-icon/icon-关闭.png" v-on:click="closePromoPop(false)" />
      </div>
      <div class="tabbar-title">
        <div :class="{ active: activeIndex == 1 }" @click="switchTab(1)">
          可用优惠券({{ couponList.able_list.length }})
          <div class="line"></div>
        </div>
        <div :class="{ active: activeIndex == 2 }" @click="switchTab(2)">
          不可用优惠券({{ couponList.unable_list.length }})
          <div class="line"></div>
        </div>
      </div>
      <div class="coupon-body" v-if="activeIndex == 1">
        <template v-if="couponList.able_list.length > 0">
          <div class="body-item" v-for="(item, index) in couponList.able_list" :key="index">
            <div class="item-left">
              <coupon-item :item="item" class="checkable"></coupon-item>
            </div>
            <div class="item-right">
              <input
                :ref="`radio${index}`"
                :checked="item.coupon_user_id == selectedCoupon.coupon_user_id"
                type="checkbox"
                :id="`coupon${index}`"
                class="coupon-input"
                @change="changeIndex(item)"
                name="changeIndex"
              />
              <label class="coupon-radius" placeholder="v" :for="`coupon${index}`"></label>
            </div>
          </div>
        </template>
        <div class="empty" v-if="couponList.able_list.length == 0">
          <img class="empty-icon" src="../../../assets/image/hh-icon/coupon/icon-list-none.png" alt="" />
          <span>无可用优惠券</span>
        </div>
      </div>
      <div class="coupon-body" v-if="activeIndex == 2">
        <template v-if="couponList.unable_list.length > 0">
          <div class="body-item" v-for="(item, index) in couponList.unable_list" :key="index">
            <coupon-item :item="item"></coupon-item>
          </div>
        </template>
        <div class="empty" v-if="couponList.unable_list.length == 0">
          <img class="empty-icon" src="../../../assets/image/hh-icon/coupon/icon-list-none.png" alt="" />
          <span>无不可用优惠券</span>
        </div>
      </div>
      <div class="coupon-footer">
        <button @click="checkConfirm">确定</button>
      </div>
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { Button } from 'mint-ui'
import CouponItem from '../../product-detail/child/CouponItem'

export default {
  data() {
    return {
      promotion: [],
      activeIndex: 1
    }
  },

  props: ['isShowCouponPop', 'couponList', 'selectedCoupon'],

  components: {
    CouponItem
  },

  created() {},

  computed: {
    ...mapState({
      // selectedItem: state => state.seckill.selectedItem
    })
  },

  mounted() {},

  methods: {
    ...mapMutations({
      // saveSelectedItem: 'saveSelectedItem',
      // unsaveSelectedItem: 'unsaveSelectedItem'
    }),

    switchTab(index) {
      this.activeIndex = index
    },

    // 关闭购物车浮层
    closePromoPop() {
      this.$emit('close', false)
    },

    /**
     * 不是用优惠券
     */
    checkConfirm() {
      this.closePromoPop()
    },

    changeIndex(item) {
      if (item.coupon_user_id != this.selectedCoupon.coupon_user_id) {
        this.$emit('confirm', item)
      } else {
        this.$emit('confirm', {})
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.coupon-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .coupon-header {
    display: flex;
    justify-content: space-between;
    height: 50px;
    align-items: center;
    padding: 0 15px;
    @include thin-border();
    span {
      color: #404040;
      font-size: 14px;
    }
    img {
      width: 12px;
    }
  }
  .tabbar-title {
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin-top: 15px;
    div {
      display: flex;
      flex-direction: column;
      align-items: center;
      font-size: 13px;
      font-weight: 400;
      color: rgba(102, 102, 102, 1);
      .line {
        background-color: transparent;
        width: 31px;
        height: 2px;
        margin-top: 7px;
      }
      &.active {
        color: rgba(119, 37, 8, 1);
        .line {
          background-color: rgba(119, 37, 8, 1);
        }
      }
    }
  }
  .coupon-body {
    flex: 1;
    overflow: auto;
    padding: 0 15px;
    .body-item {
      display: flex;
      margin-top: 15px;
      .item-left {
        flex: 1;
      }
      .item-right {
        width: 40px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        .coupon-input {
          display: none;
          &:checked + .coupon-radius {
            @include wh(22px, 22px);
            background: #d0b482 url('../../../assets/image/hh-icon/icon-checkbox-勾.svg') center no-repeat;
            background-size: 12px 12px;
            border: 0;
          }
          &:disabled + .coupon-radius {
            visibility: hidden;
          }
        }
        .coupon-radius {
          @include wh(22px, 22px);
          @include borderRadius(50%);
          border: 1px solid #d0b482;
        }
      }
      .checkable {
        display: block;
        width: 100%;
        position: relative;
      }
    }
    .empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      margin-top: 70px;
      img {
        width: 135px;
      }
      span {
        font-size: 14px;
        font-weight: 400;
        color: rgba(102, 102, 102, 1);
        margin-top: 13px;
      }
    }
  }
  .coupon-footer {
    padding: 15px;
    button {
      display: block;
      width: 100%;
      box-sizing: border-box;
      height: 46px;
      line-height: 44px;
      font-size: 18px;
      color: #ffffff;
      background-color: rgba(119, 37, 8, 1);
      border-radius: 2px;
    }
  }
}
</style>
