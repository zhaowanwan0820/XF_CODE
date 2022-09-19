<template>
  <div class="container">
    <mt-header class="header" title="设置">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="top-info-wrapper" v-if="isOnline">
      <div class="avatar-wrapper">
        <img class="avatar" v-bind:src="user.avatar" v-if="isOnline && user && user.avatar" />
        <img class="avatar" src="../../assets/image/hh-icon/f0-profile/icon-head.png" v-else />
      </div>
      <label class="nickname">{{ nickname }}</label>
    </div>
    <div class="link-rows">
      <link-column v-on:onclick="goPassword" title="交易密码" :rightTxt="isSet ? '修改' : '未设置'"> </link-column>
      <link-column v-on:onclick="goRiskTest" title="风险评级" v-if="false"> </link-column>
    </div>
    <gk-button class="button" v-on:click="signout" v-if="isOnline">退出登录</gk-button>
  </div>
</template>

<script>
import LinkColumn from '../../components/common/LinkColumn'
import { HeaderItem, Button } from '../../components/common'
import { mapState, mapMutations } from 'vuex'
import { Header, MessageBox, Switch, Indicator } from 'mint-ui'
import { Toast } from 'mint-ui'
import { goAuth } from './goAuth'
import { getIsSetPassword } from '../../api/user'

export default {
  data() {
    return {
      about_url: '',
      isInHHApp: false,
      isSet: false
    }
  },
  created() {
    this.isInHHApp = this.isHHApp
    this.getIsSetPass()
  },
  components: {
    LinkColumn
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      platform: state => state.auth.platform,
      user: state => state.auth.user,
      authStatus: state => state.itouzi.authStatus,
      authStep: state => state.itouzi.authStep,
      time: state => state.profile.time,
      type: state => state.profile.type
    }),
    nickname() {
      let title = '登录/注册'
      if (this.isOnline) {
        if (this.user && typeof this.user != 'undefined' && JSON.stringify(this.user) != '{}') {
          title = this.user.nickname
          title = this.utils.formatPhone(title)
        }
      }
      return title
    },
    getAvatarUrl() {
      let url = null
      if (this.isOnline) {
        if (this.user && typeof this.user != 'undefined' && JSON.stringify(this.user) != '{}') {
          url = this.user.avatar
        }
      }
      if (url === null) {
        url = require('../../assets/image/change-icon/e0_head1@2x.png')
      }
      return url
    },
    // isSwitch: {
    //   get: function() {
    //     return this.$store.state.profile.isSwitch
    //   },
    //   set: function() {}
    // },
    authTxt() {
      if (this.authStatus) {
        return '已授权'
      } else if (this.authStep) {
        return '待审核'
      } else {
        return '未授权'
      }
    }
  },
  methods: {
    ...mapMutations({
      clearToken: 'signout'
      // clearUnreadInfo: 'clearUnreadInfo',
    }),
    goAuthPath() {
      if (this.platform && this.authStatus) {
        return
      }
      goAuth(this.$router, this.isHHApp, this.hhApp)
    },
    goBack() {
      this.$_goBack()
    },
    goHome() {
      this.$router.push({ name: 'home', params: {} })
    },
    goAbout() {
      this.$router.push({ name: 'About', params: {} })
    },
    goCommom() {
      this.hhApp.openAppPage('yjmall://app_common')
    },
    signout() {
      MessageBox.confirm('确认退出', '').then(action => {
        this.clearToken()
        this.goHome()

        // 清除债转市场登陆数据
        window.localStorage.removeItem('m_assets_garden')

        // 清除cookie
        this.$cookie.remove('o', this.utils.getDomainA)
        this.$cookie.remove('t', this.utils.getDomainA)

        this.isHHApp && this.hhApp.logout()
      })
    },
    // app账户安全页
    goAccount() {
      this.hhApp.openAppPage('yjmall://accountSecurity')
    },
    getIsSetPass() {
      getIsSetPassword().then(res => {
        this.isSet = !res.transactionPassword || '0' == res.transactionPassword ? false : true
      })
    },
    goPassword() {
      if (this.isSet) {
        this.$router.push({ name: 'transPwdChange' })
      } else {
        this.$router.push({ name: 'transPwdSet' })
      }
    },
    goRiskTest() {
      window.location.href = '/debt/#/evaluationResult'
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  .header {
    @include header;
  }
  .top-info-wrapper {
    flex: 1;
    width: 100%;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    background-color: #fff;
    margin-top: 10px;
  }
  .avatar-wrapper {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    width: 61px;
    height: 61px;
    margin: 20px;
    .avatar {
      width: 55px;
      height: 55px;
      border-radius: 50%;
    }
  }
  .nickname {
    color: $baseColor;
    font-size: 20px;
    font-weight: 600;
    line-height: 61px;
    width: 100%;
    flex: 1;
    -webkit-box-orient: vertical;
    @include limit-line(1);
  }
  .link-rows {
    margin-top: 10px;
  }
  .button {
    font-size: 15px;
    @include button($margin: 0 0 0);
    margin-top: 15px;
  }

  .my-status {
    @include sc(15px, $subbaseColor);
  }
}
</style>
