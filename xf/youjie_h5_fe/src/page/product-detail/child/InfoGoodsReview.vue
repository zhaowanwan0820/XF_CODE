<!-- Goodsreview.vue -->
<template>
  <div class="ui-review-wrapper ui-detail-common" @click="getCommentStatus">
    <template v-if="!comment_info.comment_id">
      <div class="review-header header">
        <div class="title">
          暂无评价
        </div>
      </div>
    </template>
    <template v-else>
      <div class="review-header header">
        <div class="title">
          商品评价
          <template v-if="comment_info.count_total > 0"
            >({{ comment_info.count_total }})</template
          >
        </div>
        <div class="more">
          <span>查看全部</span>
          <img src="../../../assets/image/change-icon/icon_more.png" />
        </div>
      </div>
      <div class="prod-comments-body">
        <div class="prod-comments-swiper">
          <div class="comments-title">
            <template v-if="comment_info.hidden_name_is">
              <!-- 匿名默认头像 -->
              <img class="avatar" src="../../../assets/image/hh-icon/comment/icon-default-user.png" />
            </template>
            <template v-else>
              <!-- 非匿名头像 -->
              <img class="avatar" v-if="comment_info.member.avatar" :src="comment_info.member.avatar" />
              <img class="avatar" v-else src="../../../assets/image/hh-icon/comment/icon-default-user.png" />
            </template>
            <span>{{ comment_info.format_user_name }}</span>
            <template v-for="(i, index) in star(comment_info.goods_score)">
              <img src="../../../assets/image/hh-icon/comment/icon-star-list.png" class="star" alt="" />
            </template>
          </div>
          <div class="comment-date" v-if="comment_info.goods && comment_info.goods.goods_attr">
            <span>{{ comment_info.goods.goods_attr }} * {{ comment_info.goods.goods_number }}</span>
          </div>
          <div class="comments-body">
            <p v-if="comment_info.format_content">{{ comment_info.format_content }}</p>
            <div class="img-wrapper" v-if="comment_info.img_arr.length">
              <img v-for="(item, index) in comment_info.img_arr" v-if="index < 5" :src="item" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import { getComment } from '../../../api/product'
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {
      comment_info: {},
      swiperOption: {
        slidesPerView: 'auto',
        spaceBetween: 5,
        freeMode: true
      }
    }
  },

  computed: {
    ...mapState({
      currentProductId: state => state.detail.currentProductId
    })
  },

  created() {
    this.getNewComment()
  },

  methods: {
    ...mapMutations({
      changeIndex: 'changeIndex'
    }),

    getNewComment() {
      getComment(this.currentProductId).then(res => {
        this.comment_info = res
      })
    },

    /* 评论 */
    getCommentStatus() {
      // this.changeIndex(2)
      this.$router.push({ name: 'comments', query: { id: this.currentProductId } })
    },
    star(n) {
      if (n < 1) n = 5 //原始数据没有评分，默认展示五星
      let arr = []
      if (n > 0) arr.length = n
      return arr
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-review-wrapper {
  height: auto;
  &.ui-detail-common {
    padding: 16px 16px 23px;
  }
  .review-header {
    display: flex;
    justify-content: space-between;
    .title {
      font-size: 14px;
      font-weight: 400;
      color: #404040;
      line-height: 20px;
    }
    .more {
      display: flex;
      align-items: center;
      span {
        @include sc(11px, #999);
        font-weight: 400;
        line-height: 16px;
      }
      img {
        width: 19px;
        margin-left: 10px;
      }
    }
  }
  .prod-comments-body {
    margin-top: 10px;
    .prod-comments-swiper {
      box-sizing: border-box;
      width: 345px;
      height: 100%;
      padding: 13px;
      background-color: #f9f9f9;
      border-radius: 2px;
      margin-right: 5px;
      .comments-title {
        font-size: 0;
        display: flex;
        align-items: center;
        .avatar {
          width: 32px;
          height: 32px;
          border-radius: 32px;
        }
        span {
          font-size: 14px;
          color: #404040;
          margin: 0 8px;
        }
        .star {
          @include wh(13px, 13px);
          margin-right: 5px;
        }
      }
      .comment-date {
        font-size: 0;
        margin-top: 8px;
        span {
          font-size: 12px;
          font-weight: 400;
          color: #9b9b9b;
          line-height: 17px;
          margin-right: 10px;
        }
      }
      .comments-body {
        margin-top: 9px;
        p {
          font-size: 12px;
          font-weight: 400;
          color: #666;
          line-height: 17px;

          word-break: break-word;
          overflow: hidden;
          text-overflow: ellipsis;
          display: -webkit-box;
          /*! autoprefixer: ignore next */
          -webkit-box-orient: vertical;
          -webkit-line-clamp: 2;
        }
        .img-wrapper {
          margin: 9px 0 5px;
          width: 100%;
          height: 50px;
          overflow: hidden;
          white-space: nowrap;
          img {
            width: 50px;
            height: 50px;
            margin-right: 15px;
          }
        }
      }
    }
  }
}
</style>
