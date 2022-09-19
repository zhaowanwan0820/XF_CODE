<!-- ProductListHeader.vue -->
<template>
  <div class="ui-product-header">
    <form action="#" v-on:submit.prevent="search($event)">
      <div class="search">
        <img src="../../../assets/image/hh-icon/icon-返回.svg" class="ui-back" @click="goBack" />
        <input type="search" placeholder="请输入您要搜索的商品" v-model="keyword" autocomplete="off" />
      </div>
    </form>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { Toast } from 'mint-ui'

export default {
  props: ['value'],

  data() {
    return {
      keyword: this.value ? this.value : '' //关键字
    }
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline,
      cartNumber: state => state.tabBar.cartNumber
    }),
    getCarCount() {
      if (this.cartNumber > 0 && this.cartNumber < 100) {
        return this.cartNumber
      } else if (this.cartNumber >= 100) {
        return '99+'
      }
    }
  },

  watch: {
    value: function(value) {
      if (value) {
        this.keyword = value
      }
    }
  },

  methods: {
    /*
     * search: 搜索
     */
    search(e) {
      let data = {
        keyword: this.keyword
      }
      // debugger
      // if (!data.keyword) {
      //   Toast('请输入您要搜索的关键字')
      //   return
      // }
      this.$parent.$emit('change-list', data)
      if (e) {
        this.utils.stopPrevent(e)
      }
    },

    /*
     * goBack: 返回上一级
     */
    goBack() {
      let isFromHome = this.$route.params.isFromHome
      if (isFromHome) {
        this.$router.push({ name: 'home' })
      } else {
        this.$_goBack()
      }
    },

    /*
     *  goCart: 跳转到购物车列表
     */
    goCart() {
      if (this.isOnline) {
        this.$router.push({ name: 'cart', params: { type: 0 } })
      } else {
        this.$router.push({ name: 'signin' })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-product-header {
  background-color: #fff;
  div.search {
    display: flex;
    padding: 6px 8px 8px 12px;
    align-content: center;
    align-items: center;
    img.ui-back {
      width: 15px;
      height: 21px;
    }
    .ui-cart {
      width: 25px;
      height: 25px;
      position: relative;
      .number {
        background: #ef3338;
        border-radius: 50%;
        min-width: 13px;
        line-height: 13px;
        height: 13px;
        font-weight: normal;
        text-align: center;
        position: absolute;
        top: 0;
        right: -2px;
        .val {
          display: block;
          margin: 0 3.25px;
          @include sc(10px, #fff);
        }
      }
    }
    input {
      width: 280px;
      height: 28px;
      line-height: normal;
      margin-left: 16px;
      flex-basis: auto;
      border-radius: 2px;
      background: url('../../../assets/image/hh-icon/b0-home/icon-搜索.svg') no-repeat 10px center;
      background-size: 15px;
      background-color: #e9ecf0;
      padding-left: 30px;
      color: #a4aab3;
      font-size: 15px;
      border: 0;
      &:focus {
        outline-offset: 0;
        outline: none;
      }
      &::placeholder {
        font-size: 15px;
        color: #a4aab3;
      }
    }
  }
}
</style>
