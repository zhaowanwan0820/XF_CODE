<template>
  <div class="auth-check">
    <template v-if="!isShowAgreement">
      <div class="header-container">
        <mt-header class="header" title="身份验证">
          <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        </mt-header>
      </div>
      <div class="form-container">
        <steps :step="2"></steps>
        <div class="tips">
          <p>为保证用户信息安全，有解需要对您的身份进行验证。</p>
          请输入您在该机构登记的个人信息，并确认《用户享受确权信息服务授权许可书》，授权有解获取您在指定机构的信息，以便进行身份验证及资产确权。
        </div>
        <div class="form-item">
          <label for="name">机构</label>
          <input type="text" readonly :value="ROUT_ARR[ognztionId - 1]" />
        </div>
        <div class="form-item" v-if="isCompty">
          <label for="name">企业名称</label>
          <input type="text" placeholder="请输入您的真实姓名" name="name" v-model="name" @blur="blur('name')" />
        </div>
        <div class="form-item" v-else>
          <label for="name">真实姓名</label>
          <input type="text" placeholder="请输入您的真实姓名" name="name" v-model="name" @blur="blur('name')" />
        </div>
        <div class="form-item">
          <label for="idcard">证件类型</label>
          <select v-model="idType">
            <option value="8" v-if="isCompty">统一社会信用代码</option>
            <option v-else v-for="(item, index) in ID_TYPE" :value="item.id" :key="index">{{ item.name }}</option>
          </select>
        </div>
        <div class="form-item">
          <label for="idcard">证件号</label>
          <input
            type="text"
            placeholder="请输入您的证件号"
            name="idcard"
            v-model="IDCard"
            maxlength="18"
            @blur="blur('IDCard')"
          />
        </div>
        <div class="form-item">
          <label for="credit">银行卡号</label>
          <input type="number" placeholder="请输入您已绑定的银行卡号" name="credit" v-model="credit" @input="creditInput"/>
        </div>
        <div class="form-item">
          <p @click="goToForget" :style="num==2?'color:#999':''">{{isText}} <img src="../../assets/image/hh-icon/auth-tt.png" alt=""> <span>{{text}}</span></p>
        </div>
      </div>
      <div class="footer-bottom">
        <dir class="rules">
          <input type="checkbox" id="rules" name="rules" v-model="checkFlag" />
          <label for="rules" class="input-icon"></label>
          <label for="rules" class="rules-msg">
            进行身份验证需要同意并知晓
            <span @click="toggleAgreement(true)">《用户享受确权信息服务授权许可书》</span>
          </label>
        </dir>
        <div class="line-btn">
          <button @click="submit" :class="{ disabled: !canSubmit }">授权并验证</button>
        </div>
      </div>
    </template>
    <template v-else>
      <mt-header class="header" title="授权协议">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="toggleAgreement(false)"></header-item>
      </mt-header>
      <div class="agreement-content">
        <div class="mainBody">
          <div class="title">用户享受确权信息服务授权许可书</div>
          <p>
            用户享受确权信息服务授权许可书（以下简称“授权书”）是债权持有方（以下简称“用户”或“您”）接受有解信息服务平台（以下简称“有解”）提供的债权互联网管理信息技术服务（包括但不限于：用户债权确权服务、用户债权管理服务、用户债权交易服务、用户债权转消费服务等）签订的个人授权许可书。
          </p>
          <p>
            在本授权书中用户授权有解获取您在指定机构的个人相关数据信息以及授权有解协助您在本平台后续业务实操中签订其它协议或文书。
          </p>
          <p>
            1、用户在有解平台通过网络页面点击确认的方式接受本授权书，并同意接受本授权书的全部约定内容及有关的各项规则、操作流程、信息获取等内容。
          </p>
          <p>
            2、用户接受本授权书后，表示有解可与指定理财平台就用户的数据信息进行技术对接。有解可获取的信息数据应依托于用户登记提交的个人信息。有解不得私自获取其它未授权用户的信息，否则将承担相应的法律责任。
          </p>
          <p>
            3、用户授权有解获取的信息数据包含但不限于： （1）用户个人信息（如：姓名、联系方式、电子邮箱等） <br />
            （2）用户身份识别信息（如：身份证号码、护照号码、驾驶证号码等） <br />
            （3）用户债权信息（如：理财平台账号、银行卡号、债权项目名称、债权总额、债权协议编号、债权还本付息方式等）。
            <br />
            （4）其它与有解业务有关的必要信息数据（数据范围不得超过有解向用户提供的业务范围）。 <br />
          </p>
          <p>
            4、有解依据用户授权获取的信息数据的使用范围仅限于提供服务的范围内。禁止有解私自泄露、利用、倒卖用户信息数据或实施与服务范围无关的经营行为。
          </p>
          <p>
            5、用户承诺并保证：上述授权行为是基于用户已充分了解授权书内容及产生的法律后果后，由用户本人所进行的自愿、合法的行为，不存在非本人所为、遭第三人仿冒、盗用之情形。用户将承担因授权行为产生的法律责任及后果。
          </p>
          <p>
            6、有解承诺并保证：在为用户提供债权确权、管理、消费、转让等服务之前，均已通过合法有效方式或手段与各服务业务板块经营主体达成有效合法的合作基础或取得充分授权（该授权范围能够满足有解为用户提供服务的合理使用范围）。
          </p>
          <p>
            7、有解和用户均承诺其提供的信息及相关资料符合中华人民共和国相关法律法规之规定，具备真实性与合法性且不侵犯任何第三方权益。
          </p>
          <p>
            8、有解在依据您登记提交的信息在指定机构获取您的信息数据后将向您进行确权。您应当依据诚实信用的原则对您的个人债权信息数据进行确权。
          </p>
          <p>
            9、用户的确权行为系单方民事行为。用户债权信息一经确权不可撤销、更改。因客观原因（例如：有解系统原因、不可抗力等情况）导致用户确权结果存在错误的，用户应在24小时内与有解工作人员取得联系，并对此问题提供相关证据进行协商解决。因用户原因延迟解决产生的损害由用户独立承担。
          </p>
          <p>
            10、用户确权成功后，有解将依据确权后的债权信息数据提供服务。因您自身原因导致确认的债权存在错误致使有解提供的服务存在错误的，有解对此不承担任何责任，由用户承担。
          </p>
          <p>
            11、有解为了更好的向用户提供服务，您有义务按照有解的要求操作平台软件以及查收有解发出的指示信息，因您个人原因操作不当致使平台指令错误，由此产生的纠纷及法律责任由用户承担，有解对此免责。
          </p>
          <p>
            12、用户授权并同意：有解有权对本授权书内容进行单方面的变更，并在平台网站页面进行通知。若您在本授权书内容变更生效后继续使用本服务的，表示您已充分阅读、理解并接受变更修改后的授权书内容，也将遵循变更修改后的授权书内容；若您不同意变更修改后的授权书内容，您应立即停止使用本服务。
          </p>
          <p>
            13、用户应认真阅读、充分理解本授权书各条款及对有解具有或可能具有免责或限制责任的内容。如您不同意接受本授权书的任意内容，或者无法准确理解相关条款含义的，请您不要进行后续操作。
          </p>
          <p>14、本文件解释权归属于有解。若发生争议，协商解决，解决不成可诉至北京市朝阳区人民法院。</p>
          <p class="bottom">授权人：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br />被授权人：有解</p>
        </div>
      </div>
    </template>
    <serve-icon></serve-icon>
  </div>
