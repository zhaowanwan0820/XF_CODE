<template>
  <div class="dcfgvhbjn">
    <div class="top">债权转让协议</div>
    <div class="wrap">
      <div class="text pl10"></div>

      <div>原债权人（以下简称甲方）：</div>
      <div>身份证（护照）号码：</div>
      <div>新债权人（以下简称乙方）：</div>
      <div>身份证（护照）号码：</div>
      <br />
      <div>鉴于：</div>
      <div>
        甲方在【____】运营管理的（“【____】”，域名为【____】 ，以下简称平台）上对债务方【____】拥有合法债权，
        现甲方将其债权通过平台转让给乙方。双方达成如下协议：
      </div>
      <div>（一） 转让债权标的：</div>
      <div>原合同编号：__</div>
      <div>项目名称：__</div>
      <div>转让债权本金：人民币__元整</div>
      <div>债权年化利率(365 天/年)： __每年</div>
      <div>债权认购时间：__</div>
      <div>项目到期日：__</div>
      <div>还款方式：__</div>
      <div>（二） 债权转让价格：乙方按照甲方债权额度支付等额的【{{ platformName }}】的商城积分。</div>
      <div>（三）本债权自本合同签订之日起转移，甲方所对应的所有权利义务同时转移给乙方。</div>
      <div>（四）甲方因转让债权而产生的相关费用由平台代扣代缴。</div>
      <div>（五）乙方承继甲方债权后可以再转让给第三人。</div>
      <div>（六）甲乙双方签署本协议并办理相关手续均为各自真实意思表示并获得相关授权。</div>
      <div>
        （七）甲乙双方均确认本协议的签订、生效和履行以不违反中国的法律法规为前提。如果本协议中的任何一条或多条违反适用的法律法规，则该条将被视为无效，但该无效条款并不影响本协议其他条款的效力。
      </div>
      <div>（八） 本协议自各方签订起生效，双方如有异议均可向北京市【 朝阳区 】有管辖权的人民法院提起诉讼。</div>

      <div style="margin-top: 15px;"><span style="margin-right: 55px;">甲方：</span>乙方：</div>

      <div>合同签署日期： 年 月 日</div>
    </div>
  </div>
</template>

<script>
import CommonHeader from 'components/common/Header'
import { mapActions, mapGetters } from 'vuex'

export default {
  name: 'debt_agreement',
  components: {
    CommonHeader,
  },
  computed: {
    ...mapGetters({
      platformName: 'platformName',
      redirectUrl: 'redirectUrl',
    }),
  },
  mounted() {
    // appid: "666"
    // redirect_url: "https://m.youjiemall.com"
    // response_type: "code"
    this.changeEnv(this.$route.query)
  },
  methods: {
    ...mapActions({
      changeEnv: 'changeEnv',
    }),
    // onSubmit() {
    //   this.$router.replace({ name: 'login' })
    // },
    onCancel() {
      this.__goBack()
    },
  },
}
</script>

<style lang="scss" scoped>
.line {
  font-size: 13px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: rgba(51, 51, 51, 1);
  text-decoration: underline;
}
.pl30 {
  padding-left: 30px;
}
.pt15 {
  padding-top: 15px;
}
.c-title {
  height: 20px;
  font-size: 14px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: rgba(228, 21, 21, 1);
  line-height: 20px;
  margin: 15px 0;
  text-decoration: underline;
}
.t-title {
  font-size: 14px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: rgba(51, 51, 51, 1);
  margin: 20px 0;
}
.text {
  font-size: 14px;
  font-family: PingFangSC-Medium, PingFang SC;
  font-weight: 500;
  color: rgba(51, 51, 51, 1);
}
.dcfgvhbjn {
  width: 100%;
  font-size: 15px;
  font-family: PingFangSC-Light, PingFang SC;
  font-weight: 300;
  color: rgba(102, 102, 102, 1);
  line-height: 24px;
  letter-spacing: 1px;
  p {
    padding: 0;
    margin: 0;
  }
  .top {
    text-align: center;
    width: 100%;
    height: 40px;
    line-height: 40px;

    font-size: 16px;
    font-family: PingFangSC-Medium, PingFang SC;
    font-weight: 500;
    color: rgba(51, 51, 51, 1);
  }
  .wrap {
    margin: 15px 15px 0 15px;
  }
}
</style>
