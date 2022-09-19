<template>
  <div
    class="l-i-p-wrapper"
    ref="wrapper"
    :class="[
      {
        'ab-length': !isDone,
        mini: isBreak
      },
      classType
    ]"
  >
    <div class="price-item" ref="body">
      <!-- <label>896,537,809.69</label> -->

      <div class="img"></div>
      <label>{{ utils.formatFloat(surplus, true) }}</label>
      <!-- <label>380665489</label> -->
      <template>
        <span class="add-unit">+</span>
        <span class="price-unit">￥</span>
        <label>{{ utils.formatFloat(cash, true) }}</label>
      </template>
    </div>
  </div>
</template>
<script>
export default {
  data() {
    return {
      isDone: false,
      isBreak: false
    }
  },
  props: {
    cash: {
      type: [Number, String],
      default: 0
    },
    surplus: {
      type: [Number, String],
      default: 0
    },
    classType: {
      type: [String],
      default: ''
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.calculate()
    })
  },
  methods: {
    calculate() {
      // console.log(this.$refs.wrapper.getBoundingClientRect().width , this.$refs.body.getBoundingClientRect().width,"----")
      if (this.$refs.wrapper.getBoundingClientRect().width < this.$refs.body.getBoundingClientRect().width) {
        this.isBreak = true
      }
      this.isDone = true
    }
  }
}
</script>

<style lang="scss" scoped>
$priceColor: #b75800; // 价格颜色
.l-i-p-wrapper {
  width: 100%;
  &.ab-length {
    overflow: visible;
    position: relative;
    .price-item {
      position: absolute;
      top: 0;
      left: 0;
    }
  }
  &.mini .price-item {
    font-size: 12px;
    transform-origin: left center;
    -webkit-transform-origin: left center;
    transform: scale(floor(10 / 0.12) / 100);
    -webkit-transform: scale(floor(10 / 0.12) / 100);
  }
}
.price-item {
  font-size: 0;
  white-space: nowrap;
  label {
    display: inline-block;
    color: $priceColor;
    font-size: 12px;
    font-weight: bold;
    line-height: 1;
    font-family: DINAlternate-Bold, DINAlternate;
  }
  .price-unit {
    @include sc(8px, $priceColor);
    font-weight: 400 !important;
    line-height: 8px;
    line-height: 1 !important;
    margin-right: -1px;
    // transform: scale(.83);
  }
  .add-unit {
    display: inline-block;
    @include sc(9px, $priceColor);
    font-weight: bold !important;
    line-height: 1 !important;
    margin: 0 1px;
    margin-bottom: 1px;
  }
  .img {
    display: inline-block;
    width: 10px;
    height: 10px;
    background: url('../../assets/image/hh-icon/new-home/lable-ji.png') no-repeat;
    background-size: 100% 100%;
    margin-right: 3px;
    margin-bottom: -1px;
  }
}
.price-style-one {
  .price-item {
    .img {
      width: 14px;
      height: 14px;
    }
    label {
      font-size: 18px;
    }
    .add-unit {
      font-size: 17px;
    }
  }
}
.price-style-two {
  .price-item {
    .img {
      width: 22px;
      height: 22px;
    }
    label {
      font-size: 29px;
    }
    .add-unit {
      font-size: 22px;
    }
    .price-unit {
      font-size: 17px;
    }
  }
}
</style>
