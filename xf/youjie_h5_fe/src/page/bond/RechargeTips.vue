<template>
  <div class="container">
    <mt-header class="header" title="卡密说明">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="content1">
        <img src="../../assets/image/hh-icon/h0-bond/kami-1.png" alt="" />
      </div>
      <div class="content2">
        <img src="../../assets/image/hh-icon/h0-bond/kami-2.png" alt="" />
      </div>
      <div class="content3">
        <img src="../../assets/image/hh-icon/h0-bond/kami-3.png" alt="" />
      </div>
      <div class="content4">
        <img src="../../assets/image/hh-icon/h0-bond/kami-4.png" alt="" />
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
export default {
  name: 'BondIntroduce',
  methods: {
    goBack() {
      this.$_goBack()
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
.content {
  flex: 1;
  overflow: auto;
  background-color: #fff;
}
.content1,
.content2,
.content3,
.content4 {
  width: 100%;
  font-size: 0;
}
img {
  width: 100%;
}
</style>
