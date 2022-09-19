<!-- OrderHeader.vue -->
<template>
  <div class="h-container">
    <mt-header class="header" title="分销订单列表">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
  </div>
</template>

<script>
import { HeaderItem } from '../../../components/common'
import { Header } from 'mint-ui'

export default {
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.h-container {
  height: 44px;
  width: 100%;
}
.header {
  @include header;
  @include thin-border(#f4f4f4, 15px);
  width: 100%;
}
</style>
<style>
.icon {
  vertical-align: middle;
}
</style>
