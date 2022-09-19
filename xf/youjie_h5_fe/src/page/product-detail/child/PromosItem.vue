<template>
  <div class="body-list">
    <div class="coupon-body">
      <div class="coupon-reduce">
        <div class="number">
          <span class="number-unit">￥</span>
          <span>{{ item.detail.reduce }}</span>
        </div>
        <div class="tips">
          <template v-if="item.sub_type == 2"
            >现金优惠券</template
          >
          <template v-else
            >满减优惠券</template
          >
        </div>
      </div>
      <div class="coupon-rules">
        <div class="rules">满{{ item.detail.limit }}可用</div>
        <div class="time" v-if="item.sub_type != 2">
          {{ utils.formatDate('YYYY-MM-DD', item.start * 1000) }} -
          {{ utils.formatDate('YYYY-MM-DD', item.end * 1000) }}
        </div>
      </div>
      <div class="coupon-status" v-html="getUseRule">{{ getUseRule }}</div>
    </div>
    <div class="coupon-tips">以提交订单后的结算页为准，仅限纯现金结算</div>
  </div>
</template>

<script>
export default {
  data() {
    return {}
  },

  props: {
    item: Object
  },

  computed: {
    getUseRule() {
      let str = ''
      if (this.item.status == 2) {
        if (!this.item.is_over) {
          str = '结算时<br />自动使用'
        } else {
          str = '已抢光'
        }
      } else {
        str = '活动已结束'
      }
      return str
    }
  }
}
</script>

<style lang="scss" scoped>
.body-list {
  margin-top: 10px;
  border: 1px solid #d0b482;
  border-radius: 2px;
  display: flex;
  flex-direction: column;
  height: 105px;
  width: 100%;
  &.checkable {
    .coupon-reduce {
      width: 87px;
    }
    .coupon-status {
      width: 75px;
    }
  }
  .coupon-body {
    flex: 1;
    background: rgba(208, 180, 130, 0.1);
    display: flex;
  }
  .coupon-reduce {
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
    width: 100px;
    .number {
      font-size: 0;
      color: #404040;
      font-weight: 600;
      span {
        font-size: 34px;
        line-height: 1;
      }
      .number-unit {
        font-size: 16px;
        margin-right: -2px;
      }
    }
    .tips {
      @include sc(11px, #404040);
    }
  }
  .coupon-rules {
    font-size: 12px;
    color: #404040;
    line-height: 1.5;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .coupon-status {
    width: 85px;
    background: rgba(208, 180, 130, 0.2);
    display: flex;
    font-size: 12px;
    color: #404040;
    align-items: center;
    justify-content: center;
    text-align: center;
  }
  .coupon-tips {
    line-height: 30px;
    height: 30px;
    @include sc(11px, #666666);
  }
}
</style>
