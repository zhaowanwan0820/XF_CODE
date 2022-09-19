<template>
  <div class="container">
    <mt-header class="header" fixed v-bind:title="getTitle">
      <header-item slot="left" v-bind:isClose="true" v-on:onclick="goHome"></header-item>
    </mt-header>
    <div class="content" v-if="result == 1">
      <div class="img-wrapper">
        <img src="../../assets/image/hh-icon/auth/icon-success.png" />
      </div>
      <p>认证成功</p>
      <button @click="goHome">返回商城</button>
      <!-- <label @click="goBond">兑换积分</label> -->
    </div>
    <div class="content" v-if="result == 0">
      <div class="img-wrapper">
        <img src="../../assets/image/hh-icon/auth/icon-failed.png" />
      </div>
      <div class="con">
        <p class="title">认证失败</p>
      </div>
      <button @click="goAppAuth">重新认证</button>
      <label @click="goWapAuth">使用人工认证</label>
    </div>
    <div class="content" v-if="result == 2">
      <div class="img-wrapper">
        <img src="../../assets/image/hh-icon/auth/icon-failed.png" />
      </div>
      <div class="con">
        <p class="title">认证失败</p>
        <p class="msg" v-if="result == 2">您认证的身份信息与互金平台开户身份信息不一致</p>
      </div>
      <button @click="goWapAuth">使用人工认证</button>
    </div>
  </div>
</template>
<script>
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
import { mapState } from 'vuex'
export default {
  data() {
    return {
      result: '0',
      titelList: ['认证失败', '认证成功', '认证失败']
    }
  },
  created() {
    this.result = this.$route.params.result
  },
  computed: {
    ...mapState({
      userId: state => state.auth.user.id
    }),
    getTitle() {
      return this.titelList[this.result]
    }
  },
  methods: {
    goHome() {
      this.$router.replace({ name: 'home', params: {} })
    },
    goBond() {
      this.$router.replace({ name: 'bond', params: {} })
    },
    goAppAuth() {
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
    },
    getAppType() {
      return window.navigator.userAgent.indexOf('YOUJIEMALL_IOS') > -1 ? 'IOS' : 'AND'
    },
    getAppVersion() {
      let appVersion = this.hhApp.getAppVersion()
      appVersion = parseInt(appVersion.replace(/\./g, ''))
      return appVersion
    },
    goWapAuth() {
      this.$router.replace({ name: 'authPage', params: {} })
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
    width: 164px;
    height: 164px;
    margin-top: 117px;
    img {
      width: 164px;
      height: 164px;
    }
  }
  .con {
    height: 50px;
    margin-top: 4px;
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
    margin-top: 97px;
    margin-bottom: 30px;
  }
  label {
    font-size: 18px;
    line-height: 25px;
    color: #772508;
  }
}
</style>
