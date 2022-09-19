<template>
  <div id="app">
    <div class="app">
      <div class="page-container" :class="containerClass">
        <keep-alive :include="keepAliveInclude" :max="10">
          <router-view :key="routeKey"></router-view>
        </keep-alive>
      </div>

      <v-auth-popup v-if="showAuthPopup" :isShowauth="showAuthPopup"></v-auth-popup>

      <template v-if="activityTabBarSwitch">
        <v-tab-bar-activity v-if="isShowTabBar" ref="bar"></v-tab-bar-activity>
      </template>
      <template v-else>
        <v-tab-bar v-if="isShowTabBar" ref="bar"></v-tab-bar>
      </template>
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import tabBar from './components/common/Tabbar'
import ItouziAuthPopup from './components/common/ItouziAuthPopup'
import { Toast } from 'mint-ui'

import { activity_tabar as config_activity } from './config/activity'
import tabbarActivity from './components/common/TabbarActivity'
import { sendBuryingPointInfo } from './api/buryingPoint'
import viewportUnits from './assets/js/vw.js'

// 自动更新客户端前端旧代码
import autoRefresh from './util/autoRefresh'

export default {
  name: 'app',
  components: {
    'v-tab-bar': tabBar,
    'v-tab-bar-activity': tabbarActivity,
    'v-auth-popup': ItouziAuthPopup
  },
  data() {
    return {
      activityTabBarSwitch: false,
      routeKey: this.$route.fullPath // 主要是相同路由间跳转时强制更新组件（完整地触发组件的生命周期钩子），参见：https://cn.vuejs.org/v2/api/#key
    }
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      user: state => state.auth.user,
      showAuthPopup: state => state.itouzi.showAuthPopup,
      isHHAppTop: state => state.app.isHHAppTop,
      keepAliveInclude: state => state.keepAlive.include,
      cartNumber: state => state.tabBar.cartNumber, // 购物车内商品总数
      counterForTabRefresh: state => state.app.counterForTabRefresh // App Tabbar切换时 通知到H5的计数器
    }),
    isShowTabBar() {
      let show = this.$route.meta.isshowtabbar

      // App原生 v0.4.0开始 底部tabBar有原生
      if (this.AppVersion >= 40) {
        show = false
      }
      return show
    },
    containerClass() {
      return {
        bottom: this.isShowTabBar
      }
    }
  },
  created: function() {
    let name = this.$route.name
    if (name) {
      this.changeTabBar(name)
    }

    if (this.isOnline) {
      // 同步购物车数据
      this.fetchCartNumber()
      // 同步用户信息
      this.syncUserProfile()
    }

    this.saveAuthInfo()
    this.setCookieFromToken()

    // 初始化埋点
    // sendBuryingPointInfo({
    //   click_position: 'app_restart'
    // })
  },

  watch: {
    $route(to, from) {
      // 路由改变发起重置
      this.resetStates()
      this.changeTabBar(to.name)

      this.routeKey = this.$route.fullPath
    },
    isHHAppTop(newv, oldv) {
      // 为避免频繁调用bridge.isTop方法，故在watch中处理
      this.isHHApp && this.hhApp.isTop(newv)
    },
    cartNumber(value) {
      // 购物车内商品总数发生变化时 通知 App
      if (this.isHHApp && this.AppVersion >= 40) {
        this.hhApp.cartChanged(value)
      }
    },
    counterForTabRefresh(value) {
      // ！！！App由其他Webview切回一级页面时 页面并不刷新，会出现localStorage数据与组件store数据不同步的情况，需要及时更新一些数据
      if (this.isOnline) {
        this.fetchCartNumber()
      }
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
      saveToken: 'saveToken',
      clearToken: 'clearToken',
      changeTabBar: 'changeTabBar',
      setCookieFromToken: 'setCookieFromToken'
    }),
    ...mapActions({
      resetStates: 'resetStates',
      fetchCartNumber: 'fetchCartNumber',
      syncUserProfile: 'fetchUserInfos'
    }),
    goBack() {
      this.$_goBack()
    },
    saveAuthInfo() {
      let location = window.location
      let references = this.utils.getUrlKey(location.href, 'u')
      if (references) {
        this.utils.setCookie('r', references)
      }
      let token = this.utils.getUrlKey(location.href, 'token')
      if (token) {
        this.utils.setCookie('t', token)
        this.saveToken(token)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.app {
  position: relative;
  height: 100%;
  overflow-x: hidden;

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
      bottom: 48px;
    }
  }
}
</style>
