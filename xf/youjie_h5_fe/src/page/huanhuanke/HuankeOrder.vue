<!-- OrderHeader.vue -->
<template>
  <div class="container">
    <order-header></order-header>
    <order-nav></order-nav>
  </div>
</template>

<script>
import OrderHeader from './child/OrderHeader'
import OrderNav from './child/OrderNav'
export default {
  name: 'HuankeOrder',
  components: {
    OrderHeader,
    OrderNav
  },
  beforeRouteLeave(to, from, next) {
    if (to.name == 'profile') {
      next()
    }

    if (to.name == 'payResult') {
      next({ name: 'profile' })
    } else {
      next()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
}
</style>
