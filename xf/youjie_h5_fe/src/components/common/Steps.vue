<template>
  <div class="step-container">
    <div class="num">
      <span class="num-icon" v-if="step <= 1" :class="{ opacity: step < 1 }">1</span>
      <div v-else class="img-wrapper"></div>
      <div class="txt">
        <span :class="{ opacity: step != 1 }">{{ stepStrs[0] }}</span>
      </div>
    </div>
    <div class="line" :class="{ 'opacity-line': step > 1 }"></div>
    <div class="num">
      <span class="num-icon" v-if="step <= 2" :class="{ opacity: step < 2 }">2</span>
      <!-- <img v-else src="../../assets/image/hh-icon/confirmation/icon-checked-on.png" alt="" /> -->
      <div v-else class="img-wrapper"></div>
      <div class="txt">
        <span :class="{ opacity: step != 2 }">{{ stepStrs[1] }}</span>
      </div>
    </div>
    <div class="line" :class="{ 'opacity-line': step > 2 }"></div>
    <div class="num">
      <span class="num-icon" v-if="step <= 3" :class="{ opacity: step < 3 }">3</span>
      <!-- <img v-else src="../../assets/image/hh-icon/confirmation/icon-checked-on.png" alt="" /> -->
      <div v-else class="img-wrapper"></div>
      <div class="txt">
        <span :class="{ opacity: step != 3 }">{{ stepStrs[2] }}</span>
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

<style lang="scss" scoped>
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
      width: 30px;
      height: 30px;
      text-align: center;
      border-radius: 50%;
      background-color: $primaryColor;

      font-size: 20px;
      font-weight: 500;
      color: #fff;
      line-height: 30px;
    }
    .img-wrapper {
      width: 30px;
      height: 30px;
      background: url('../../assets/image/hh-icon/confirmation/icon-checked-on.png') no-repeat;
      background-size: 30px;
    }
    .txt {
      margin-top: 7px;
      height: 20px;
      // position: relative;
      span {
        display: block;
        position: absolute;
        transform: translateX(-50%);
        white-space: nowrap;

        font-size: 14px;
        color: $primaryColor;
        line-height: 20px;
      }
    }
  }
  .line {
    width: 44px;
    height: 4px;
    padding-top: 15px;
    background: url('../../assets/image/hh-icon/confirmation/line-off.png') no-repeat center 15px;
    background-size: 44px 4px;
    &.opacity-line {
      background-image: url('../../assets/image/hh-icon/confirmation/line-on.png');
    }
  }
}
.opacity {
  opacity: 0.33;
}
</style>
