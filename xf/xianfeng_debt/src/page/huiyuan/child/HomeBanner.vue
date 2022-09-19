<template>
  <div class="banner-wrapper">
    <van-swipe :autoplay="0" indicator-color="white">
      <van-swipe-item v-for="(item, index) in banners" :key="index" @click="bannerClick(item)">
        <div class="img-wrapper">
          <img class="img" alt="" :src="item.thumb" />
        </div>
      </van-swipe-item>
    </van-swipe>
  </div>
</template>

<script>
import { mapState } from 'vuex'
export default {
  name: 'HomeBanner',
  data() {
    return {}
  },
  computed: {
    ...mapState({
      banners: state => state.home.huiYuanBanners
    }),
    isShowIndicators() {
      if (this.banners && this.banners.length > 1) {
        return true
      }
      return false
    }
  },
  methods: {
    bannerClick(item) {
      let link = item.link
      if (link && link.length) {
        // 可判断站内链接 使用router处理跳转
        // 外链
        this.Jump(link)
      }else{
        this.$router.push({name:item.routeName})
      }
    }
  }
}
</script>

<style lang="less" scoped>
.banner-wrapper {
  position: relative;
  background-color: #fff;
  height: 140px;
}
.img-wrapper {
  width: 100%;
  height: 140px;

  .img {
    display: block;
    width: auto;
    height: 100%;
  }
}
</style>
