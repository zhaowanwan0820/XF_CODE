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
  </div>
</template>

<script>
import { header } from '../product-detail/static'
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
      index: state => state.detail.index
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
      border: 1px solid #552e20;
      color: #552e20;
      background-color: #fff;
      border-color: #552e20;
      &.navbar_active {
        color: #ffffff;
        border-color: #b58573;
        background: #b58573;
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
