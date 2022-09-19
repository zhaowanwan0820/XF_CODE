<template>
  <div class="coupon-container">
    <div class="coupon-info" :class="{ trans: opacity }">
      <div class="left">
        <div>
          <span class="icon">￥</span>
          <span class="num">{{ item.coupon_price }}</span>
        </div>
        <span class="term">{{ getUseTerm }}</span>
      </div>
      <div class="line"></div>
      <div class="right">
        <span class="coupon-name">{{ item.coupon_name }}</span>
        <span class="coupon-time">{{ item.period_time }}</span>
      </div>
      <template v-if="isDetail">
        <div v-if="item.is_rec == 2" class="btn is-rec">已领取</div>
        <div v-else class="btn" @click="btnClick">领取</div>
      </template>
    </div>
    <div class="coupon-desc" v-if="item.coupon_desc" :class="{ trans: opacity }" @click="upToDown">
      <p :class="{ down: isShowDesc }">
        <label>{{ item.coupon_desc }}</label>
      </p>
      <img src="../../../assets/image/hh-icon/coupon/icon-coupon-down.png" v-if="!isShowDesc" alt="" />
      <img src="../../../assets/image/hh-icon/coupon/icon-coupon-up.png" v-else alt="" />
    </div>
    <div class="img-wrapper" v-if="isUsed">
      <img src="../../../assets/image/hh-icon/coupon/icon-isUsed.png" alt="" />
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isShowDesc: false
    }
  },
  /**
   * [props description]
   * item     优惠券info
   * opacity  true透明度50%（除绝对定位的按钮和图标）
   * isUsed   【已使用】图标
   */
  props: ['item', 'opacity', 'isUsed', 'isDetail'],

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
      this.isShowDesc = !this.isShowDesc
    }
  }
}
</script>

<style lang="scss" scoped>
.coupon-container {
  width: 100%;
  box-sizing: border-box;
  border-radius: 2px;
  border: 1px solid rgba(208, 180, 130, 0.5);
  & + .coupon-container {
    margin-top: 25px;
  }
  &.is-rec {
    .coupon-info {
      background-color: rgba(208, 180, 130, 0.2);
      .left {
        opacity: 0.5;
        div span,
        .term {
          color: rgba(202, 187, 152, 1);
        }
      }
      .right {
        .coupon-name,
        .coupon-time {
          color: rgba(202, 187, 152, 1);
        }
      }
    }
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
      div {
        font-size: 0;
        display: flex;
        align-items: baseline;
        span {
          color: #404040;
        }
        .icon {
          font-size: 16px;
          font-weight: 600;
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
      margin-left: 12px;
      display: flex;
      justify-content: center;
      flex-direction: column;
      .coupon-name {
        font-size: 12px;
        font-weight: 300;
        color: #404040;
        line-height: 17px;
      }
      .coupon-time {
        display: inline-block;
        width: 120%;
        margin-top: 6px;
        @include sc(10px, #404040, left);
        font-size: 10px;
        font-weight: 300;
        line-height: 14px;
      }
    }
    .btn {
      margin: 0 8px;
      width: 52px;
      height: 30px;
      background-color: rgba(208, 180, 130, 0.39);
      border-radius: 15px;
      text-align: center;
      line-height: 30px;
      color: rgba(159, 106, 11, 1);
      font-size: 14px;
      &.is-rec {
        font-size: 12px;
        background-color: transparent;
      }
    }
  }
  .coupon-desc {
    padding: 7px 20px 7px 9px;
    display: flex;
    justify-content: space-between;
    p {
      font-size: 0;
      overflow: hidden;
      height: 16px;
      word-wrap: break-word;
      &.down {
        height: auto;
      }
    }
    label {
      flex: 1;
      display: inline-block;
      width: 109%;
      @include sc(11px, #999, left);
      font-weight: 300;
      line-height: 16px;
      word-break: break-all;
    }
    img {
      margin-left: 15px;
      width: 7px;
      height: 7px;
      margin-top: 5px;
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
  }
}
</style>
