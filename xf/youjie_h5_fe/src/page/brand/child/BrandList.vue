<template>
  <div class="list-wrapper" ref="list">
    <div class="list">
      <div class="list-group" v-for="(item, key) in listKeys" :key="key" :ref="item">
        <div class="list-group-title">{{ item }}</div>
        <template v-for="brandItem in list[item]">
          <brand-list-item :item="brandItem"></brand-list-item>
        </template>
      </div>
    </div>
    <!-- 弹出层 -->
    <transition name="fade">
      <div class="toast" v-show="showToast">
        <span class="letter">{{ this.letter }}</span>
      </div>
    </transition>
  </div>
</template>

<script>
import BrandListItem from './BrandListItem'
import Bscroll from '@better-scroll/core'
import eventBus from '@/model/eventBus'
export default {
  name: 'brandList',
  props: {
    list: Object,
    listKeys: Array
  },

  data() {
    return {
      scroll: null,
      letter: '',
      showToast: false,
      groupList: []
    }
  },

  components: {
    BrandListItem
  },

  mounted() {
    this.getGroupHList()
    this.bindGoEvents()
    this.initScroll()
  },

  methods: {
    changeToast() {
      this.showToast = true
      if (this.timer) {
        clearTimeout(this.timer)
      }
      this.timer = setTimeout(() => {
        this.showToast = false
      }, 500)
    },

    /**
     * 初始化better scroll
     */
    initScroll() {
      this.scroll = new Bscroll(this.$refs.list, {
        click: true,
        probeType: 3
      })

      this.scroll.on('scroll', pos => {
        const y = -pos.y

        for (let i = 0; i < this.groupList.length - 1; i++) {
          let height1 = this.groupList[i]
          let height2 = this.groupList[i + 1]
          if (y >= height1 && y < height2) {
            eventBus.$emit('brandChangeScroll', i)
          }
        }
      })
    },

    /**
     * 绑定事件监听，通过事件总线程实现点击索引滚动到指定地方
     */
    bindGoEvents() {
      eventBus.$on('brandChangeIndex', letter => {
        this.letter = letter
      })
    },

    /**
     * Gets the group h list.
     * 获取每个字母下dom的高度，拼成列表，用于与索引列联动
     */
    getGroupHList() {
      const groupDomList = document.querySelectorAll('.list-group')
      const headerHeight = document.querySelector('.header').offsetHeight
      for (var i = 0; i < groupDomList.length; i++) {
        this.groupList.push(groupDomList[i].offsetTop - headerHeight)
      }
    }
  },

  watch: {
    letter(a, b) {
      if (this.letter) {
        const elment = this.$refs[this.letter][0]
        this.scroll.scrollToElement(elment)
        this.changeToast()
      }
    }
  }
}
</script>

<style lang="scss" scoped="scoped">
.list-wrapper {
  background-color: #ffffff;
  overflow: hidden;
  .list-group {
    padding-bottom: 15px;
  }
  .list-group-title {
    background-color: #f4f4f4;
    padding: 0 15px;
    font-size: 14px;
    color: #999999;
    line-height: 24px;
  }
  .toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
    width: 80px;
    height: 80px;
    background: rgba(0, 0, 0, 0.7);
    border-radius: 6px;
    text-align: center;
    transition: all 0.5s;
    &.fade-enter {
      opacity: 0;
    }
    &.fade-leave,
    &.fade-enter-active {
      opacity: 1;
    }
    &.fade-leave-active {
      opacity: 0;
    }
    .letter {
      font-size: 50px;
      font-family: PingFangSC;
      font-weight: 500;
      color: rgba(201, 181, 148, 1);
      line-height: 80px;
    }
  }
}
</style>
