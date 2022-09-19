<template>
  <div class="shop-dashboard-container" ref="scrollDom">
    <div class="left-goback" @click="goBack">
      <img v-if="backIcon" src="../../assets/image/hh-icon/icon-header-返回.svg" class="back-no-bg" />
      <img v-else src="../../assets/image/change-icon/back_bg@3x.png" />
    </div>
    <div class="shop-info">
      <div class="shop-icon" :style="getBg(shop_base_infos)"></div>
      <p>{{ shop_base_infos.shop_name }}的小店</p>
    </div>
    <div class="shop-dashboard-wrapper">
      <shop-dashboard-brokerage v-if="dashboardInfo.money" :money="dashboardInfo.money"></shop-dashboard-brokerage>
      <shop-dashboard-order
        v-if="dashboardInfo.order_info"
        :order_info="dashboardInfo.order_info"
      ></shop-dashboard-order>
      <shop-dashboard-generalize v-if="dashboardInfo.expand" :expand="dashboardInfo.expand"></shop-dashboard-generalize>
    </div>
  </div>
</template>

<script>
import { getMyShopInfo } from '../../api/huanhuanke'
import { getShopDashboard } from '../../api/mlm'
import ShopDashboardBrokerage from './child/ShopDashboardBrokerage'
import ShopDashboardGeneralize from './child/ShopDashboardGeneralize'
import ShopDashboardOrder from './child/ShopDashboardOrder'
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {
      backIcon: true,

      dashboardInfo: {}
    }
  },

  created() {
    this.getShopDashboardInfo()
    if (!this.shop_base_infos.id) {
      this.getShopInfos()
    }
  },

  mounted() {
    this.$nextTick(() => {
      this.$refs.scrollDom.addEventListener('scroll', this.scroll)
    })
  },

  computed: {
    ...mapState({
      shop_base_infos: state => state.mystore.shop_base_infos
    })
  },

  components: {
    ShopDashboardBrokerage,
    ShopDashboardGeneralize,
    ShopDashboardOrder
  },

  methods: {
    ...mapMutations({
      setShopBaseInfos: 'setShopBaseInfos'
    }),

    goBack() {
      this.$_goBack()
    },

    /**
     * 获取小店数据看板信息
     */
    getShopDashboardInfo() {
      getShopDashboard().then(res => {
        this.dashboardInfo = res
      })
    },

    /**
     * 获取小店信息
     */
    getShopInfos() {
      getMyShopInfo().then(res => {
        this.setShopBaseInfos(res)
      })
    },

    scroll(e) {
      if (this.$refs.scrollDom.scrollTop > 50) {
        this.backIcon = false
      } else {
        this.backIcon = true
      }
    },

    /**
     * 获取店铺背景图
     *
     * @param      {object}  shop_base_infos  小店信息
     * @return     {Object}  返回样式对象
     */
    getBg(shop_base_infos) {
      const bg = shop_base_infos.shop_icon
        ? shop_base_infos.shop_icon
        : require('../../assets/image/hh-icon/f0-shop/icon-shop-default.png')
      return {
        backgroundImage: `url(${bg})`
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.shop-dashboard-container {
  height: 100%;
  box-sizing: border-box;
  background-color: #ffffff;
  background-image: url('../../assets/image/hh-icon/f0-shop/shop-bg.png');
  background-size: 100%;
  background-position: left -18px;
  background-repeat: no-repeat;
  overflow-y: auto;
  .left-goback {
    width: 31px;
    height: 31px;
    cursor: pointer;
    position: fixed;
    z-index: 1;
    top: 10px;
    left: 4px;
    font-size: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    img {
      width: 100%;
      height: 100%;
      &.back-no-bg {
        width: 9px;
        height: auto;
      }
    }
  }
  .shop-info {
    padding: 15px 0 10px;
    text-align: center;
    .shop-icon {
      width: 40px;
      height: 40px;
      overflow: hidden;
      margin: 0 auto 11px;
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50px;
      background-repeat: no-repeat;
      background-size: cover;
      background-position: center;
    }
    p {
      font-size: 13px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: #404040;
      line-height: 18px;
    }
  }
  .shop-dashboard-wrapper {
    background-color: #f9f9f9;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
    overflow: hidden;
    & > div {
      background-color: #ffffff;
      & + div {
        margin-top: 10px;
      }
    }
  }
}
</style>
