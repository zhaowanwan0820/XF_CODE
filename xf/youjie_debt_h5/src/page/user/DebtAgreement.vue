<template>
  <div class="container page-debt-agreement">
    <div class="content">
      <div class="mainBody" v-html="content"></div>
    </div>
    <div class="footer-bottom">
      <van-checkbox v-model="checkedRead" shape="square" checked-color="#FC810C">我已阅读并知晓该协议内容</van-checkbox>
      <div class="line-btn">
        <van-button type="primary" :disabled="!checkedRead" :block="true" @click="next">同意并继续</van-button>
      </div>
    </div>
  </div>
</template>

<script>
import { saveDebtAgreement } from '../../api/user'
import { mapState, mapMutations } from 'vuex'

import { DEBT_AGREEMENT } from './const'

export default {
  data() {
    return {
      checkedRead: false
    }
  },
  computed: {
    ...mapState({
      authAgreement: state => state.auth.authAgreement
    }),
    content() {
      return DEBT_AGREEMENT
    }
  },
  created() {
    if (this.authAgreement) {
      this.checkedRead = true
    }
  },
  methods: {
    ...mapMutations({
      saveAgreement: 'saveDebtAgreement'
    }),
    next() {
      if (!this.authAgreement) {
        this.$loading.open()
        saveDebtAgreement()
          .then(res => {
            if (res.data.agree_status == 1) {
              this.saveAgreement(true)
              this.$_goBack()
            } else {
              this.$toast(res.info)
            }
          })
          .finally(() => {
            this.$loading.close()
          })
      }
    }
  }
}
</script>

<style lang="less">
.container.page-debt-agreement {
  position: relative;
  height: 100%;
  background: rgba(255, 255, 255, 1);

  display: flex;
  justify-content: flex-start;
  flex-direction: column;

  .content {
    flex: 1;
    position: relative;

    .mainBody {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      box-sizing: border-box;
      padding: 5px 35px 35px;
      overflow-y: auto;
    }
    h3 {
      text-align: center;
      font-size: 20px;
    }
    h4 {
      margin-top: 20px;
      font-size: 16px;
    }
    p {
      font-size: 12px;
      font-weight: 400;
      color: rgba(112, 112, 112, 1);
      line-height: 17px;
      padding-top: 15px;

      .em {
        font-weight: bold;
      }
    }

    &:after {
      content: '';
      position: absolute;
      z-index: 2;
      left: 0;
      bottom: 0;
      width: 100%;
      height: 35px;
      background: linear-gradient(180deg, hsla(0, 0%, 100%, 0), hsla(0, 0%, 100%, 0.8) 70%, #fff);
    }

    .warm-notice {
      font-weight: bold;
      margin-bottom: -10px;
    }
  }
  .footer-bottom {
    flex-basis: 120px;
    padding: 2px 24px 0;

    .line-btn {
      padding-top: 15px;
    }
    .button {
      display: block;
      width: 100%;
      height: 46px;
    }
  }
}
</style>
