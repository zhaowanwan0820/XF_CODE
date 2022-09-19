<template>
  <div class="wrapper">
    <div class="tel-input">
      <div class="card-wrapper">
        <input type="string" v-model="cardid" @input="checkCardNumber"  />
      </div>
      <div class="vel-wrapper">
        <div class="input-wrp">
          <input type="string" v-model="vel" @input="checkVel" />
        </div>
       
        <div class="captcha">        
          <img :src="imgcode" class="pictrue" @click="captchas"/>
        </div>
      </div>
    </div>
   
    
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
import { getCaptcha, cardLogin } from '../../../api/user'
import { Toast } from "vant";

export default {
  name: 'componentSubmitByPhoneAndCode',
  data() {
    return {
      cardid: process.env.NODE_ENV === 'production'?'':'411421198908201222',
      vel: process.env.NODE_ENV === 'production'?'':'', // 这个值是多少就登录UID为多少的用户
      captchaUrl:location.host === "m.xfuser.com"?"https://api.xfuser.com/apiService/Captcha/Login":"http://qa1api.xfuser.com/apiService/Captcha/Login",
      isCountDown: false, //是否展示倒计时
      countDown: 60,
      cardidIsOk: process.env.NODE_ENV !== 'production', //是否为11位手机号码
      velIsOk: process.env.NODE_ENV !== 'production',
      code_type: 0, //验证码类型 0短信验证码 1语音验证码
      isOnce: true, //是否第一次获取短信验证码
      isVoiceOnce: true, //是否第一次获取语音验证码
      isVelGeting: false, //是否正在请求获取验证码接口，防止重复点击
      message:'',
      msgText:'获取验证码',
      checked: false,
      from:'',
      imgcode: '',
    }
  },
  props: {
    getCaptcha: {
      // 获取短信验证码接口
      type: Function,
      default: getCaptcha
    },
    submitApi: {
      // 表单提交接口
      type: Function,
      default: cardLogin
    },
    subBtnTxt: {
      type: String,
      default: '借款人登录'
    },
  },
   created() {
    this.from = this.$route.query.from;
    this.captchas();
    
  },
  methods: {
    changeAgreement(){
      
      this.checked = this.checked;
      this.message = ''
    },
    captchas(){
      this.imgcode = this.captchaUrl+'?t='+Date.parse(new Date());
     },
    // 
    checkCardNumber() {
      if (this.cardid.length > 18) {
        this.cardid = this.cardid.slice(0, 18)
      }
      
      let twrule =  /^[a-zA-Z\d]+$/
      if ( !twrule.test(this.cardid)) {
        this.cardidIsOk = false
        this.message = '请输入证件号'
      } else {
        this.cardidIsOk = true
        this.message = ''
      }
    },
    // 验证验证码
    checkVel() {
      if (this.vel.length > 6) {
        this.vel = this.vel.slice(0, 6)
      }
      let velrule = /^[a-zA-Z\d]+$/
      if (velrule.test(this.vel)) {
        this.velIsOk = true
        this.message = ''
      } else {
        this.velIsOk = false
        this.message = '请输入图形验证码'
        return
      }
    },
   
    submit() {

      if (!this.cardidIsOk) {
        this.message = '请输入11位手机号'
        return
      }
      if (!this.velIsOk) {
        Toast("输入短信发送的验证码")
        return
      }
      this.login(this.cardid, this.vel)
    },

    login(cardid, val) {


      this.submitApi({ number: cardid, code: val})
        .then(
          res => {
             
            if (res.code === 1012) {
              this.isCountDown = false
              this.isVelGeting = false
            }
              
            if (res.code && res.code !== 0) {
              Toast(res.info)
              this.captchas();
            } else{
              this.$emit('submit-success', res)

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
