<template>
  <header class="common-header">
    <div class="logo"></div>
    <span class="copyright">Copyright © China UCF Group Co., Limited.</span>
    <van-divider :style="{ borderColor: '#ededed', margin: '0 -3px' }" />
  </header>
</template>

<script>
export default {
  name: 'CommonHeader',
}
</script>

<style lang="scss">
@import 'assets/scss/mixin';
.common-header {
  height: 68px;
  .logo {
    height: 26px;
    background: center center no-repeat url('~images/common/logo@3x.png');
    background-size: 175px 26px;
    margin-top: 7px;
  }
  .copyright {
    @include sc(9);
    height: 29px;
    color: #9b9b9b;
    text-align: center;
    font-family: ArialMT;
    display: block;
  }
}
</style>
