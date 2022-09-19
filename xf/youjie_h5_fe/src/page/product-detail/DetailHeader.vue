<!-- DetailHeader.vue -->
<template>
  <div
    class="ui-detail-header"
    :class="{ fixed: headerBackgroundOpacite == 1 }"
    :style="{ backgroundColor: 'rgba(255, 255, 255, ' + headerBackgroundOpacite + ')' }"
  >
    <div class="left-goback" @click="goBack">
      <img
        src="../../assets/image/hh-icon/icon-header-返回.svg"
        class="back-no-bg"
        v-if="headerBackgroundOpacite == 1"
      />
      <img
        src="../../assets/image/change-icon/back_bg@3x.png"
        :style="{ opacity: 1 - headerBackgroundOpacite }"
        v-else
      />
    </div>
    <div class="navbar-wrapper" :style="{ opacity: headerBackgroundOpacite }">
      <div v-for="(item, key) in data" :class="{ navbar_active: key == index }" @click="changeEvent(key)" :key="key">
        <span class="name">{{ item.name }}</span>
      </div>
    </div>
    <!-- <div
      v-if="isInHHApp && headerBackgroundOpacite < 1 && !isHidden"
      class="right-share"
      :style="{ opacity: 1 - headerBackgroundOpacite }"
      @click="share"
      v-stat="{ id: 'common_product_info_share' }"
    >
      <img src="../../assets/image/change-icon/icon-share@3x.png" />
    </div> -->
  </div>
</template>

<script>
import { header } from './static'
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {
      data: header,
      scrollEle: null,
      isInHHApp: false
    }
  },

  computed: {
    ...mapState({
      index: state => state.detail.index,
      isHidden: state => state.detail.detailInfo.is_hidden,
      goodName: state => state.detail.detailInfo.name,
      goodPhotothumb: state => state.detail.detailInfo.thumb,
      shareUrl: state => state.detail.detailInfo.share_url
    })
  },

  props: {
    headerBackgroundOpacite: Number,
    prodDetailOfstHt: Number
  },

  created() {
    this.isInHHApp = this.isHHApp
  },

  mounted() {
    this.scrollEle = document.querySelector('.ui-detail-swiper')
  },

  methods: {
    ...mapMutations({
      changeIndex: 'changeIndex'
    }),

    changeEvent(index) {
      if (!this.scrollEle) {
        this.scrollEle = document.querySelector('.ui-detail-swiper')
      }
      this.changeIndex(index)
      if (index == 1) {
        this.scrollEle.scrollTop = this.prodDetailOfstHt
      } else {
        this.scrollEle.scrollTop = 0
      }
    },

    goBack() {
      this.$_goBack()
    },

    share() {
      this.hhApp.share(
        '万物有本则生，事事有道则解',
        this.utils.getShareImage(),
        'all',
        'wx-productDetal-share',
        this.utils.storeName,
        encodeURIComponent(this.shareUrl),
        '商品分享'
      )
    }
  }
}
</script>

<style lang="scss">
.ui-detail-header {
  padding: 0 9px;
  height: 50px;
  // border-bottom: 0.5px solid rgba(232,234,237,1);
  color: #55595f;
  font-size: 15px;
  width: auto;
  display: flex;
  justify-content: center;
  align-content: center;
  align-items: center;
  flex-basis: auto;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;

  .left-goback,
  .right-share {
    width: 31px;
    height: 31px;
    cursor: pointer;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    img {
      width: 100%;
      height: 100%;
      &.back-no-bg {
        width: 9px;
        height: auto;
      }
    }
  }
  .left-goback {
    left: 9px;
  }
  .right-share {
    right: 9px;
  }
  &.fixed {
    @include thin-border(#eaebec, 0);
    // div.navbar-wrapper {
    //   display: flex;
    // }
  }
  div.navbar-wrapper {
    display: flex;
    // display: none;
    div {
      width: 70px;
      text-align: center;
      line-height: 30px;
      border: 1px solid #b75800;
      color: #b75800;
      background-color: #fff;
      border-color: #b75800;
      &.navbar_active {
        color: #ffffff;
        border-color: #fc7f0c;
        background: #fc7f0c;
      }
      &:first-child {
        border-right-width: 0;
        border-top-left-radius: 2px;
        border-bottom-left-radius: 2px;
      }
      &:last-child {
        border-left-width: 0;
        border-top-right-radius: 2px;
        border-bottom-right-radius: 2px;
      }
      &:focus {
        outline: none;
      }
    }
  }
}
</style>
