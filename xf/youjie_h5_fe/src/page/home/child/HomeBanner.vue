<template>
  <div class="banner-wrapper">
    <div class="banner-bg" :style="getBgStyle"></div>
    <mt-swipe :showIndicators="isShowIndicators" class="banner-swipe" :auto="2000">
      <mt-swipe-item v-for="(item, index) in items" :key="index" v-stat="{ id: `index_banner_${index}` }">
        <div class="img-wrapper">
          <img
            @click="bannerClick(item)"
            class="img"
            alt="轮播图"
            v-lazy="{
              src: item.thumb,
              error: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-banner.png'),
              loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-banner.png')
            }"
          />
        </div>
      </mt-swipe-item>
    </mt-swipe>
    <div class="line-square">
      <img src="../../../assets/image/hh-icon/new-home/line-arc.png" />
    </div>
  </div>
</template>

<script>
import { REG_URL_HHYW_INNER } from '../../../const/regExp'
import { activity_tabar } from '../../../config/activity'
import { mapMutations } from 'vuex'

export default {
  name: 'HomeBanner',
  data() {
    return {
      activity_tabar,
      startX: 0,
      startY: 0,
      isSet: false
    }
  },
  props: ['items'],
  mounted() {
    if (this.isHHApp) {
      // 首页滑动banner时，不自觉调用app下拉刷新fix
      const swipeDOM = document.querySelector('.banner-swipe')
      swipeDOM.addEventListener('touchstart', e => {
        this.touchStart(e)
      })
      swipeDOM.addEventListener('touchmove', e => {
        this.touchMove(e)
      })
      swipeDOM.addEventListener('touchend', e => {
        this.touchEnd(e)
      })
    }
  },
  created: function() {},
  computed: {
    isShowIndicators() {
      if (this.items && this.items.length > 1) {
        return true
      }
      return false
    },
    getBgStyle() {
      const style = {}
      const time = new Date().getTime()
      const startTime = this.activity_tabar.startTime
      const endTime = this.activity_tabar.endTime
      if (time >= startTime && time < endTime) {
        style.backgroundImage = `url(${this.activity_tabar.bannerBg})`
      }
      return style
    }
  },
  methods: {
    ...mapMutations({
      setIsTop: 'SET_IS_TOP'
    }),
    bannerClick(item) {
      let link = item.link
      if (link && link.length) {
        if (item.title == '海鲜' && this.isHHApp) {
          this.Jump('https://gio.ren/oB9zo1r')
          return
        }
        if (link.substr(0, 1) == '/') {
          // 识别 vue h5站内链接
          return this.$router.push({ path: link })
        } else if (REG_URL_HHYW_INNER.test(link)) {
          // 识别 vue h5站内链接
          return this.$router.push({ path: link.replace(REG_URL_HHYW_INNER, '/') })
        }

        // 外链
        this.Jump(link)
      }
    },
    touchStart(e) {
      this.isSet = false
      this.setIsTop(false)
      this.startX = e.targetTouches[0].pageX
      this.startY = e.targetTouches[0].pageY
    },
    touchMove(e) {
      if (this.isSet) {
        return
      } else {
        const x = e.targetTouches[0].pageX - this.startX
        const y = e.targetTouches[0].pageY - this.startY
        // 判断用户手势移动，x:y > 5:2 判定为横向滑动，不触发下拉刷新
        const swipeDirection = Math.abs(x / y) > 0.4 ? 'horizontal' : 'vertical'
        if (swipeDirection == 'vertical') {
          this.setIsTop(true)
        }
        this.isSet = true
      }
    },
    touchEnd(e) {
      this.$parent.handleScroll()
    }
  }
}
</script>

<style lang="scss" scoped>
.banner-wrapper {
  position: relative;
  // padding-top: 55px;
  height: 180px;
  // padding-bottom: 4px;
}
.banner-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 180px;
  border-bottom-left-radius: 0 100px;
  background: linear-gradient(180deg, rgba(252, 127, 12, 1) 0%, rgba(244, 244, 244, 1) 100%);
  background-repeat: no-repeat;
  background-size: 100%;
  background-position: center top;
}
.banner-swipe {
  width: 100%;
  height: 100%;
  overflow: hidden;
  .mint-swipe-item {
    text-align: center;
    font-size: 0;
    .img-wrapper {
      display: inline-block;
      border-radius: 3px;
      overflow: hidden;
      font-size: 0;
      width: 100%;
      height: 100%;
      box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.15);
    }
    .img {
      width: 100%;
      height: 100%;
      overflow: hidden;
      &[lazy='loading'] {
        opacity: 1;
      }
    }
  }
  /deep/ .mint-swipe-indicators {
    bottom: 26px;
    div.mint-swipe-indicator {
      background: #efeff4;
      border-radius: 0;
      margin: 0 2px;
      height: 5px;
      width: 5px;
      border-radius: 5px;
      background-color: rgba(255, 255, 255, 1);
      transition: height 0.1s ease-in-out;
      &.is-active {
        opacity: 1;
        width: 6px;
        height: 6px;
      }
    }
  }
}
.line-square {
  height: 24px;
  position: relative;
  top: -14px;
  img{
    display: block;
    width: 100%;
  }
}
</style>
