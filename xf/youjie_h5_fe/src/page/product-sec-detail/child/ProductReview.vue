<!-- Evaluation.vue -->
<template>
  <div class="ui-evaluation">
    <div class="ui-evaluation-header">
      <div
        v-for="item in staticData"
        :key="item.id"
        :class="{ active: currentTag == item.value }"
        @click="changeTab(item.value, item.grade)"
      >
        {{ item.name }}({{ subTotal[item.value] }})
      </div>
    </div>
    <div
      class="ui-evaluation-body"
      v-infinite-scroll="loadMore"
      infinite-scroll-disabled="loading"
      infinite-scroll-distance="10"
    >
      <div class="list" v-for="(item, index) in reviewList" :key="index">
        <template v-if="item.nickname">
          <div class="evaluation-title">
            <img class="avatar" v-if="item.avatar" :src="item.avatar" />
            <img class="avatar" v-else src="../../../assets/image/change-icon/a0_user@2x.png" />
            <span class="evaluation-name">{{ item.nickname }}</span>
            <span class="evaluation-time">{{ getTime(item.created_at) }}</span>
          </div>
          <div class="evaluation-body">
            <template v-if="item.content">
              {{ item.content }}
              <div class="supplier-reply" v-if="item.reply">
                <span>{{ getRepayTitle(item.reply) }}：</span>{{ item.reply.content }}
              </div>
            </template>
            <template v-else
              >无评价信息</template
            >
          </div>
        </template>
      </div>

      <div class="list-empty" v-if="reviewList.length <= 0">
        <img src="../../../assets/image/change-icon/empty_comments@2x.png" />
        <p>本商品暂无评价</p>
      </div>
    </div>
  </div>
</template>

<script>
import { evaluation } from '../static'
import { getReviewList, getReviewsubtotal } from '../../../api/product'
export default {
  data() {
    return {
      staticData: evaluation,
      currentTag: 'total',
      grade: 0,
      subTotal: {},
      reviewList: [],
      page: 0,
      loading: false,
      total: 1
    }
  },
  props: ['id'],
  created() {
    this.getReviewTotal()
  },
  methods: {
    getReviewTotal() {
      getReviewsubtotal(this.id).then(res => {
        this.subTotal = res
      })
    },
    loadMore() {
      this.loading = true
      this.page = ++this.page
      if (this.page <= this.total) {
        this.loading = false
        this.getReviewList(true)
      }
    },
    getReviewList(ispush) {
      getReviewList(this.id, this.grade, 1, this.page, 10).then(res => {
        if (ispush) {
          this.reviewList = [...this.reviewList, ...res.list]
        } else {
          this.reviewList = res.list
        }
        if (res.paged.more) {
          this.loading = false
        } else {
          this.loading = true
        }
        this.total = res.paged.total
      })
    },
    changeTab(value, grade) {
      this.currentTag = value
      this.grade = grade
      this.page = 1
      this.getReviewList(false)
    },
    getGrade(grade) {
      if (grade == 1) {
        return '差评'
      } else if (grade == 2) {
        return '中评'
      } else {
        return '好评'
      }
    },
    getTime(timestamps) {
      let date = new Date(timestamps * 1000)
      let year = date.getFullYear(),
        month = date.getMonth() + 1,
        day = date.getDate(),
        H = date.getHours(),
        M = date.getMinutes(),
        S = date.getSeconds()
      H = H >= 10 ? H : `0${H}`
      M = M >= 10 ? M : `0${M}`
      S = S >= 10 ? S : `0${S}`
      return `${year}-${month}-${day} ${H}:${M}:${S}`
    },
    getRepayTitle(item) {
      let str = '掌柜回复'
      if (item.comment_role == 2) {
        str = '掌柜回复'
      } else if (item.comment_role == 1) {
        str = `${this.utils.storeNameForShort}官方`
      }
      return str
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-evaluation {
  flex: 1;
  box-sizing: border-box;
  padding-top: 15px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  .ui-evaluation-header {
    padding: 0 15px;
    background: #fff;
    height: 44px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    div {
      color: #808080;
      font-size: 12px;
      width: 72px;
      height: 24px;
      border: 1px solid #808080;
      line-height: 24px;
      text-align: center;
      border-radius: 2px;
      &.active {
        color: #772508;
        border-color: #772508;
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
      .evaluation-title {
        margin-top: 10px;
        display: flex;
        align-items: center;
        img {
          width: 32px;
          height: 32px;
        }
        .evaluation-name {
          flex: 1;
          margin-left: 8px;
          color: #404040;
          font-size: 14px;
        }
        .evaluation-time {
          color: #999999;
          font-size: 12px;
        }
        span {
          &.good-review {
            background: #fc2e39;
            width: 36px;
            height: 16px;
            text-align: center;
            background-size: cover;
            line-height: 16px;
            border-radius: 8px;
          }
          &.medium-review {
            background: #fd9f21;
            width: 36px;
            height: 16px;
            text-align: center;
            background-size: cover;
            line-height: 16px;
            border-radius: 8px;
          }
          &.bad-review {
            background: #c3c3c3;
            width: 36px;
            height: 16px;
            text-align: center;
            background-size: cover;
            line-height: 16px;
            border-radius: 8px;
          }
        }
      }
      .evaluation-body {
        margin-top: 10px;
        font-size: 12px;
        color: #666666;
        line-height: 1.5;
        padding-bottom: 10px;
        .supplier-reply {
          position: relative;
          margin-top: 16px;
          padding: 9px 7px 9px 11px;
          background-color: #f9f9f9;
          color: #404040;
          font-size: 12px;
          line-height: 1.5;
          &:before {
            display: block;
            content: '';
            position: absolute;
            left: 22px;
            top: -6px;
            width: 12px;
            height: 12px;
            background-image: url('../../../assets/image/change-icon/comment_arrow.png');
            background-repeat: no-repeat;
            background-size: 100%;
          }
          span {
            font-weight: 600;
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
        width: 55px;
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
