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
  name: 'OrderList',
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
  },
  // keepAlive 被唤醒时
  activated() {
    const scrollTop = this.$route.meta.scrollTop
    if (scrollTop > 0) {
      document.querySelector('.order-body').scrollTop = scrollTop
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
