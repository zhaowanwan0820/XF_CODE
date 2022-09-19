<!-- ProductVedio.vue -->
<template>
  <div class="container">
    <div class="ui-vedio-header">
      <img src="../../assets/image/change-icon/back@2x.png" v-on:click="goBack" />
    </div>
    <div class="content">
      <video :src="src" width="100%" height="100%" preload="auto" controls autoplay>
        您的浏览器不支持video标签，建议您更换一个浏览器试试哦~
      </video>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      src: this.$route.query.src
    }
  },

  mounted() {
    if (!this.src) {
      alert('视频地址不能为空！')
    }
    const video = document.querySelector('video')
    const promise = video.play()

    if (promise !== undefined) {
      promise
        .catch(error => {
          // here is important!!!
          // Auto-play was prevented
          // Show a UI element to let the user manually start playback
        })
        .then(() => {
          // Auto-play started
        })
    }
  },

  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  position: relative;
  height: 100%;
  background-color: #000;

  .content {
    position: absolute;
    top: 50%;
    left: 0;
    width: 100%;
    transform: translateY(-50%);
  }
}
.ui-vedio-header {
  padding: 0 9px;
  height: 50px;
  background: rgba(255, 255, 255, 1);
  // border-bottom: 0.5px solid rgba(232,234,237,1);
  color: #55595f;
  font-size: 15px;
  width: auto;
  display: flex;
  justify-content: center;
  align-content: center;
  align-items: center;
  flex-basis: auto;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  img {
    width: 24px;
    height: 24px;
    cursor: pointer;
    position: absolute;
    left: 9px;
    top: 10px;
  }
}
</style>
