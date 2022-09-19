<template>
  <div class="container">
    <mt-header class="header" title="烟台山水龙城">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="guide-body">
      <img src="../../assets/image/hh-icon/pageSSLC/sslc1.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageSSLC/sslc2.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageSSLC/sslc3.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageSSLC/sslc4.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageSSLC/sslc5.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageSSLC/sslc6.jpg" alt="" />
    </div>
  </div>
</template>
<script>
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
