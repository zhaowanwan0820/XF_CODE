<template>
  <div id="app">
    <div class="app">
      <div class="page-container">
        <router-view :key="routeKey"></router-view>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'app',
  data() {
    return {
      routeKey: '' // 主要是相同路由间跳转时强制更新组件（完整地触发组件的生命周期钩子），参见：https://cn.vuejs.org/v2/api/#key
    }
  },
  watch: {
    $route(to, from) {
      this.routeKey = this.$route.fullPath
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
  }
}
</style>
