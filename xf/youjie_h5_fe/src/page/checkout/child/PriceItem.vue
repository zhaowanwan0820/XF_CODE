<template>
  <div class="price-item-wrapper">
    <div class="left">
      <span>{{ title }}</span>
    </div>
    <div class="right" :style="{ color: valueColor }">
      <span v-if="icon" :style="{ 'font-weight': isBold }">￥</span>
      <span v-if="negative && value > 0" :style="{ 'font-weight': isBold }">-</span>
      <span :style="{ 'font-weight': isBold }">{{ utils.formatFloat(value) }}</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PriceItem',
  data() {
    return {}
  },
  props: {
    title: {
      type: String
    },
    value: [String, Number],
    valueColor: {
      type: String
    },
    icon: {
      type: Boolean
    },
    negative: {
      type: Boolean
    },
    isBold: {
      type: String,
      default: 'normal'
    }
  }
}
</script>

<style lang="scss" scoped>
.price-item-wrapper {
  font-size: 0;
  padding: 13px 15px 2px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  .left,
  .right {
    color: #404040;
    span {
      font-size: 13px;
      font-weight: 300;
      line-height: 18px;
    }
  }
}
</style>
