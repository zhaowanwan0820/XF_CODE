<template>
  <div class="q-a-item">
    <div class="title" @click="toggle">
      <div class="t-name">{{ item.q }}</div>
      <img :class="{ active: isOpen }" src="../../../assets/image/hh-icon/s9-service/icon-arr.png">
    </div>
    <div class="content" v-if="isOpen">
      <p v-for="(c, i) in item.a" :key="i">{{ c }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: "QAItem",
  data() {
    return {
      isOpen: false
    }
  },

  props: ['item'],

  methods: {
    toggle() {
      this.isOpen = !this.isOpen
    }
  }
}
</script>

<style lang="scss" scoped>
.q-a-item {
  @include thin-border(rgba(85, 46, 32, 0.2), 0, 0, true);
  padding-bottom: 15px;
  .title {
    display: flex;
    justify-content: space-between;
    margin-top: 14px;
    align-items: center;
    .t-name {
      @include sc(14px, #404040);
    }
    img {
      width: 13px;
      height: 8px;
      &.active {
        transform: rotateX(-180deg);
      }
    }
  }
  .content {
    margin-top: 12px;
    position: relative;
    padding: 13px;
    background-color: #f6f6f6;
    @include sc(12px, #666666);
    line-height: 20px;
    &:before {
      content: "";
      display: block;
      position: absolute;
      border-top: 0;
      border-bottom: 6px solid #F6F6F6;
      border-left: 6px solid transparent;
      border-right: 6px solid transparent;
      top: -6px;
      left: 24px;
    }
  }
}
</style>