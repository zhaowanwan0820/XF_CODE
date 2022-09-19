<template>
  <div class="sec-container">
    <div class="sec-banner">
      <p>该商品正参加限时秒杀活动</p>
      <span @click="goProduct">立即前往</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    id: [Number, String]
  },
  methods: {
    goProduct() {
      this.$router.push({ name: 'SeckillProduct', query: { id: this.id } })
    }
  }
}
</script>

<style lang="scss" scoped>
.sec-container {
  position: fixed;
  bottom: 69px;
  animation: upanimation 1.5s ease;

  // left: 0;
  // right: 0;
  width: 100%;
  .sec-banner {
    width: 345px;
    height: 42px;
    border-radius: 21px;
    background: linear-gradient(90deg, rgba(140, 35, 35, 1) 0%, rgba(195, 71, 71, 1) 100%);
    margin: 0 auto;

    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  p {
    font-size: 15px;
    font-weight: 500;
    color: #fff;
    line-height: 21px;
    margin-left: 20px;
  }
  span {
    font-size: 14px;
    font-weight: 500;
    color: #fff;
    line-height: 20px;
    padding: 6px 15px;
    border-radius: 16px;
    background-color: #9a2e2e;
    margin-right: 10px;
  }
}
@keyframes upanimation {
  0% {
    bottom: 0;
  }
  100% {
    bottom: 69px;
  }
}
</style>
