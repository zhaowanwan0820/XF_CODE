import Vue from 'vue'

// 被分享出去的页面 形成的独立入口，点击返回时 因 没有方向，故处理去首页
Vue.mixin({
  methods: {
    // 关于命名规则见：https://cn.vuejs.org/v2/style-guide/#%E7%A7%81%E6%9C%89%E5%B1%9E%E6%80%A7%E5%90%8D-%E5%BF%85%E8%A6%81
    $_goBack: function() {
      this.$router.go(-1)
      // 若 600ms后还在当前页面 则认为go(-1)页面不存在
      this.$_pageBefore = this.$router.history.current.fullPath
      setTimeout(() => {
        console.log('_pageBefore: ' + this.$_pageBefore, ' & _pageAfter: ' + this.$router.history.current.fullPath)
        if (this.$_pageBefore === this.$router.history.current.fullPath) {
          this.$router.push({ path: '/' })
        }
      }, 600)
    }
  }
})
