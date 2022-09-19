<template>
  <div class="container">
    <mt-header class="header" title="签署协议">
      <header-item slot="left" v-bind:isClose="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <h2>{{ utils.storeName }}平台债权兑换积分服务协议</h2>
      <div class="agreement-content-container">
        <div class="agreement-content">
          <div class="agreement-people">
            <p>出借人/投资人（以下简称“甲方”）</p>
          </div>
          <div class="agreement-people">
            <p>电商服务平台（以下简称“乙方”或“{{ utils.storeName }}”）</p>
          </div>
          <section class="agreement-given">
            <p class="title">鉴于：</p>
            <p>
              甲方欲将其通过北京东方联合投资管理有限公司运营管理的网信普惠平台（域名：https://www.youjiemall.com/）和北京经讯时代科技有限公司运营管理的网信平台（域名：https://www.ncfwx.com/）（两个平台以下统称“网信平台”）拥有的债权转让给受让方，受让方以与转让债权相应的对价物-积分受让用户的债权；{{
              utils.storeName
              }}基于商品作为协助甲方和受让方双方实现权益购物的电商服务平台，接受甲方和受让方双方的授权委托，协助双方在债权转让过程中的兑换积分及后续交易等事宜。
            </p>
            <p>
              根据《中华人民共和国电商法》及相关法律法规规定，现就{{
              utils.storeName
              }}向用户提供债权兑换积分服务事宜，在平等自愿、诚实信用的基础下达成如下条款：
            </p>
            <p>
              一、鉴于本协议中所述的和本协议相关的内容，均详见甲方和受让方签署的《债权转让协议》（以下称“债权协议”）、{{
              utils.storeName
              }}和受让方签署的《积分发行和结算协议》、《委托销售服务协议》等。
            </p>
            <p>
              二、本协议是在甲方与受让方《债权转让协议》成立的基础上，由{{
              utils.storeName
              }}为协助用户与受让方的债转协议事宜而签署并适用的配套服务协议。为了更好的服务债权转让事宜，用户您授权并同意网信平台通过合法、安全的技术方式将您的个人信息对接于{{
              utils.storeName
              }}使用。{{ utils.storeName }}的使用用途仅限于本协议和相关协议的约定。
            </p>
            <p>
              三、为了债转工作的进行，甲方需在{{ utils.storeName }}平台开设账户，{{
              utils.storeName
              }}需同意用户使用其在网信平台注册的手机号码进行注册及后续验证流程。用户应当审慎保管、使用前述手机号码，通过前述手机号码及验证号码登录{{
              utils.storeName
              }}的，视为用户本人的登录行为。
            </p>
            <p>
              四、本协议中当用户登录{{ utils.storeName }}时授权并同意{{
              utils.storeName
              }}可获取甲方的信息类型，包括但不限于：<br />
              1、甲方个人信息（如：姓名、联系方式、电子邮箱等）；<br />
              2、用于甲方身份识别的信息（如：身份证号码、护照号码、驾驶证号码等）；<br />
              3、财务信息（如：XX账号、银行卡号/账号、拥有债权的金额、债权交易明细等）。
            </p>
            <p>
              五、甲方在{{ utils.storeName }}平台通过网络页面点击确认的方式接受本协议，并同意接受{{
              utils.storeName
              }}有关规则、页面展示、操作流程、公告或通知等，表示甲方允许{{
              utils.storeName
              }}通过相应程序协助执行债转双方的债权转让行为。
            </p>
            <p>
              六、受让方以相应的积分作为受让甲方债权的支付对价；故当甲方登录{{
              utils.storeName
              }}欲完成债转的过程中，受让方委托{{ utils.storeName }}告知用户，受让方欲用相应的积分购买甲方债权。
            </p>
            <p>
              七、甲方使用电子签章签署债权转让协议后，受让方应当根据本协议的约定，委托{{
              utils.storeName
              }}将和甲方转让债权金额相应的积分发送到甲方在{{ utils.storeName }}开设的账户内，供甲方自由使用。
            </p>
            <p>
              八、甲方自在{{
              utils.storeName
              }}开设的账户收到转让债权相应的积分时，视为受让方的支付义务已经充分、完整、及时地履行，同时意味着债权转让完成；甲方与受让方的债转完成与否不以用户是否用积分兑换商品为准。
            </p>
            <p>
              九、甲方可在{{ utils.storeName }}平台用积分进行支付，以此获得商品或者服务（统称：商品）；受让方委托{{
              utils.storeName
              }}平台对代售的商品标注可消费的积分，以便用户进行后续交易。
            </p>
            <p>
              十、用户在{{
              utils.storeName
              }}平台选择商品时以点击下单、填写订单信息的方式进行交易，并根据该商品的定价中积分价格部分，可用其持有的积分支付。其中：<br />
              1、甲方持有的积分不足支付的，用户可以在符合有解电商平台规则的情况下，再行自愿转让债权以获得新的积分。{{
              utils.storeName
              }}应协助甲方的债转行为以及再次兑换积分的行为以便足额支付；<br />
              2、甲方持有的积分不足支付的，且持有债权不足以再次完成债权转让的，用户可以根据{{
              utils.storeName
              }}的支付规则，另行采用现金+积分支付。<br />
            </p>
            <p>
              十一、用户同意并认可本协议约定的债权转让一经生效不可撤销，用户不得擅自退回积分。因用户未能消费或者没有消费积分，或者因在{{
              utils.storeName
              }}进行买卖、商品物流运输、商品质量等交易过程中所产生的纠纷，为新的法律关系纠纷。
            </p>
            <p>
              十二、{{
              utils.storeName
              }}受托协助完成甲方和受让方的债权转让过程中，甲方每次债转兑换积分，网信平台根据与{{
              utils.storeName
              }}签署的合作协议及在甲方授权的情况下，将用户的数据和信息的归档传输至{{ utils.storeName }}，以便{{
              utils.storeName
              }}根据实际数据协助甲方和受让人完成债权转让。
            </p>
            <p>
              十三、就本协议涉及的债权兑换积分及后续流程，{{
              utils.storeName
              }}会根据实际运营情况在其平台配注相应流程细节图解，作为对本协议内容的解释和附注，用户可自行前往了解。相关内容以本协议约定为准。
            </p>
            <p>
              十四、为了更好地为债权转让主体提供服务，甲方有义务按照{{ utils.storeName }}的平台软件要求操作及查收{{
              utils.storeName
              }}发出的指示信息，因甲方个人操作不当致使平台指令错误，由此产生的法律责任由甲方自行承担，{{
              utils.storeName
              }}对此不承担法律责任。
            </p>
            <p>
              十五、甲方及{{
              utils.storeName
              }}均应具有完全民事行为能力，双方实施本协议项下行为的意思表示真实，自愿，不具有欺诈、胁迫、乘人之危及损害国家、集体、他人的合法利益，亦不具有以合法形式掩盖非法的目的。
            </p>
            <p>
              十六、甲方及受让人同意并确认，其通过{{
              utils.storeName
              }}上各自账户操作的行为所产生的法律效果和法律责任归属于各自本身，其授权根据本协议及其他所采取的全部行动和措施的法律后果均归属于各自本身。
            </p>
            <p>
              十七、本协议约定，甲方同意将通过网络页面点击确认的方式签订本协议，并不以此为由拒绝履行本协议项下的义务。甲方、受让人及{{
              utils.storeName
              }}均认可本协议约定的签署方式、协议内容以及合同效力。
            </p>
            <p>
              十八、甲方、受让人、{{
              utils.storeName
              }}在履行本协议的过程中，如发生争议，应协商解决。协商不成的，任何一方均可诉至北京市朝阳区人民法院。如因本协议争议而发生诉讼，在诉讼期间本协议不涉及争议部分的条款仍须履行。
            </p>
            <p>
              十九、本协议为线上电子协议，{{
              utils.storeName
              }}平台应合法、有效的进行保存。本电子协议具有《中华人民共和国合同法》规定的法律效力。
            </p>
            <p>
              二十、本协议解释权归属乙方。
            </p>
          </section>
        </div>
        <div class="gradient bottom"></div>
      </div>
      <div class="button-wrapper">
        <button @click="goAuth">同意并继续</button>
      </div>
    </div>
  </div>
