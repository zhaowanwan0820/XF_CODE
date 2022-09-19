<template>
  <button class="button-item" :class="typeStyle" @click="click" :disabled="disabled">
    <label><slot></slot></label>
  </button>
</template>

<script>
export default {
  name: 'gk-button',
  props: {
    type: {
      type: String,
      default: 'default' // default/primary/secondary/positive/negative
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    typeStyle: function() {
      return {
        default: this.isItemType('default'),
        primary: this.isItemType('primary'),
        primary2: this.isItemType('primary-secondary'),
        primarywhite: this.isItemType('primary-secondary-white'),
        secondary: this.isItemType('secondary'),
        positive: this.isItemType('positive'),
        disable: this.isItemType('disable'),
        disable2: this.isItemType('disable-secondary'),
        negative: this.isItemType('negative')
      }
    }
  },
  methods: {
    isItemType(type) {
      if (this.type === type) {
        return true
      }
      return false
    },
    click() {
      this.$emit('click')
    }
  }
}
</script>

<style lang="scss" scoped>
@import '../style/var.scss';
.button-item {
  text-align: center;
  font-size: $button-font-size;
  border: none;
  outline: 0;
  &:focus {
    outline-style: none;
  }
}
.default {
  color: $button-default-color;
  background-color: $button-default-background-color;
}
.primary {
  color: $button-primary-color;
  background-color: $button-primary-background-color;
  &:focus {
    color: $button-primary-focus-color;
    background-color: $button-primary-background-focus-color;
  }
}
.primary2 {
  color: $button-primary-secondary-color;
  background-color: $button-primary-secondary-background-color;
  &:focus {
    color: $button-primary-secondary-focus-color;
    background-color: $button-primary-secondary-background-focus-color;
  }
  &.disable {
    color: $button-primary-secondary-disable-color !important;
    background-color: $button-primary-secondary-background-disable-color !important;
  }
}
.primarywhite {
  color: $button-primary-secondary-white-color;
  background-color: $button-primary-secondary-white-background-color;
  border: 1px solid $button-primary-secondary-white-border-color;
  &:focus {
    color: $button-primary-secondary-white-focus-color;
    background-color: $button-primary-secondary-white-background-focus-color;
    border: 1px solid $button-primary-secondary-white-border-focus-color;
  }
}
.secondary {
  color: $button-secondary-color;
  background-color: $button-secondary-background-color;
  &:focus {
    color: $button-secondary-focus-color;
    background-color: $button-secondary-background-focus-color;
  }
}
.positive {
  color: $button-positive-color;
  background-color: $button-positive-background-color;
  &:focus {
    color: $button-positive-focus-color;
    background-color: $button-positive-background-focus-color;
  }
}
.disable {
  color: $button-disable-color;
  background-color: $button-disable-background-color;
}
.disable2 {
  color: $button-disable-secondary-color;
  background-color: $button-disable-secondary-background-color;
}
.negative {
  color: $button-negative-color;
  background-color: $button-negative-background-color;
}
</style>
