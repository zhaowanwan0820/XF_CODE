<template>
  <div class="container">
    <div class="setting-row">请设置6位数字的交易密码</div>
    <div class="form-box">
      <div class="form-box-left">交易密码</div>
      <div class="form-box-right">
        <input
          type="number"
          placeholder="请输入6位数字密码"
          v-model="form.pay_password"
          @input="getInput($event, 6, 'p')"
        />
      </div>
    </div>
    <div class="form-box">
      <div class="form-box-left">确认密码</div>
      <div class="form-box-right">
        <input
          type="number"
          placeholder="请再次输入6位数字密码"
          v-model="form.confirm_pay_password"
          @input="getInput($event, 6, 'd')"
        />
      </div>
    </div>
    <div class="warning">{{ isShow ? '两次输入的密码不一致' : '' }}</div>
    <div class="btn" @click="submit">确认</div>
  </div>
</template>

<script>
import { Toast } from 'vant'
import { getPassword } from '../../api/mine'
export default {
  name: 'settpass',
  data() {
    return {
      form: {
        pay_password: '',
        confirm_pay_password: ''
      },
      isShow: false
    }
  },
  methods: {
    getInput(e, n, j) {
      this.isShow = false
      if (j == 'p') {
        if (e.target.value.length >= n) {
          this.form.pay_password = e.target.value.substr(0, n)
        }
      } else if (j == 'd') {
        if (e.target.value.length >= n) {
          this.form.confirm_pay_password = e.target.value.substr(0, n)
        }
      }
    },
    submit() {
      this.isShow = false
      if (this.form.pay_password != this.form.confirm_pay_password) {
        this.isShow = true
        Toast(`密码输入不一致`)
        return false
      }
      getPassword(this.form)
        .then(res => {
          console.log(res)
          if (res.code == 0) {
            Toast(`设置支付密码成功`)
            this.$store.commit('updatedUserPwdStatus', 1)
          } else {
            Toast(res.info)
          }
          this.$router.push({ name: 'setting' })
        })
        .catch(err => {})
    }
  }
}
</script>

<style lang="less" scoped>
.container {
  background-color: #fff;
  padding: 0 18px;
  .setting-row {
    height: 17px;
    font-size: 12px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(51, 51, 51, 1);
    line-height: 17px;
    margin: 11px 0 25px;
  }
  .form-box {
    display: flex;
    align-items: center;
    margin-top: 20px;
    .form-box-left {
      width: 60px;
      font-size: 15px;
      font-family: PingFangSC-Light, PingFang SC;
      font-weight: 300;
      color: rgba(51, 51, 51, 1);
    }
    .form-box-right {
      flex: 1;
      height: 40px;
      line-height: 40px;
      border: 1px solid rgba(221, 221, 221, 1);
      margin-left: 9px;
      input {
        font-size: 16px;
        margin-left: 11px;
      }
    }
  }
  .warning {
    height: 21px;
    font-size: 15px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(4, 177, 164, 1);
    line-height: 21px;
    margin-left: 69px;
    margin-top: 12px;
  }
  .btn {
    height: 46px;
    background: rgba(4, 177, 164, 1);
    border-radius: 2px;
    font-size: 18px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(255, 255, 255, 1);
    line-height: 46px;
    text-align: center;
    margin-top: 85px;
  }
}
input {
  background: none;
  outline: none;
  border: none;
}
input:focus {
  border: none;
}
input::-webkit-input-placeholder {
  font-size: 16px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: rgba(204, 204, 204, 1);
}
</style>
