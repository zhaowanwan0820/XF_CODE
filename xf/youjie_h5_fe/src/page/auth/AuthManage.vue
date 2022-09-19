<template>
  <div class="container">
    <div class="header-container">
      <mt-header class="header" title="授权管理">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
    </div>
    <div class="manage-head">
      <img src="../../assets/image/hh-icon/auth/bg-authManage.png" alt="" />
    </div>
    <div class="step-wrapper">
      <div class="step-item" @click="goAuth">
        <div class="left">
          <img src="../../assets/image/hh-icon/auth/icon-first@2x.png" alt="" />
          <span>授权转让协议</span>
        </div>
        <div class="right" v-if="isAgree">
          <span class="done">已签署</span>
          <img src="../../assets/image/hh-icon/auth/icon-arrow-done.png" alt="" />
        </div>
        <div class="right" v-else>
          <span>去签署</span>
          <img src="../../assets/image/hh-icon/auth/icon-arrow.png" alt="" />
        </div>
      </div>
      <div class="step-item" @click="goFace">
        <div class="left">
          <img src="../../assets/image/hh-icon/auth/icon-second@2x.png" alt="" />
          <span>手持身份证照片</span>
        </div>
        <div class="right">
          <span>{{ authTxt }}</span>
          <img src="../../assets/image/hh-icon/auth/icon-arrow.png" alt="" />
        </div>
      </div>
      <div class="warn-msg" v-if="!authStatus && authStep == 2">
        提示：您上传的照片不符合规范，请重新上传
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { mapState, mapMutations } from 'vuex'
export default {
  name: 'AuthManage',
  data() {
    return {}
  },
  computed: {
    ...mapState({
      userId: state => state.auth.user.id,
      isAgree: state => state.itouzi.auth_agreement,
      authStatus: state => state.itouzi.authStatus,
      authStep: state => state.itouzi.authStep
    }),
    authTxt() {
      if (this.authStatus) {
        return '已授权'
      } else {
        return '未授权'
      }
    }
  },
  methods: {
    goAuth() {
      if (this.isAgree) return
      this.$router.push({ name: 'agreementPage', params: { from: 1 } })
    },
    goFace() {
      if (this.authStatus) return
      if (this.authStep == 1) return

      const appType = this.getAppType()
      let orderId = appType + new Date().getTime() + this.userId
      let url = 'yjmall://app_yd_identify?orderId=' + orderId
      url = encodeURIComponent(url)
      this.hhApp.openAppPage(url)
    },
    getAppType() {
      return window.navigator.userAgent.indexOf('YOUJIEMALL_IOS') > -1 ? 'IOS' : 'AND'
    },
    goBack() {
      this.hhApp.close()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  background: #fff;
  .manage-head {
    height: 66px;
    img {
      width: 100%;
    }
  }
  .step-wrapper {
    padding: 0 15px;
    .step-item {
      height: 55px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px dotted rgba(85, 46, 32, 0.2);
      .left {
        img {
          width: 37px;
          height: 15px;
          margin-right: 7px;
        }
        span {
          font-size: 15px;
          font-weight: 400;
          color: #333;
          line-height: 21px;
        }
      }
      .right {
        display: flex;
        align-items: center;
        span {
          font-size: 13px;
          color: #552e20;
          font-weight: 400;
          line-height: 18px;
          margin-right: 6px;
          &.done {
            color: #999;
          }
        }
        img {
          width: 6px;
          height: 6px;
        }
      }
    }
    .warn-msg {
      @include sc(11px, #9b210b, left);
      font-weight: 400;
      line-height: 16px;

      margin-top: 7px;
    }
  }
}
</style>
