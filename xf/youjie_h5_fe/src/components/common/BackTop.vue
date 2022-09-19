<!-- BackTop.vue 回到顶部 -->
<template>
  <div class="ui-back-top">
    <div class="back-top" v-if="isShow" v-bind:style="customStyle">
      <img src="../../assets/image/hh-icon/b0-home/icon-返回顶部.svg" @click="goBackTop" />
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isShow: false,
      interval: undefined
    }
  },

  props: {
    target: {},
    bottom: {
      default: 0
    }
  },

  created() {
    // this.srollertop = document.documentElement.scrollTop || document.body.scrollTop;
    var that = this
    this.target.addEventListener('scroll', event => {
      if (this.target.scrollTop && this.target.scrollTop >= 500) {
        that.isShow = true
      } else {
        that.isShow = false
      }
    })
  },

  computed: {
    isHasBottomBar() {
      return this.$route.meta.isshowtabbar
    },
    customStyle() {
      if (this.bottom > 0 || this.isHasBottomBar) {
        return {
          bottom: `${this.bottom || 60}px`
        }
      } else {
        return {}
      }
    }
  },

  mounted() {},

  methods: {
    /*
     *  goBackTop： 回到顶部
     */
    goBackTop() {
      this.goTop().then(res => {
        this.isTop = true
      })
    },

    goTop() {
      return new Promise(resolve => {
        let interval = setInterval(() => {
          if (this.target.scrollTop == 0) {
            clearInterval(interval)
            resolve()
            return
          }
          this.target.scrollTop -= 55
        })
      })
    }
  }
}
</script>

<style lang="scss"></style>
