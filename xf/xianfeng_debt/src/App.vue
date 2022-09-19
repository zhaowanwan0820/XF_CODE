<template>
  <div id="app">
    <div class="app">
      <div class="page-container">
        <van-nav-bar v-if="isShowHeader" :title="title" left-arrow @click-left="onClickLeft" />
        <!--        <keep-alive :include="keepAliveInclude" :max="10">-->
        <!-- <router-view :key="routeKey" class="app-container" ref="routeview"></router-view> -->
        <!--        </keep-alive>-->
        <keep-alive>
          <router-view :key="routeKey" class="app-container" ref="routeview" v-if="$route.meta.keepAlive"></router-view>
        </keep-alive>
        <router-view :key="routeKey" class="app-container" ref="routeview" v-if="!$route.meta.keepAlive"></router-view>
      </div>
      <v-tab-bar v-if="isShowTabBar" ref="bar"></v-tab-bar>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import tabBar from './components/common/Tabbar'

import viewportUnits from './assets/js/vw.js'
// 自动更新客户端前端旧代码
import autoRefresh from './util/autoRefresh'

export default {
  name: 'app',
  data() {
    return {
      isClass: true,
      routeKey: this.$route.fullPath // 主要是相同路由间跳转时强制更新组件（完整地触发组件的生命周期钩子），参见：https://cn.vuejs.org/v2/api/#key
    }
  },
  computed: {
    ...mapGetters({
      isDoneRiskTest: 'isDoneRiskTest'
    }),
    title() {
      return this.$route.meta.title
    },
    isShowTabBar() {
      return this.$route.meta.isshowtabbar
    },
    isShowHeader() {
      return !this.$route.meta.hideHeader
    }
  },
  created: function() {
    if ('production' === process.env.NODE_ENV) {
      // 获取商城localStorage中 auth 用户信息，保存到债权市场
      let MALL_ECM = localStorage.getItem('auth')
      if (!MALL_ECM) return this.goLogin()
      MALL_ECM = JSON.parse(MALL_ECM)
      const isOnline = MALL_ECM.auth.isOnline
      const TOKEN = MALL_ECM.auth.token
      if (!isOnline || !TOKEN) return this.goLogin()
      // 保存用户信息（token）
      this.saveAuthInfo({ token: TOKEN })
    }
    // this.getRisk()
    // 获取用户交易密码设置状态
    this.$loading.open()
    this.updatePwdStatus().finally(() => this.$loading.close())
  },

  watch: {
    $route(to, from) {
      this.routeKey = this.$route.fullPath
      this.changeTabBar(to.name)
    }
  },
  mounted() {
    window.onload = () => {
      // vw兼容
      viewportUnits.viewportUnitsBuggyfill.init({
        hacks: viewportUnits.viewportUnitsBuggyfillHacks
      })
    }
    // 自动更新旧的前端代码
    process.env.NODE_ENV === 'production' && autoRefresh()
  },
  methods: {
    ...mapMutations({
      changeTabBar: 'changeTabBar',
      saveAuthInfo: 'signin'
    }),
    ...mapActions({
      fetchRiskResult: 'fetchRiskResult',
      updatePwdStatus: 'updatePwdStatus'
    }),
    onClickLeft() {
      // 优先调用组件内goback方法
      this.$refs.routeview.goBack ? this.$refs.routeview.goBack() : this.$_goBack()
    },
    goLogin() {
      // window.location.href = '/#/login'
      // return;
      // this.$router.push({ name: 'login' })
    },
    getRisk() {
      this.fetchRiskResult().then(res => {
        console.log(res)
        if (!res.code && !res.data.risk_level) this.$router.replace({ name: 'evaluation', params: { type: 1 } })
      })
    }
  },
  components: {
    'v-tab-bar': tabBar
  }
}
</script>

<style lang="less" scoped>
#app {
  height: 100%;
}
.app {
  position: relative;
  height: 100%;
  overflow-x: hidden;
  font-size: 13px;
  background-color: rgba(249, 249, 249, 1);
  .page-container {
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 100%;
    box-sizing: border-box;
    overflow-x: hidden;
    overflow-y: auto;

    &.bottom {
      bottom: 50px;
    }

    // 剩余容器自动撑高
    display: flex;
    flex-direction: column;
    .app-container {
      flex: 1 0 0;
      overflow-x: hidden;
      overflow-y: auto;
    }
    /deep/ .van-hairline--bottom:after {
      border-bottom-color: #f4f4f4;
    }
  }
}
.isapp {
  background-color: #fff;
}
</style>
