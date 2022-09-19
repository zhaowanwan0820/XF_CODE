<template>
  <div class="container" >
    <van-nav-bar v-if="!xf_from" :title="title" />
    <van-nav-bar v-if="xf_from" :title="title" left-arrow @click-left="onClickLeft" />
    <home-banner></home-banner>
    <list-entrance></list-entrance>
    <discount-data-display></discount-data-display>
    <!-- <debt-plan-list></debt-plan-list> -->
    <!-- <debt-list></debt-list> -->
    <debt-sail-or-buy></debt-sail-or-buy>
    <FloatBall :exclusive_purchase_id="exclusive_purchase_id" v-if="exclusive_purchase_id>0"></FloatBall>
    <!-- 实名认证 -->
    <Verified v-model="visible" title="请先完成实名认证" @confirm="goVerifiedPage" />
  </div>
</template>

<script>

import { mapMutations, mapActions,mapState } from 'vuex'
import HomeBanner from './child/HomeBanner'
import FloatBall from '../../components/common/FloatBall'

import ListEntrance from './child/ListEntrance'
import DiscountDataDisplay from './child/DiscountDataDisplay'
// import DebtPlanList from './child/DebtPlanList'
import DebtList from './child/DebtList'
import DebtSailOrBuy from './child/DebtSailOrBuy'


import { getLast30DiscountList, getLast7dayDiscountList } from '../../api/home'
import { getDebtMarketList } from '../../api/debtmarket'
import { getPurchaseList } from '../../api/debtmarket'
import { getUser } from '../../api/mine'
// 模拟数据
// import { DATA_DISCOUNT_LAST_30, DATA_DISCOUNT_BY_DAY, DATA_PLAN_LIST, DATA_BANNERS } from './DATA'
import { DATA_BANNERS } from './DATA'
import Verified from "@/components/Verified";
import VerifiedMixin from "@/components/Verified/VerifiedMixin";

export default {
  name: 'home',
  data() {
    return {
      reqestCount: 3, // 当前页面需要获取的数据的接口数量
      title: '债转市场',
      xf_from: false,
      exclusive_purchase_id:0,
      purchase_status:-1,
    }
  },
  components: {
    Verified,
    FloatBall,
    HomeBanner,
    ListEntrance,
    DiscountDataDisplay,
    DebtList,
    DebtSailOrBuy
  },
  mixins: [VerifiedMixin],
  watch: {
    reqestCount: function(val) {
      if (val <= 0) {
        this.$loading.close()
      }
    }
  },
  created() {
    if(!this.isOnline){
          window.location.href = '/#/login?from=debtMarket'
        return
    }
    this.saveBanners(DATA_BANNERS)
    if (localStorage.getItem('xianfeng')) {
      this.xf_from = localStorage.getItem('xianfeng')
    }
    this.onLoad();
    // this.purchase();
  },
   computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,

    }),

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
      // 求购列表
    getPurchaseList({
      area_id:1 ,
      limit: 5
    })
      .then(data => {
        data.code == 0 && this.saveHuiYuanPurchaseList(data.data.data)
        // this.savePurchaseList(DATA_DEBT_LIST)
      })
      .finally(() => {
      })

  },
  methods: {


    ...mapMutations(['saveBanners', 'saveDataLast30', 'saveDataByDay', 'saveHomeDebtList','saveHuiYuanPurchaseList']),

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
    },
    onClickLeft() {
      window.location.href = '/#/'
    },

    onLoad() {
        getUser()
                .then(res => {

                    this.exclusive_purchase_id = res.data.exclusive_purchase_id*1
                    this.purchase_status = res.data.purchase_status*1
                    if(this.exclusive_purchase_id > 0 && this.purchase_status == 0){
                     this.$toast.clear()
                     this.$router.push({ name: 'exclusiveDetails', query: { id: this.exclusive_purchase_id } })
                    }
                    this.$toast.clear()
                })
                .catch(err => {})
      },

  }
}
</script>

<style lang="less" scoped>
.dialog-home-agreement {
  .van-dialog__message {
    padding: 58px 0 47px;
  }
}
.container{
  margin-bottom: 50px;
}
</style>
