<template>
  <div class="sec-container" v-if="hasSeckill">
    <div class="sec-title">
      <div class="title">
        <img src="../../../assets/image/hh-icon/seckill/home-title.png" alt="" class="left" />
        <div class="right">
          <span class="time-left">{{ title }}场</span>
          <span class="time-right">
            <!-- {{ timeTxt }} -->
            <sec-countdown :start_at="countdown_start" :end_at="countdown_end" :from_class="from_class"></sec-countdown>
          </span>
        </div>
      </div>
      <div class="more-w" @click="goSeckillList">
        <span class="more" v-stat="{ id: 'index_seckill_entry_more' }">更多...</span>
      </div>
    </div>

    <swiper :options="swiperOption" ref="mySwiper" class="list-body" @click="goSeckillList">
      <template v-if="seckillInfo.list && seckillInfo.list.length" v-for="(item, index) in seckillInfo.list">
        <swiper-slide :key="index" class="seckill-swiper">
          <div class="list-item" v-stat="{ id: `index_seckill_entry_product_${item.id}` }">
            <div class="discount" v-if="item.discount > 0 && item.discount < 10">
              <span>{{ utils.formatFloat(item.discount) }}折</span>
            </div>
            <img
              v-lazy="{
                src: item.goods.thumb,
                error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
                loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
              }"
              alt="商品图片"
            />
            <label class="item-name">{{ item.goods.name }}</label>
            <label class="item-price">
              <span class="icon">￥</span>
              <span class="num">{{ utils.formatFloat(item.cash_price) }}</span>
            </label>
            <label class="item-price-line">
              <span class="icon">￥</span>
              <span class="num">{{ utils.formatFloat(item.goods.price) }} </span>
            </label>
          </div>
        </swiper-slide>
      </template>
    </swiper>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import SecCountdown from '../../../components/common/SecCountdown'

export default {
  name: 'HomeSeckill',
  data() {
    return {
      swiperOption: {
        slidesPerView: 'auto',
        paginationClickable: true,
        spaceBetween: 5,
        freeMode: true,
        timer: null
      },
      countdown_start: 0, // 活动开始时间
      countdown_end: 0, // 活动结束时间
      now: 0, // 当前时间戳
      from_class: 'from-home',
      timer: null,
      clearTimeSync: null
    }
  },
  components: {
    SecCountdown
  },
  created() {
    this.fetchHomeSeckillProducts()
  },
  computed: {
    ...mapState({
      seckillInfo: state => state.home.seckill
    }),
    title() {
      let h = this.utils.formatDate('HH', this.seckillInfo.start_at)
      let m = this.utils.formatDate('mm', this.seckillInfo.start_at)
      return h + ':' + m
    },
    activity_st() {
      return this.seckillInfo.start_at
    },
    activity_et() {
      return this.seckillInfo.end_at
    },
    hasSeckill() {
      return this.seckillInfo && this.seckillInfo.list && this.seckillInfo.list.length ? true : false
    }
  },
  watch: {
    seckillInfo() {
      this.getTime()
    }
  },
  methods: {
    ...mapMutations({
      clearHomeSeckill: 'clearHomeSeckill'
    }),
    ...mapActions({
      fetchHomeSeckillProducts: 'fetchHomeSeckillProducts'
    }),
    getTime() {
      this.now = this.getSysTime()
      let now = this.now
      let start_at = this.activity_st * 1e3
      let end_at = this.activity_et * 1e3

      if (now < start_at) {
        this.countdown_start = this.now
        this.countdown_end = start_at
      } else if (now > start_at && now < end_at) {
        this.countdown_start = this.now
        this.countdown_end = end_at
      } else {
        this.countdown_start = 0
        this.countdown_end = 0
      }
    },
    changeState() {
      // 倒计时结束后 获取新的开始和结束时间
      this.getTime()
    },
    goSeckillList() {
      this.$router.push({ name: 'Seckill' })
    },
    goProduct(id) {
      this.$router.push({ name: 'SeckillProduct', query: { id: id } })
    }
  },
  beforeDestroy() {
    this.clearHomeSeckill()
  }
}
</script>

<style lang="scss" scoped>
.sec-container {
  margin-bottom: 10px;
  padding: 17px 0 15px;
  background-color: #fff;
  .sec-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 10px 17px;
    .title {
      display: flex;
      align-items: center;
    }
    .left {
      width: 70px;
      height: 20px;
      margin-right: 10px;
    }
    .right {
      box-sizing: border-box;
      padding-left: 5px;
      padding-right: 8px;
      width: 142px;
      height: 20px;
      background: url('../../../assets/image/hh-icon/seckill/home-timer.png') center no-repeat;
      background-size: 142px 20px;

      display: flex;
      justify-content: space-between;
      align-items: center;
      .time-left {
        font-size: 12px;
        color: #fff;
        margin-top: -3px;
        // line-height: 15px;
      }
      .time-right {
        width: 69px;
        font-size: 13px;
        font-weight: bold;
        color: #8a2222;
        line-height: 16px;
        letter-spacing: 2px;
      }
    }
    .more-w {
      flex: 1;
      display: flex;
      justify-content: flex-end;
      .more {
        display: inline-block;
        @include sc(9px, #999, right center);
      }
    }
  }
  .list-body {
    padding: 0 10px;
    .seckill-swiper {
      width: auto;
    }
    .list-item {
      display: flex;
      flex-direction: column;
      align-items: center;

      box-sizing: border-box;
      width: 104px;
      height: 151px;
      border-radius: 6px;

      border: 5px solid;
      border-image: url('../../../assets/image/hh-icon/seckill/home-border-dash.svg') 5 round;
      position: relative;
      .discount {
        position: absolute;
        top: 5px;
        left: -5px;
        width: 36px;
        height: 16px;
        background: linear-gradient(90deg, rgba(247, 61, 231, 1) 0%, rgba(165, 66, 240, 1) 100%);
        border-radius: 0px 39px 39px 0px;

        display: flex;
        align-items: center;
        justify-content: center;
        span {
          display: inline-block;
          @include sc(11px, #fff);
          font-weight: 500;
          line-height: 16px;
        }
      }
      img {
        width: 70px;
        height: 70px;
        padding: 2px 0 6px;
      }
      .item-name {
        display: inline-block;
        @include sc(10px, #404040);
        margin: 0 -7px;
        font-weight: 500;
        line-height: 16px;
        height: 30px;
        overflow: hidden;
        margin-bottom: 7px;
      }
      .item-price,
      .item-price-line {
        display: flex;
        justify-content: flex-start;
        align-items: baseline;
      }
      .item-price {
        width: 100%;
        height: 14px;
        font-size: 0;
        .icon {
          display: inline-block;
          @include sc(9px, #772508, right center);
          font-weight: bold;
          line-height: 10px;
        }
        .num {
          font-size: 14px;
          font-weight: bold;
          color: #772508;
          line-height: 14px;
        }
      }
      .item-price-line {
        width: 100%;
        height: 13px;
        font-size: 0;
        .icon {
          display: inline-block;
          @include sc(9px, #999, right center);
          font-weight: bold;
          line-height: 13px;
          text-decoration: line-through;
        }
        .num {
          display: inline-block;
          @include sc(10px, #999, left center);
          font-weight: 400;
          line-height: 13px;
          text-decoration: line-through;
        }
      }
    }
  }
}
</style>
