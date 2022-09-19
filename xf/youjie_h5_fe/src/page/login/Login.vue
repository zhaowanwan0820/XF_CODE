<template>
  <div class="page-wrapper">
    <div class="header-container">
      <mt-header class="header">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
    </div>
    <div class="container-wrapper" v-if="!isAgreement">
      <div class="logo"></div>
      <!-- 手机号 + 验证码提交表单 -->
      <form-submit-by-phone-and-code v-on:submit-success="submitSuccess"></form-submit-by-phone-and-code>
      <div class="agreement">
        <p>
          注册/登录即表示阅读并同意
          <span @click="goAgreement">《{{ utils.storeName }}注册及服务协议》</span>
        </p>
      </div>
      <router-link tag="div" class="go-service" :to="{ name: 'service' }">手机号无法使用？</router-link>
      <div class="tips">您可以借助有解进行投资权益的转让、权益管理、积分购物及信息服务。</div>
    </div>
    <!-- 登录/注册 协议 -->
    <login-agreement v-if="isAgreement"></login-agreement>
  </div>
</template>
<script>
import $cookie from 'js-cookie'
import { mapState, mapMutations, mapGetters } from 'vuex'
import { bondGet } from '../../api/bond'
import { getAuthStatus } from '../../api/auth'
import { getConfirmInfo } from '../../api/confirmation'
import { HeaderItem } from '../../components/common'
import LoginAgreement from './LoginAgreement'
import { Header, Toast, Indicator } from 'mint-ui'
import FormSubmitByPhoneAndCode from './child/ComponentSubmitByPhoneAndCode'
// import ThirdLoginBindPhone from './child/ThirdLoginBindPhone'
// import LoginWithThird from './child/LoginWithThird'

export default {
  name: 'login',
  data() {
    return {
      isAgreement: false
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user
    }),
    ...mapGetters({
      isXiache: 'isXiache'
    })
  },
  components: {
    LoginAgreement,
    FormSubmitByPhoneAndCode
  },
  beforeRouteEnter(to, from, next) {
    // 默认 登录后 跳转 地址
    if (from['name'] && from['name'] != 'home' && from['name'] !== 'agreement' && from['name'] !== 'loginGuide') {
      $cookie.set('signinForm', JSON.stringify({ path: from['path'], query: from['query'] }))
    }
    next()
  },
  created() {
    Indicator.close()
    // 自定义 登录后 跳转 地址
    let redirect = this.$route.query.redirect
    if (redirect) {
      $cookie.set('signinFormRedirect', redirect)
    }

    // 若已是 登录状态
    if (this.isOnline) {
      return this.goPrev()
    }
  },
  methods: {
    ...mapMutations({
      saveAuthInfo: 'signin',
      saveWxAuthCheckInfo: 'saveWxAuthCheckInfo',
      saveCurrentBondState: 'saveCurrentBondState'
    }),
    submitSuccess(res) {
      Toast({ message: '登录成功' })
      this.saveAuthInfo({ token: res.token, user: res.user })

      // this.checkAuth(res.user.user_platform)
      // 通知App登录成功
      this.isHHApp && this.hhApp.loginSuccess(res.token)
      this.saveAuth()
    },
    // 返回进入前页面 默认个人中心
    goPrev() {
      let from = { path: '/profile' }
      let signinFormRedirect = $cookie.get('signinFormRedirect')

      if (signinFormRedirect) {
        $cookie.remove('signinFormRedirect')
        signinFormRedirect = decodeURIComponent(signinFormRedirect)
        if (signinFormRedirect.substr(0, 4) == 'http') {
          // 跳往一个http uri（非Vue架构），后续没办法通过token获取用户信息，故需带上hashId，作登录后用户标识
          location.href = this.utils.updateGetParameter(signinFormRedirect, 'hashid', this.user.id)
          return
        } else {
          from = { path: signinFormRedirect }
        }
      } else {
        const signinForm = $cookie.get('signinForm')
        if (signinForm) {
          $cookie.remove('signinForm')
          from = JSON.parse(signinForm)
        }
      }

      this.$router.replace(from)
    },
    goBack() {
      if (this.isAgreement) {
        this.isAgreement = false
      } else {
        this.$_goBack()
      }
    },
    goAgreement() {
      this.isAgreement = true
    },
    getAppVersion() {
      let appVersion = this.hhApp.getAppVersion()
      appVersion = parseInt(appVersion.replace(/\./g, ''))
      return appVersion
    },
    getItzLastDebt() {
      bondGet().then(res => {
        this.saveCurrentBondState(res)
      })
    },
    saveAuth() {
      getAuthStatus().then(res => {
        this.saveWxAuthCheckInfo(res)
        if (this.isXiache) {
          // 下车用户
          this.$router.push({ name: 'promotion001' })
        } else {
          this.goPrev()
        }
      })
    },

    checkAuth(user_platform) {
      Indicator.open()
      let p0 = getConfirmInfo()
      let p1 = getAuthStatus()
      Promise.all([p0, p1])
        .then(res => {
          this.saveWxAuthCheckInfo(res[1])

          var notNeedConfirmation
          if (res[0]) {
            notNeedConfirmation =
              Number(res[0].phHasConfirmWaitCapital) == Number(res[0].phAllConfirmWaitCapital) &&
              Number(res[0].zxHasConfirmWaitCapital) == Number(res[0].zxAllConfirmWaitCapital) &&
              Number(res[0].zxHasConfirmAccount) == Number(res[0].zxAllConfirmAccount)
          }

          if (res[1].hasDebtAuthentication == 0) {
            this.$router.push({ name: 'AuthChooseOgnztion' })
          }
          // else if (res[1].count == 0 && !notNeedConfirmation) {
          //   this.$router.push({ name: 'confirmation' })
          // }
          else {
            setTimeout(() => {
              this.goPrev()
            }, 1000)
            // 若是itz用户（user_platform==1）查询itz在投债权
            user_platform === 1 && this.getItzLastDebt()
            // 通知App登录成功
            // this.isHHApp && this.hhApp.loginSuccess(res[1].token)
          }
        })
        .finally(() => {
          Indicator.close()
        })
    }
  }
}
</script>
<style lang="scss" scoped>
.page-wrapper {
  width: 100%;
  min-height: 100%;
  background: #fff;
  display: flex;
  flex-direction: column;
}
.header {
  height: 50px;
  background: rgba(0, 0, 0, 0);
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  @include thin-border(rgba(0, 0, 0, 0), 0, 0, true);
}
.container-wrapper {
  padding: 0 24px;
  flex: 1;
  display: flex;
  flex-direction: column;
  .logo {
    width: 100%;
    height: 120px;
    background: url('../../assets/image/hh-icon/wx-logo.png') no-repeat 50% 10px;
    background-size: 85px auto;
  }

  .agreement {
    height: 20px;
    margin-top: 15px;
    p {
      color: #ccc;
      font-size: 12px;
      line-height: 20px;
      span {
        color: #999;
        font-size: 12px;
      }
    }
  }

  .go-service {
    @include sc(12px, #fc7f0c);
    margin-top: 10px;
    text-align: center;
  }
  .tips {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding-bottom: 20px;
    font-size: 12px;
    text-align: center;
    color: #999999;
  }
}
</style>
