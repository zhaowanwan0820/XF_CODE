<template>
  <div v-if="popupVisible" class="popup-wrapper">
    <mt-popup v-model="popupVisible">
      <div class="container">
        <div class="head">
          <div class="title">
            <h2><slot name="title"></slot>：</h2>
          </div>
          <div class="right-top">
            <img class="close" src="../../assets/image/hh-icon/plus-close.png" @click="close" />
            <img class="horn" src="../../assets/image/hh-icon/site-announce/horn@3x.png" />
          </div>
        </div>
        <div class="content">
          <slot></slot>
        </div>
        <div class="bottom"></div>
      </div>
    </mt-popup>
  </div>
</template>

<script>
import { mapMutations } from 'vuex'

// 网站公告 弹层
export default {
  name: 'SiteAnnouncement',
  data() {
    return {
      popupVisible: false
    }
  },

  created() {
    this.checkPopup()
  },

  computed: {
    dayStart() {
      const now = new Date()
      return new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime()
    },

    dayEnd() {
      return this.dayStart + 24 * 60 * 60 * 1000 - 1
    }
  },

  methods: {
    ...mapMutations({
      setIsTop: 'SET_IS_TOP'
    }),
    /**
     * 检测是否弹窗
     */
    checkPopup() {
      let popup_storage = (window.localStorage.getItem('h_p_l_sa') || '').split(',')

      // 当天没有弹过就弹出
      if (popup_storage.length > 0) {
        if (popup_storage[0] < this.dayStart || popup_storage[0] > this.dayEnd) {
          popup_storage = []
        }
      }

      // 弹过2次不再弹出
      if (popup_storage.length >= 2) {
        return
      }
      let now = new Date().getTime()
      let last = popup_storage[popup_storage.length - 1] || 0

      // 距离上次不足60分钟不弹出
      if (popup_storage.length > 0 && now - last <= 60 * 60 * 1000) {
        return
      }

      // 弹出并记录localStorage
      this.$nextTick(() => {
        this.popupVisible = true
        popup_storage.push(now)
        window.localStorage.setItem('h_p_l_sa', popup_storage)
      })
    },

    close() {
      this.popupVisible = false
    }
  }
}
</script>

<style lang="scss" scoped>
.popup-wrapper {
  .mint-popup {
    height: 66.4%;
    width: 81.33%;
    border-radius: 6px;
    transform: translate3d(-50%, -44.5%, 0);
  }
  .container {
    position: absolute;
    width: 100%;
    top: 0;
    left: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
  }
  .head {
    height: 56px;
    flex: 0 0 56px;
    background: url('../../assets/image/hh-icon/site-announce/container-bg-top@3x.png') no-repeat 0 0;
    background-size: contain;

    .horn {
      display: block;
      width: 83px;
      margin-top: 23px;
    }
    .title {
      padding: 26px 0 0 21px;
      h2 {
        font-weight: bold;
        font-size: 20px;
        color: rgba(101, 25, 21, 1);
        line-height: 26px;
      }
    }
    .right-top {
      position: absolute;
      right: 0;
      top: -85px;
      text-align: right;
      font-size: 0;

      .close {
        width: 30px;
        position: relative;
        margin-right: -7px;
      }
    }
  }
  .content {
    flex: 1 0 0;
    overflow-x: hidden;
    overflow-y: auto;

    padding: 15px 20px;
    font-size: 14px;
    color: rgba(64, 64, 64, 1);
    line-height: 26px;

    /deep/ p {
      text-align: left;
      // margin-bottom: 20px;
      &.t-main {
        text-indent: 2em;
      }

      &.t-r {
        margin-top: 10px;
        text-align: right;
      }
    }
  }
  .bottom {
    height: 56px;
    flex: 0 0 56px;
    background: url('../../assets/image/hh-icon/site-announce/container-bg-bottom@3x.png') no-repeat 0 0;
    background-size: contain;
  }
}
</style>
