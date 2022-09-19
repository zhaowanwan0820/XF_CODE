<template>
  <div class="container">
    <mt-header class="header" title="康桥永乐街">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="guide-body">
      <img src="../../assets/image/hh-icon/pageYlj/1.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/2.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/3.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/4.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/5.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/6.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/7.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/8.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/9.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageYlj/10.jpg" alt="" />
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
