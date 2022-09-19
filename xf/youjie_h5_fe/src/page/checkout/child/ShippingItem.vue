<template>
  <div class="container">
    <label class="title">配送方式</label>
    <label class="subtitle">{{ priceStr }}</label>
  </div>
</template>

<script>
import { mapState } from 'vuex'
export default {
  // props: {
  //   price: {
  //     type: String
  //   }
  // },
  computed: {
    priceStr() {
      // if (this.price === '免运费') {
      //   return this.price
      // }

      // return '￥' + this.price
      return '普通物流'
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  background-color: #fff;
  height: 42px;
  padding: 0 15px;
  color: $baseColor;
  font-size: 13px;
  @include thin-border(#f4f4f4, 15px);
  font-weight: 300;
}
</style>
