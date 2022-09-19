<template>
  <div class="top-wrapper">
    <div class="nav-item">
      <img src="../../../assets/image/hh-icon/f0-profile/icon-setting.png" @click="goSetting" />
      <div class="img-wrapper">
        <!-- <div class="red" v-if="isRead"></div> -->
        <!-- <img src="../../../assets/image/hh-icon/f0-profile/icon-message@2x.png" @click="goMessage" /> -->
        <!-- <img v-if="isHHApp" src="../../../assets/image/hh-icon/f0-profile/icon-share@2x.png" @click="goShare" /> -->
      </div>
    </div>
    <div class="top-info-wrapper">
      <div class="info-wrapper">
        <div class="avatar-wrapper">
          <img class="avatar" v-if="isOnline && user && user.avatar" v-bind:src="user.avatar" />
          <img class="avatar" v-else src="../../../assets/image/hh-icon/f0-profile/icon-head.png" />
        </div>
        <label class="nickname">{{ nickname }}</label>
        <img
          class="icon-more"
          src="../../../assets/image/hh-icon/f0-profile/icon-more.png"
          alt=""
          v-if="isHHApp && AppVersion >= 31"
        />
      </div>
      <div class="count-wrapper only-two">
        <div class="count-wrapper-item" @click="goExchangeDebt">
          <div class="count">{{ utils.formatFloat(currentBalance) }}</div>
          <div class="title">积分</div>
        </div>
        <img src="../../../assets/image/hh-icon/f0-profile/icon-ver-line.png" alt="" />
        <div class="count-wrapper-item" @click="goCoupon">
          <div class="count">{{ utils.formatFloat(coupon) }}</div>
          <div class="title">优惠券</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { balanceGet } from '../../../api/balance'
import { couponNumber } from '../../../api/coupon'
import { unReadMsg } from '../../../api/message'
import { ENUM } from '../../../const/enum'
export default {
  name: 'ProfileUserInfo',
  data() {
    return {
      coupon: 0,
      isRead: false
    }
  },

  created() {
    this.getBalance()
    this.getCouponNumber()
    // this.getMessage()
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user,
      currentBalance: state => state.balance.currentBalance,
      currentTokenHD: state => state.balance.currentTokenHD
    }),
    AppVersion() {
      let appVersion = this.hhApp.getAppVersion()
      appVersion = parseInt(appVersion.replace(/\./g, ''))
      return appVersion
    },
    nickname() {
      let title = '登录/注册'
      if (this.isOnline) {
        if (this.user && typeof this.user != 'undefined' && JSON.stringify(this.user) != '{}') {
          title = this.user.nickname
          title = this.utils.formatPhone(title)
        }
      }
      return title
    }
  },

  methods: {
    ...mapMutations({
      saveCurrentBalanceState: 'saveCurrentBalanceState',
      saveCurrentTokenHDState: 'saveCurrentTokenHDState'
    }),
    /**
     * 进入设置页面
     */
    goSetting() {
      if (this.isOnline) {
        this.$router.push({ name: 'setting' })
      } else {
        this.showLogin()
      }
    },
    // getMessage() {
    //   unReadMsg().then(res => {
    //     this.isRead = res && res.isRead === 'yes' ? true : false
    //   })
    // },
    goMessage() {
      this.$router.push({ name: 'message' })
    },

    goShare() {
      this.hhApp.share(
        '万物有本则生，事事有道则解',
        this.utils.getShareImage(),
        'all',
        'wx-app-share',
        `${this.utils.storeName}-有积分 更便宜`,
        encodeURIComponent(`${location.origin}${location.pathname}#/downloadapp`),
        '商城分享'
      )
    },

    goProfileInfo() {
      // if (this.isOnline) {
      //   if (this.isHHApp && this.AppVersion >= 31) {
      //     this.hhApp.openAppPage('yjmall://userInfo')
      //   }
      // } else {
      //   this.showLogin()
      // }
    },

    goExchangeHD() {
      if (this.isOnline) {
        this.$router.push({ name: 'BondHD' })
      } else {
        this.showLogin()
      }
    },

    goExchangeDebt() {
      if (this.isOnline) {
        this.$router.push({ name: 'bond' })
      } else {
        this.showLogin()
      }
    },

    /**
     * 获取账户积分
     */
    getBalance() {
      balanceGet(this.isHbUser ? 'userCenter' : '').then(
        res => {
          this.saveCurrentBalanceState(res.surplus)
          this.saveCurrentTokenHDState(res.token_all)
        },
        error => {}
      )
    },
    // 获取优惠券数量
    getCouponNumber() {
      couponNumber().then(
        res => {
          this.coupon = res.number
        },
        error => {}
      )
    },
    goCoupon() {
      if (this.isOnline) {
        this.$router.push({ name: 'coupon' })
      } else {
        this.showLogin()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.top-wrapper {
  padding: 10px 15px 15px;
  position: relative;
  background: linear-gradient(45deg, rgba(254, 131, 58, 1) 0%, rgba(255, 102, 88, 1) 100%);
}
.nav-item {
  position: absolute;
  right: 20px;
  top: 13px;
  img {
    width: 18px;
    height: 18px;
  }
}
.top-info-wrapper {
  margin: 0 -15px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  .info-wrapper {
    height: 40px;
    display: flex;
    justify-content: flex-start;
    align-items: center;
  }
  .avatar-wrapper {
    margin-left: 15px;
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
    }
  }
  .nickname {
    font-size: 20px;
    font-weight: bold;
    color: #ffffff;
    line-height: 24px;
    margin-left: 10px;
  }
  .icon-more {
    width: 5px;
    height: 5px;
    margin-left: 8px;
  }
  .count-wrapper {
    display: flex;
    justify-content: space-around;
    margin-top: 22px;
    padding: 0 15px;
    align-items: center;
    img {
      width: 1px;
      height: 33px;
    }
  }
  .count-wrapper-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    & + .count-wrapper-item {
      @include thin-left-border(rgba(85, 46, 32, 0.2), 0, auto, true);
    }
    .count {
      font-size: 17px;
      font-weight: bold;
      color: #ffffff;
      line-height: 20px;
    }
    .title {
      font-size: 14px;
      font-weight: 400;
      color: #ffffff;
      line-height: 20px;
      margin-top: 7px;
    }
  }
}
</style>
