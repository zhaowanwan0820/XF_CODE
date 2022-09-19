<template>
  <div class="product-list products-list-hot">
    <div class="product-list-header" @click="productListClick"></div>
    <mt-navbar v-model="selected" class="prod-list-hot-navbar">
      <template v-for="(item, index) in NAVBAR">
        <mt-tab-item :id="index" :key="0 + index + 'navbar'">{{ item.name }}</mt-tab-item>
      </template>
    </mt-navbar>

    <swiper :options="swiperOption" ref="mySwiper" class="product-list-body-swipe">
      <!-- slides -->
      <template v-for="(item, index) in NAVBAR">
        <swiper-slide :key="index" v-if="index < 5">
          <home-product-hot-body :data="items" :category="item.category"></home-product-hot-body>
        </swiper-slide>
      </template>
    </swiper>
  </div>
</template>

<script>
import HomeProductHotBody from './HomeProductHotBody'
const NAVBAR = [
  {
    name: '每日好货',
    category: 0
  },
  {
    name: '面部护肤',
    category: 133
  },
  {
    name: '洗发护发',
    category: 142
  },
  {
    name: '粮油调味',
    category: 57
  },
  {
    name: '休闲食品',
    category: 169
  }
]

export default {
  name: 'HomeProductHot',
  data() {
    return {
      selected: 0,
      NAVBAR,
      swiperOption: {
        on: {
          slideChange: () => {
            if (this.swiper) {
              this.selected = this.swiper.activeIndex
            }
          }
        }
      }
    }
  },
  props: ['items', 'title', 'type'],
  watch: {
    selected(value, oldValue) {
      this.swiper.slideTo(value, Math.abs(value - oldValue) * 300, false)
    }
  },
  components: {
    HomeProductHotBody
  },
  mounted() {},
  computed: {
    swiper() {
      return this.$refs.mySwiper.swiper
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
  background-color: #fff;
  margin-bottom: 15px;
  background-size: 100%;
  background-image: url('../../../assets/image/hh-icon/b0-home/home-hot-prods-bg.png');
  background-repeat: no-repeat;
  .product-list-header {
    width: 100%;
    height: 70px;
    margin-bottom: 35px;
  }
  .product-list-body-swipe {
    height: 420px;
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
  .prod-list-hot-navbar {
    background-color: transparent;
    align-items: baseline;
    .mint-tab-item {
      position: relative;
      padding: 0 0 10px;
      color: #dda97a;
      font-size: 12px;
      transition: 0.2s all ease-in-out;
      &.is-selected {
        color: #e5a775;
        border-bottom: 0;
        margin-bottom: 0;
        /deep/ .mint-tab-item-label {
          font-size: 14px;
        }
        &:before {
          display: block;
          content: '';
          width: 6px;
          height: 4px;
          background-image: url('../../../assets/image/hh-icon/b0-home/home-hot-icon-bg.png');
          background-repeat: no-repeat;
          background-size: 6px;
          position: absolute;
          bottom: 0;
          left: 50%;
          transform: translateX(-50%);
        }
      }
    }
  }
}
</style>
