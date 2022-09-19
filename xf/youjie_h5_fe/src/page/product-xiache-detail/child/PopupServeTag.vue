<!-- Shopping.vue -->
<template>
  <mt-popup v-model="isShow" position="bottom" v-bind:close-on-click-modal="true" style="height: 78%;">
    <div class="popup-container">
      <div class="popup-header">
        <span>服务说明</span>
        <img src="../../../assets/image/hh-icon/icon-关闭.png" v-on:click="closePopup(false)" />
      </div>
      <div class="popup-body">
        <template v-for="item in serveTags">
          <div class="list" :class="`type-${item.type}`" :key="item.type">
            <div class="title">{{ item.title }}</div>
            <div class="desc">{{ item.desc }}</div>
          </div>
        </template>
      </div>
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { Toast, MessageBox, Button } from 'mint-ui'

export default {
  data() {
    return {
      isShow: false
    }
  },

  props: {
    isShowServeTag: {
      type: Boolean,
      default: false
    }
  },

  created() {},

  computed: {
    ...mapState({
      serveTags: state => state.detail.detailInfo.base_set
    })
  },

  mounted() {
    this.isShow = this.isShowServeTag
  },

  watch: {
    isShow(value) {
      this.saveServeTagPopupState(value)
    }
  },

  methods: {
    ...mapMutations({
      saveServeTagPopupState: 'saveServeTagPopupState'
    }),

    // 关闭购物车浮层
    closePopup(value) {
      this.saveServeTagPopupState(value)
    }
  }
}
</script>
<style lang="scss" scoped>
.popup-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  .popup-header {
    display: flex;
    justify-content: space-between;
    height: 50px;
    align-items: center;
    padding: 0 15px;
    @include thin-border();
    span {
      color: #404040;
      font-size: 14px;
    }
    img {
      width: 12px;
    }
  }
  .popup-body {
    flex: 1;
    padding: 0 0 15px;
    color: #999999;
    font-size: 13px;
    overflow-y: auto;
    overflow-x: hidden;
    .list {
      padding: 12px 15px 15px 30px;
      &:before {
        display: block;
        width: 22px;
        height: 22px;
        position: absolute;
        content: '';
        left: 15px;
        top: 12px;
        background-size: 100%;
        background-repeat: no-repeat;
        background-position: center;
      }
      // &.type-1:before {
      //   background-image: url('../../../assets/image/hh-icon/detail/serve-type-1.png');
      // }
      // &.type-2:before {
      //   background-image: url('../../../assets/image/hh-icon/detail/serve-type-2.png');
      // }
      // &.type-3:before {
      //   background-image: url('../../../assets/image/hh-icon/detail/serve-type-3.png');
      // }
      // &.type-4:before {
      //   background-image: url('../../../assets/image/hh-icon/detail/serve-type-4.png');
      // }
      @include thin-border(#dbdbdb, 15px, auto, true);
      .title {
        line-height: 22px;
        font-size: 14px;
        font-weight: bold;
        color: #404040;
      }
      .desc {
        margin-top: 5px;
        @include sc(12px, #999999);
        line-height: 1.5;
      }
    }
  }
}
</style>
