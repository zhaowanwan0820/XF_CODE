<template>
  <div v-if="isOutofStock" class="to-app" @click="toApp">
    <img src="../../../assets/image/hh-icon/mlm/hh-icon@2x.png" />
    <span class="txt">进入{{ utils.storeName }}App</span>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  data() {
    return {}
  },
  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    }),
    isOutofStock() {
      return this.detailInfo.good_stock <= 0
    }
  },
  methods: {
    toApp() {
      window.location.href = this.hhAppUrl
    }
  }
}
</script>

<style lang="scss" scoped>
p {
  margin: 0;
  padding: 0;
}
.to-app {
  position: fixed;
  z-index: 2;
  box-sizing: border-box;
  top: 65px;
  right: 0;

  width: 166px;
  height: 44px;
  background: linear-gradient(90deg, rgba(245, 81, 95, 1) 0%, rgba(159, 4, 27, 1) 100%);
  box-shadow: 0px 2px 4px 0px rgba(208, 180, 130, 1);
  border-radius: 4px 0px 0px 4px;

  padding-left: 20px;
  display: flex;
  justify-content: flex-start;
  align-items: center;

  img {
    width: 31px;
  }
  span {
    display: inline-block;
    padding-left: 6px;

    font-size: 16px;
    font-weight: bold;
    color: rgba(255, 255, 255, 1);
  }
}
</style>
