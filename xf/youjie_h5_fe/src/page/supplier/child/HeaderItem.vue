<template>
  <div v-if="isBack" @click="onclick" class="left-item">
    <img class="icon icon-back" src="../../../assets/image/hh-icon/icon-header-返回.svg" />
  </div>
  <div v-else-if="isClose" @click="onclick" class="left-item">
    <img class="icon icon-close" src="../../../assets/image/hh-icon/icon-关闭.png" />
  </div>
  <div v-else>
    <div v-if="icon" @click="onclick">
      <img class="icon" :src="icon" :class="{ minIcon: minIcon }" />
    </div>
    <div v-else-if="title" @click="onclick">
      <label class="title" :style="{ color: titleColor }">{{ title }}</label>
    </div>
  </div>
</template>

<script>
export default {
  name: 'header-item',
  props: {
    icon: {
      type: String
    },
    title: {
      type: String
    },
    isBack: {
      type: Boolean,
      default: false
    },
    isClose: {
      type: Boolean,
      default: false
    },
    isLeft: {
      type: Boolean,
      default: false
    },
    titleColor: {
      type: String,
      default: '#5CAAF8'
    },
    minIcon: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    onclick() {
      this.$emit('onclick')
    }
  }
}
</script>

<style lang="scss" scoped>
.title {
  font-size: 14px;
  color: #5caaf8;
}
.left-item {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  .icon-back {
    margin-left: 5px;
    @include wh(8px, 16px);
  }
  .icon-close {
    @include wh(15px, 15px);
    margin-left: 5px;
  }
}
.right-item {
  width: 31px;
  height: 31px;
  display: flex;
  flex-direction: row;
  align-items: center;
}
.icon {
  width: 31px;
  height: 31px;
}
.minIcon {
  width: 31px;
  height: 31px;
}
img {
  padding: 0 5px;
}
</style>
