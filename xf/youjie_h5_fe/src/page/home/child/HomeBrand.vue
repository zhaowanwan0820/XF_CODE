<template>
  <div class="home-brand-wrapper" v-if="list.length">
    <div class="home-brand">
      <div class="home-brand-header" @click="goBrandList" v-stat="{ id: `index_brand_more` }">
        <img src="../../../assets/image/hh-icon/b0-home/brand-bg.png" />
        <span>更多...</span>
      </div>
      <swiper :options="swiperOption" ref="mySwiper" class="home-brand-body">
        <!-- slides -->
        <template v-for="(item, index) in list">
          <swiper-slide
            class="home-brand-swiper"
            @click.native="itemClick(item)"
            :key="index"
            v-if="index < 12"
            v-stat="{ id: `index_brand_${index}` }"
          >
            <img
              v-lazy="{
                src: item.thumb,
                error: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-large.png'),
                loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-large.png')
              }"
              alt=""
            />
          </swiper-slide>
        </template>
        <!-- <div class="swiper-pagination" slot="pagination"></div> -->
      </swiper>
    </div>
  </div>
</template>

<script>
import { getHomeBrand } from '../../../api/brand'
import { swiper, swiperSlide } from 'vue-awesome-swiper'
export default {
  name: 'homeBrand',
  data() {
    return {
      list: [],
      swiperOption: {
        // pagination: {
        //   el: '.swiper-pagination'
        // },
        slidesPerView: 'auto',
        paginationClickable: true,
        spaceBetween: 0,
        centeredSlides: true
      }
    }
  },

  created() {
    this.getHomeBrandList()
  },

  components: {
    swiper,
    swiperSlide
  },

  methods: {
    getHomeBrandList() {
      getHomeBrand().then(res => {
        this.list = res
        if (this.list.length > 2) {
          this.swiperOption.initialSlide = 1
        }
      })
    },

    itemClick(item) {
      this.$router.push({ name: 'products', query: { brand_id: item.id } })
    },

    goBrandList() {
      this.$router.push({ name: 'brand' })
    }
  }
}
</script>

<style lang="scss" scoped="scoped">
.home-brand-wrapper {
  padding: 0 10px;
  margin-bottom: 10px;
}
.home-brand {
  padding: 15px 0 0;
  background-color: #ffffff;
  margin-bottom: 10px;
  border-radius: 4px;
  .home-brand-header {
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
  .home-brand-body {
    padding: 20px 0;
    .home-brand-swiper {
      width: 165px;
      height: 165px;
      transition: 0.3s;
      transform: scale(0.82);
      background: rgba(255, 255, 255, 1);
      transform-origin: center 120px;
      box-shadow: 0px 3px 8px 0px rgba(155, 155, 155, 0.12);
      border-radius: 4px;
      &.swiper-slide-active {
        transform: scale(1);
      }
      img {
        max-height: 100%;
        max-width: 100%;
      }
    }
  }
}
</style>
