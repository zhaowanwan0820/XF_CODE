<template>
  <div class="return-cash">
    <img v-lazy="lazy" alt="" />
    新人下单最高可返{{ returnCash }}元红包
  </div>
</template>

<script>
export default {
  data() {
    return {
      lazy: {
        src: require('../../../assets/image/hh-icon/detail/return-cash-icon.png'),
        error: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png'),
        loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
      }
    }
  },

  props: ['returnCash']
}
</script>

<style lang="scss" scoped>
.return-cash {
  padding: 0 10px;
  background-color: rgba(251, 235, 212, 1);
  display: flex;
  height: 40px;
  align-items: center;
  justify-content: flex-start;
  color: rgba(155, 33, 11, 1);
  font-size: 14px;
  line-height: 1;
  img {
    width: 40px;
  }
}
</style>
