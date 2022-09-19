<template>
  <div v-if="popupVisible" class="popup-wrapper">
    <mt-popup v-model="popupVisible" position="center">
      <div class="btn-wrapper">
        <button @click="onclick"></button>
      </div>
    </mt-popup>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
export default {
  name: 'GoodsListAddDone',
  data() {
    return {}
  },
  props: {
    popupVisible: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    onclick() {
      this.$emit('onclick')
    }
  }
}
</script>

<style lang="scss" scoped>
.popup-wrapper {
  .mint-popup {
    background: transparent;
  }
  .btn-wrapper {
    width: 322px;
    height: 461px;
    background: url('../../../assets/image/hh-icon/mlm/bg-popup.png') center no-repeat;
    background-size: 322px 461px;
    border-radius: 2px;
    text-align: center;
    button {
      width: 199px;
      height: 44px;
      margin-top: 397px;
      opacity: 0;
    }
  }
}
</style>
