<template>
  <div class="alphabet" ref="alphabet">
    <ul>
      <li
        class="item"
        v-for="(item, index) in list"
        :class="{ active: activeIndex == index }"
        :key="item"
        :ref="item"
        @click="handleLetterClick"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
      >
        {{ item }}
      </li>
    </ul>
  </div>
</template>

<script>
import eventBus from '@/model/eventBus'
export default {
  name: 'brandAlphabet',
  props: {
    list: Array
  },
  data() {
    return {
      startY: 0, // 第一个元素距离list顶部的距离
      listOffsetTop: 0, // list距离页面顶部的距离
      itemHeight: 0, // 每个字母的高度
      touchStatus: false,
      timer: undefined,
      activeIndex: 0
    }
  },

  mounted() {
    eventBus.$on('brandChangeScroll', index => {
      this.activeIndex = index
    })
    this.startY = this.$refs[this.list[0]][0].offsetTop
    this.listOffsetTop = this.$refs['alphabet'].offsetTop
    this.itemHeight = this.$refs[this.list[0]][0].offsetHeight
  },
  methods: {
    handleLetterClick(e) {
      eventBus.$emit('brandChangeIndex', e.target.innerText)
      this.activeIndex = this.list.indexOf(e.target.innerText)
    },
    handleTouchStart() {
      this.touchStatus = true
    },
    handleTouchMove(e) {
      if (this.touchStatus) {
        //函数节流
        if (this.timer) {
          clearTimeout(this.timer)
        }
        this.timer = setTimeout(() => {
          const touchY = e.touches[0].clientY - this.listOffsetTop // 移动时距离list顶部的距离
          const index = Math.floor((touchY - this.startY) / this.itemHeight) // 距离首个元素便宜的距离 / 每个元素的高度 = 滑到了第几个字母
          if (index >= 0 && index < this.list.length) {
            eventBus.$emit('brandChangeIndex', this.list[index])
            this.activeIndex = index
          }
        }, 16)
      }
    },
    handleTouchEnd() {
      this.touchStatus = false
    }
  }
}
</script>

<style lang="scss" scoped="scoped">
.alphabet {
  display: flex;
  justify-content: center;
  align-items: center;
  position: absolute;
  top: 50px;
  left: 340px;
  bottom: 0;
  font-family: PingFangSC;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
  color: rgba(64, 64, 64, 1);
  text-align: center;
  .item {
    width: 30px;
    text-align: center;
    &.active {
      color: rgba(201, 181, 148, 1);
    }
  }
}
</style>
