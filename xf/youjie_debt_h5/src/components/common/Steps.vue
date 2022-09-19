<template>
  <div class="step-container">
    <div class="num">
      <span class="num-icon" v-if="step <= 1" :class="{ opacity: step < 1 }">1</span>
      <div v-else class="img-wrapper"></div>
      <div class="txt">
        <span :class="{ opacity: step < 1 }">{{ stepStrs[0] }}</span>
      </div>
    </div>
    <div class="line" :class="{ 'opacity-line': step < 2 }"></div>
    <div class="num">
      <span class="num-icon" v-if="step <= 2" :class="{ opacity: step < 2 }">2</span>
      <!-- <img v-else src="../../assets/image/confirmation/icon-checked-on.png" alt="" /> -->
      <div v-else class="img-wrapper"></div>
      <div class="txt">
        <span :class="{ opacity: step < 2 }">{{ stepStrs[1] }}</span>
      </div>
    </div>
    <div class="line" :class="{ 'opacity-line': step < 3 }"></div>
    <div class="num">
      <span class="num-icon" v-if="step <= 3" :class="{ opacity: step < 3 }">3</span>
      <!-- <img v-else src="../../assets/image/confirmation/icon-checked-on.png" alt="" /> -->
      <div v-else class="img-wrapper"></div>
      <div class="txt">
        <span :class="{ opacity: step < 3 }">{{ stepStrs[2] }}</span>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  name: 'Steps',
  props: {
    step: {
      type: Number,
      default: 1
    },
    stepStrs: {
      type: Array,
      default: function() {
        return ['选择机构', '身份验证', '资产确权']
      }
    }
  }
}
</script>

<style lang="less" scoped>
.step-container {
  position: relative;
  width: 266px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  .num {
    width: 30px;
    // height: 30px;
    font-size: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    .num-icon {
      display: block;
      width: 24px;
      height: 24px;
      text-align: center;
      border-radius: 50%;
      background-color: @themeColor;

      font-size: 16px;
      font-weight: 500;
      color: #fff;
      line-height: 24px;
    }
    .img-wrapper {
      width: 24px;
      height: 24px;
      background: url('../../assets/image/confirmation/icon-checked-on.png') no-repeat;
      background-size: 24px;
    }
    .txt {
      margin-top: 10px;
      height: 20px;
      // position: relative;
      span {
        display: block;
        position: absolute;
        transform: translateX(-50%);
        white-space: nowrap;

        font-size: 14px;
        color: @themeColor;
        line-height: 20px;
      }
    }
  }
  .line {
    width: 44px;
    height: 4px;
    padding-top: 12px;
    background: url('../../assets/image/confirmation/line.png') no-repeat center 12px;
    background-size: 44px 4px;
    &.opacity-line {
      opacity: 0.34;
    }
  }
}
.opacity {
  opacity: 0.33;
}
</style>
