<template>
  <div class="products-list-new-wrapper">
    <div class="products-list-new">
      <div class="product-list-header" @click="productListClick" v-stat="{ id: 'index_new_products_more' }">
        <img src="../../../assets/image/hh-icon/b0-home/new-title.png" />
        <span>更多...</span>
      </div>
      <div class="product-list-body-swipe">
        <mt-swipe :auto="3000">
          <template v-for="(pageItem, pageIndex) in pageArr">
            <mt-swipe-item :key="pageIndex">
              <div class="product-list-body">
                <template v-for="(item, index) in list">
                  <home-product-new-body
                    :item="item"
                    :index="index"
                    v-if="index < (pageIndex + 1) * 6 && index >= pageIndex * 6"
                    v-bind:key="index"
                    v-stat="{ id: `index_new_products_${pageIndex}_${index}` }"
                  ></home-product-new-body>
                </template>
              </div>
            </mt-swipe-item>
          </template>
        </mt-swipe>
      </div>
    </div>
  </div>
</template>

<script>
import HomeProductNewBody from './HomeProductNewBody'
import { productList } from '../../../api/product'
import { ENUM } from '../../../const/enum'

export default {
  name: 'HomeProductHot',
  data() {
    return {
      list: [],
      type: ENUM.SORT_KEY.DATE
    }
  },
  created() {
    this.getList()
  },
  components: {
    HomeProductNewBody
  },
  computed: {
    pageArr() {
      return new Array(Math.ceil(this.list.length / 6))
    }
  },
  methods: {
    productListClick: function() {
      this.$router.push({
        name: 'products',
        query: { sort_key: this.type }
      })
    },

    getList() {
      productList({
        sort_key: 5,
        sort_value: 2,
        page: 1,
        per_page: 12
      }).then(
        res => {
          this.list = res.list
        },
        error => {}
      )
    }
  }
}
</script>

<style lang="scss" scoped>
.products-list-new-wrapper {
  margin-bottom: 15px;
  padding: 0 10px;
  .products-list-new {
    padding-top: 15px;
    background: #fff;
    border-radius: 4px;
  }
  .product-list-header {
    height: 20px;
    font-size: 0;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 0 10px;
    img {
      height: 20px;
    }
    span {
      display: inline-block;
      @include sc(9px, #999999, right center);
    }
  }
  .product-list-body-swipe {
    height: 330px;
    margin-top: 20px;
  }
  .product-list-body {
    padding: 0 10px;
    padding-bottom: 0;
    display: inline-flex;
    flex-flow: row wrap;
    justify-content: space-between;
    align-content: flex-start;
  }
  /deep/ .mint-swipe-indicator {
    width: 3px;
    height: 3px;
    border-radius: 3px;
    background: #cccccc;
    opacity: 1;
    margin: 0 3px;
    &.is-active {
      background: #b75800;
    }
  }
}
</style>
