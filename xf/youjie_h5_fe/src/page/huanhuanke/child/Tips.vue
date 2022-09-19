<template>
  <div class="container">
    <mt-header class="header" title="注意事项">
      <header-item
        slot="left"
        isLeft
        :icon="require('../../../assets/image/hh-icon/detail/icon-close@3x.png')"
        class="header-close"
        v-on:onclick="closeClick"
      ></header-item>
    </mt-header>
    <div class="tips-content">
      <p>1、使用支付宝提现需要您已注册支付宝并在支付宝中实名认证过。</p>
      <p>2、支付宝账号是您注册支付宝的手机号或者邮箱号，您可登录支付宝查看。</p>
      <p>3、您的姓名和支付宝账号仅作为提现使用。</p>
      <p>4、每次提现时，将保留您上次提现的支付宝账号信息。您可直接提现到该账号，也可以编辑并填写新的支付宝账号。</p>
      <p>
        5、请认真核实提现支付宝账号，保证支付宝实名与账号信息准确。避免因账号输入有误，导致提现到他人账号而造成您的财产损失。
      </p>
      <p>6、可提现金额大于0.10元时才可提现。每笔提现金额将按照0.60%收取服务费，服务费最低0.10元/笔。</p>
      <p>7、如您在提现中遇到其他问题，请联系{{ utils.storeNameForShort }}客客服邮箱：{{ utils.hhkEmail }}。</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {}
  },

  methods: {
    closeClick() {
      this.$emit('click')
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  flex: 1;
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
.tips-content {
  flex: 1;
  flex-shrink: 0;
  padding: 20px 15px 0;
  background: #ffffff;
  overflow-y: auto;
  overflow-x: hidden;
  p {
    @include sc(12px, #666666);
    line-height: 21px;
    margin-bottom: 20px;
  }
}
</style>
