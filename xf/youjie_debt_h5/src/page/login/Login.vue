<template>
  <div class="page-wrapper">
    <template v-if="!isAgree">
      <div class="container-wrapper">
        <!-- 手机号 + 验证码提交表单 -->
        <form-submit-by-phone-and-code v-on:submit-success="submitSuccess"></form-submit-by-phone-and-code>
        <div class="agreement">
          <p>
            注册/登录即表示阅读并同意
            <span @click="goAgreement">《平台注册协议》</span>
          </p>
        </div>
      </div>
    </template>
    <template v-else>
      <login-agreement></login-agreement>
    </template>
  </div>
</template>
<script>
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import $cookie from 'js-cookie'
// import { bondGet } from '../../api/bond'
import { getAuthStatus } from '../../api/auth'
import { getRiskTestResult } from '../../api/user'
import LoginAgreement from './LoginAgreement'
// import { Loading } from 'vant';
import FormSubmitByPhoneAndCode from './child/ComponentSubmitByPhoneAndCode'

export default {
  name: 'login',
  data() {
    return {
      isAgree: false,
      routeFrom: ''
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user,
      selected_platformId: state => state.auth.selected_platformId
    }),
    ...mapGetters({
      isDoneRiskTest: 'isDoneRiskTest'
    })
  },
  components: {
    FormSubmitByPhoneAndCode,
    LoginAgreement
  },
  beforeRouteEnter(to, from, next) {
    if ('production' === process.env.NODE_ENV) {
      // 去商城登录
      return window.location.replace('/h5/#/login')
    }

    // 记录登录来源
    if (from['name']) {
      $cookie.set('signinForm', JSON.stringify({ path: from['path'], query: from['query'] }))
    }
    next()
  },
  created() {
    // 若已是 登录状态
    if (this.isOnline) {
      return this.goPrev()
    }
  },
  methods: {
    ...mapActions({
      fetchHasConfirmList: 'fetchHasConfirmList'
    }),
    ...mapMutations({
      saveAuthInfo: 'signin',
      saveWxAuthCheckInfo: 'saveWxAuthCheckInfo',
      // saveCurrentBondState: 'saveCurrentBondState',
      savePlateInfo: 'savePlateInfo',
      saveDebtAgreement: 'saveDebtAgreement',
      saveRisk: 'saveRisk'
    }),
    async submitSuccess(res) {
      if (res.code == 0) {
        if (!Number(res.data.userInfo.is_new)) this.$toast({ message: '登录成功' })

        if (res.data && res.data.token) {
          // 保存用户信息（token）
          this.saveAuthInfo({ token: res.data.token, user: res.data.userInfo })
          // 用户平台信息
          if (res.data.bindPlatform.length) {
            // 保存用户当前平台
            this.savePlateInfo(res.data.bindPlatform[0])
            // 保存用户确权项目信息
            this.fetchHasConfirmList()
          }
        }

        // 获取用户风险测评结果
        await this.checkRisk()

        setTimeout(() => {
          this.$toast.clear()
          // 开始跳转判断
          if (!this.isDoneRiskTest) {
            //无风险评级 => 风险评级
            this.$router.push({ name: 'evaluation', params: { type: 1 } })
          } else {
            // 正常用户登录，跳转个人中心
            this.$router.push({ name: 'mine' })
          }
        }, 1500)
      } else {
        this.$toast(res.info)
      }
    },
    // 返回进入前页面 默认个人中心
    goPrev() {
      let from = { path: '/mine' }
      let signinFormRedirect = this.$cookie.get('signinFormRedirect')

      if (signinFormRedirect) {
        this.$cookie.remove('signinFormRedirect')
        signinFormRedirect = decodeURIComponent(signinFormRedirect)
        if (signinFormRedirect.substr(0, 4) == 'http') {
          // 跳往一个http uri（非Vue架构），后续没办法通过token获取用户信息，故需带上hashId，作登录后用户标识
          location.href = this.utils.updateGetParameter(signinFormRedirect, 'hashid', this.user.id)
          return
        } else {
          from = { path: signinFormRedirect }
        }
      } else {
        const signinForm = this.$cookie.get('signinForm')
        if (signinForm) {
          this.$cookie.remove('signinForm')
          from = JSON.parse(signinForm)
        }
      }

      this.$router.replace(from)
    },
    checkAuth(user_platform) {
      getAuthStatus().then(res => {
        this.saveWxAuthCheckInfo(res)
        if (res.hasDebtAuthentication == 0) {
          this.$router.push({ name: 'AuthChooseOgnztion' })
        } else if (res.count == 0) {
          this.$router.push({ name: 'confirmation' })
        } else {
          setTimeout(() => {
            this.goPrev()
          }, 1000)
        }
      })
    },
    checkRisk() {
      getRiskTestResult().then(res => {
        this.saveRisk({
          level_name: res.data.risk_level,
          level_id: res.data.level_risk_id
        })
      })
    },
    goAgreement() {
      this.isAgree = true
    },
    goBack() {
      this.isAgree ? (this.isAgree = false) : this.$_goBack()
    }
  }
}
</script>
<style lang="less" scoped>
.page-wrapper {
  width: 100%;
  background: #fff;
  display: flex;
  flex-direction: column;
}
.container-wrapper {
  padding: 0 24px;
  flex: 1;
  display: flex;
  flex-direction: column;
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
}
</style>
