<template>
  <div class="instal-item">
    <span class="title">{{ title }}</span>
    <span class="sub-title">{{ subTitle }}</span>
  </div>
</template>

<script>
// import { SHIPPING_STATUS } from '../static'
export default {
  name: 'OrderInstalmentItem',

  data() {
    return {}
  },

  props: ['title', 'subTitle']
}
</script>

<style lang="scss" scoped>
.instal-item {
  padding: 0 22px;
  display: flex;
  justify-content: flex-start;
  align-items: baseline;
  margin-top: 15px;
  .title {
    font-size: 14px;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    line-height: 20px;
    width: 75px;
  }
  .sub-title {
    width: 230px;
    font-size: 14px;
    font-weight: 400;
    color: rgba(153, 153, 153, 1);
    line-height: 22px;
  }
}
</style>
