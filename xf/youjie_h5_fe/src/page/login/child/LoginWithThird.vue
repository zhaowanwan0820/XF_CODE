<template>
  <div class="third-login" v-if="thirdLogin.length > 0">
    <p class="t-l-desc">第三方登录</p>
    <ul>
      <li v-for="item in thirdLogin" :key="item.id" :item="item">
        <div @click="goThirdLogin(item.name)" :class="'t-l-item ' + item.name"></div>
        <span class="t-l-i-txt">{{ item.txt }}</span>
      </li>
    </ul>
  </div>
</template>
<script>
import { Toast, Indicator } from 'mint-ui'
import { mapMutations } from 'vuex'
import $cookie from 'js-cookie'
import wechat from '../../../config/wechat'
import { authWeb } from '../../../api/auth-web'
import { ENUM } from '../../../const/enum'

export default {
  data() {
    return {
      thirdLogin: []
    }
  },
  mounted() {
    if (
      this.wxApi.isweixin() || // 微信webview
      this.isAppWithWx()
    ) {
      // 微信登录入口
      this.thirdLogin.push({ id: 1, name: 'wechat', txt: '微信' })

      // 是否微信授权回调
      const code = $cookie.get('wx_code')
      if (code) {
        $cookie.remove('wx_code')
        this.loginWithWXcode(code)
      }
    }
  },
  methods: {
    ...mapMutations({
      saveOpenId: 'saveOpenId',
      setPopupBindPhone: 'setPopupBindPhone'
    }),
    goThirdLogin(name) {
      switch (name) {
        case 'wechat':
          this.goWechatLogin()
          break
        default:
          console.error('unknown thirdLogin name')
          break
      }
    },
    goWechatLogin() {
      if (this.isHHApp) {
        const appVersion = this.getAppVersion()
        const appType = this.getAppType()
        if (appVersion < 23 || (appType == 'ios' && appVersion < 24)) {
          this.$router.push({ name: 'loginGuide' })
        } else {
          if (this.hhApp.isWXAppInstalled()) {
            this.defindAppWXloginCall() // 定义app原生微信登录后的回调函数
            this.hhApp.openAppPage('yjmall://login_use_wx')
          } else {
            Toast({
              message: '请先下载微信App，再使用微信登录吧'
            })
          }
        }
        return
      }
      /*
       *  微信登录
       *  1、引导用户进入 https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=[loginUrl]&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect
       *  2、用户同意后会进入 [loginUrl] ,获取其中的GET参数 code
       *  3、发起ajax请求 将上一步得到的 code 传给后端
       **/
      const appId = wechat.appId
      const redirect_uri = encodeURIComponent(location.origin + location.pathname + '#/login')
      location.href = `https://open.weixin.qq.com/connect/oauth2/authorize?appid=${appId}&redirect_uri=${redirect_uri}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect`
    },
    defindAppWXloginCall() {
      window.onWXLoginResult = code => {
        this.loginWithWXcode(code)
      }
    },
    isAppWithWx() {
      // hhApp展示第三方微信登录
      // return this.isHHApp

      // hhApp环境 => ios 024版本隐藏入口(for提审)
      // hhApp环境 => ios 未安装微信不展示微信登录入口
      let res = false
      if (this.isHHApp) {
        res = true

        const appVersion = this.getAppVersion()
        const appType = this.getAppType()
        if (appType == 'ios' && !this.hhApp.isWXAppInstalled()) {
          res = false
        }
      }
      return res
    },

    loginWithWXcode(code) {
      // 微信登录
      Indicator.open('提交中...')
      authWeb(ENUM.AUTH_VENDOR.WEIXIN, 'snsapi_userinfo', code, '', this.isHHApp ? 1 : 0)
        .then(
          res => {
            if (res.token) {
              this.$parent.submitSuccess(res)
              return
            }
            // 2019-0819 三方登录 须 认证一下手机号
            this.saveOpenId(res.openid)
            this.setPopupBindPhone(true)
          },
          error => {
            console.error(error)
          }
        )
        .finally(() => {
          Indicator.close()
        })
    },
    getAppVersion() {
      let appVersion = this.hhApp.getAppVersion()
      appVersion = parseInt(appVersion.replace(/\./g, ''))
      return appVersion
    }
  }
}
</script>
<style lang="scss" scoped>
.third-login {
  padding-top: 60px;
  margin-bottom: 37.5px;

  .t-l-desc {
    font-size: 13px;
    font-weight: 400;
    color: rgba(189, 189, 189, 1);
    line-height: 18px;
    text-align: center;
    margin-bottom: 30px;
  }

  ul {
    text-align: center;
    li {
      display: inline-block;
      text-align: center;
    }
  }

  .t-l-item {
    width: 45px;
    height: 45px;
    background-color: transparent;
    background-repeat: no-repeat;
    background-position: 50% 50%;
    background-size: contain;

    &.wechat {
      background-image: url('../../../assets/image/hh-icon/login/wechat-login@3x.png');
    }
  }
  .t-l-i-txt {
    font-size: 12px;
    font-weight: 400;
    color: rgba(51, 51, 51, 1);
    line-height: 16px;
    margin-top: 8px;
  }
}
</style>
