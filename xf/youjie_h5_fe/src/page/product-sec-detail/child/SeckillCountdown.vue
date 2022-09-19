<template>
  <div class="sec-countdown">
    <div class="price-wrapper" v-if="seckillStatus === 0">
      <div class="price">
        <div class="price-left">
          <div class="flex">
            <span class="txt">秒杀价</span>
            <label>￥</label>
            <span class="num">{{ utils.formatFloat(detailInfo.secbuy.cash_price) }}</span>
          </div>
          <p>积分抵扣￥{{ utils.formatFloat(detailInfo.secbuy.money_line) }}</p>
        </div>
        <div class="price-right"></div>
        <div class="discount-wrapper" v-if="detailInfo.secbuy.discount < 10">
          <div class="discount">
            <span>{{ utils.formatFloat(detailInfo.secbuy.discount) }}折</span>
          </div>
        </div>
      </div>
    </div>
    <img src="../../../assets/image/hh-icon/seckill/icon-pro-countdown.png" alt="" v-else />
    <div class="countdown">
      <label v-if="seckillStatus === 0">距秒杀开始仅剩</label>
      <label v-else-if="seckillStatus === 1">距结束仅剩</label>
      <label v-else>秒杀已结束</label>
      <sec-countdown :start_at="countdown_start" :end_at="countdown_end" :from_class="from_class"></sec-countdown>
    </div>
  </div>
</template>

<script>
import secCountdown from '../../../components/common/SecCountdown'
import { mapState, mapMutations } from 'vuex'
import { Toast } from 'mint-ui'

export default {
  name: 'seckillCountdown',
  data() {
    return {
      countdown_start: 0, // 活动开始时间
      countdown_end: 0, // 活动结束时间
      now: 0, // 当前时间戳
      change_code: false, // 倒计时结束 是否正在切换状态
      from_class: 'from-product'
    }
  },
  components: {
    secCountdown
  },
  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo,
      seckillStatus: state => state.seckillList.seckillStatus
    }),
    activity_st() {
      return this.detailInfo.secbuy.start_at
    },
    activity_et() {
      return this.detailInfo.secbuy.end_at
    }
  },
  watch: {
    seckillStatus(val) {
      if (2 == val) {
        this.goProduct()
        return
      }
      this.countdown()
    }
  },
  created() {
    this.getTime()
  },
  methods: {
    ...mapMutations({
      setSeckillStatus: 'setSeckillStatus',
      clearSeckillStatus: 'clearSeckillStatus'
    }),
    getTime() {
      this.now = this.getSysTime()
      let now = this.now
      let start_at = this.activity_st * 1e3
      let end_at = this.activity_et * 1e3

      if (now < start_at) {
        this.countdown_start = this.now
        this.countdown_end = start_at
        this.setSeckillStatus(0)
      } else if (now > start_at && now < end_at) {
        this.countdown_start = this.now
        this.countdown_end = end_at
        this.setSeckillStatus(1)
      } else {
        this.countdown_start = 0
        this.countdown_end = 0
        this.setSeckillStatus(2)
      }
    },
    countdown() {
      if (this.change_code) {
        switch (this.seckillStatus) {
          case 1:
            this.countdown_start = this.activity_st * 1000
            this.countdown_end = this.activity_et * 1000
            break
          case 2:
            this.countdown_start = 0
            this.countdown_end = 0
            break
        }
        this.change_code = false
      }
    },
    changeState() {
      // 倒计时结束后 更改倒计时的status
      if (this.seckillStatus < 2) {
        this.change_code = true
        this.setSeckillStatus(this.seckillStatus + 1)
      }
    },
    goProduct() {
      this.$router.replace({
        name: 'product',
        query: { id: this.detailInfo.id }
      })
    }
  },
  beforeDestroy() {
    this.clearSeckillStatus()
  }
}
</script>

<style lang="scss" scoped>
.sec-countdown {
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(90deg, rgba(138, 34, 34, 1) 0%, rgba(196, 72, 72, 1) 100%);
  img {
    width: 68px;
    height: 16px;
    padding-left: 15px;
  }
  .price-wrapper {
    height: 100%;
    position: relative;
    .price {
      position: absolute;
      left: 0;
      bottom: 0;
      height: 73px;

      display: flex;
      justify-content: flex-start;
      align-items: center;
      .price-left {
        min-width: 106px;
        height: 100%;
        font-size: 0;
        color: #fff;
        padding-left: 12px;
        padding-top: 12px;
        box-sizing: border-box;
        background: linear-gradient(90deg, rgba(151, 37, 37, 1) 0%, rgba(198, 59, 46, 1) 100%);
        box-shadow: 5px 0px 11px -2px rgba(126, 12, 12, 0.16);

        .flex {
          display: flex;
          align-items: flex-end;
          justify-content: flex-start;
        }
        label {
          font-size: 20px;
          font-weight: bold;
          line-height: 1;
          margin-bottom: 3px;
        }
        span.txt {
          font-size: 12px;
          font-weight: 400;
          color: #fff;
          line-height: 17px;
          word-break: keep-all;
          margin-bottom: 3px;
        }
        span.num {
          font-size: 33px;
          font-weight: bold;
          line-height: 1;
          vertical-align: text-bottom;
        }
        p {
          display: inline-block;
          @include sc(10px, #fff, left);
          margin-top: 6px;
          font-weight: 400;
          line-height: 14px;
          opacity: 0.8;
          word-break: break-all;
        }
      }
      .price-right {
        width: 67px;
        height: 73px;
        background: url('../../../assets/image/hh-icon/seckill/bg-pro-price.png') no-repeat;
        background-size: 67px 73px;
        z-index: 1;
      }
      .discount-wrapper {
        width: 0;
        height: 100%;
        position: relative;
        .discount {
          position: absolute;
          top: 16px;
          left: -49px;

          width: 82px;
          height: 28px;
          background: linear-gradient(90deg, rgba(247, 61, 231, 1) 0%, rgba(165, 66, 240, 1) 100%);
          border-radius: 0px 14px 14px 0px;

          display: flex;
          align-items: center;
          justify-content: center;
          span {
            font-size: 16px;
            font-weight: 500;
            color: rgba(255, 255, 255, 1);
            line-height: 1;
          }
        }
      }
    }
  }
  .countdown {
    font-size: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 15px;
    label {
      font-size: 12px;
      font-weight: 400;
      color: #feffff;
      line-height: 17px;
      padding-right: 9px;
    }
    span {
      display: inline-block;
      @include sc(11px, #f9f9f9);
      font-weight: bold;
      line-height: 21px;
      text-align: center;
    }
    .num {
      @include wh(21px, 21px);
      background: rgba(138, 34, 34, 0.5);
      border-radius: 50%;
    }
  }
}
</style>
