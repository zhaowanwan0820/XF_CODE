<template>
  <div class="container">
    <mt-header class="header" title="关注微信公众账号">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goHome"></header-item>
    </mt-header>
    <div class="introduce-content">
      <div>
        <img class="avatar" src="../../assets/image/hh-icon/g0-guide/ofclacnt-guide-1.png" />
        <img class="avatar" src="../../assets/image/hh-icon/g0-guide/ofclacnt-guide-2.png" />
        <img class="avatar" src="../../assets/image/hh-icon/g0-guide/ofclacnt-guide-3.png" />
        <img class="avatar" src="../../assets/image/hh-icon/g0-guide/ofclacnt-guide-4.png" />
        <img class="avatar" src="../../assets/image/hh-icon/g0-guide/ofclacnt-guide-5.png" />
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
export default {
  data() {
    return {}
  },
  methods: {
    goHome() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.header {
  @include header();
}
.introduce-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  font-size: 0;
  overflow-y: auto;
  & > div {
    flex-shrink: 0;
  }
  img {
    width: 100%;
  }
}
</style>
