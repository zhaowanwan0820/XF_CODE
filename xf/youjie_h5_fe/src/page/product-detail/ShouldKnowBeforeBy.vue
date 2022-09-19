<template>
  <div id="shouldKnownBeforePage" class="container">
    <mt-header class="header" :title="title">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="mainBody" v-html="content"></div>
    </div>
    <div class="footer-bottom">
      <dir :class="{ rules: true, notvisible: hasCheckedBefore }">
        <input type="checkbox" id="rules" name="rules" v-model="checkedRead" />
        <label for="rules" class="input-icon"></label>
        <label for="rules" class="rules-msg">我已知晓，下次不再显示</label>
      </dir>
      <div class="line-btn">
        <gk-button class="button" type="primary-secondary" @click="next">我已阅读并同意</gk-button>
      </div>
    </div>
  </div>
</template>

<script>
import { Indicator } from 'mint-ui'
import { saveHasRead } from '../../api/product'
import { mapState, mapMutations } from 'vuex'

import { rules } from './mustRead/config'

export default {
  data() {
    return {
      ruleId: this.$route.query.ruleId,
      from: this.$route.query.from ? this.$route.query.from : '',
      checkedRead: false
    }
  },
  computed: {
    ...mapState({
      user: state => state.auth.user
    }),
    currentRule() {
      return rules.find(item => item.id == this.ruleId)
    },
    hasCheckedBefore() {
      // 是否之前已经勾选 【已知晓不再显示】
      const read_marker = this.user.read_marker || []
      return read_marker.indexOf(this.currentRule['keyInDb']) !== -1
    },
    content() {
      return this.currentRule.template
    },
    title() {
      return this.currentRule.tempTitle
    }
  },
  methods: {
    ...mapMutations({
      saveCheck: 'saveCheckShouldKnowBeforeBy'
    }),
    next() {
      this.beforeNext().then(() => {
        if (this.from == 'checkout') {
          this.goBack()
        } else {
          if (this.from == 'cartList') {
            //from 购物车
            this.$router.replace({ name: 'checkout', params: { isCart: true } })
          } else {
            this.$router.replace({ name: 'checkout' })
          }
        }
      })
    },
    goBack() {
      this.$_goBack()
    },
    beforeNext() {
      return new Promise((resolve, reject) => {
        if (this.checkedRead && !this.hasCheckedBefore) {
          Indicator.open()
          saveHasRead(this.currentRule.keyInDb).then(res => {
            Indicator.close()
            this.saveCheck(this.currentRule.keyInDb)

            resolve()
          })
        } else {
          resolve()
        }
      })
    }
  }
}
</script>

<style lang="scss">
#shouldKnownBeforePage {
  position: relative;
  height: 100%;
  background: rgba(255, 255, 255, 1);

  display: flex;
  justify-content: flex-start;
  flex-direction: column;

  .header {
    @include header;
    @include thin-border();
    height: 50px;
    flex-basis: 50px;
  }
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

    .sea-notice {
      font-weight: bold;
      font-size: 14px;
      padding-top: 20px;
    }
    .icecream-notice {
      font-weight: bold;
      font-size: 14px;
      padding-top: 25px;
    }
    .warm-notice {
      font-weight: bold;
      margin-bottom: -10px;
    }
  }
  .footer-bottom {
    flex-basis: 138px;
    padding: 2px 24px 0;

    .line-btn {
      padding-top: 25px;
    }
    .button {
      display: block;
      width: 100%;
      height: 46px;
    }
  }
  .rules {
    display: flex;
    align-items: center;
    padding: 0;
    margin: 0;

    label.input-icon {
      display: inline-block;
      @include wh(14px, 14px);
      background-size: 100%;
      border: 1px solid #b89385;
      border-radius: 1px;
      background-color: #ffffff;
      margin-right: 10px;
    }
    input {
      display: none;
      &:checked + label.input-icon {
        background-color: #772508;
        background-image: url('../../assets/image/hh-icon/icon-checkbox-active.png');
      }
      &:disabled + label.input-icon {
        visibility: hidden;
      }
    }
    .rules-msg {
      font-size: 12px;
      color: #666666;
    }

    &.notvisible {
      visibility: hidden;
    }
  }
}
</style>
