<template>
  <div class="container">
    <!-- header -->
    <mt-header class="header" title="我的收藏">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <!-- body -->
    <div class="body" v-infinite-scroll="getMore" infinite-scroll-disabled="loading" infinite-scroll-distance="10">
      <mt-cell-swipe v-for="item in collectionList" :key="item.id" :right="rightBottom(item.id)">
        <div class="ui-collection-body" v-on:click="goOrderDetail(item.id)">
          <div class="ui-image-wrapper">
            <img
              class="collection-img"
              v-lazy="{
                src: item.thumb,
                error: require('../../assets/image/change-icon/default_image_02@2x.png'),
                loading: require('../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
              }"
            />
            <span class="wrapper-red" v-if="item.good_stock == 0">无货</span>
            <span v-if="item.good_stock > 0 && item.good_stock <= 10">仅剩{{ item.good_stock }}件</span>
          </div>

          <div class="flex-right">
            <h3 class="title" style="-webkit-box-orient:vertical">{{ item.name }}</h3>
            <div class="price">
              <span>￥{{ item.current_price }}</span>
            </div>
          </div>
        </div>
      </mt-cell-swipe>
      <!-- 表示是否还有更多数据的状态 -->
      <div class="loading-wrapper">
        <p v-if="!isMore && collectionList.length > 0">没有更多了</p>
        <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
      </div>
      <div v-if="!isMore && collectionList.length <= 0" class="order-air">
        <img src="../../assets/image/hh-icon/l0-list-icon/collect-list.png" />
        <p>暂无商品</p>
        <gk-button class="button" type="primary-secondary-white" v-on:click="goVisit">
          <label>随便逛逛</label>
        </gk-button>
      </div>
    </div>
  </div>
</template>

