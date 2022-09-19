<template>
  <section class="layout">
    <section class="container">
      <main class="content">
        <transition name="content">
          <router-view class="inner" />
        </transition>
      </main>
    </section>
  </section>
</template>

<script>
export default {
  name: 'Layout',
}
</script>

<style lang="scss">
@import 'assets/scss/theme';

.container {
  height: 100%;
  display: flex;
  flex: 1;
  overflow: hidden;
}

.content {
  height: 100%;
  background: #fff;
  position: relative;
  flex: 1;
  overflow-x: hidden;
}

.content-enter-active,
.content-leave-active {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  transition: opacity 0.5s ease;
}

.content-enter,
.content-leave-active {
  opacity: 0;
}
</style>
