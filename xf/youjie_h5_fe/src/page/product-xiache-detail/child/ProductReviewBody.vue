<template>
  <div>
    <div class="list" v-if="reviewList.length" v-for="(item, index) in reviewList" :key="index">
      <div class="evaluation-title">
        <template v-if="item.hidden_name_is">
          <!-- 匿名默认头像 -->
          <img class="avatar trans" src="../../../assets/image/hh-icon/comment/icon-default-user.png" />
        </template>
        <template v-else>
          <!-- 非匿名头像 -->
          <img class="avatar" v-if="item.member.avatar" :src="item.member.avatar" />
          <img class="avatar trans" v-else src="../../../assets/image/hh-icon/comment/icon-default-user.png" />
        </template>
        <span class="evaluation-name">{{ item.format_user_name }}</span>
        <template v-for="(i, index) in star(item.goods_score)">
          <img src="../../../assets/image/hh-icon/comment/icon-star-list.png" class="star" alt="" />
        </template>
      </div>
      <div class="comment-data">
        <span>{{ item.add_time | convertTime }}</span>
        <span v-if="item.goods && item.goods.goods_attr"
          >{{ item.goods.goods_attr }}*{{ item.goods.goods_number }}</span
        >
      </div>
      <div class="evaluation-body">
        <!-- 评价部分 -->
        <p v-if="item.format_content">
          {{ item.format_content }}
        </p>
        <div class="img-wrapper" v-if="item.img_arr.length">
          <img
            v-for="(item0, item0Index) in item.img_arr"
            alt=""
            @click="showImagePopup(item, item0Index)"
            v-lazy="{
              src: item0,
              error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
              loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
            }"
          />
        </div>
        <!-- 追评部分 -->
        <div class="add-content" v-if="item.append">
          <p class="title">追加评价:</p>
          <p class="content" v-if="item.append.content">{{ item.append.content }}</p>
          <div class="img-wrapper" v-if="item.append.img_arr.length">
            <img v-for="item0 in item.append.img_arr" :src="item0" alt="" />
          </div>
        </div>
        <!-- 评论回复部分 -->
        <div class="repeat-wrapper" v-if="item.reply">
          <p>
            <span>{{ getRepayTitle(item.reply) }}：</span>
            {{ item.reply.content }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapState, mapMutations } from 'vuex'
export default {
  name: 'ProductReviewBody',
  props: ['reviewList'],
  methods: {
    ...mapMutations({
      setComments: 'setComments',
      resetComments: 'resetComments',
      saveImageType: 'saveImageType',
      setSwiperId: 'setSwiperId',
      setisPreviewPicture: 'setisPreviewPicture'
    }),
    getRepayTitle(item) {
      // comment_role 1官方 2掌柜回复
      let str
      switch (item.comment_role) {
        case 1:
          str = `${this.utils.storeNameForShort}官方`
          break
        case 2:
          str = '掌柜回复'
          break
        default:
          str = '掌柜回复'
      }
      return str
    },
    showImagePopup(item, index) {
      this.saveImageType('comments')
      let imgs = []
      item.img_arr.forEach((img, i) => {
        imgs[i] = {}
        imgs[i].large = img
      })
      let comments = {
        comment_id: item.comment_id,
        img_arr: imgs
      }
      this.setComments(comments)
      this.setSwiperId(index)
      this.setisPreviewPicture(true)
    },
    star(n) {
      if (n < 1) n = 5 //原始数据没有评分，默认展示五星
      let arr = []
      arr.length = n
      return arr
    }
  },
  beforeDestroy() {
    // 离开页面 清空comments 重置imagetype
    this.resetComments()
    this.saveImageType(null)
  }
}
</script>
<style lang="scss" scoped>
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
        color: $markColor;
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
</style>

<style lang="scss">
.ui-common-swiper {
  background-color: #ffffff;
  width: 100%;
  height: 270px !important;
  position: relative;
  .mint-swipe-items-wrap {
    .mint-swipe-item {
      text-align: center;
      overflow: hidden;
      img {
        height: 100%;
        width: auto;
      }
    }
  }
  .mint-swipe-indicators {
    div.mint-swipe-indicator {
      background: #efeff4;
      opacity: 1;
      &.is-active {
        background: $primaryColor;
      }
    }
  }
  .mint-swipe-indicators-diy {
    box-sizing: border-box;
    position: absolute;
    bottom: 15px;
    right: 15px;
    border: 1px solid #d9d8d8;
    border-radius: 1px;
    background-color: #ffffff;
    line-height: 20px;
    height: 20px;
    padding: 0 5px;
    @include sc(12px, #999999);
    span {
      @include sc(12px, #b75800);
      &.line {
        margin: 0 2px;
        color: #999999;
      }
    }
  }
}
</style>
