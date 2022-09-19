<template>
  <div class="content-wrapper" @click="onclick">
    <label class="title">{{ title }}</label>
    <label v-if="isRequired" class="required">*</label>
    <label class="value">{{ getValue }}</label>
    <!-- <img v-if="isShowIndicator" class="indicator" src="../../assets/image/icon_next@2x.png"/>    -->
    <div class="bottom-line" v-if="isShowLine"></div>
  </div>
</template>

<script>
export default {
  name: 'InfoTextItem',
  props: {
    isRequired: {
      type: Boolean,
      default: true
    },
    title: {
      type: String
    },
    default: {
      type: String
    },
    placeholder: {
      type: String
    },
    isShowIndicator: {
      type: Boolean,
      default: true
    },
    isShowLine: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      value: this.default
    }
  },
  computed: {
    getValue() {
      if (this.value && this.value.length) {
        return this.value
      }
      return this.placeholder ? this.placeholder : ''
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
  position: relative;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  background-color: #fff;
}
.title {
  font-size: $info-item-title-font-size;
  color: $info-item-title-color;
  margin-left: 12px;
}
.required {
  color: #ff3c3c;
  font-size: $info-item-title-font-size;
  text-align: center;
  width: 16px;
  height: 16px;
}
.value {
  flex: 1;
  font-size: $info-item-subtitle-font-size;
  color: $info-item-subtitle-color;
  margin-left: 10px;
  margin-right: 0;
  text-align: right;
}
.indicator {
  width: 15px;
  height: 15px;
  margin-left: 4px;
  margin-right: 10px;
}
.bottom-line {
  position: absolute;
  height: 1px;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: $line-bg-color;
}
</style>
