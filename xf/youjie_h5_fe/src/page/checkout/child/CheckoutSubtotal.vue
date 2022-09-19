<template>
  <div class="container">
    <div class="title">共计{{ subtotalamount }}件商品</div>
    <div>
      小计 :<span class="subtotal"><span>￥</span>{{ subtotal }}</span>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {}
  },
  props: {
    subtotal: { type: String, default: '0' },
    subtotalamount: { type: Number, default: 0 }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  height: 50px;
  text-align: right;
  line-height: 50px;
  padding: 0 15px;
  background: #ffffff;
  box-sizing: border-box;
  font-size: 15px;
  color: $baseColor;
  @include thin-border(#f4f4f4, 15px);
  .title {
    font-weight: 300;
  }
  .subtotal {
    color: #772508;
    font-size: 15px;
    font-weight: bold;
    padding-left: 3px;
    span {
      @include sc(9px, #772508);
    }
  }
}
</style>