</template>
<script>
import { mapMutations, mapState } from 'vuex'
import { HeaderItem } from '../../components/common'
import { Toast, Indicator } from 'mint-ui'
import { saveAgreementForExchangeToken,subjectGet } from '../../api/user'
export default {
  name: 'agreementPage',
  data() {
    return {
      isFromAuthManage: false, //是否从授权管理页面进入
      params:{}
    }
  },
  beforeRouteEnter(to, from, next) {
    next(vm => {
      if (from['name'] === 'AuthManage') {
        vm.isFromAuthManage = true
      }
    })
  },
  created(){
    // this.getDebtInfo()
  },
  methods: {
    ...mapMutations({
      saveAgreement: 'saveAgreementForExchangeToken'
    }),
    goBack() {
      this.$_goBack()
    },
    goAuth() {
      Indicator.open()
      saveAgreementForExchangeToken()
        .then(res => {
          this.saveAgreement(true)
          this.goBack()
        })
        .finally(() => {
          Indicator.close()
        })
    },
    //获取用户债权信息
    // getDebtInfo(){
    //   subjectGet().then(res=>{
    //     console.log(res)
    //     this.params = {...res}
    //   })
    // }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  background: #fff;
}
div.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 20px;
  h2 {
    font-size: 19px;
    color: #000;
    line-height: 26px;
    margin-top: 50px;
    margin-bottom: 5px;
  }
  .button-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 40px;
    button {
      width: 327px;
      height: 46px;
      color: #fff;
      font-size: 18px;
      line-height: 25px;
      background-color: #772508;
      border-radius: 2px;
      border: none;
      outline: none;
    }
  }
}
.agreement-content-container {
  width: 100%;
  height: 394px;
  background: #ffffff;
  position: relative;
  display: flex;
  .gradient {
    position: absolute;
    box-sizing: border-box;
    width: 100%;
    &.top {
      height: 2rem;
      top: 0;
      background: linear-gradient(to top, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 1) 75%);
    }
    &.bottom {
      height: 1.5rem;
      bottom: 0;
      background: linear-gradient(to bottom, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 1) 100%);
    }
  }
}

