<template>
  <div class="container page-debt-agreement">
    <div class="content">
      <div class="mainBody" v-html="content"></div>
    </div>
    <div class="footer-bottom">
      <div class="line-btn">
        <van-button type="primary" :block="true" @click="next">知道了</van-button>
      </div>
    </div>
  </div>
</template>

<script>
import { saveDebtAgreement } from '../../api/user'
import { mapState, mapMutations } from 'vuex'

import { DEBT_AGREEMENT_DEMO } from './debt_agreement_demo'

export default {
  data() {
    return {
      checkedRead: false
    }
  },
  computed: {
    
    content() {
      return DEBT_AGREEMENT_DEMO
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
      this.$_goBack()
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
    .van-button {
      border-radius: 7px;
        border: 0;
      display: block;
      width: 100%;
      height: 46px;
    }
  }
}
</style>
