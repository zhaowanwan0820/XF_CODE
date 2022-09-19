<template>
  <div class="container">
    <home-banner></home-banner>
    <list-entrance></list-entrance>
    <discount-data-display></discount-data-display>
    <!-- <debt-plan-list></debt-plan-list> -->
    <debt-list></debt-list>
  </div>
</template>

<script>
import { mapMutations, mapActions } from 'vuex'
import HomeBanner from './child/HomeBanner'
import ListEntrance from './child/ListEntrance'
import DiscountDataDisplay from './child/DiscountDataDisplay'
// import DebtPlanList from './child/DebtPlanList'
import DebtList from './child/DebtList'

import { getLast30DiscountList, getLast7dayDiscountList } from '../../api/home'
import { getDebtMarketList } from '../../api/debtmarket'

// 模拟数据
// import { DATA_DISCOUNT_LAST_30, DATA_DISCOUNT_BY_DAY, DATA_PLAN_LIST, DATA_BANNERS } from './DATA'
import { DATA_BANNERS } from './DATA'

export default {
  name: 'home',
  data() {
    return {
      reqestCount: 3 // 当前页面需要获取的数据的接口数量
    }
  },
  components: {
    HomeBanner,
    ListEntrance,
    DiscountDataDisplay,
    DebtList
  },
  watch: {
    reqestCount: function(val) {
      if (val <= 0) {
        this.$loading.close()
      }
    }
  },
  created() {
    this.saveBanners(DATA_BANNERS)
  },
  mounted() {
    this.$loading.open()

    // 近15笔
    getLast30DiscountList()
      .then(data => {
        data.code == 0 && this.saveDataLast30(this.buildLast30ChartData(data.data))
      })
      .finally(() => {
        this.reqestCount--
      })
    // 近7天
    getLast7dayDiscountList()
      .then(data => {
        data.code == 0 && this.saveDataByDay(this.buildLast7dayChartData(data.data))
      })
      .finally(() => {
        this.reqestCount--
      })
    // top2求购计划
    // getDiscountPlanList(2)
    //   .then(data => {
    //     data.code == 0 && this.saveDataPlanList(data.data.data)
    //     // this.saveDataPlanList(DATA_PLAN_LIST)
    //   })
    //   .finally(() => {
    //     this.reqestCount--
    //   })
    // top5债权
    getDebtMarketList({
      type: 1,
      field: 2,
      order: 2,
      limit: 5
    })
      .then(data => {
        data.code == 0 && this.saveHomeDebtList(data.data.data)
        // this.saveDebtList(DATA_DEBT_LIST)
      })
      .finally(() => {
        this.reqestCount--
      })
  },
  methods: {
    ...mapMutations(['saveBanners', 'saveDataLast30', 'saveDataByDay', 'saveHomeDebtList']),
    buildLast30ChartData(data) {
      const ret = []
      data.forEach((ele, index) => {
        ret.push({ xdata: ++index + '', ydata: Number(ele.discount) })
      })
      return ret
    },
    buildLast7dayChartData(data) {
      const ret = []
      data.forEach((ele, index) => {
        ret.push({ xdata: ele.date, ydata: ele.discount })
      })
      return ret
    }
  }
}
</script>

<style lang="less" scoped>
.dialog-home-agreement {
  .van-dialog__message {
    padding: 58px 0 47px;
  }
}
</style>
