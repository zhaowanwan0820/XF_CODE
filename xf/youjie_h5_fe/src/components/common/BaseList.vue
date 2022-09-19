<template>
  <div v-infinite-scroll="onLoadMore" infinite-scroll-distance="10">
    <slot></slot>
    <div v-if="isShowMore" class="loading-wrapper">
      <p v-if="!isMore">没有更多了</p>
      <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BaseList',
  props: {
    items: {
      type: Array,
      default: () => []
    },
    isMore: {
      type: Number,
      default: 0
    },
    isLoaded: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    isEmpty() {
      if (this.items.length === 0) {
        return true
      }
      return false
    },
    isShowMore() {
      if (this.isLoaded && this.isEmpty) {
        return false
      }
      return true
    }
  },
  methods: {
    onLoadMore() {
      this.$emit('loadMore')
    }
  }
}
</script>

<style lang="scss" scoped>
.loading-wrapper {
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 44px;
  p {
    color: #7c7f88;
    font-size: 12px;
    font-weight: 'Regular';
    padding: 0;
    margin: 0;
  }
  span {
    display: inline-block;
  }
}
</style>
