<template>
  <div class="product-list products-list-debt">
    <div class="product-list-header" @click="productListClick"></div>
    <div class="product-list-body-swipe">
      <mt-swipe :auto="0">
        <template v-for="(pageItem, pageIndex) in pageArr">
          <mt-swipe-item :key="pageIndex">
            <div class="product-list-body">
              <template v-for="(item, index) in items">
                <home-product-debt-body
                  :item="item"
                  :index="index"
                  v-if="index < (pageIndex + 1) * 6 && index >= pageIndex * 6"
                  v-bind:key="index"
                ></home-product-debt-body>
              </template>
            </div>
          </mt-swipe-item>
        </template>
      </mt-swipe>
    </div>
  </div>
</template>

<script>
import HomeProductDebtBody from './HomeProductDebtBody'

export default {
  name: 'HomeProductDebt',
  data() {
    return {}
  },
  props: ['items', 'title', 'type'],
  components: {
    HomeProductDebtBody
  },
  computed: {
    pageArr() {
      return new Array(Math.ceil(this.items.length / 6))
    }
  },
  methods: {
    productListClick: function() {
      this.$router.push({
        name: 'products',
        query: { sort_key: this.type }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.product-list {
  background: #fff;
  margin-bottom: 10px;
  .product-list-header {
    width: 100%;
    height: 70px;
    background-position: top center;
    background-repeat: no-repeat;
    background-size: 100%;
  }
  .product-list-body-swipe {
    height: 420px;
  }
  .product-list-body {
    padding: 9px 15px 15px;
    padding-bottom: 0;
    display: inline-flex;
    flex-flow: row wrap;
    align-content: flex-start;
  }
  /deep/ .mint-swipe-indicator {
    width: 5px;
    height: 2px;
    border-radius: 0;
    background: #cccccc;
    opacity: 1;
    margin: 0 3px;
    &.is-active {
      background: #772508;
    }
  }
}
</style>
