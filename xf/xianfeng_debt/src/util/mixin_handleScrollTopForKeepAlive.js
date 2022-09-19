import Vue from 'vue'

const selector = '.scroll-container-keepAlive'
// beforeRouteLeave 记录 scrollup，待keepAlive唤醒时 scroll至指定位置
Vue.mixin({
  data() {
    return {
      $_scrollTop: []
    }
  },
  beforeRouteLeave(to, from, next) {
    // 该mixin钩子 只在顶层component 执行一次
    this.$data.$_scrollTop = []

    const container = document.querySelectorAll(selector)
    Array.prototype.forEach.call(container, (e, index) => {
      this.$data.$_scrollTop.push(e.scrollTop || 0)
    })
    next()
  },
  activated() {
    // 顶层component 唤醒时执行一次
    if (this.$parent && this.$parent.$el.id == 'app' && this.$data.$_scrollTop.length > 0) {
      const container = document.querySelectorAll(selector)
      Array.prototype.forEach.call(container, (e, index) => {
        e.scrollTop = this.$data.$_scrollTop[index] || 0
      })
    }
  }
})
