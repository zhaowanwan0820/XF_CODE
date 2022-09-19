<template>
  <div class="order-wrapper">
    <div class="order-header-wrapper">
      <div class="order-header" @click="goOrder">
        <div class="order-header-item">
          <label class="item-title order-header-title">我的订单</label>
        </div>
        <div class="order-header-item">
          <label class="order-subtitle">查看全部订单</label>
          <img class="indicator" src="../../../assets/image/hh-icon/f0-profile/icon-tip.png" />
        </div>
        <!-- <div class="order-header-line"></div> -->
      </div>
    </div>
    <div class="order-body-wrapper">
      <!-- 如果svg在特定宽度下会破图就改成png -->
      <profile-order-item
        class="order-item"
        testAttr="order"
        id="0"
        :icon="require('../../../assets/image/hh-icon/f0-profile/1-icon-代付款@3x.png')"
        title="待付款"
        :orderNumber="orderCount.created"
      ></profile-order-item>
      <profile-order-item
        class="order-item"
        testAttr="order"
        id="1"
        :icon="require('../../../assets/image/hh-icon/f0-profile/2-icon-代发货@3x.png')"
        title="待发货"
        :orderNumber="orderCount.paid"
      ></profile-order-item>
      <profile-order-item
        class="order-item"
        testAttr="order"
        id="2"
        :iconWidth="30"
        :icon="require('../../../assets/image/hh-icon/f0-profile/3-icon-待收货@3x.png')"
        title="待收货"
        :orderNumber="orderCount.delivering"
      ></profile-order-item>
      <profile-order-item
        class="order-item"
        testAttr="order"
        id="3"
        :icon="require('../../../assets/image/hh-icon/f0-profile/4-icon-待评价@3x.png')"
        title="待评价"
        :orderNumber="orderCount.deliveried"
      ></profile-order-item>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { orderSubtotal } from '../../../api/order'
import { ENUM } from '../../../const/enum'
import ProfileOrderItem from './ProfileOrderItem'
export default {
  name: 'ProfileOrder',
  data() {
    return {}
  },

  components: {
    ProfileOrderItem
  },

  computed: {
    ...mapState({
      orderStatus: state => state.order.orderStatus,
      user: state => state.auth.user,
      isOnline: state => state.auth.isOnline,
      orderCount: state => state.profile.orderCount
    })
  },

  methods: {
    ...mapMutations({
      changeStatus: 'changeStatus'
    }),
    goOrder() {
      if (this.isOnline) {
        if (this.orderStatus != 10) {
          this.changeStatus(10)
        }
        this.$router.push({
          name: 'order',
          params: { order: ENUM.ORDER_STATUS.ALL }
        })
      } else {
        this.showLogin()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.order-header-wrapper {
  padding: 0 15px;
  background-color: #fff;
  margin-top: -4px;
  .order-header {
    height: 44px;
    display: flex;
    justify-content: space-between;
  }
  .order-header-item {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    align-items: center;
  }
  .order-header-line {
    position: absolute;
    height: 1px;
    left: 0;
    bottom: 0;
    right: 0;
    background-color: $lineColor;
  }
  .item-title {
    font-size: 15px;
    color: $baseColor;
  }
  .order-header-title {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    line-height: 25px;
  }
  .indicator {
    width: 6px;
    height: 6px;
    margin-top: -2px;
  }
  .order-subtitle {
    @include sc(11px, #999);
    font-weight: 400;
    line-height: 16px;
  }
}
.order-body-wrapper {
  flex: none;
  display: flex;
  justify-content: space-between;
  background-color: #fff;
  padding: 0 5px 20px;
  .order-item {
    flex: 1;
  }
}
</style>
