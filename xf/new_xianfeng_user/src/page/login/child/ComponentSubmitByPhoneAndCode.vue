<template>
  <div class="wrapper">
    <div class="tel-input">
      <div class="tel-wrapper">
        <input type="number" v-model="tel" @input="checkTel"  />
      </div>
      <div class="vel-wrapper">
        <div class="input-wrp">
          <input type="number" v-model="vel" @input="checkVel" />
        </div>
        <div
          class="vel"
          v-if="!isCountDown || code_type"
          :class="{ deep: telIsOk && (!code_type || !isCountDown) }"
          @click="getSmsCode"
        >
          {{msgText}}
        </div>
        <div class="vel" v-if="isCountDown && !code_type">{{ countDown }}s</div>
      </div>
    </div>
     <van-checkbox v-model="checked" @click="changeAgreement" class="agreementcheckbox">
          <label>
            我已阅读并同意
            <span @click="$router.push({ path: 'serviceAgreement' })">《注册协议及隐私保护政策》</span>
          </label>
        </van-checkbox>
    
    <div class="waring">
      <p v-model='message'>{{message}}</p>
    </div>
    <div class="login">
      <button @click="submit" >{{ subBtnTxt }}</button>
    </div>
  </div>
</template>
<script>
import { mapState, mapMutations } from 'vuex'
import { getVelCode, getLogin } from '../../../api/user'
import { Toast } from "vant";

export default {
  name: 'componentSubmitByPhoneAndCode',
  data() {
    return {
      tel: process.env.NODE_ENV === 'production'?'':'13716970622',
      vel: process.env.NODE_ENV === 'production'?'':'999999', // 这个值是多少就登录UID为多少的用户
      isCountDown: false, //是否展示倒计时
      countDown: 60,
      telIsOk: process.env.NODE_ENV !== 'production', //是否为11位手机号码
      velIsOk: process.env.NODE_ENV !== 'production',
      code_type: 0, //验证码类型 0短信验证码 1语音验证码
      isOnce: true, //是否第一次获取短信验证码
      isVoiceOnce: true, //是否第一次获取语音验证码
      isVelGeting: false, //是否正在请求获取验证码接口，防止重复点击
      message:'',
      msgText:'获取验证码',
      checked: false,
      from:'',
    }
  },
  props: {
    getVelApi: {
      // 获取短信验证码接口
      type: Function,
      default: getVelCode
    },
    submitApi: {
      // 表单提交接口
      type: Function,
      default: getLogin
    },
    subBtnTxt: {
      type: String,
      default: '登录'
    },
  },
   created() {
    this.from = this.$route.query.from;
    
  },
  methods: {
    changeAgreement(){
      
      this.checked = this.checked;
      this.message = ''
    },
    // 验证手机号
    checkTel() {
      if (this.tel.length > 11) {
        this.tel = this.tel.slice(0, 11)
      }
      let telrule = /^1\d{10}$/
      let twrule = /^09\d{8}$/
      if (!telrule.test(this.tel) && !twrule.test(this.tel)) {
        this.telIsOk = false
        this.message = '请输入11位手机号'
      } else {
        this.telIsOk = true
        this.message = ''
      }
    },
    // 验证手机验证码
    checkVel() {
      if (this.vel.length > 6) {
        this.vel = this.vel.slice(0, 6)
      }
      let velrule = /^\d{6}$/
      if (velrule.test(this.vel)) {
        this.velIsOk = true
        this.message = ''
      } else {
        this.velIsOk = false
        this.message = '请输入6位数字验证码'
        return
      }
    },
    // 获取短信验证码
    getSmsCode() {
      this.code_type = 0
      this.getVel(this.code_type)
    },
    // 获取验证码
    getVel(is_voice) {
      if (!this.telIsOk) {
        return
      }

      if (this.isCountDown) {
        return
      }

      if (this.isVelGeting) {
        return
      }

      this.isVelGeting = true
      let params = {number:this.tel,from:this.from}
      this.getVelApi(params)
        .then(
          res => {
            if (res.code === 0) {
              this.isCountDown = true
              this.getCountDown(res.data.ttl)
            }else {
              Toast( res.info )
            }
          },
          error => {
            console.log(error)
          }
        )
        .finally(() => {
          this.isVelGeting = false
        })
    },
    //获取验证码倒计时
    getCountDown(num) {
      if (this.isCountDown) {
        let timer = setInterval(() => {
          this.countDown--
          if (this.countDown <= 0) {
            if (this.isOnce) this.isOnce = false
            this.isCountDown = false
            this.countDown = num
            clearInterval(timer)
          }
          this.msgText='重发验证码'
        }, 1000)
      }
    },
    submit() {

      if (!this.checked) {
        this.message = '请阅读并同意协议'
        return
      }
      if (!this.telIsOk) {
        this.message = '请输入11位手机号'
        return
      }
      if (!this.velIsOk) {
        Toast("请点击【获得验证码】，输入短信发送的验证码")
        return
      }
      this.login(this.tel, this.vel)
    },

    login(tel, val) {

      // Toast.loading({
      //   duration: 0, // 持续展示 toast
      //   forbidClick: true,
      // });
      this.submitApi({ number: tel, code: val,from:this.from})
        .then(
          res => {
             
            if (res.code === 1012) {
              this.isCountDown = false
              this.isVelGeting = false
            }
           
              
            if (res.code && res.code !== 0) {
              Toast(res.info)
            } else{
           
              if(this.from=='special_area'){
                if(res.data.auth_code){
                  return  window.location.href = 'https://shop.xfuser.com/pages/users/login/index?code='+res.data.auth_code;
                }else{
                  Toast({
                      duration: 5000, // 持续展示 toast
                      message: `礼包专区逐步开放中\n\n您暂时还不能访问礼包专区\n我们会尽快优化礼包化债方案\n\n即将为您跳转至用户中心`,  
                    }); 
                   setTimeout(() => {
                      this.$emit('submit-success', res)
                   }, 3000);
                }
              }else{
                  this.$emit('submit-success', res)
              }
            }
          },
          error => {
            Toast({
              message: error.errorMsg
            })
          }
        )
        .finally(() => {
          // setTimeout(() => {
          //  Toast.clear();
          // }, 1000);
        })
    }
  }
}
</script>
<style >
.van-checkbox__icon--checked .van-icon{
  color:#fff;
  background-color:#8d98db;
  border-color:#8d98db;
  }
</style>
<style lang="less" scoped>


 
  .van-checkbox { 
        margin-top: 20px;
        margin-left: 35px;
        label {
          font-size: 10px;
          color: rgb(146, 161, 177);
        }
        span {
          color: #FFF;
        }
       
      }
// 不同功能的手机号+验证码的UI样式可能会有差异，可以在prop传入自定义的fromClass，然后在下边的css文件中自定义相应的样式
@import './ComponentSubmitByPhoneAndCode.less';
</style>
