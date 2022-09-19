<template>
  <div class="ui-cart-wrapper">
    <v-cart-header ref="header"></v-cart-header>
    <div class="cart-body scroll-container-keepAlive">
      <v-cart-list ref="list" :isCheckedAll="isFinish"></v-cart-list>
      <recommend-list v-if="!isLoading" :params="recommendParams" :scrollDom="'cart-body'"></recommend-list>
    </div>
    <template v-if="!isEmpty && !isLoading">
      <v-cart-footer></v-cart-footer>
    </template>
  </div>
</template>

<script>
import cartHeader from './child/CartHeader'
import cartList from './child/CartList'
import cartFooter from './child/CartFooter'
import RecommendList from '../recommend/RecommendList'
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { Indicator } from 'mint-ui'

export default {
  name: 'cart',
  beforeRouteEnter(to, from, next) {
    next(vm => {
      if (!vm.isOnline) {
        vm.$router.replace({ name: 'login', params: {} })
      }
    })
  },
  data() {
    return {
      isFinish: false, //编辑 false - 编辑~完成  true - 完成~编辑 false
      isStatus: true, //是否默认选中底部的全选按钮
      isshowpromos: true, //是否显示促销信息
      recommendParams: {} // 猜你喜欢额外请求数据
    }
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      isLoading: state => state.cart.isLoading,
      counterForTabRefresh: state => state.app.counterForTabRefresh
    }),
    ...mapGetters({
      isEmpty: 'cart_isEmpty',
      cart_all_amount: 'cart_all_amount'
    })
  },

  watch: {
    counterForTabRefresh(value) {
      // 更新数据
      Indicator.open()
      this.fetchCartList().then(() => {
        Indicator.close()
      })
    },
    cart_all_amount(value) {
      this.setCartNumber(value)
    }
  },

  components: {
    'v-cart-header': cartHeader,
    'v-cart-list': cartList,
    'v-cart-footer': cartFooter,
    RecommendList
  },
  methods: {
    ...mapMutations({
      setCartNumber: 'setCartNumber'
    }),
    ...mapActions({
      fetchCartList: 'fetchCartList'
    })
  }
}
</script>

<style lang="scss" scoped>
.ui-cart-wrapper {
  position: absolute;
  width: 100%;
  top: 0;
  left: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;

  .cart-body {
    flex: 1;
    overflow-x: hidden;
    overflow-y: auto;
  }
}
.empty-cart {
  padding-top: 124px;
  text-align: center;
  img {
    width: 75px;
    height: 75px;
  }
  p {
    font-size: 16px;
    color: rgba(124, 127, 136, 1);
    line-height: 22px;
    padding: 26px 0 40px 0;
  }
  div {
    font-size: 16px;
    color: rgba(255, 255, 255, 1);
    height: 44px;
    background: $primaryColor;
    border-radius: 3px;
    line-height: 44px;
    display: inline-block;
    width: 200px;
  }
}
</style>
