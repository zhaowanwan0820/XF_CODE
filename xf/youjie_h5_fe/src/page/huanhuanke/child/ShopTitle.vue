<template>
  <div class="setTitle">
    <img src="../../../assets/image/hh-icon/icon-header-返回.svg" alt="" @click="goBack" />
    {{ title }}
  </div>
</template>
<script>
export default {
  name: 'ShopTitle',
  props: ['title'],
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>
<style lang="scss" scoped>
.setTitle {
  background: #fff;
  border-bottom: 0.5px solid rgba(85, 46, 32, 0.2);
  height: 44px;
  line-height: 44px;
  font-size: 18px;
  font-family: PingFangSC-Medium;
  font-weight: 500;
  color: #333333;
  text-align: center;

  img {
    position: absolute;
    padding-right: 50px;
    top: 14px;
    left: 15px;
  }
}
</style>
