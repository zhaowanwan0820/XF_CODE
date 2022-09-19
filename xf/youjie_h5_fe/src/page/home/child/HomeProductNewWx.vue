<template>
  <div class="products-list-new-wrapper">
    <div class="products-list-new">
      <div class="product-list-header" v-stat="{ id: 'index_new_products_more' }">
        <!-- <img src="../../../assets/image/hh-icon/b0-home/new-title.png" />
        <div class="more">
          <span>查看全部</span>
          <img src="../../../assets/image/hh-icon/b0-home/new-more-icon.png" alt="" />
        </div> -->
        <module-title :titleContent="titleContent"></module-title>
      </div>
      <div class="product-list">
        <!-- <list-body v-for="(item, index) in list" :key="index" :item="item"></list-body> -->
        <div class="product-list-body-swipe">
          <swiper :auto="0" v-if="list.length"  :options="swiperOption" class="swiper-container">
            <template v-for="(pageItem, pageIndex) in pageArr">
              <swiper-slide :key="pageIndex"  class="swiper-item">
                <div class="product-list-body">
                  <template v-for="(item, index) in list">
                    <home-product-floor-body
                      :item="item"
                      :index="index"
                      v-if="index < (pageIndex + 1) * 6 && index >= pageIndex * 6"
                      v-bind:key="'list_new_'+pageIndex+'_'+index"
                      v-stat="{ id: `list_new_${pageIndex}_${index}` }"
                    ></home-product-floor-body>
                  </template>
                </div>
              </swiper-slide>
            </template>
          </swiper>
        </div>
        <div class="pagination swiper-pagination-xp"  slot="pagination"></div>
      </div>
    </div>
  </div>
</template>

<script>
import { productList } from '../../../api/product'
import { ENUM } from '../../../const/enum'
// import HomeProductNewWxBody from './HomeProductNewWxBody'
import ModuleTitle from "./ModuleTitle"
import HomeProductFloorBody from './HomeProductFloorBody'

export default {
  name: 'HomeProductNewWx',
  data() {
    return {
      swiperOption:{
        direction: 'horizontal',
        pagination:{
          el:".swiper-pagination-xp"
        },
        on: {
        },
      },
      list: [],
      type: ENUM.SORT_KEY.DATE,
      titleContent:{
        title1:"新品上架",
        title2:"好货甄选",
        rightMore:true,
        jumpUrl:this.productListClick
      },
    }
  },
  created() {
    this.getList()
  },
  components: {
    // 'list-body': HomeProductNewWxBody,
    ModuleTitle,
    HomeProductFloorBody,
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
          this.list = res.list;
        },
        error => {}
      )
    },
  },
  computed: {
    pageArr() {
      return new Array(Math.ceil(this.list.length / 6))
    }
  },
}
</script>

<style lang="scss" scoped>
.products-list-new-wrapper {
  margin-top: 16px;
  margin-bottom: 16px;
  padding: 0 10px;
  .products-list-new {
    padding-top: 10px;
    background: #fff;
    padding-bottom: 15px;
  }
  // .product-list-header {
  //   height: 27px;
  //   font-size: 0;
  //   display: flex;
  //   align-items: flex-end;
  //   justify-content: space-between;
  //   padding: 0 10px;
  //   img {
  //     height: 27px;
  //   }
  //   div {
  //     height: 21px;
  //     border-radius: 11px;
  //     background-color: rgba(211, 24, 24, 0.15);
  //     display: flex;
  //     align-items: center;
  //     justify-content: center;
  //     padding: 0 6px 0 1px;
  //     img {
  //       width: 5px;
  //       height: 7px;
  //     }
  //     span {
  //       display: inline-block;
  //       @include sc(9px, #d31818, center);
  //     }
  //   }
  // }
  .product-list-header{
    padding: 0 10px;
    box-sizing: border-box;
    margin-bottom: 16px;
  }
  .product-list {
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
    background-color: #ffffff;
    border-radius: 2px;
    // height: 329px;
    position: relative;
    // .swiper-pagination{
    //   background: #000;
    // }
    .pagination{
      position: absolute;
      bottom: 0;
      left: 0;
      text-align: center;
      width: 100%;
    }
    .pagination /deep/ span {
      width:3px;
      height:3px;
      background: #F0F0F0;
      border-radius:2px;
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
  .product-list-body {
    width: 100%;
    display: inline-flex;
    flex-flow: row wrap;
    justify-content: flex-start;
    align-content: flex-start;
    padding: 0 8px;
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
