<template>
  <div class="product-list products-list-new">
    <div class="product-list-header" @click="productListClick"></div>
    <div class="product-list-body-swipe" :class="{ 'no-page': items.length < 6 }">
      <mt-swipe :auto="0" :showIndicators="items.length > 6">
        <template v-for="(pageItem, pageIndex) in pageArr">
          <mt-swipe-item :key="pageIndex">
            <div class="product-list-body">
              <template v-for="(item, index) in items">
                <home-mlm-product-body
                  :item="item"
                  :index="index"
                  v-if="index < (pageIndex + 1) * 6 && index >= pageIndex * 6"
                  v-bind:key="index"
                  @click="productItemClick(item)"
                ></home-mlm-product-body>
              </template>
            </div>
          </mt-swipe-item>
        </template>
      </mt-swipe>
    </div>
  </div>
</template>

<script>
import HomeMlmProductBody from './HomeMlmProductBody'

export default {
  name: 'HomeProductHot',
  data() {
    return {}
  },
  props: ['items'],
  components: {
    HomeMlmProductBody
  },
  created() {},
  computed: {
    pageArr() {
      return new Array(Math.ceil(this.items.length / 6))
    }
  },
  methods: {
    productListClick: function() {
      this.$router.push({
        name: 'mlmProducts'
      })
    },
    productItemClick(item) {
      this.$router.push({ name: 'sharerDetail', query: { id: item.id } })
    }
  }
}
</script>

<style lang="scss" scoped>
.product-list {
  background: #fff;
  margin-bottom: 15px;
  .product-list-header {
    width: 100%;
    height: 115px;
    background: url('../../../assets/image/hh-icon/b0-home/home-mlm-prods-title.png') no-repeat;
    background-size: 100%;
  }
  .product-list-body-swipe {
    height: 420px;
    background-color: #fff6dd;
    &.no-page {
      height: 400px;
    }
  }
  .product-list-body {
    padding: 9px 15px 15px;
    padding-bottom: 0;
    display: inline-flex;
    flex-flow: row wrap;
    align-content: flex-start;
  }
  /deep/ .mint-swipe-indicator {
    border-radius: 3px;
    width: 3px;
    height: 3px;
    background: #cccccc;
    opacity: 1;
    margin: 0 3px;
    &.is-active {
      background: #b75800;
    }
  }
}
</style>
