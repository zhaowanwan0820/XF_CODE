<template>
  <div class="container">
    <mt-header class="header" fixed title="兑换结果">
      <header-item slot="right" title="完成" v-on:onclick="goPrev" titleColor="#552E20"></header-item>
    </mt-header>
    <div class="wrapper">
      <div class="content">
        <template v-if="status == 2">
          <img class="icon" src="../../assets/image/hh-icon/b10-pay/pay-success@3x.png" />
          <label class="title">成功兑换 {{ account }} 个积分</label>
          <label class="subtitle">预计2分钟内发送至您的账户,请稍后查看</label>
        </template>
        <template v-else-if="status == 3">
          <img class="icon" src="../../assets/image/hh-icon/b10-pay/pay-fail@3x.png" />
          <label class="title">兑换失败</label>
        </template>
      </div>
      <gk-button class="button left-button" type="primary-secondary" v-on:click="goPrev">继续付款</gk-button>
    </div>
  </div>
</template>

<script>
import $cookie from 'js-cookie'
import { mapState } from 'vuex'
import { HeaderItem, Button } from '../../components/common'
import { Header, Toast, Indicator } from 'mint-ui'
import { bondResult } from '../../api/bond'
import { ENUM } from '../../const/enum'
export default {
  data() {
    return {
      status: -1,
      account: 0
    }
  },

  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    })
  },
  created() {
    if (this.isOnline) {
      let sso = this.$cookie.get('sso')
      Indicator.open()
      bondResult(sso)
        .then(
          res => {
            this.status = res.status
            if (res.status < 3) {
              this.account = res.account
              if (res.pay && res.pay.order) {
                this.goPay(res.pay.order)
              }
            }
          },
          error => {
            Toast(error.errorMsg)
            this.goPrev()
          }
        )
        .finally(() => {
          Indicator.close()
          this.$cookie.remove('sso', this.utils.getDomainA)
        })
    } else {
      this.goPrev()
    }
  },
  methods: {
    goPay(order) {
      // 这笔债权兑换顺便购买了某商品
      this.$cookie.remove('bondForm')
      this.$router.replace({ name: 'paySucceed', params: { order: order } })
    },
    goPrev() {
      let from
      if ((from = this.$cookie.get('bondForm'))) {
        this.$cookie.remove('bondForm')
        this.$router.replace(JSON.parse(from))
      } else {
        this.$router.replace('/profile')
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: #fff;
}
.header {
  @include header;
  @include thin-border($lineColor);
}
.wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.content {
  display: flex;
  flex-direction: column;
  text-align: justify;
  justify-content: center;
  align-items: center;
  margin-top: 150px;
  margin-bottom: 51px;
}
.icon {
  width: 60px;
  height: 60px;
}
.title {
  color: $baseColor;
  font-size: 20px;
  margin-top: 25px;
}
.subtitle {
  color: #b5b6b6;
  font-size: 14px;
  margin-top: 15px;
}
.button {
  @include button($margin: 23px 20px 28px, $radius: 2px);
}
</style>
