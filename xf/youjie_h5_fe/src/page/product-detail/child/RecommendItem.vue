<!-- recommend.vue -->
<template>
  <div class="ui-recommend-wrapper" v-if="list.length > 0">
    <div class="wrapper-title">
      <template v-for="(item, index) in NAVBAR">
        <div
          class="wrapper-title-item"
          :class="{ active: index == parseInt(active / NAVBAR.length) }"
          @click="changeNavbar(index)"
          v-stat="{ id: `infos_btn_${index === 0 ? 'guess' : 'hot'}` }"
        >
          {{ item }}
        </div>
      </template>
    </div>
    <div class="wrapper-swipe">
      <swiper :options="swiperOption" ref="mySwiper" class="product-list-body-swipe">
        <!-- slides -->
        <template v-for="(items, indexs) in list">
          <swiper-slide>
            <product-body :items="items"></product-body>
          </swiper-slide>
        </template>
      </swiper>
    </div>

    <div class="swiper-indicators" v-if="list.length > 0">
      <div class="ui-indicator">
        <div class="indicator-item" :class="{ active: active % 2 == 0 }"></div>
        <div class="indicator-item" :class="{ active: active % 2 == 1 }"></div>
      </div>
    </div>
  </div>
</template>

<script>
import { getRecommendList } from '../../../api/recommend'
import ProductBody from './ProductBody'
import { mapState, mapMutations } from 'vuex'
const NAVBAR = ['你可能喜欢', '24小时热销']

export default {
  data() {
    return {
      list: [],
      currentIndex: 0,

      active: 0,

      NAVBAR,
      swiperOption: {
        on: {
          slideChange: () => {
            if (this.swiper) {
              this.active = this.swiper.activeIndex
            }
          }
        }
      }
    }
  },

  created() {
    this.getRecommendList()
  },

  components: {
    ProductBody
  },

  computed: {
    ...mapState({
      currentProductId: state => state.detail.currentProductId
    }),
    swiper() {
      return this.$refs.mySwiper.swiper
    }
  },

  methods: {
    /*
      getRecommendList: 获取推荐商品
    */
    async getRecommendList() {
      let params = {
        product: this.currentProductId ? this.currentProductId : ''
      }
      getRecommendList(params).then(res => {
        this.list.push(res[0].slice(0, 6), res[0].slice(6, 12), res[1].slice(0, 6), res[1].slice(6, 12))
      })
    },

    changeNavbar(type) {
      this.active = type * (this.list.length / this.NAVBAR.length)
    }
  },

  watch: {
    active(value, oldValue) {
      this.swiper.slideTo(value, Math.abs(value - oldValue) * 300, false)
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-recommend-wrapper {
  background: #fff;
  margin-top: 8px;
  margin-bottom: 10px;
  padding-bottom: 15px;

  .wrapper-title {
    @include thin-border();
    height: 40px;
    display: flex;
    justify-content: space-around;
    align-items: stretch;
    .wrapper-title-item {
      display: flex;
      align-items: center;
      font-size: 12px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
      line-height: 17px;
      border-bottom: 2px transparent solid;
      padding: 0 6px;
      &.active {
        color: #b75800;
        border-bottom-color: #b75800;
      }
    }
  }

  .wrapper-swipe {
    padding: 10px 0 0;
  }

  .swiper-indicators {
    position: relative;
    margin-top: 20px;
    bottom: 0;
    .indicator-item {
      width: 3px;
      height: 3px;
      border-radius: 3px;
      background: #cccccc;
      opacity: 1;
      margin: 0 3px;
      &.active {
        background: #b75800;
      }
    }
  }
}
</style>
