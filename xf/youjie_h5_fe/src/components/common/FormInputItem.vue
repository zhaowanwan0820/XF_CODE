<template>
  <div class="content-wrapper" :class="{ showBottomBorder: isShowLine }">
    <label class="title">{{ title }}</label>
    <input
      class="value"
      :maxlength="maxlength"
      v-bind:placeholder="placeholder"
      v-bind:value="value"
      v-on:input="value = $event.target.value"
    />
    <label v-if="isRequired" class="required">*</label>
  </div>
</template>

<script>
export default {
  name: 'FormInputItem',
  props: {
    isRequired: {
      type: Boolean,
      default: false
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
    maxlength: {
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
    },
    onchange(value) {
      this.value = value
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
  @include formInput;
  flex: 1;
  margin-left: 10px;
  margin-right: 15px;
  // font-size: $form-item-value-font-size;
  font-size: 14px;
  color: $baseColor;
  &::-webkit-input-placeholder {
    color: #b5b6b6;
  }
}
.required {
  color: #ff3c3c;
  font-size: $info-item-title-font-size;
  text-align: center;
  width: 24px;
  height: 16px;
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
