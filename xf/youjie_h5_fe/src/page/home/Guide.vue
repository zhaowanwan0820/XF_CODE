<template>
  <div class="container">
    <mt-header class="header" title="积分购物新手引导">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="guide-body">
      <img src="../../assets/image/hh-icon/guide/0.png" alt="" />
      <img src="../../assets/image/hh-icon/guide/1.png" alt="" />
      <img src="../../assets/image/hh-icon/guide/2.png" alt="" />
      <img src="../../assets/image/hh-icon/guide/3.png" alt="" />
      <img src="../../assets/image/hh-icon/guide/4.png" alt="" />
    </div>
  </div>
</template>
<script>
import { Header, Indicator } from 'mint-ui'
import { HeaderItem } from '../../components/common'
export default {
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>
<style lang="scss" scoped>
.header {
  @include thin-border(#f4f4f4);
}
.container {
  height: 100%;
  background-color: #fff;
  display: flex;
  flex-direction: column;
}
.guide-body {
  flex: 1;
  overflow: auto;
  font-size: 0;
  img {
    width: 100%;
  }
}
</style>
