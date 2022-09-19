<template>
  <div class="sec-c" :class="from_class">
    <span class="num">{{ countdown_h }}</span>
    <span class="icon">{{ separator['hour'] }}</span>
    <span class="num">{{ countdown_m }}</span>
    <span class="icon">{{ separator['minute'] }}</span>
    <span class="num">{{ countdown_s }}</span>
    <span class="icon" v-if="separator['second']">{{ separator['second'] }}</span>
  </div>
</template>

<script>
export default {
  data() {
    return {
      countdown_start: 0,
      countdown_end: 0,
      timer: null
    }
  },
  props: {
    end_at: Number,
    from_class: String,
    separatorConf: Object
  },
  watch: {
    active_info(val) {
      // 监听prop变化 重置当前倒计时
      this.start()
    }
  },
  computed: {
    active_info() {
      return {
        end_at: this.end_at
      }
    },
    count_rest_time() {
      const rest = this.countdown_end - this.countdown_start
      return rest <= 0 ? 0 : rest
    },
    countdown_h() {
      const rest = this.count_rest_time / 1000
      return this.utils.padLeftZero(parseInt(rest / 3600))
    },
    countdown_m() {
      const rest = this.count_rest_time / 1000
      return this.utils.padLeftZero(parseInt((rest - this.countdown_h * 3600) / 60))
    },
    countdown_s() {
      const rest = this.count_rest_time / 1000
      return this.utils.padLeftZero(parseInt(rest - this.countdown_h * 3600 - this.countdown_m * 60))
    },
    separator() {
      let sep = {
        hour: ':',
        minute: ':',
        second: ''
      }
      if (this.separatorConf) {
        sep = { ...sep, ...this.separatorConf }
      }
      return sep
    }
  },
  created() {
    this.start()
  },
  methods: {
    start() {
      this.clear()

      if (this.end_at <= 0) {
        // 结束时间异常 时间重置为0
        this.countdown_start = this.countdown_end = 0
        return
      }

      this.countdown_start = this.getSysTime()
      this.countdown_end = this.end_at
      this.startCountdown()
    },
    startCountdown() {
      this.countdown()
    },
    countdown() {
      if (this.count_rest_time <= 0) {
        this.countdown_start = this.countdown_end
        // 倒计时结束后 通知父组件改变计时状态
        this.$parent.changeState && this.$parent.changeState()
        return
      }
      this.timer = setTimeout(() => {
        this.clear()
        this.countdown_start = this.getSysTime()
        this.countdown()
      }, 1000)
    },
    clearTimer() {
      if (this.timer) clearTimeout(this.timer)
    },
    clear() {
      this.clearTimer()
    }
  },
  beforeDestroy() {
    this.clear()
  },
  deactivated() {
    this.clear()
  },
  activated() {
    // 恢复倒计时
    this.clear()
    this.startCountdown()
  }
}
</script>

<style lang="less" scoped>
// 不同文件的倒计时样式 在这个文件里注入
@import '../../components/style/secCountdown.less';
.sec-c {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
