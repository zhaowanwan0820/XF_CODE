<!-- Goodsreview.vue -->
<template>
  <div class="ui-review-wrapper ui-detail-common" v-if="reviewList.length > 0" @click="getCommentStatus">
    <div class="review-header header">
      <div class="title">
        商品评论
        <template v-if="total"
          >({{ total }})</template
        >
      </div>
      <div class="more">
        <span>查看更多</span>
        <img src="../../../assets/image/change-icon/icon_more.png" />
      </div>
    </div>
    <swiper :options="swiperOption" ref="mySwiper" class="prod-comments-body">
      <!-- slides -->
      <template v-for="(item, index) in reviewList" v-if="item.nickname">
        <swiper-slide class="prod-comments-swiper" :key="index" v-if="index < 5">
          <div class="comments-title">
            <img class="avatar" v-if="item.avatar" :src="item.avatar" />
            <img class="avatar" v-else src="../../../assets/image/change-icon/a0_user@2x.png" />
            <span>{{ item.nickname }}</span>
          </div>
          <div class="comments-body">
            <template v-if="item.content">{{ item.content }}</template>
            <template v-else
              >无评价信息</template
            >
          </div>
        </swiper-slide>
      </template>
      <div class="swiper-pagination" slot="pagination"></div>
    </swiper>
  </div>
</template>

<script>
import ReviewList from './ReviewList'
import { getReviewList } from '../../../api/product'
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {
      reviewList: [],
      total: 0,
      swiperOption: {
        slidesPerView: 'auto',
        spaceBetween: 5,
        freeMode: true
      }
    }
  },

  computed: {
    ...mapState({
      currentProductId: state => state.detail.detailInfo.id
    })
  },

  components: {
    ReviewList
  },

  created() {
    this.getReviewList()
  },

  methods: {
    ...mapMutations({
      changeIndex: 'changeIndex'
    }),

    getReviewList() {
      getReviewList(this.currentProductId, 0, 0, 1, 3).then(res => {
        this.reviewList = res.list
        this.total = res.paged.total
      })
    },

    /* 评论 */
    getCommentStatus() {
      // this.changeIndex(2)
      this.$router.push({ name: 'comments', query: { id: this.currentProductId } })
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-review-wrapper {
  height: auto;
  &.ui-detail-common {
    padding: 0;
  }
  .review-header {
    padding: 0 15px;
    height: 50px;
    color: #404040;
    display: flex;
    justify-content: space-between;
    .title {
      font-size: 16px;
      color: #333;
    }
    .more {
      color: #999999;
      font-size: 11px;
      display: flex;
      align-items: center;
      img {
        width: 19px;
        margin-left: 10px;
      }
    }
  }
  .prod-comments-body {
    padding: 0 15px 15px;
    .prod-comments-swiper {
      box-sizing: border-box;
      padding: 13px;
      width: 308px;
      height: 110px;
      background-color: #f9f9f9;
      margin-right: 5px;
      .comments-title {
        font-size: 0;
        display: flex;
        align-items: center;
        img {
          width: 32px;
          height: 32px;
          border-radius: 32px;
        }
        span {
          font-size: 14px;
          color: #404040;
          margin-left: 8px;
        }
      }
      .comments-body {
        margin-top: 12px;
        color: #666666;
        font-size: 12px;
        line-height: 1.5;
        min-height: 35px;
        word-break: break-word;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        /*! autoprefixer: ignore next */
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
      }
    }
  }
}
</style>
