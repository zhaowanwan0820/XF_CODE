<template> </template>
<script>
export default {
  data() {
    return {
      flag: false,
      time: {
        d: '', // 天
        h: '', // 时
        m: '', // 分
        s: '' // 秒
      },
      timer: null
    }
  },
  props: {
    endTime: {
      type: Number // 时间戳 ms
    }
  },
  mounted() {
    this.timer = setInterval(() => {
      if (this.flag) {
        clearInterval(this.timer)
        return
      }
      this.timeDown()
    }, 500)
  },
  beforeDestroy() {
    clearInterval(this.timer)
  },
  methods: {
    timeDown() {
      const endTime = new Date(this.endTime)
      const nowTime = new Date()
      let leftTime = parseInt((endTime.getTime() - nowTime.getTime()) / 1000)
      if (leftTime > 0) {
        this.time = {
          d: parseInt(leftTime / (24 * 60 * 60)),
          h: this.formate(parseInt((leftTime / (60 * 60)) % 24)),
          m: this.formate(parseInt((leftTime / 60) % 60)),
          s: this.formate(parseInt(leftTime % 60))
        }
      } else {
        this.time = {
          d: 0,
          h: '00',
          m: '00',
          s: '00'
        }
      }

      this.$emit('time-change', this.time)

      if (leftTime <= 0) {
        this.flag = true
        this.$emit('time-end')
      }
    },
    formate(time) {
      if (time >= 10) {
        return time
      } else {
        return `0${time}`
      }
    }
  }
}
</script>