.agreement-content {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  box-sizing: border-box;
  padding: 10px 8px 12px;
  line-height: 1.5;
  font-size: 0.7rem;
  text-align: justify;
  color: #888888;
  h2 {
    text-align: center;
    color: #333333;
    font-size: 1rem;
    margin-bottom: 0.5rem;
  }
  .agreement-people,
  .agreement-given {
    margin-bottom: 0.7rem;
  }
  .agreement-given {
    p {
      text-indent: 24px;
    }
    .title,
    .no-indent {
      text-indent: 0;
    }
  }
  section.content {
    margin-bottom: 0.7rem;
    h3.content-title {
      font-size: 0.75rem;
      color: #333333;
      margin-bottom: 0.7rem;
    }
    p {
      margin-bottom: 0.2rem;
    }
  }
  section.agreement-bottom {
    &.right-align {
      text-align: right;
      margin: 1rem 0;
    }
    .right-para {
      display: inline-block;
      text-align: left;
      padding-right: 0.5rem;
    }
    p {
      margin-bottom: 1rem;
      &.text-right {
        text-align: right;
      }
    }
  }
}

h3.title {
  font-size: 0.7rem;
  color: #333333;
  margin: 0.5rem 0 0.7rem;
}

section {
  .info,
  .son {
    text-indent: 1rem;
  }
}
.blod {
  font-weight: 600;
  color: #000;
}
</style>
