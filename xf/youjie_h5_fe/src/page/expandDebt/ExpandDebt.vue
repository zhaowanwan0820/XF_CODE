<template>
  <div class="container">
    <mt-header class="header" title="债权兑换">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="list-wrapper">
      <product-list-body
        v-if="list.length"
        v-for="item in list"
        :key="item.id"
        :item="item"
        :productId="item.id"
      ></product-list-body>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { Header, Toast, MessageBox, Indicator } from 'mint-ui'
import { mapState, mapMutations, mapActions } from 'vuex'
import { hashLogin, outGoods } from '../../api/expandDebt'
import ProductListBody from '../product-list/child/ProductListBody'

export default {
  name: 'expandDebt',
  data() {
    return {
      hashid: process.env.NODE_ENV === 'development' ? 'e9b9f62b59f5666f9b25ebb159d96c3a' : '',
      list: []
    }
  },
  computed: {},
  components: {
    ProductListBody
  },
  created() {
    this.getHashid()
  },
  methods: {
    ...mapMutations({
      saveAuthInfo: 'signin'
    }),
    getHashid() {
      let hid = this.utils.getCookie('expandDebtUser')
      hid && (this.hashid = hid)
      this.getInfo()
    },
    getInfo() {
      if (!this.hashid) return

      Indicator.open()
      hashLogin(this.hashid)
        .then(
          res => {
            if (!res.code) {
              this.saveAuthInfo({ token: res.data.token, user: res.data.user })
              this.getGoodsList()
            } else {
              Toast({
                message: res.info
              })
            }
          },
          error => {
            console.log(error)
            if (error.errorCode) {
              Toast(error.errorMsg)
            }
          }
        )
        .finally(() => {
          Indicator.close()
        })
    },
    getGoodsList() {
      outGoods().then(
        res => {
          this.list = res.data
        },
        error => {
          console.log(error)
          if (error.errorCode) {
            Toast(error.errorMsg)
          }
        }
      )
    },
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .header {
    height: 44px;
  }
  .list-wrapper {
    flex: 1;
    overflow: auto;
    background-color: #fff;
  }
}
</style>
