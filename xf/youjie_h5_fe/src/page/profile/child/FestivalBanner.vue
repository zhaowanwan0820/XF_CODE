<template>
  <div class="f-container">
    <rolling-line></rolling-line>
  </div>
</template>

<script>
import RollingLine from '../../../components/common/RollingLine'
export default {
  components: {
    RollingLine
  }
}
</script>

<style lang="scss" scoped>
.f-container {
  padding: 0 15px;
  background-color: #fff;
}
</style>
