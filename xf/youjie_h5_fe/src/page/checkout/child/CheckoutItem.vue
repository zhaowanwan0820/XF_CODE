<template>
  <div class="checkout-item-container" @click="onclick">
    <label class="title">{{ title }}</label>
    <label class="tips" v-if="isShowTips">{{ tips }}</label>
    <label class="subtitle">{{ subtitle }}</label>
    <img class="indicator" src="../../../assets/image/change-icon/enter@2x.png" />
  </div>
</template>

<script>
export default {
  props: {
    title: {
      type: String
    },
    subtitle: {
      type: String
    },
    tips: {
      type: String
    }
  },
  computed: {
    isShowTips: function() {
      if (this.tips && this.tips.length) {
        return true
      }
      return false
    }
  },
  methods: {
    onclick() {
      this.$emit('click')
    }
  }
}
</script>

<style lang="scss" scoped>
.checkout-item-container {
  margin-top: 10px;
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  background-color: #fff;
  padding: 13px 15px 12px;
  @include thin-border(#f4f4f4, 15px);
}
.title,
.subtitle {
  font-size: 13px;
  font-weight: 300;
  color: #404040;
  line-height: 18px;
}
.tips {
  width: auto;
  font-size: 10px;
  color: $primaryColor;
  margin-left: 10px;
  border: 1px solid $primaryColor;
  border-radius: 2px;
  padding-left: 5px;
  padding-right: 5px;
}
.subtitle {
  flex: 1;
  margin-left: 20px;
  text-align: right;
}
.indicator {
  width: 7px;
  height: 11px;
  margin-left: 5px;
}
</style>
