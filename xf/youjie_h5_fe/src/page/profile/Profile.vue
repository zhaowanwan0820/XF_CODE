<template>
  <div class="container scroll-container-keepAlive">
    <!-- 用户信息 -->
    <profile-user-info></profile-user-info>

    <!-- 国庆公告 -->
    <!-- <festival-banner></festival-banner> -->

    <div class="sec-container">
      <!-- 我的订单 -->
      <profile-order></profile-order>

      <profile-asset ref="server" v-on:massage="isPopup"></profile-asset>

      <!-- <profile-debt></profile-debt> -->

      <profile-service></profile-service>
    </div>
    <mt-popup v-model="popupVisible" popup-transition="popup-fade">
      <h1>总权益</h1>
      <p>已确认的所有权益</p>
    </mt-popup>
    <!-- 广告位 -->
    <ad-banner
      v-if="isInnerUser"
      :img="require('../../assets/image/hh-icon/f0-profile/capsule-banner@3x.png')"
      url="https://m.youjiemall.com/h5/#/products?tags_id=17"
    ></ad-banner>

    <!-- 店主专区 -->
    <!-- <profile-shopkeeper
      :unpayCount="unpay_count"
      :rebateCount="rebate_count"
      :sharepayCount="sharepay_count"
    ></profile-shopkeeper> -->

    <!-- 猜你喜欢 -->
    <recommend-list :params="recommendParams" :scrollDom="'container'" class="profile-recommend"></recommend-list>
  </div>
</template>

<script>
import ProfileUserInfo from './child/ProfileUserInfo'
import ProfileOrder from './child/ProfileOrder'
import ProfileAsset from './child/ProfileAsset'
import ProfileDebt from './child/ProfileDebt'
import ProfileShopkeeper from './child/ProfileShopkeeper'
import ProfileService from './child/ProfileService'
import FestivalBanner from './child/FestivalBanner'
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { balanceGet } from '../../api/balance'
import { huanAccount } from '../../api/huanhuanke'
import { bondGet } from '../../api/bond'
import { ENUM } from '../../const/enum'
import RecommendList from '../recommend/RecommendList'

import AdBanner from '../../components/common/AdBanner'

export default {
  name: 'profile',
  beforeRouteEnter(to, from, next) {
    next(vm => {
      if (!vm.isOnline) {
        vm.$router.replace({ name: 'login', params: {} })
      }
    })
  },
  data() {
    return {
      unpay_count: 0, //分销返佣未支付订单数
      rebate_count: 0, //已返佣订单数
      sharepay_count: 0, //代付笔数
      user_money: 0, //账户总额
      recommendParams: {},
      popupVisible: false
    }
  },
  watch: {
    hasChange() {
      this.getUserProfile()
    },
    counterForTabRefresh(value) {
      // App Tabbar切换时 监听以更新数据
      this.appInit()
    }
  },
  components: {
    ProfileUserInfo,
    ProfileOrder,
    ProfileAsset,
    ProfileDebt,
    ProfileShopkeeper,
    ProfileService,
    RecommendList,
    AdBanner,
    FestivalBanner
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user,
      hasChange: state => state.profile.hasChange,
      currentBalance: state => state.balance.currentBalance,
      counterForTabRefresh: state => state.app.counterForTabRefresh
    }),
    isInnerUser() {
      // 是否内部内购用户
      return this.user && this.user.service_list && this.user.service_list.includes(1) ? true : false
    }
  },
  created: function() {
    if (this.isOnline) {
      this.appInit()
    }
  },
  // keepAlive被唤醒时
  activated() {
    this.$refs.server.getData()
  },
  methods: {
    ...mapMutations({
      saveUser: 'saveUser',
      saveCurrentBalanceState: 'saveCurrentBalanceState',
      saveInternal: 'saveInternal',
      saveCurrentBondState: 'saveCurrentBondState'
    }),
    ...mapActions({
      fetchOrderSubtotal: 'fetchOrderSubtotal',
      getUserProfile: 'fetchUserInfos'
    }),
    appInit() {
      this.getUserProfile()

      this.fetchOrderSubtotal()

      balanceGet().then(
        res => {
          this.saveCurrentBalanceState(res.surplus)
        },
        error => {}
      )

      // 分销客消息数量
      this.getHuanMessageCount()

      // itz剩余可兑换债权
      bondGet().then(res => {
        this.saveCurrentBondState(res)
      })
    },
    // 分销客消息数量
    getHuanMessageCount() {
      huanAccount(ENUM.HUANKE_STATUS.ALL).then(
        res => {
          this.unpay_count = res.unpay_count
          this.rebate_count = res.rebate_count
          this.user_money = Number(res.user_money) + Number(res.frozen_money)
          this.sharepay_count = res.sharepay_count
          this.saveInternal(res.allow_rebate)
        },
        error => {
          console.log(error)
        }
      )
    },
    showLogin() {
      this.$router.push({ name: 'login' })
    },
    isPopup(t) {
      console.log(t)
      this.popupVisible = true
    }
  }
}
</script>

<style lang="scss" scoped>
/deep/.mint-popup {
  position: absolute;
  left: 28%;
  top: 482px;
  width: 149px;
  padding: 12px 10px;
  border-radius: 5px;
  box-shadow: 0px 0px 10px 0px rgba(252, 127, 12, 0.12);
  h1 {
    font-size: 14px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    line-height: 20px;
    margin-bottom: 7px;
  }
  p {
    font-size: 12px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(153, 153, 153, 1);
    line-height: 20px;
  }
}
/deep/.mint-popup:before {
  display: inline-block;
  width: 0;
  height: 0;
  border: solid transparent;
  border-width: 10px;
  border-bottom-color: #fff;
  content: '';
  position: absolute;
  top: -20px;
  left: 70px;
}
.container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  background-color: $mainbgColor;
  height: auto;
  position: absolute;
  bottom: 0;
  top: 0;
  width: 100%;
  overflow-x: hidden;
  overflow-y: auto;
  padding-bottom: 20px;
  .sec-container {
    flex-shrink: 0;
    margin-top: 20px;
    border-radius: 10px;
    overflow: hidden;
    background-color: #ffffff;
    padding: 15px 0;
  }
  .profile-recommend {
    padding-left: 25.5px;
    padding-right: 25.5px;
  }
}
</style>
