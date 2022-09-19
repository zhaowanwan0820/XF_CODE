<template>
  <div class="product-new-wrapper">
    <div class="product-new">
      <div class="product-new-header" v-stat="{ id: `index_hot_more` }">
        <!-- <img src="../../../assets/image/hh-icon/b0-home/24h-bg.png" />
        <div class="more">
          <span>查看全部</span>
          <img src="../../../assets/image/hh-icon/b0-home/24h-more-icon.png" alt="" />
        </div> -->
        <module-title :titleContent="titleContent"></module-title>
      </div>
      <swiper :options="swiperOption" ref="mySwiper" class="product-new-body">
        <swiper-slide
          class="product-new-swiper"
          :key="`index_hot_${item.id}`"
          v-for="(item, index) in first5Goods"
          v-stat="{ id: `index_hot_${index}` }"
        >
          <home-product-dayhot-body :item="item" :index="index"></home-product-dayhot-body>
        </swiper-slide>
      </swiper>
    </div>
  </div>
</template>
<script>
import HomeProductDayhotBody from './HomeProductDayhotBody'
import ModuleTitle from "./ModuleTitle"

export default {
  name: 'HomeProductDayhot',
  data() {
    return {
      swiperOption: {
        // pagination: {
        //   el: '.swiper-pagination'
        // },
        slidesPerView: 'auto',
        paginationClickable: true,
        spaceBetween: 0,
        freeMode: true
      },
      titleContent:{
        title1:"热卖爆品",
        title2:"跟着大家买",
        rightMore:true,
        jumpUrl:this.productListClick
      },
    }
  },
  props: ['items', 'type'],
  components: {
    HomeProductDayhotBody,
    ModuleTitle,
  },
  computed: {
    first5Goods() {
      return this.items.slice(0, 5)
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
.product-new-wrapper {
  padding: 0 10px;
  margin-top: 16px;
}
.product-new {
  padding: 16px 0 16px;
  background-color: #ffffff;
  margin-bottom: 10px;
  .product-new-header {
    padding: 0 10px;
    box-sizing: border-box;
  }
  .product-new-body {
    margin-top: 20px;
    padding: 0 10px;
    .product-new-swiper {
      width: 106px;
      & + .product-new-swiper {
        // @include thin-left-border(rgba(108, 108, 108, 0.2), 0, 0, true);
      }
    }
    .swiper-pagination /deep/ .swiper-pagination-bullet {
      border-radius: 3px;
      width: 3px;
      height: 3px;
      background: #ccc;
      margin: 0 2.5px;
    }
    .swiper-pagination /deep/ .swiper-pagination-bullet.swiper-pagination-bullet-active {
      background: #b75800;
    }
  }
}
</style>
