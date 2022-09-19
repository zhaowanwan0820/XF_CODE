<template>
  <div v-if="popupVisible" class="popup-wrapper">
    <mt-popup v-model="popupVisible" :closeOnClickModal="false">
      <div class="container">
        <div class="head"></div>
        <!-- 手机号 + 验证码提交表单 -->
        <form-submit-by-phone-and-code
          :getVelApi="getVelCode"
          :submitApi="submitApi"
          :subBtnTxt="'确　定'"
          v-on:submit-success="submitSuccess"
        ></form-submit-by-phone-and-code>
      </div>
      <img class="close" src="../../../assets/image/hh-icon/plus-close.png" @click="close" />
    </mt-popup>
  </div>
</template>
<script>
import { mapState, mapMutations } from 'vuex'
import { getVelCode, getLogin } from '../../../api/user'
import { ENUM } from '../../../const/enum'

import FormSubmitByPhoneAndCode from './ComponentSubmitByPhoneAndCode'

export default {
  data() {
    return {
      getVelCode: getVelCode,
      submitApi: getLogin,
      popupVisible: false
    }
  },
  computed: {
    ...mapState({
      popupBindPhoneShow: state => state.login.popupBindPhoneShow
    })
  },
  watch: {
    popupBindPhoneShow: function(val) {
      this.popupVisible = val
    }
  },
  components: {
    FormSubmitByPhoneAndCode
  },
  methods: {
    ...mapMutations({
      saveAuthInfo: 'signin',
      saveCurrentBondState: 'saveCurrentBondState',
      setPopupBindPhone: 'setPopupBindPhone',
      saveOpenId: 'saveOpenId'
    }),
    submitSuccess(res) {
      this.setPopupBindPhone(false)
      this.$parent.submitSuccess(res)
    },
    close() {
      this.setPopupBindPhone(false)
      this.saveOpenId('')
    }
  },
  beforeDestroy() {
    this.saveOpenId('')
  }
}
</script>
<style lang="scss" scoped>
.popup-wrapper {
  .mint-popup {
    height: 390px;
    width: 82.66%;
    transform: translate3d(-50%, -54.23%, 0);
    background: transparent;
  }
  .container {
    position: absolute;
    width: 100%;
    top: 0;
    left: 0;
    bottom: 0;

    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
  }
  .head {
    height: 102px;
    flex: 0 0 102px;
    background: url('../../../assets/image/hh-icon/login/bind-phone-head@3x.png') no-repeat 0 0;
    background-size: contain;
  }
  .close {
    position: absolute;
    width: 30px;
    height: 30px;
    left: 50%;
    bottom: -65px;
    transform: translateX(-50%);
  }

  /* 样式重写 */
  /deep/ .wrapper {
    padding: 33px 18px 0;

    .tel-wrapper,
    .vel-wrapper {
      border-radius: 2px;
      border: 1px solid rgba(244, 244, 244, 1);

      &:after {
        display: none;
      }
      input {
        background: none !important;
        text-indent: 10px;
        width: 100%;
      }
    }
    .vel-wrapper {
      padding: 2.667vw 0;
      margin-top: 20px;
      display: flex;
      justify-content: flex-start;
      align-items: center;

      .input-wrp {
        flex: 1 0 0;
      }
      .vel {
        width: 100px;
        flex: 0 0 100px;
        font-size: 14px;
      }
    }

    .login {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      bottom: 48px;

      button {
        width: 140px;
        height: 36px;
        margin: 0;
        font-size: 18px;
      }
    }
  }
}
</style>