</template>

<script>
import { confirmAuth, identity } from '../../api/auth'
import { getAuthStatus } from '../../api/auth'
import { ROUT_ARR, ID_TYPE } from './static'
import Steps from '../../components/common/Steps'
import ServeIcon from '../../components/common/ServeIcon'
import { mapMutations } from 'vuex'
import {BankRecordGet} from '../../api/bankCard'
export default {
  name: 'AuthCheck',
  data() {
    return {
      ognztionId: this.$route.query.id,
      ROUT_ARR,
      ID_TYPE,
      name: '',
      IDCard: '',
      credit: '',
      idType: 1,
      isLoading: false,
      checkFlag: false,
      isShowAgreement: false,
      isCompty: false,
      text:'',
      num:'',
      isText:'忘记卡号'
    }
  },

  components: {
    ServeIcon,
    Steps
  },

  created() {
    if (!this.ognztionId) {
      this.goBack()
    }
    identity().then(res => {
      console.log(res)
      if (res == 1) {
        this.isCompty = true
        this.idType = 8
        return
      }
    })
    this.getBankCrad()
  },
  computed: {
    canSubmit() {
      return this.name && this.IDCard && this.credit && this.checkFlag && this.idType > 0
    }
  },

  methods: {
    ...mapMutations({
      saveWxAuthCheckInfo: 'saveWxAuthCheckInfo'
    }),
    creditInput() {
      this.credit = this.credit.replace(/(^\s*)|(\s*$)/g, '')
      if (this.credit.length > 20) {
        this.credit = this.credit.slice(0, 20)
      }
    },
    goBack() {
      this.$_goBack()
    },
    //查询用户银行卡状态 status	1无申请 2已提交未审核 3已拒绝 4已审核
    getBankCrad(){
      BankRecordGet().then(res=>{
        console.log(res,typeof res)
        this.num = res
        switch (res) {
          case 1:
            this.text = "";
            break;
          case 2:
            this.text = "（审核中）";
            break;
          case 3:
            this.text = "（已拒绝）";
            break;
          case 4:
            this.text = "（已审核）";
            break;
        }
      })
    },

    change(name) {
      this[name] = this[name].replace(/\s+/g, '')
    },
    blur(name) {
      this[name] = this[name].replace(/(^\s*)|(\s*$)/g, '')
    },
    submit() {
      if (this.isLoading) {
        return
      }

      if (this.idType === 0) {
        return this.$toast('请选择证件类型')
      }

      // 添加了其他证件类型，暂不做身份证校验
      // if (!this.utils.checkIDCard(this.IDCard)) {
      //   return this.$toast('您输入的身份证号格式有误，请核对后再试！')
      // }

      const params = {
        name: this.name,
        idNumber: this.IDCard,
        idType: this.idType,
        card: this.credit
      }

      this.isLoading = true

      confirmAuth(params)
        .then(res => {
          this.isLoading = false
          if (res.status) {
            this.saveAuth()
          } else {
            this.$toast(res.info)
          }
        })
        .catch(err => {
          this.isLoading = true
        })
    },
    saveAuth() {
      getAuthStatus().then(res => {
        this.saveWxAuthCheckInfo(res)
        this.$router.push({ name: 'AuthCheckResult' })
      })
    },

    toggleAgreement(flag) {
      this.isShowAgreement = flag
    },

    goToForget(){
      if(this.num != 2){
        this.$router.push({name:'forgetbankcard',query:{is:'true'}})
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.auth-check {
  width: 100%;
  min-height: 100%;
  background: #fff;
  display: flex;
  flex-direction: column;
}
.header {
  @include thin-border();
}
.form-container {
  flex: 1;
  padding: 15px;
  box-sizing: border-box;
  .tips {
    margin-top: 19px;
    text-align: justify;
    @include sc(12px, #fc7f0c);
    div {
      text-align: center;
    }
  }
  .form-item {
    margin-top: 20px;
    display: flex;
    align-items: center;
    p{
      display: flex;
      align-items: center;
      margin-left: 84px;
      height:18px;
      font-size:13px;
      font-family:PingFang-SC-Medium,PingFang-SC;
      font-weight:500;
      color:rgba(250,128,10,1);
      line-height:18px;
      img{
        width: 5px;
        height: 9px;
        margin-left: 10px;
      }
      span{
        height:17px;
        font-size:12px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(64,64,64,1);
        line-height:17px;
      }
    }
  }
  label {
    @include sc(15px, #666666);
    margin-right: 15px;
    width: 65px;
    text-align: right;
  }
  input {
    box-shadow: 0 0 0 0.5px #dddddd;
    box-sizing: border-box;
    border: none;
    padding: 10px;
    height: 40px;
    font-size: 15px;
    flex: 1;
    &::-webkit-input-placeholder {
      color: #cccccc;
    }
    &:-moz-placeholder {
      color: #cccccc;
    }
    &::-moz-placeholder {
      color: #cccccc;
    }
    &:-ms-input-placeholder {
      color: #cccccc;
    }
  }
  select {
    box-shadow: 0 0 0 0.5px #dddddd;
    box-sizing: border-box;
    border: none;
    padding: 10px;
    height: 40px;
    font-size: 15px;
    background-color: #ffffff;
    flex: 1;
    option {
      background-color: #ffffff;
      width: 100%;
      border: none;
    }
  }
}
.agreement-content {
  flex: 1;
  position: relative;

  .mainBody {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    padding: 15px 24px 35px;
    overflow-y: auto;
  }
  .title {
    text-align: center;
    @include sc(16px, #404040);
    font-weight: 500;
  }
  p {
    @include sc(14px, #666666);
    line-height: 1.5;
    padding-top: 15px;
    text-align: justify;
    &.bottom {
      text-align: right;
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
}
.footer-bottom {
  flex-basis: 138px;
  padding: 2px 24px 0;

  .line-btn {
    width: 100%;
    text-align: center;
    margin-bottom: 20px;
    button {
      width: 327px;
      height: 46px;
      border: 0;
      color: #fff;
      background-color: $primaryColor;
      border-radius: 2px;
      font-size: 16px;
      margin-top: 15px;
      outline: none;
    }
    .disabled {
      background: rgba(252, 127, 12, 0.3);
      pointer-events: none;
    }
  }
  .rules {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin: 0;

    label.input-icon {
      display: inline-block;
      @include wh(14px, 14px);
      margin-right: 10px;
      background-size: 100%;
      background-repeat: no-repeat;
      background-position: center;
      background-image: url('../../assets/image/hh-icon/icon-checkbox.png');
    }
    input {
      display: none;
      &:checked + label.input-icon {
        background-image: url('../../assets/image/hh-icon/icon-checkbox-active.png');
      }
      &:disabled + label.input-icon {
        visibility: hidden;
      }
    }
    .rules-msg {
      @include sc(12px, #999999);
      span {
        color: #404040;
      }
    }

    &.notvisible {
      visibility: hidden;
    }
  }
}
</style>
