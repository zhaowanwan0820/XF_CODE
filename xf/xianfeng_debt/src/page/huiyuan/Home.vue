<template>
  <div class="container">
    <van-nav-bar v-if="!xf_from" :title="title" />
    <van-nav-bar v-if="xf_from" :title="title" left-arrow @click-left="onClickLeft"  />
    <home-banner></home-banner>
    <purchase-list></purchase-list>
  </div>
</template>

<script>
import { mapMutations, mapActions } from 'vuex'
import HomeBanner from './child/HomeBanner'
import PurchaseList from './child/PurchaseList'
import { getPurchaseList } from '../../api/debtmarket'
import { HUIYUAN_BANNERS } from './DATA'

export default {
  name: 'home',
  data() {
    return {
      title: '汇源专区',
      xf_from: false
    }
  },
  components: {
    HomeBanner,
    PurchaseList
  },
  watch: {
    reqestCount: function(val) {
      if (val <= 0) {
        this.$loading.close()
      }
    }
  },
  created() {
    
    this.saveHuiYuanBanners(HUIYUAN_BANNERS)
    this.updatePwdStatus();
    if (localStorage.getItem('xianfeng')) {
      this.xf_from = localStorage.getItem('xianfeng')
    }
  },
  mounted() {  
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
    ...mapActions({
      updatePwdStatus: 'updatePwdStatus'
    }),
    ...mapMutations(['saveHuiYuanBanners',  'saveHuiYuanPurchaseList']),
   
    onClickLeft() {
      window.location.href = '/#/'
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
