<template>
  <!-- 服务联系方式 -->
  <mt-popup v-model="isShowServicePopup" position="bottom">
    <div class="pop-container">
      <div class="title">
        <p>联系方式</p>
        <img src="../../../assets/image/hh-icon/detail/icon-close@3x.png" @click="closeServicePopup" alt="" />
      </div>
      <div class="content">
        <a
          :href="'tel:' + (isIos ? '//' : '') + detailInfo.supplier.service_tel"
          v-if="detailInfo.supplier.service_tel"
          class="serviceType-wrapper"
        >
          <div class="content-line">
            <div class="content-left">
              <p class="content-title">客服电话</p>
              <p class="content-num">{{ detailInfo.supplier.service_tel }}</p>
            </div>
            <div class="content-right">
              <img src="../../../assets/image/hh-icon/detail/icon-tel@3x.png" alt="" />
            </div>
          </div>
        </a>

        <a
          :href="'mqq://im/chat?chat_type=wpa&uin=' + detailInfo.supplier.service_qq + '&version=1&src_type=web'"
          class="serviceType-wrapper"
        >
          <div class="content-line" v-if="detailInfo.supplier.service_qq">
            <div class="content-left">
              <p class="content-title">客服QQ</p>
              <p class="content-num">{{ detailInfo.supplier.service_qq }}</p>
            </div>
            <div class="content-right">
              <img src="../../../assets/image/hh-icon/detail/icon-qq@3x.png" alt="" />
            </div>
          </div>
        </a>

        <div class="content-none" v-if="!detailInfo.supplier.service_tel && !detailInfo.supplier.service_qq">
          <p>该商家未提供客服联系方式</p>
        </div>
      </div>
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { Toast, MessageBox, Button } from 'mint-ui'
export default {
  name: '',
  data() {
    return {
      isIos: false
    }
  },

  props: ['isShowServicePopup'],

  created() {
    this.isIos = 1 == this.utils.getOpenBrowser() ? true : false
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    })
  },

  methods: {
    ...mapMutations({
      saveServicePopupState: 'saveServicePopupState'
    }),
    closeServicePopup() {
      this.saveServicePopupState(false)
    }
  }
}
</script>

<style lang="scss" scoped>
.mint-popup-bottom {
  height: 440px;
  .pop-container {
    // padding: 0 15px;
    .title {
      height: 50px;
      padding: 0 15px;
      border-bottom: 1px dotted #d8d8d8;
      display: flex;
      align-items: center;
      justify-content: space-between;
      p {
        font-size: 14px;
        line-height: 20px;
        color: #404040;
        margin: 0;
      }
      img {
        width: 14px;
        height: 14px;
      }
    }
    .content {
      padding: 0 15px;

      .serviceType-wrapper {
        display: block;
        text-decoration: none;
      }

      .content-line {
        height: 85px;
        border-bottom: 1px dotted #d8d8d8;
        display: flex;
        justify-content: space-between;
        align-items: center;
        p {
          font-size: 13px;
          line-height: 20px;
        }
        .content-title {
          color: #999;
          margin-bottom: 5px;
        }
        .content-num {
          color: #333;
          margin: 0;
        }
        img {
          width: 30px;
          height: 30px;
        }
      }
      .content-none {
        height: 85px;
        display: flex;
        justify-content: space-between;
        align-items: center;

        p {
          margin: 0;
        }
      }
    }
  }
}
</style>
