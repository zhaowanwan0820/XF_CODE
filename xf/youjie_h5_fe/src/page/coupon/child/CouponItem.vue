<template>
  <div class="coupon-container" :class="{ 'trans-b': opacity }">
    <div class="coupon-info" :class="{ trans: opacity }">
      <div class="left">
        <label>
          <span class="icon">￥</span>
          <span class="num">{{ item.coupon_price }}</span>
        </label>
        <span class="term">{{ getUseTerm }}</span>
      </div>
      <div class="line"></div>
      <div class="right">
        <span class="coupon-name">{{ item.coupon_name }}</span>
        <span class="coupon-time">{{ item.period_time }}</span>
      </div>
    </div>
    <div class="coupon-desc" :class="{ trans: opacity }" @click="upToDown">
      <label :class="{ down: isUp }">{{ item.coupon_desc }}</label>
      <img src="../../../assets/image/hh-icon/coupon/icon-coupon-up.png" v-if="isUp" alt="" />
      <img src="../../../assets/image/hh-icon/coupon/icon-coupon-down.png" v-else alt="" />
    </div>
    <button @click="btnClick" v-if="btnTxt && !opacity">
      <label>{{ btnTxt }}</label>
    </button>
    <div class="img-wrapper" v-if="isUsed">
      <img src="../../../assets/image/hh-icon/coupon/icon-isUsed.png" alt="" />
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isUp: false
    }
  },
  /**
   * [props description]
   * item     优惠券info
   * btnTxt   btn文字 无则btn不展示
   * opacity  true透明度50%（除绝对定位的按钮和图标）
   * isUsed   【已使用】图标
   */
  props: ['item', 'btnTxt', 'opacity', 'isUsed'],
  computed: {
    getUseTerm() {
      let str = ''
      if (this.item.use_term == -1) {
        str = '无门槛'
      } else {
        str = `满${this.item.use_term}元可用`
      }
      return str
    }
  },
  methods: {
    btnClick() {
      this.$emit('onclick')
    },
    upToDown() {
      this.isUp = !this.isUp
    }
  }
}
</script>

<style lang="scss" scoped>
.coupon-container {
  position: relative;
  margin-top: 25px;
  width: 100%;
  border-radius: 2px;
  border: 0.5px solid rgba(208, 180, 130, 0.5);
  &.trans-b {
    border-color: rgba(208, 180, 130, 0.3);
  }
  .coupon-info {
    height: 74px;
    background-color: rgba(208, 180, 130, 0.3);
    display: flex;
    align-items: center;
    .left {
      width: 93px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      label {
        font-size: 0;
        display: flex;
        justify-content: center;
        align-items: baseline;
        span {
          color: #404040;
        }
        .icon {
          font-size: 16px;
          font-weight: bold;
          line-height: 22px;
        }
        .num {
          font-size: 34px;
          font-weight: bold;
          line-height: 40px;
        }
      }
      .term {
        @include sc(11px, #404040);
        font-weight: 400;
        line-height: 16px;
      }
    }
    .line {
      height: 47px;
      border-right: 1px dashed #e1d7c1;
    }
    .right {
      flex: 1;
      padding-left: 12px;
      display: flex;
      justify-content: center;
      flex-direction: column;
      .coupon-name {
        width: 170px;
        font-size: 12px;
        font-weight: 300;
        color: #404040;
        line-height: 17px;

        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
      }
      .coupon-time {
        margin-top: 6px;
        @include sc(11px, #404040, left);
        font-size: 10px;
        font-weight: 300;
        line-height: 14px;
      }
    }
  }
  .coupon-desc {
    padding: 9px;
    display: flex;
    justify-content: space-between;
    label {
      flex: 1;
      display: inline-block;
      @include sc(11px, #999, left);
      font-weight: 300;
      line-height: 18px;

      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      &.down {
        white-space: normal;
        word-break: break-all;
      }
    }
    img {
      width: 7px;
      height: 7px;
      margin-top: 6px;
      margin-right: 11px;
    }
  }
  button {
    position: absolute;
    top: 24px;
    right: 6px;

    width: 62px;
    height: 26px;
    background: #d0b482;
    border-radius: 100px;
    label {
      font-size: 13px;
      font-weight: 400;
      color: #fff;
      line-height: 26px;
    }
  }
  .img-wrapper {
    width: 35px;
    height: 35px;
    position: absolute;
    top: 6px;
    right: 5px;
    img {
      width: 35px;
      height: 35px;
    }
  }
  .trans {
    opacity: 0.5;
    .icon,
    .num,
    .term,
    .coupon-name,
    .coupon-time {
      color: #d0b482 !important;
    }
  }
}
</style>
