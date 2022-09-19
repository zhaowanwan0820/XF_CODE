<template>
  <div class="container">
    <template v-if="currentIndex == 1">
      <mt-header class="header" title="账户余额">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        <header-item slot="right" titleColor="#552E20" title="账单" v-on:onclick="goBalanceHistory"></header-item>
      </mt-header>
      <div class="content">
        <img src="../../assets/image/hh-icon/mlm/HK-account@2x.png" alt="" />
        <p class="title">可提现金额</p>
        <div class="money">
          <label>￥</label>
          <span>{{ getMoney }}</span>
        </div>
        <p class="frozen">冻结中: ￥{{ getFrozen }}</p>
        <div class="botton-wrapper">
          <gk-button class="button" type="primary-secondary" v-if="getMoney >= 0.1" v-on:click="goWithdraw"
            >立即提现</gk-button
          >
          <gk-button class="button disable" type="primary-secondary" v-else>立即提现</gk-button>
        </div>
        <p class="warn" @click="changeCurrent(2)">注意事项</p>
      </div>
    </template>
    <template v-if="currentIndex == 2">
      <tips v-on:click="changeCurrent(1)"></tips>
    </template>
  </div>
</template>
<script>
import { HeaderItem } from '../../components/common'
import { huanAccount } from '../../api/huanhuanke'
import { ENUM } from '../../const/enum'
import Tips from './child/Tips'

export default {
  name: 'HuankeAccount',
  data() {
    return {
      currentIndex: 1, // 展示内容; 1-佣金面板 2-注意事项

      user_money: 0, //账户佣金
      frozen_money: 0 //冻结金额
    }
  },

  components: {
    Tips
  },

  created() {
    this.getHuankeProfile()
  },
  computed: {
    getMoney() {
      return this.utils.formatFloat(this.user_money)
    },
    getFrozen() {
      return this.utils.formatFloat(this.frozen_money)
    }
  },
  methods: {
    getHuankeProfile() {
      huanAccount(ENUM.HUANKE_STATUS.ALL).then(
        res => {
          this.user_money = res.user_money
          this.frozen_money = res.frozen_money
        },
        error => {
          console.log(error)
        }
      )
    },
    goBack() {
      this.$_goBack()
    },
    goBalanceHistory() {
      this.$router.push({ name: 'HuankeBalanceHistory' })
    },

    changeCurrent(index) {
      this.currentIndex = index || 1
    },

    goWithdraw() {
      this.$router.push({ name: 'huankeWithdraw' })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  position: relative;
  flex-direction: column;
  justify-content: flex-start;
  background: #fff;
}
.header {
  @include header;
  @include thin-border();
  flex-basis: 44px;
  .header-close /deep/ .icon {
    width: 16px;
    height: 16px;
    margin-left: 5px;
  }
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  position: relative;
  img {
    width: 66px;
    height: 66px;
    margin-top: 73px;
  }
  .title {
    font-size: 16px;
    color: #404040;
    line-height: 22px;
    margin-top: 28px;
  }
  .money {
    display: flex;
    align-items: baseline;
    justify-content: center;
    label {
      font-size: 32px;
      font-weight: 600;
      color: #404040;
      line-height: 45px;
    }
    span {
      font-size: 52px;
      font-weight: bold;
      color: #404040;
      line-height: 61px;
    }
  }
  .frozen {
    margin-top: 27px;
    font-size: 14px;
    color: #999999;
    line-height: 20px;
  }
  .botton-wrapper {
    margin-top: 60px;
    button {
      @include button($margin: 0, $radius: 2px, $spacing: 1px);
      width: 327px;
      font-size: 18px;
      color: #fff;
    }
  }
  .warn {
    position: absolute;
    bottom: 40px;
    @include sc(14px, $deleteColor);
  }
}
</style>
