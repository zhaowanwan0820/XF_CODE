<template>
  <div class="cart-header-wrapper ui-commmon-header">
    <img src="../../../assets/image/change-icon/back@2x.png" v-if="showBack" @click="goBack" class="ui-go-back" />
    <h3>购物车</h3>
    <span @click="changeMode" v-if="showEdit">编辑</span>
    <span @click="changeMode" v-else-if="2 == mode">取消</span>
    <span v-else></span>
  </div>
</template>

<script>
import { mapState, mapMutations, mapGetters } from 'vuex'
export default {
  data() {
    return {
      hideLeave: false // 是否隐藏返回按钮
    }
  },
  computed: {
    ...mapState({
      mode: state => state.cart.mode
    }),
    ...mapGetters({
      isEmpty: 'cart_isEmpty',
      cart_all_amount: 'cart_all_amount'
    }),
    showEdit() {
      return this.mode == 1 && !this.isEmpty
    },
    showBack() {
      return !(this.hideLeave || this.mode == 2)
    }
  },
  watch: {
    cart_all_amount(value) {
      // 全部删除时
      this.mode == 2 && value === 0 && this.changeMode()
    }
  },
  created() {
    this.hideLeave = this.$route.query.hideLeave
  },
  methods: {
    ...mapMutations({
      changeMode: 'changeMode'
    }),
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.cart-header-wrapper {
  width: -webkit-fill-available;
  height: 44px;
  span {
    position: absolute;
    font-size: 15px;
    color: #b75800;
    display: inline-block;
    height: 44px;
    line-height: 44px;
    top: 0;
    right: 15px;
  }
}
</style>
