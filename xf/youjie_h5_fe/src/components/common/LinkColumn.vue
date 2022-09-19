<template>
  <div class="link-column-wrapper" @click="onclick">
    <slot name="title">
      <label class="column-title link-column-title">{{ title }}</label>
    </slot>
    <div class="link-right">
      <slot name="rightTxt">
        <span class="txt-right">{{ rightTxt }}</span>
      </slot>
      <img class="indicator" src="../../assets/image/hh-icon/f0-profile/icon-tip.png" />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    title: {
      type: String,
      default: ''
    },
    rightTxt: {
      type: String,
      default: ''
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
.link-column-wrapper {
  height: 54px;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  background-color: #fff;
  flex-shrink: 0;
  padding: 0 15px;
  @include thin-border(#eaebec, 15px);
}

.column-title {
  font-size: 15px;
  color: $baseColor;
}
.link-column-title {
  flex: 1;
}
.link-right {
  display: flex;
  height: 100%;
  align-items: center;

  .txt-right {
    font-size: 13px;
    color: rgba(252, 127, 12, 1);
    margin-right: 8px;
  }
}
.indicator {
  width: 6px;
}
.section-header {
  margin-top: 10px;
}
.section-footer {
  &:after {
    height: 0;
  }
}
</style>
