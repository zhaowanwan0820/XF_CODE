<template>
  <div class="serve-icon-wrapper" :style="getStyle">
    <img @click="goServePage" src="../../assets/image/hh-icon/icon-serve.png">
  </div>
</template>
<script>
export default {
  name: "ServeIcon",
  data() {
    return {}
  },

  props: {
    top: {
      type: String,
      default: "auto"
    },
    left: {
      type: String,
      default: "auto"
    },
    right: {
      type: String,
      default: "24px"
    },
    bottom: {
      type: String,
      default: "138px"
    }
  },

  computed: {
    getStyle() {
      return {
        top: this.top,
        left: this.left,
        right: this.right,
        bottom: this.bottom
      }
    }
  },

  methods: {
    goServePage() {
      this.$router.push({ name: "service" })
    }
  }
}
</script>
<style lang="scss" scoped>
.serve-icon-wrapper {
  position: fixed;
  width: 63px;
  height: 63px;
  font-size: 0;
  top: auto;
  left: auto;
  right: 24px;
  bottom: 138px;
  img {
    width: 100%;
    height: 100%;
  }
}
</style>