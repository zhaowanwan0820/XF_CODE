<template>
  <mt-popup v-model="popupVisible" class="new-guide" position="center">
    <div class="new-guide-container">
      <img v-if="step == 1" @click="showNextStep" src="../../../assets/image/hh-icon/myStore/guide-1.png" alt="" />
      <img v-if="step == 2" @click="showNextStep" src="../../../assets/image/hh-icon/myStore/guide-2.png" alt="" />
      <img v-if="step == 3" @click="showNextStep" src="../../../assets/image/hh-icon/myStore/guide-3.png" alt="" />
      <img v-if="step == 4" @click="showNextStep" src="../../../assets/image/hh-icon/myStore/guide-4.png" alt="" />
      <img v-if="step == 5" @click="showNextStep" src="../../../assets/image/hh-icon/myStore/guide-5.png" alt="" />
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
export default {
  name: 'NewOwnerGuide',
  data() {
    return {
      popupVisible: true,
      step: 1
    }
  },
  created() {},
  mounted() {},
  computed: {},
  methods: {
    showNextStep() {
      this.step += 1
      if (this.step == 6) {
        // 保存已读规则信息
        this.popupVisible = false
        this.$parent.getProductsList()
        return
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.new-guide {
  width: 100%;
  height: 100%;
  background-color: black;
  display: flex;
  align-items: center;
  justify-content: center;
  .new-guide-container {
    width: 100%;
    img {
      width: 100%;
    }
  }
}
</style>
