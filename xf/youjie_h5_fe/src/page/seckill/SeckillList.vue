<template>
  <div class="container">
    <mt-header class="header" title="限时秒杀">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="head">
      <div :class="{ 'time-list-little': secList.length < 5, 'time-list': secList.length > 4 }" :style="getSecWidth">
        <div
          class="time-item"
          v-for="item in secList"
          :key="item.id"
          :class="{ active: secCurrentItem.id === item.id }"
          @click="selectItem(item)"
          v-stat="{ id: `seckill_list_time_${item.id}` }"
        >
          <span class="title">{{ item.title }}</span>
          <span class="status">{{ STATUS[item.status] }}</span>
        </div>
      </div>

      <div class="countdown-wrapper">
        <img src="../../assets/image/hh-icon/seckill/bg-countdown.png" alt="" />
        <div class="countdown">
          <label>{{ WARNTXT[secCurrentItem.status] }}</label>
          <sec-countdown :end_at="countdown_end" :from_class="from_class"></sec-countdown>
        </div>
      </div>
    </div>
    <seckill-body ref="secBody"></seckill-body>
  </div>
</template>
<script>
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
import { mapState, mapMutations, mapActions } from 'vuex'
import { seckillTabs } from '../../api/seckill'
import { STATUS, WARNTXT } from './static'

import SecCountdown from '../../components/common/SecCountdown'
import SeckillBody from './child/SeckillBody'
export default {
  name: 'Seckill',
  data() {
    return {
      STATUS: STATUS,
      WARNTXT: WARNTXT,

      countdown_end: 0, // 活动结束时间
      change_code: false, // 倒计时结束 是否正在切换状态
      from_class: 'from-seckill'
    }
  },
  created() {
    this.getSeckillTabs()
  },
  watch: {
    secCurrentStatus(val) {
      this.countdown()
    }
  },
  computed: {
    ...mapState({
      secList: state => state.seckillList.secList,
      secCurrentItem: state => state.seckillList.secCurrentItem,
      secProducts: state => state.seckillList.secProducts,
      secCurrentStatus: state => state.seckillList.secCurrentItem.status
    }),
    getSecWidth() {
      let w
      let r = 60 * this.secList.length + 40 * (this.secList.length - 1)
      if (this.secList.length < 4) {
        w = Math.min(375, r)
      } else {
        w = 375
      }
      if (w === 375) {
        return { width: '100%' }
      } else if (this.secList.length === 1) {
        return { width: w + 'px', padding: '0' }
      } else {
        return { width: w + 'px' }
      }
    },
    activity_st() {
      return this.secCurrentItem.start_at
    },
    activity_et() {
      return this.secCurrentItem.end_at
    }
  },
  components: {
    SeckillBody,
    SecCountdown
  },
  methods: {
    ...mapMutations({
      setSeckillTabStatus: 'setSeckillTabStatus',
      setSecItem: 'setSecItem',
      changeItemStatus: 'changeItemStatus',
      clearSeckillProducts: 'clearSeckillProducts',
      clearSeckill: 'clearSeckill'
    }),
    getSeckillTabs() {
      this.$indicator.open()
      seckillTabs().then(val => {
        this.$indicator.close()
        const data = { list: [...val], time: this.getSysTime() }
        this.setSeckillTabStatus(data)
        this.getTime()
        this.$refs.secBody.getList(true)
      })
    },
    selectItem(item) {
      if (item.id === this.secCurrentItem.id) return
      // 设置当前选中项
      this.setSecItem(item)

      // 开始新的倒计时
      this.getTime()

      if (this.secProducts[item.id]) return

      this.$refs.secBody.getList()
    },
    // 倒计时相关
    getTime() {
      const now = this.getSysTime()
      const start_at = this.activity_st * 1e3
      const end_at = this.activity_et * 1e3
      if (now < start_at) {
        this.countdown_end = start_at
        this.changeItemStatus(0)
      } else if (now > start_at && now < end_at) {
        this.countdown_end = end_at
        this.changeItemStatus(1)
      } else {
        this.countdown_end = 0
        this.changeItemStatus(2)
      }
    },
    countdown() {
      if (this.change_code) {
        switch (this.secCurrentStatus) {
          case 1:
            this.countdown_end = this.activity_et * 1000
            break
          case 2:
            this.countdown_end = 0
            break
        }
        this.change_code = false
      }
    },
    changeState() {
      if (this.secCurrentStatus < 2) {
        this.change_code = true
        this.changeItemStatus(this.secCurrentStatus + 1)
      }
    },
    goBack() {
      this.$_goBack()
    }
  },
  beforeDestroy() {
    this.clearSeckillProducts()
    this.clearSeckill()
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  background-color: #fff;
  display: flex;
  flex-direction: column;
  .head {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: url('../../assets/image/hh-icon/seckill/bg-head.png') no-repeat;
    background-size: 375px 75px;
    padding-bottom: 3px;
  }
  .time-list {
    justify-content: flex-start;
    overflow: auto;
    .time-item {
      margin: 0 5px;
    }
  }
  .time-list-little {
    justify-content: space-between;
  }
  .time-list,
  .time-list-little {
    width: 100%;
    display: flex;
    box-sizing: border-box;
    padding: 0 15px;
    .time-item {
      flex: 0 0 60px;
      padding: 9px 0 10px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      opacity: 0.5;
      .title {
        font-size: 18px;
        font-weight: bold;
        color: #fff;
        line-height: 21px;
      }
      .status {
        display: inline-block;
        @include sc(10px, #fff);
        font-size: 10px;
        font-weight: 400;
        line-height: 14px;
      }
      &.active {
        opacity: 1;
        padding: 10px 0 9px;
        .status {
          padding: 1px 6px;
          background-color: #fff;
          border-radius: 9px;
          text-align: center;
          color: #8a2222;
        }
      }
    }
  }
  .countdown-wrapper {
    width: 355px;
    height: 40px;
    box-shadow: 0px 2px 4px 0px rgba(230, 78, 78, 0.1);
    background-color: #fff;
    border-radius: 2px;
    box-sizing: border-box;
    padding: 0 15px;

    display: flex;
    justify-content: space-between;
    align-items: center;
    img {
      width: 175px;
      height: 12px;
    }
    .countdown {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      @include thin-left-border(rgba(85, 46, 32, 0.3), 0, auto, true);
      label {
        font-size: 12px;
        font-weight: 400;
        color: #999;
        line-height: 16px;
        margin-right: 15px;
        padding-left: 7px;
      }
      .icon,
      .block {
        font-size: 12px;
        font-weight: 400;
        line-height: 16px;
      }
      .icon {
        color: #8a2222;
        line-height: 16px;
        margin: 0 3px;
      }
      .block {
        width: 18px;
        height: 16px;
        background: #8a2222;
        border-radius: 2px;

        text-align: center;
        color: #fff;
      }
    }
  }
}
</style>
