<template>
  <transition name="fade">
    <div id="app">
      <van-nav-bar
        :class="`header-navbar--${$route.name}`"
        v-if="isShowHeader"
        :title="title"
        :left-arrow="isShowHeaderBack"
        @click-left="onClickLeft"
      />
      <router-view :key="routeKey" class="wrapper" ref="routeview" v-if="!$route.meta.keepAlive"></router-view>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'app',
  computed: {
    title() {
      return this.$route.meta.title
    },
    isShowHeader() {
      return !this.$route.meta.hideHeader
    },
    isShowHeaderBack() {
      return !this.$route.meta.hideHeaderBack
    },
  },
  data() {
    return {
      routeKey: this.$route.fullPath, // 主要是相同路由间跳转时强制更新组件（完整地触发组件的生命周期钩子），参见：https://cn.vuejs.org/v2/api/#key
    }
  },
  methods: {
    onClickLeft() {
      if (!this.isShowHeaderBack) {
        return
      }
      // 优先调用组件内goback方法
      this.$refs.routeview.goBack ? this.$refs.routeview.goBack() : this.__goBack()
    },
  },
}
</script>

<style lang="scss">
@import 'assets/scss/theme';

#app {
  width: 100%;
  height: 100%;
  overflow: hidden;
  position: relative;
  background-color: rgba(249, 249, 249, 1);
  display: flex;
  flex-direction: column;
}
.wrapper {
  width: 100%;
  height: 100%;
  flex: 1;
  overflow-x: hidden;
}

// 改顶部导航样式
#app .van-nav-bar {
  height: 50px;
  background-color: #ededed;
  flex: 0 0 auto;
  .van-ellipsis {
    font-size: 18px;
    color: #4a4a4a;
  }
  .van-icon-arrow-left {
    width: 18px;
    height: 18px;
    background: center center no-repeat url('~images/common/返回.svg');
    background-size: 18px 18px;
    &::before {
      content: '';
    }
  }
}
.van-dialog {
  .van-dialog__header {
    font-family: PingFangSC-Medium;
    font-size: 20px;
    color: #3833df;
  }
  .van-dialog__confirm {
    color: #3833df;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter,
.fade-leave-active {
  opacity: 0;
}
</style>
