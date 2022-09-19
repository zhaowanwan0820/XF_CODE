<template>
  <div class="container">
    <mt-header class="header" title="分销返佣攻略">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goHome"></header-item>
    </mt-header>
    <div class="introduce-content">
      <div>
        <img class="avatar" src="../../assets/image/hh-icon/mlm/hhk-desc-1.png" />
        <img class="avatar" src="../../assets/image/hh-icon/mlm/hhk-desc-2.png" />
        <img class="avatar" src="../../assets/image/hh-icon/mlm/hhk-desc-3.png" />
        <img class="avatar" src="../../assets/image/hh-icon/mlm/hhk-desc-4.png" />
        <img class="avatar" src="../../assets/image/hh-icon/mlm/hhk-desc-5.png" />
        <div class="rules-bottom">
          <gk-button class="button" type="primary-secondary" @click="goHome">知道了</gk-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
export default {
  data() {
    return {}
  },
  methods: {
    goHome() {
      this.$router.push({ name: 'home' })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.header {
  @include header();
}
.introduce-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  font-size: 0;
  overflow-y: auto;
  & > div {
    flex-shrink: 0;
  }
  img {
    width: 100%;
  }
}
.rules-bottom {
  height: 44px;
  background-color: #ffffff;
  padding: 0 25px 50px;
  .button {
    display: block;
    width: 100%;
    @include button($margin: 0 0, $radius: 0, $spacing: 1px);
  }
}
</style>
