<!-- Evaluation.vue -->
<template>
  <div class="ui-evaluation">
    <div class="ui-evaluation-header">
      <div
        v-for="item in staticData"
        :key="item.id"
        :class="{ active: currentTag == item.id }"
        @click="changeTab(item.id)"
      >
        <span>{{ item.name }}</span>
        <label v-if="item.id !== 7">({{ comment[item.value] }})</label>
      </div>
    </div>
    <div
      class="ui-evaluation-body"
      v-infinite-scroll="loadMore"
      infinite-scroll-disabled="loading"
      infinite-scroll-distance="10"
    >
      <product-review-body :reviewList="reviewList"></product-review-body>

      <div class="list-empty" v-if="reviewList.length <= 0">
        <img src="../../../assets/image/hh-icon/l0-list-icon/comment-list.png" />
        <p>本商品暂无评价</p>
      </div>
    </div>
  </div>
</template>

<script>
import { Indicator } from 'mint-ui'
import { evaluation, COMMENTSTATUS } from '../static'
import { getCommentList } from '../../../api/product'
import ProductReviewBody from './ProductReviewBody'
export default {
  data() {
    return {
      id: this.$route.query.id ? this.$route.query.id : '',
      staticData: COMMENTSTATUS,
      currentTag: 1,
      comment: {},
      reviewList: [],
      page: 1,
      loading: false,
      total: 1
    }
  },
  components: {
    ProductReviewBody
  },
  created() {
    this.getCommentList()
  },
  methods: {
    loadMore() {
      this.loading = true
      this.page = ++this.page
      if (this.page <= this.total) {
        this.loading = false
        this.getCommentList(true)
      }
    },
    getCommentList(ispush) {
      let data = {}
      data['goods_id'] = this.id
      data['type'] = this.currentTag
      data['page'] = this.page
      data['per_page'] = 10

      Indicator.open()
      getCommentList(data)
        .then(res => {
          if (ispush) {
            this.reviewList = [...this.reviewList, ...res.list]
          } else {
            this.reviewList = res.list
          }
          if (this.page < res.count_page) {
            this.loading = false
          } else {
            this.loading = true
          }
          this.comment = res
          this.total = res.count_page
        })
        .finally(() => {
          Indicator.close()
        })
    },
    changeTab(id) {
      this.currentTag = id
      this.page = 1
      this.getCommentList(false)
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-evaluation {
  flex: 1;
  box-sizing: border-box;
  padding-top: 6px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  .ui-evaluation-header {
    padding: 0 15px;
    background: #fff;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    div {
      width: 79px;
      height: 24px;
      font-size: 0;
      border: 1px solid $primaryColor;
      background-color: rgba(252, 127, 12, 0.1);
      border-radius: 13px;

      display: flex;
      align-items: center;
      justify-content: center;

      span,
      label {
        font-size: 12px;
        line-height: 26px;
        color: $primaryColor;
        text-align: center;
      }
      &.active {
        border-color: $primaryColor;
        background-color: $primaryColor;
        span,
        label {
          color: #fff;
        }
      }
      margin: 9px 6px 6px 0;
      &:nth-child(4n) {
        margin-right: 0;
      }
    }
  }
  .ui-evaluation-body {
    padding: 0 15px;
    flex: 1;
    overflow: auto;
    .list {
      @include thin-border(#e8eaed, 0, auto, true);
      color: #4e545d;
      font-size: 15px;
      padding: 15px 0;
      .evaluation-title {
        display: flex;
        align-items: center;
        .avatar {
          width: 32px;
          height: 32px;
        }
        .trans {
          opacity: 0.5;
        }
        .evaluation-name {
          margin-left: 8px;
          margin-right: 15px;
          font-size: 14px;
          font-weight: 400;
          color: rgba(64, 64, 64, 1);
          line-height: 20px;
        }
        .star {
          @include wh(13px, 13px);
          margin-right: 5px;
        }
      }
      .comment-data {
        font-size: 0;
        margin-top: 9px;
        span {
          font-size: 12px;
          font-weight: 400;
          color: rgba(153, 153, 153, 1);
          line-height: 17px;
          margin-right: 10px;
        }
      }
      .evaluation-body {
        p {
          padding: 4px 0;
          font-size: 12px;
          font-weight: 400;
          color: rgba(102, 102, 102, 1);
          line-height: 17px;
        }
        .img-wrapper {
          margin-top: 12px;
          display: flex;
          align-items: center;
          flex-wrap: wrap;
          img {
            @include wh(100px, 100px);
            margin-right: 20px;
            &:nth-child(3n) {
              margin-right: 0;
            }
            &:nth-child(n + 4) {
              margin-top: 10px;
            }
          }
        }
        .add-content {
          .title {
            height: 17px;
            font-size: 12px;
            font-weight: 500;
            color: rgba(85, 46, 32, 1);
            line-height: 17px;
          }
          .content {
            padding: 4px 0;
            font-size: 12px;
            font-weight: 400;
            color: rgba(102, 102, 102, 1);
            line-height: 17px;
          }
        }
        .repeat-wrapper {
          p {
            box-sizing: border-box;
            width: 100%;
            padding: 9px 7px 6px 11px;
            background-color: #f9f9f9;

            font-size: 12px;
            font-weight: 500;
            color: rgba(64, 64, 64, 1);
            line-height: 18px;
            span {
              font-weight: 700;
            }
          }
        }
      }
    }
    .list-empty {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      img {
        width: 135px;
      }
      p {
        color: #7c7f88;
        font-size: 17px;
        padding: 0;
        margin: 0;
        font-weight: normal;
      }
    }
  }
}
</style>
