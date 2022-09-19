<template>
  <div class="products-list-new-wrapper" v-if="list.length > 0">
    <div class="products-list-new" v-lazy:background-image="lazy">
      <div class="product-list-header" @click="productListClick" v-stat="{ id: `index_type${cur}_more` }"></div>
      <div class="product-list">
        <div class="product-list-body-swipe">
          <swiper :auto="0" v-if="list.length" :options="swiperOption" class="swiper-container">
            <template v-for="(pageItem, pageIndex) in pageArr">
              <swiper-slide :key="pageIndex" class="swiper-item">
                <div class="product-list-body">
                  <template v-for="(item, index) in list">
                    <home-product-floor-body
                      :item="item"
                      :index="index"
                      v-if="index < (pageIndex + 1) * 6 && index >= pageIndex * 6"
                      v-bind:key="index"
                      v-stat="{ id: `index_type${cur}_${pageIndex}_${index}` }"
                    ></home-product-floor-body>
                  </template>
                </div>
              </swiper-slide>
            </template>
          </swiper>
        </div>
        <div :class="['pagination',`swiper-pagination-${cur}`]"  slot="pagination"></div>
      </div>
    </div>
  </div>
</template>

<script>
import HomeProductFloorBody from './HomeProductFloorBody'
import { productList } from '../../../api/product'
import { ENUM } from '../../../const/enum'

export default {
  name: 'HomeProductHot',
  data() {
    return {
      list: [],
      lazy: {
        src: this.config.bg,
        error: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-floor-bg.png'),
        loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-floor-bg.png')
      },
      swiperOption:{
        direction: 'horizontal',
        pagination:{
          el:`.swiper-pagination-${this.cur}`
        },
        on: {
        },
      },
    }
  },
  props: ['type', 'title', 'config', 'cur'],
  created() {
    // this.getList()
  },
  components: {
    HomeProductFloorBody
  },
  computed: {
    pageArr() {
      return new Array(Math.ceil(this.list.length / 6))
    },
  },
  methods: {
    productListClick: function() {
      if (this.config.name == '以物抵债') {
        this.$router.push({
          name: 'products',
          query: {
            sort_key: 6
          }
        })
      } else {
        delete this.config.params.admin_order
        this.$router.push({
          name: 'products',
          query: this.config.params
        })
      }
    },

    getList() {
      productList({
        sort_value: 2,
        page: 1,
        per_page: 12,
        ...this.config.params
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
    // border-radius: 4px;
    background-repeat: no-repeat;
    background-size: 100%;
  }
  .product-list-header {
    height: 104px;
  }
  .product-list{
    padding-bottom: 16px;
    box-sizing: border-box;
    background-color: #ffffff;
    position: relative;
    .pagination{
      // position: absolute;
      // bottom: 0px;
      // left: 0;
      text-align: center;
      width: 100%;
      // height: 5px;
      margin-top: -16px;
    }
    .pagination /deep/ span {
      width:3px;
      height:3px;
      background: #F0F0F0;
      border-radius:50%;
      opacity:0.8;
      display: inline-block;
      margin-right: 6px;
    }
    .pagination /deep/ span:last-child {
      margin-right: 0px;
    }
    .pagination /deep/ .swiper-pagination-bullet-active{
      background:#b75800;
    }
  }
  .product-list-body-swipe {
    border-radius: 2px;
    height: 318px;
    position: relative;
  }
  .product-list-body {
    padding-bottom: 0;
    width: 100%;
    display: inline-flex;
    flex-flow: row wrap;
    justify-content: flex-start;
    align-content: flex-start;
    padding: 8px 8px;
    box-sizing: border-box;
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
