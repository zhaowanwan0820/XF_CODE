<template>
  <div class="ad-wrapper" @click="goAd">
    <img v-lazy="lazy" alt="" />
  </div>
</template>

<script>
export default {
  name: 'AdBanner',
  data() {
    return {
      lazy: {
        src: this.img,
        error: require('../../assets/image/hh-icon/y0-lazy-load/lazy-ad.png'),
        loading: require('../../assets/image/hh-icon/y0-lazy-load/lazy-ad.png')
      }
    }
  },
  props: {
    img: {
      type: String,
      default: ''
    },
    url: {
      type: String,
      default: ''
    },
    route: {
      type: Object,
      default: () => {
        return {}
      }
    }
  },
  methods: {
    goAd() {
      if (this.url) {
        this.Jump(this.url)
      } else if (Object.keys(this.route).length > 0) {
        this.$router.push(this.route)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.ad-wrapper {
  padding: 10px;
  background: #ffffff;
  font-size: 0;
  img {
    width: 100%;
    &[lazy='loading'] {
      opacity: 1;
    }
  }
}
</style>
