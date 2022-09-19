<template>
  <div class="profile-service">
    <div class="service-head">
      我的服务
    </div>
    <div class="service-wrapper">
      <div class="service-item" @click="goAddress">
        <img src="../../../assets/image/hh-icon/f0-profile/service/address.png" alt="" />
        <label>收货地址</label>
      </div>
      <div class="service-item" @click="goFavourite">
        <img src="../../../assets/image/hh-icon/f0-profile/service/collection.png" alt="" />
        <label>宝贝收藏</label>
      </div>
      <div class="service-item" @click="goLegalRightCenter" v-if="showHbArea">
        <img src="../../../assets/image/hh-icon/f0-profile/service/integral.png" alt="" />
        <label>积分专区</label>
      </div>
      <div class="service-item" @click="goRight">
        <img src="../../../assets/image/hh-icon/f0-profile/service/quequan.png" alt="" />
        <label>确权服务</label>
      </div>
      <div class="service-item" @click="goBank" v-if="isShow">
        <img src="../../../assets/image/hh-icon/f0-profile/service/bankcard.png" alt="" />
        <label>我的银行卡</label>
      </div>
      <!-- 美景1对1消债 -->
      <div class="service-item" @click="goMjGoods" v-if="isShowMjDebtGoods">
        <img src="../../../assets/image/hh-icon/f0-profile/service/specificDebtGoods.png" alt="" />
        <label>0元购</label>
      </div>
      <!-- 敬老专区 -->
      <div class="service-item" @click="goOldMan" v-if="isShowOldMan">
        <img src="../../../assets/image/hh-icon/f0-profile/service/icon-respect-to-oldMan.png" alt="" />
        <label>敬老专区</label>
      </div>
      <!-- 限量精选 -->
<!--      <div class="service-item" @click="goMjGoods" v-if="isShowLimited">-->
      <!-- <div class="service-item" @click="goLimitedGoods" v-if="isShowLimited">
        <img src="../../../assets/image/hh-icon/f0-profile/service/specificDebtGoods.png" alt="" />
        <label>限量精选</label>
      </div> -->
      <div class="service-item" @click="goVote" v-if="isShowRepayment">
        <img src="../../../assets/image/hh-icon/f0-profile/service/icon-repayment@2x.png" alt="" />
        <label>还款兑付</label>
      </div>
      <div class="service-item" @click="goService">
        <img src="../../../assets/image/hh-icon/f0-profile/service/kefu.png" alt="" />
        <label>联系客服</label>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { getRepaymenBtn } from '../../../api/newplane'
export default {
  name: 'ProfileService',
  data() {
    return {
      isShow: false,
      isShowRepayment: false,
      isPuhui: 0
    }
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user,
      systemTime: state => state.app.systemTime,
      hasDebtAuthentication: state => state.auth.wxAuthCheckInfo.hasDebtAuthentication
    }),
    showHbArea() {
      // 2019-08-08 24:00:00 关闭积分专区入口
      return this.systemTime < 1565280000000
    },
    isShowMjDebtGoods() {
      return this.user && this.user.service_list && this.user.service_list.includes(2) ? true : false
    },
    isShowOldMan() {
      return this.user && this.user.service_list && this.user.service_list.includes(3) ? true : false
    },
    isShowLimited() {
      return this.user && this.user.service_list && this.user.service_list.includes(6) ? true : false
    }
  },

  methods: {
    goFavourite() {
      if (this.isOnline) {
        this.$router.push('collection')
      } else {
        this.showLogin()
      }
    },
    goAddress() {
      if (this.isOnline) {
        this.$router.push({ name: 'addressManage' })
      } else {
        this.showLogin()
      }
    },
    goHelp() {
      this.$router.push({ name: 'help' })
    },
    // A类用户去往积分中心
    goLegalRightCenter() {
      this.$router.push({
        name: 'products',
        query: {
          sort_key: 6,
          from: 'ucenter'
        }
      })
    },
    // 确权
    goRight() {
      if (this.hasDebtAuthentication == 0) {
        this.$router.push({ name: 'AuthChooseOgnztion' })
      } else {
        this.$router.push({ name: 'confirmation' })
      }
    },
    // 银行卡
    goBank() {
      this.$router.push({ name: 'bankcard' })
    },
    // 拥有指定债权的用户的 指定商品列表
    goMjGoods() {
      this.$router.push({ name: 'products', query: { is_appoint: 1, sort_key: 1 } })
    },
    // 客服
    goService() {
      this.$router.push({ name: 'service' })
    },
    goVote() {
      this.$router.push({ name: 'repaymentList' })
    },
    // 敬老专区
    goOldMan() {
      this.$router.push({ name: 'products', query: { tags_id: 22 } })
    },
    goLimitedGoods(){
      this.$router.push({ name: 'products', query: { admin_order: 1, tags_id: 31 } })
    }
  },
  created() {
    getRepaymenBtn().then(res => {
      if (res.status == 200) {
        this.isShowRepayment = true
        // this.isPuhui=res.data==0?0:1
      } else {
        this.isShowRepayment = false
      }
    })
  }
}
</script>

<style lang="scss" scoped>
.profile-service {
  background-color: #ffffff;
  .service-head {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    line-height: 25px;
    padding: 15px;
  }
  .service-wrapper {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    .service-item {
      width: 25%; //明天加商务合作
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 5px 0 15px;
      img {
        width: 30px;
        height: 30px;
      }
      label {
        font-size: 12px;
        font-weight: 300;
        color: #404040;
        line-height: 17px;
        margin-top: 7px;
      }
    }
  }
}
</style>
