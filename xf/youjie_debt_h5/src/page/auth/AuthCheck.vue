<template>
  <div class="auth-check">
    <template v-if="!isShowAgreement">
      <div class="form-container">
        <steps :step="2"></steps>
        <div class="tips">
          请输入您登记的个人信息,并确认《用户授权同意书》,授权有解债转信息平台获取您在指定机构的信息,以便进行身份验证及资产确权。
        </div>
        <div class="form-item">
          <label for="name">机构</label>
          <input type="text" readonly :value="authPlatInfo.name" />
        </div>
        <div class="form-item">
          <label for="name">真实姓名</label>
          <input type="text" placeholder="请输入您的真实姓名" name="name" v-model="name" @input="change('name')" />
        </div>
        <div class="form-item">
          <label for="idcard">证件类型</label>
          <select v-model="idType">
            <option v-for="(item, index) in ID_TYPE" :value="item.id" :key="index">{{ item.name }}</option>
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
            @input="change('IDCard')"
          />
        </div>
        <div class="form-item">
          <label for="credit">银行卡号</label>
          <input
            type="number"
            placeholder="请输入您已绑定的银行卡号"
            name="credit"
            v-model="credit"
            @input="creditInput"
          />
        </div>
      </div>
      <div class="footer-bottom">
        <dir class="rules">
          <input type="checkbox" id="rules" name="rules" v-model="checkFlag" />
          <label for="rules" class="input-icon"></label>
          <label for="rules" class="rules-msg">
            进行身份验证需要同意并知晓
            <span @click="toggleAgreement(true)">《用户授权同意书》</span>
          </label>
        </dir>
        <div class="line-btn">
          <button @click="submit" :class="{ disabled: !canSubmit }">授权并验证</button>
        </div>
      </div>
    </template>
    <template v-else>
      <!-- <mt-header class="header" title="授权协议">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="toggleAgreement(false)"></header-item>
      </mt-header> -->
      <check-agreement></check-agreement>
    </template>
    <!-- <serve-icon></serve-icon> -->
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
import { confirmAuth, authPlatform } from '../../api/auth'
import { getUser } from '../../api/mine'
import { ROUT_ARR, ID_TYPE } from './static'
import Steps from '../../components/common/Steps'
import ServeIcon from '../../components/common/ServeIcon'
import CheckAgreement from './child/CheckAgreement'
export default {
  name: 'AuthCheck',
  data() {
    return {
      ROUT_ARR,
      ID_TYPE,
      name: '',
      IDCard: '',
      credit: '',
      idType: 1,
      isLoading: false,
      checkFlag: false,
      isShowAgreement: false
    }
  },

  components: {
    ServeIcon,
    Steps,
    CheckAgreement
  },

  created() {},

  computed: {
    ...mapState({
      authPlatInfo: state => state.auth.authPlatInfo
    }),
    canSubmit() {
      return this.name && this.IDCard && this.credit && this.checkFlag && this.idType > 0
    }
  },

  methods: {
    ...mapMutations({
      savePlateInfo: 'savePlateInfo',
      saveAuthInfo: 'signin'
    }),
    creditInput() {
      this.credit = this.credit.replace(/\s+/g, '')
      if (this.credit.length > 20) {
        this.credit = this.credit.slice(0, 20)
      }
    },
    goBack() {
      this.isShowAgreement ? (this.isShowAgreement = false) : this.$router.push({ name: 'mine' })
    },

    change(name) {
      this[name] = this[name].replace(/\s+/g, '')
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
        platform_id: this.authPlatInfo.id,
        real_name: this.name,
        id_no: this.IDCard,
        // idType: this.idType,
        bank_card: this.credit
      }

      this.isLoading = true
      this.$loading.open()

      let p0 = confirmAuth(params)
      let p1 = authPlatform(this.authPlatInfo.id)
      let p2 = getUser()
      Promise.all([p0, p1, p2])
        .then(res => {
          if (!res[0].code && !res[1].code && !res[2].code) {
            this.savePlateInfo(res[2].data.bindPlatform[0])
            this.saveAuthInfo({ user: res[2].data.userInfo })

            this.$router.push({ name: 'AuthCheckResult' })
          } else {
            this.$toast(res[0].info || res[1].info || res[2].info)
          }
        })
        .finally(() => {
          this.isLoading = false
          this.$loading.close()
        })
    },

    toggleAgreement(flag) {
      this.isShowAgreement = flag
    }
  }
}
</script>

<style lang="less" scoped>
.auth-check {
  width: 100%;
  background: #fff;
}
.form-container {
  flex: 1;
  padding: 25px 15px;
  box-sizing: border-box;
  min-height: 510px;

  .tips {
    margin-top: 19px;
    text-align: justify;
    font-size: 12px;
    color: @themeColor;
  }
  .form-item {
    margin-top: 20px;
    display: flex;
    align-items: center;
  }
  label {
    white-space: nowrap;
    font-size: 15px;
    color: #666;
    margin-right: 15px;
    width: 60px;
    text-align: right;
  }
  input,
  select {
    -webkit-appearance: none; //禁用ios默认样式
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
.footer-bottom {
  flex-basis: 110px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 2px 24px 0;

  .line-btn {
    width: 100%;
    text-align: center;
    margin: 14px 0 30px 0;
    button {
      width: 327px;
      height: 46px;
      border: 0;
      color: #fff;
      background-color: @themeColor;
      border-radius: 2px;
      font-size: 16px;
      outline: none;
    }
    .disabled {
      opacity: 0.3;
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
      width: 12px;
      height: 12px;
      margin-right: 10px;
      background-size: 100%;
      background-repeat: no-repeat;
      background-position: center;
      border: 1px solid @themeColor;
      border-radius: 2px;
    }
    input {
      display: none;
      &:checked + label.input-icon {
        width: 14px;
        height: 14px;
        border: none;
        background-image: url('../../assets/image/confirmation/icon-checked-on2.png');
      }
      &:disabled + label.input-icon {
        visibility: hidden;
      }
    }
    .rules-msg {
      font-size: 12px;
      color: #999;
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
