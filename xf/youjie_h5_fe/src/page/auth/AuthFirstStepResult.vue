<template>
  <div class="container">
    <mt-header class="header" fixed title="签署成功">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="img-wrapper">
        <img src="../../assets/image/hh-icon/auth/icon-firststep.png" />
      </div>
      <div class="con">
        <p class="title">签署成功</p>
      </div>
      <button @click="goAppAuth">开始身份认证</button>
      <p class="per" @click="goWapAuth">人工认证</p>
      <div class="warn">
        <div class="warn-title">
          <img src="../../assets/image/hh-icon/auth/icon-start0.png" alt="" />
          <span>温馨提示</span>
        </div>
        <div class="warn-msg" v-if="AppVersion0 < 25">
          <p>1、请提前准备好身份证，进行身份认证环节；</p>
          <p>2、如果您的身份证有效期为<span>长期有效</span>，请使用人工认证进行认证；</p>
        </div>
        <div class="warn-msg" v-else>
          <p>请提前准备好身份证，进行身份认证环节；</p>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import $cookie from 'js-cookie'
import { mapState, mapMutations } from 'vuex'
import { authCheck } from '../../api/auth-base'
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
export default {
  name: 'AuthFirstStepResult',
  data() {
    return {
      fromPage: ''
    }
  },
  beforeRouteEnter(to, from, next) {
    if (from.name) {
      $cookie.set('authFromPage', from.name)
    }
    next()
  },
  // 离开销毁cookie
  beforeRouteLeave(to, from, next) {
    if (this.fromPage) {
      $cookie.remove('authFromPage', '')
    }
    next()
  },
  created() {
    this.fromPage = $cookie.get('authFromPage')
  },
  computed: {
    ...mapState({
      userId: state => state.auth.user.id
    }),
    AppVersion0() {
      let appVersion = this.hhApp.getAppVersion()
      appVersion = parseInt(appVersion.replace(/\./g, ''))
      return appVersion
    }
  },
  methods: {
    ...mapMutations({
      saveAuthData: 'saveAuthData'
    }),
    goAppAuth() {
      // 授权之前先联网检查授权状态
      authCheck(1).then(res => {
        if (res.data && res.data.status) {
          this.saveAuthData(res)
          this.goBack()
        } else {
          // 先返回至来源页面，然后打开app原生认证页
          this.goBack()
          this.$nextTick(function() {
            const appVersion = this.getAppVersion()
            const appType = this.getAppType()
            let orderId = appType + new Date().getTime() + this.userId
            if (appVersion >= 25) {
              let url = 'yjmall://app_yd_identify?orderId=' + orderId
              url = encodeURIComponent(url)
              this.hhApp.openAppPage(url)
            } else {
              this.hhApp.openAppPage('yjmall://app_identify')
            }
          })
        }
      })
    },
    goWapAuth() {
      this.$router.replace({ name: 'authPage', params: {} })
    },
    getAppType() {
      return window.navigator.userAgent.indexOf('YOUJIEMALL_IOS') > -1 ? 'IOS' : 'AND'
    },
    getAppVersion() {
      let appVersion = this.hhApp.getAppVersion()
      appVersion = parseInt(appVersion.replace(/\./g, ''))
      return appVersion
    },
    goBack() {
      if (this.fromPage === 'agreementPage') {
        let url = $cookie.get('fromAuth')
        this.$router.push(url)
        $cookie.remove('fromAuth', '')
      } else {
        this.$_goBack()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  width: 100%;
  height: 100%;
  background: #fff;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  .img-wrapper {
    width: 154px;
    height: 154px;
    margin-top: 100px;
    img {
      width: 154px;
      height: 154px;
    }
  }
  .con {
    margin-top: 3px;
    display: flex;
    flex-direction: column;
    align-items: center;
    .title {
      font-size: 18px;
      line-height: 25px;
    }
    .msg {
      margin-top: 7px;
      font-size: 13px;
      line-height: 28px;
      color: #777;
    }
  }
  button {
    width: 327px;
    height: 46px;
    background: #772508;
    border: none;
    border-radius: 2px;
    outline: none;
    color: #fff;
    font-size: 18px;
    margin-top: 58px;
    margin-bottom: 30px;
  }
  label {
    font-size: 18px;
    line-height: 25px;
    color: #772508;
  }
  .per {
    font-size: 18px;
    color: #772508;
    line-height: 25px;
    margin-bottom: 74px;
  }
  .warn {
    padding: 0 18px 0 24px;
    .warn-title {
      display: flex;
      align-items: center;
      img {
        width: 11px;
        height: 11px;
        margin-right: 6px;
      }
      span {
        font-size: 14px;
        color: #772508;
        line-height: 20px;
      }
    }
    .warn-msg {
      margin-top: 11px;
      p {
        font-size: 14px;
        color: #999;
        line-height: 20px;
        margin-bottom: 7px;
        span {
          color: #772508;
        }
      }
    }
  }
}
</style>