<script>
import { Button } from '../../components/common'
import { CellSwipe, MessageBox } from 'mint-ui'
import { productLikedList, productUnlike } from '../../api/product' //已收藏商品 //取消收藏商品
export default {
  data() {
    return {
      collectionList: [],
      orderListParams: { page: 0, per_page: 10 },
      loading: false,
      isMore: true
    }
  },
  created() {},
  methods: {
    rightBottom(productId) {
      return [
        {
          content: '删除',
          style: { background: '#FF3950', color: '#fff' },
          handler: () =>
            MessageBox({
              title: '确认删除',
              message: '是否要删除此商品？',
              showCancelButton: true
            }).then(action => {
              if (action === 'confirm') {
                this.getCancelCollection(productId)
              }
            })
        }
      ]
    },
    goBack() {
      this.$_goBack()
    },
    // 获取已收藏商品数据
    orderCollection(isFirst) {
      this.loading = true
      isFirst ? (this.orderListParams.page = 1) : (this.orderListParams.page += 1)
      let data2 = this.orderListParams
      productLikedList(data2.page, data2.per_page).then(
        res => {
          this.loading = false
          this.isMore = res.paged.more == 1 ? true : false
          if (isFirst) {
            this.collectionList = res.list
          } else {
            this.collectionList = [...this.collectionList, ...res.list]
          }
        },
        error => {}
      )
    },
    // 去商品详情
    goOrderDetail(orderId) {
      this.$router.push({ name: 'product', query: { id: orderId } })
    },
    // 取消收藏商品数据
    getCancelCollection(productId) {
      productUnlike(productId).then(res => {
        this.orderCollection(true)
      })
    },
    // 随便逛逛
    goVisit() {
      this.$router.push({ name: 'home' })
    },

    getMore() {
      if (this.loading) {
        return
      }
      if (!this.isMore) {
        return
      }
      this.orderCollection()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  background-color: #ffffff;
  position: relative;
  padding-top: 50px;
  box-sizing: border-box;

  .header {
    @include header;
    @include thin-border();
    // border-bottom: 1px solid #e8eaed;
    // padding: 13px 15px;/
    height: 50px;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 2;
  }
  .body {
    background-color: #ffffff;
    .ui-collection-body {
      display: flex;
      width: auto;
      align-items: center;
      justify-content: space-between;
      padding: 10px;
      position: relative;
      div.ui-image-wrapper {
        width: 100px;
        height: 100px;
        position: relative;
        margin-left: 2px;

        display: flex;
        justify-content: center;
        align-content: center;
        align-items: center;
        flex-basis: 100px;
        flex-shrink: 0;

        border-radius: 4px;

        img.collection-img {
          width: 100px;
          height: 100px;
          flex-basis: 100px;
          flex-shrink: 0;
          border-radius: 4px;
        }
        img.collection-img[lazy='loading'] {
          width: 30px;
          height: 30px;
        }
        img.collection-img[lazy='error'] {
          width: 30px;
          height: 30px;
        }
        img.collection-img[lazy='loaded'] {
          width: 100px;
          height: 100px;
          flex-basis: 100px;
          flex-shrink: 0;
          background: rgba(255, 255, 255, 1);
        }

        span {
          position: absolute;
          height: 20px;
          background: #f3f4f5;
          line-height: 20px;
          text-align: center;
          font-size: 14px;
          color: $primaryColor;
          width: 100px;
          border-radius: 0 0 4px 4px;
          bottom: 0;
          left: 0;
        }

        .wrapper-red {
          color: #fff;
          background: #000;
          opacity: 0.4;
        }
      }
      .flex-right {
        padding-left: 14px;
        width: 100%;
        height: 110px;
        .title {
          color: #333;
          font-size: 14px;
          line-height: 20px;
          font-weight: normal;

          display: -moz-box;
          display: -webkit-box;
          display: flex;

          -webkit-line-clamp: 2;
          -moz-line-clamp: 2;

          -moz-box-orient: vertical;
          -webkit-box-orient: vertical;
          box-orient: vertical;

          overflow: hidden;
          margin-top: 10px;
          margin-bottom: 8px;
        }
        .price {
          margin-bottom: 10px;
          position: absolute;
          bottom: 12px;
          width: 100%;
          span {
            &:first-child {
              color: $markColor;
              font-size: 15px;
              font-weight: bold;
            }
          }
        }
        .sendway {
          font-size: 12px;
          display: flex;
          align-items: center;
          span {
            color: #7c7f88;
            padding-left: 7px;
            &.self-support {
              font-size: 10px;
              color: $primaryColor;
              border: 1px solid $primaryColor;
              border-radius: 2px;
              width: 32px;
              height: 16px;
              line-height: 16px;
              text-align: center;
              padding: 3px;
            }
          }
          img {
            width: 22px;
            height: 20px;
          }
        }
      }
    }
    .order-air {
      width: 100%;
      vertical-align: middle;
      text-align: center;
      padding-bottom: 65px;
      img {
        width: 135px;
        box-sizing: border-box;
        margin: 60px auto 15px;
      }
      p {
        font-size: 18px;
        color: #666666;
        line-height: 25px;
        text-align: center;
      }
      .button {
        @include button($radius: 2px);
        width: 140px;
        margin-top: 40px;
      }
    }
    .loading-wrapper {
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 44px;
      p {
        color: #7c7f88;
        font-size: 12px;
        font-weight: 'Regular';
        padding: 0;
        margin: 0;
      }
      span {
        display: inline-block;
      }
    }
  }
}
</style>

<!--Cell Swipe样式覆盖 -->
<style>
.mint-cell-swipe-button {
  width: 60px;
  font-size: 14px;
  display: flex !important;
  justify-content: center;
  align-items: center;
  box-sizing: border-box;
}
.mint-cell-wrapper {
  padding: 0;
}

.mint-cell-wrapper .mint-cell-value {
  width: 100%;
  border-bottom: 1px solid rgba(232, 234, 237, 1);
}
</style>
