<template>
  <div class="content-wrapper" :class="{ showBottomBorder: isShowLine }" @click="onclick">
    <label class="title">{{ title }}</label>
    <label class="value" v-if="value">{{ value }}</label>
    <label class="placeholder" v-else>{{ placeholder }}</label>
    <img class="indicator" src="../../assets/image/hh-icon/f0-profile/icon-tip.png" />
  </div>
</template>

<script>
export default {
  name: 'FormTextItem',
  props: {
    title: {
      type: String
    },
    default: {
      type: String
    },
    placeholder: {
      type: String
    },
    isShowLine: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      value: this.default
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
.content-wrapper {
  display: flex;
  position: relative;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  background-color: #fff;
  padding-left: 20px;
  &.showBottomBorder {
    @include thin-border(#f4f4f4, 20px, 0);
  }
}
.title {
  width: 90px;
  font-size: 14px;
  font-family: PingFangSC-Regular;
  font-weight: 400;
  color: rgba(65, 65, 65, 1);
  line-height: 20px;
}
.value {
  flex: 1;
  font-size: $form-item-value-font-size;
  margin-left: 10px;
  margin-right: 15px;
}
.placeholder {
  flex: 1;
  font-size: $form-item-value-font-size;
  color: $form-item-value-color;
  margin-left: 10px;
  margin-right: 15px;
}
.indicator {
  width: 6px;
  margin-left: 10px;
  margin-right: 10px;
}
.bottom-line {
  position: absolute;
  height: 1px;
  left: 20px;
  right: 0;
  bottom: 0;
  background-color: $line-bg-color;
}
</style>
