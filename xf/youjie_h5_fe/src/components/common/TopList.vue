<template>
  <div class="list">
    <div class="item" v-for="(item, index) in items" :key="index" v-on:click="onClickItem(index)">
      <label class="title-wrapper">
        <span class="title" v-bind:class="{ active: index === currentIndex, normal: index !== currentIndex }">{{
          getTitle(item)
        }}</span>
        <div class="line" v-if="isShowLine(index)"></div>
      </label>
    </div>
  </div>
</template>

<script>
import TopItem from './TopItem'
export default {
  name: 'TopList',
  components: {
    TopItem
  },
  props: {
    items: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      currentIndex: 0,
      currentItem: this.items && this.items.length ? this.items[0] : null
    }
  },
  methods: {
    getTitle(item) {
      return item ? item.title : ''
    },
    isShowLine(index) {
      return index === this.currentIndex ? true : false
    },
    onClickItem(index) {
      if (this.currentIndex !== index) {
        this.currentIndex = index
        this.$emit('onIndexChange', index)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.list {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-content: center;
  align-items: stretch;
  background-color: #fff;
  z-index: 99;
}
.item {
  flex: 1;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
.title {
  font-size: 15px;
  color: $subbaseColor;
  text-align: center;
}
.active {
  color: $baseColor;
}
.normal {
  color: $subbaseColor;
}
.line {
  width: 67%;
  height: 2.001px;
  margin: 3px auto 0;
  background-color: $primaryColor;
}
</style>
