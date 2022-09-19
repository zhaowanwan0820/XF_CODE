<!-- 小店看板—推广数据 -->
<template>
  <div class="dashboard-wrapper">
    <shop-dashboard-title
      :icon="require('../../../assets/image/hh-icon/f0-shop/icon-generalize.png')"
      :title="'推广数据'"
    ></shop-dashboard-title>
    <div class="dashboard-body">
      <div class="toggle-bar">
        <template v-for="item in CHART_STATUS">
          <div class="toggle-bar-item" :class="{ active: item.id == defailtItem.id }" @click="changeIndex(item)">
            {{ item.name }}
          </div>
        </template>
      </div>
      <div class="toggle-body">
        <div class="left">
          <div class="toggle-body-item">
            <div class="title">粉丝数</div>
            <div class="content">{{ fans_num }}</div>
          </div>
        </div>
        <div class="right">
          <div class="toggle-body-item">
            <div class="title">小店点击</div>
            <div class="content">{{ hits_num }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { CHART_STATUS } from '../static.js'
import ShopDashboardTitle from './ShopDashboardTitle'
export default {
  data() {
    return {
      CHART_STATUS,
      defailtItem: CHART_STATUS[1]
    }
  },

  props: {
    expand: {
      type: Object,
      default() {
        return {
          today: {
            fans_num: '--',
            hits_num: '--'
          },
          yesterday: {
            fans_num: '--',
            hits_num: '--'
          },
          all: {
            fans_num: '--',
            hits_num: '--'
          }
        }
      }
    }
  },

  computed: {
    fans_num() {
      return this.expand[this.defailtItem.e_name].fans_num || 0
    },
    hits_num() {
      return this.expand[this.defailtItem.e_name].hits_num || 0
    }
  },

  components: {
    ShopDashboardTitle
  },

  methods: {
    changeIndex(item) {
      this.defailtItem = { ...item }
    }
  }
}
</script>

<style lang="scss" scoped>
.dashboard-wrapper {
  .dashboard-body {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px 0 0;
    .toggle-bar {
      display: flex;
      box-sizing: border-box;
      border: 1px solid #772508;
      height: 26px;
      align-items: center;
      .toggle-bar-item {
        width: 50px;
        line-height: 26px;
        font-size: 12px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #772508;
        text-align: center;
        &.active {
          background-color: #772508;
          color: #ffffff;
        }
        & + div {
          border-left: 1px solid #772508;
        }
      }
    }
    .toggle-body {
      align-self: stretch;
      display: flex;
      justify-content: center;
      margin-top: 25px;
      .right {
        margin-left: 98px;
      }
      .toggle-body-item {
        margin-bottom: 20px;
      }
      .title {
        height: 17px;
        font-size: 12px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #999999;
        line-height: 17px;
      }
      .content {
        font-size: 16px;
        font-family: DINAlternate-Bold;
        font-weight: bold;
        color: #404040;
        line-height: 19px;
        display: flex;
        margin-top: 5px;
        align-items: baseline;
      }
    }
  }
}
</style>
