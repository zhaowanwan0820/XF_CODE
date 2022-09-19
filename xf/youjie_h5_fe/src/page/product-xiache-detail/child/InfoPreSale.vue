<template>
  <div class="container" v-show="isPreSale && time.day != '--'">
    <div class="content">
      <template v-if="time.day > 0">
        <div class="left">预售中</div>
        <div class="right has-bg">
          <span>{{ saleTime.month }}月{{ saleTime.day }}日 {{ saleTime.hour }}:{{ saleTime.minute }}开抢</span>
        </div>
      </template>
      <template v-if="time.day == 0">
        <div class="left">限时抢购中</div>
        <div class="right no-bg">
          <span class="subtitle">距开抢时间</span>
          <span class="time"
            ><span>{{ time.hour }}</span></span
          ><span class="l-m">:</span
          ><span class="time"
            ><span>{{ time.minute }}</span></span
          ><span class="l-m">:</span
          ><span class="time"
            ><span>{{ time.second }}</span></span
          >
        </div>
      </template>
    </div>
    <!-- 状态为待支付 -->
    <count-down
      v-if="detailInfo"
      :endTime="detailInfo.sale_time * 1000"
      v-on:time-change="timeChange"
      v-on:time-end="timeEndEvent"
    ></count-down>
  </div>
</template>

<script>
import CountDown from '../../../components/common/CountDown'
import { mapState, mapMutations, mapGetters } from 'vuex'

export default {
  data() {
    return {
      time: {
        day: '--',
        hour: '--',
        minute: '--',
        second: '--'
      }
    }
  },

  components: {
    CountDown
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo,
      isPreSale: state => state.detail.isPreSale
    }),

    saleTime() {
      const time = new Date(this.detailInfo.sale_time * 1000)
      const hour = time.getHours()
      const minute = time.getMinutes()
      return {
        month: time.getMonth() + 1,
        day: time.getDate(),
        hour: hour < 10 ? `0${hour}` : hour,
        minute: minute < 10 ? `0${minute}` : minute
      }
    }
  },

  methods: {
    ...mapMutations({
      saveSaleFlag: 'saveSaleFlag'
    }),
    timeChange(time) {
      this.time = {
        ...{
          day: time.d,
          hour: time.h,
          minute: time.m,
          second: time.s
        }
      }
      this.saveSaleFlag()
    },
    timeEndEvent() {
      this.saveSaleFlag(false)
    }
  }
}
</script>

<style lang="scss" scoped>
.content {
  height: 36px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #ffffff;
  background: linear-gradient(90deg, rgba(220, 108, 111, 1) 0%, rgba(205, 91, 91, 1) 100%);
  .left {
    padding-left: 15px;
    font-size: 17px;
    font-family: PingFangSC-Semibold;
    font-weight: 600;
  }
  .right {
    padding-right: 15px;
    display: flex;
    align-items: baseline;
    &.has-bg {
      align-self: stretch;
      display: flex;
      align-items: center;
      padding: 0 15px 0 35px;
      position: relative;
      background-color: rgba(138, 34, 34, 0.5);
      &:before {
        content: '';
        display: block;
        position: absolute;
        left: 0;
        top: 0;
        width: 0;
        height: 0;
        border-left: 20px #d46264 solid;
        border-top: 0;
        border-right: 0;
        border-bottom: 36px transparent solid;
      }
    }
    div {
      font-size: 13px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
    }
    span {
      line-height: 1;
    }
    .subtitle {
      font-size: 12px;
      font-family: PingFangSC-Regular;
      margin-right: 9px;
    }
    .l-m {
      margin: 0 2px;
    }
    .time {
      display: flex;
      width: 21px;
      height: 21px;
      align-items: center;
      justify-content: center;
      border-radius: 20px;
      background-color: #8a2222;
      span {
        line-height: 1;
        font-weight: bold !important;
        @include sc(11px, #ffffff);
      }
    }
  }
}
</style>
