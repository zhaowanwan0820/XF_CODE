<template>
  <main class="agreement">
    <header>
      <h1>债权转让协议</h1>
      <small>编号：</small>
    </header>
    <section class="agreement-user">
      <p>原债权人（以下简称甲方）：</p>
      <p>身份证（护照）号码：</p>
      <p>新债权人（以下简称乙方）：</p>
      <p>身份证（护照）号码：</p>
    </section>
    <section>
      <p>鉴于：</p>
      <p>
        甲方在<span />运营管理的（“<span />”，域名为<span />，以下简称平台）上对转让债权标的拥有合法债权，现甲方将其债权通过平台转让给乙方，双方达成如下协议：
      </p>
    </section>
    <section>
      <h2>（一）转让债权标的：</h2>
      <p>
        本协议下的转让标的内容由基础合同约定，详情参见原债权协议。基础合同作为本协议附件，由原交易撮合平台（网信普惠平台）提供数据保全服务。为方便批量操作，本协议相关原合同请参见附件。
      </p>
    </section>
    <section>
      <h2>（二） 债权转让价格：乙方按照甲方债权额度支付等额的《商城》的<span class="underscore" />积分。</h2>
    </section>
    <section>
      <h2>（三） 本债权自本合同签订之日起转移，甲方所对应的所有权利义务同时转 移给乙方。</h2>
    </section>
    <section>
      <h2>（四） 甲方因转让债权而产生的相关费用由平台代扣代缴。</h2>
    </section>
    <section>
      <h2>（五） 乙方承继甲方债权后可以再转让给第三人。</h2>
    </section>
    <section>
      <h2>（六） 甲乙双方签署本协议并办理相关手续均为各自真实意思表示并获得相关授权。</h2>
    </section>
    <section>
      <h2>（六） 甲乙双方签署本协议并办理相关手续均为各自真实意思表示并获得相关授权。</h2>
    </section>
    <section>
      <h2>
        （八）为确保公平公正、效率原则实现，在确认甲乙双方知情前提下，进行批量合同管理，本协议下的转让标的内容由基础合同约定，基础合同作为本协议附件，详情可见，合同效力视为一致。
      </h2>
    </section>
    <section>
      <h2>（九） 本协议自各方签订起生效，双方如有异议均可向北京市【 朝阳区 】 有管辖权的人民法院提起诉讼。</h2>
    </section>
    <footer class="flex">
      <div>
        <label>甲方：</label>
      </div>
      <div>
        <label>乙方：</label>
      </div>
    </footer>
    <footer>
      <p class="tr">合同签署日期：<span />年 <span />月<span />日</p>
      <div>
        附件：
      </div>
      <div>
        原合同详情列表：
      </div>
    </footer>
  </main>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'

export default {
  name: 'debt_agreement_v2',
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
main {
  .tr {
    text-align: right;
  }
  padding: 10px;
  color: #666;

  header {
    text-align: center;
  }

  h2 {
    font-size: 14px;
  }

  p {
    font-size: 14px;
  }

  span {
    display: inline-block;
    vertical-align: bottom;
    width: 100px;

    &.underscore {
      border-bottom: 1px #666 solid;
    }
  }

  .flex {
    display: flex;
    div {
      flex: 1;
    }
  }

  footer {
    margin-top: 40px;
    div {
      min-height: 60px;
    }
    span {
      display: inline-block;
      vertical-align: bottom;
      width: 35px;
    }
  }
}
</style>
