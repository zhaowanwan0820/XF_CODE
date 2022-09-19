<template>
  <div class="container">
    <li
      class="item"
      @click="onClickItem"
      v-bind:class="{
        active: item && currentItem && item.id === currentItem.id,
        normal: item && currentItem && item.id !== currentItem.id
      }"
    >
      <label class="title">{{ getTitle }}</label>
    </li>
  </div>
</template>

<script>
export default {
  name: 'TopItem',
  props: {
    item: null,
    currentItem: null
  },
  computed: {
    getTitle() {
      return this.item ? this.item.title : ''
    }
  },
  methods: {
    onClickItem() {
      this.$emit('clickItem', this.item)
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
}
.item {
  font-size: 14px;
  color: #4e545d;
  border-bottom: 2px solid transparent;
  position: relative;
  flex-basis: 100px;
  text-align: center;
  height: 42px;
  padding: 0;
  line-height: 42px;
  background-color: lightpink;
}
.title {
  font-size: 14px;
  color: #404245;
}
.active {
  border-bottom-color: $primaryColor;
}
.normal {
  border-bottom-color: transparent;
}
</style>
