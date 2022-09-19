<template>
  <div class="container">
    <span class="num-wrapper">共计{{ totalnum }}件商品</span>
    <span class="word-wrapper">小计：</span>
    <span class="unit-wrapper">￥</span>
    <span class="price-wrapper">{{ isXiache ? '0' : utils.formatFloat(item.use_surplus.product_price) }}</span>
  </div>
</template>

<script>
export default {
  name: 'GroupTotal',
  props: ['item', 'isXiache'],
  computed: {
    totalnum() {
      return this.item.list.reduce((total, currentValue) => {
        return total + Number(currentValue.amount)
      }, 0)
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: row;
  align-items: center;
  height: 50px;
  line-height: 50px;
  color: #404040;
  justify-content: flex-end;
  padding-right: 15px;
  span.num-wrapper {
    font-size: 14px;
    font-weight: 300;
    margin-right: 20px;
  }
  span.word-wrapper {
    font-size: 13px;
    font-weight: 400;
  }
  span.unit-wrapper {
    @include sc(9px, #772508);
    font-weight: 600;
    position: relative;
    top: 2px;
  }
  span.price-wrapper {
    font-weight: bold;
    color: #772508;
  }
}
</style>
