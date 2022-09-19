<template>
    <div class="container">
        <div class="phone">
            <input type="text" v-model="form.phone" placeholder="请输入手机号" @input="getInput($event,11)">
        </div>
        <div class="code">
            <div class="code-input">
                <input type="text" v-model="form.code" placeholder="请输入验证码" @input="getInput($event,6)">
            </div>
            <div class="get-code" @click="get_Code">{{isCodeText}}{{sendAuthCode?'':' S'}}</div>
        </div>
        <div class="btn-is" :class="{btn:isShow}" @click="goToStep">下一步</div>
    </div>
</template>

<script>
  import { Toast } from 'vant';
  import { getCode } from '../../api/mine'
  export default {
    name: 'settphone',
    data () {
      return {
        form:{
          phone:'18612449932',
          code:''
        },
        isShow:false,
        isCodeText:'短信验证码',
        sendAuthCode:true
      }
    },
    computed: {},
    created() {},
    methods: {
      get_Code(){
        let that = this;
        let telrule = /^1[3456789]\d{9}$/;
        if (!telrule.test(that.form.phone)) {
          Toast("请输入正确手机号");
          return;
        }
        if(that.sendAuthCode){
          console.log(that.form.phone)
          that.getData({phone:that.form.phone});
          that.sendAuthCode = false;
          that.isCodeText = 60;
          that.auth_str =  setInterval(()=>{
            that.isCodeText-- ;
            if(that.isCodeText<=0){
              that.sendAuthCode = true;
              clearInterval(that.auth_str);
              that.isCodeText = '重新获取'
            }
          }, 1000);
        }
      },
      getData(params){
        getCode(params)
          .then(res=>{
            console.log(res)
            if(res.code == 1000){
              Toast(res.info);
              clearInterval(this.auth_str);
              this.sendAuthCode = true;
              this.isCodeText = '短信验证码'
            }else if(res.code == 0){
              Toast(`获取验证码成功`);
            }
          })
          .catch(err=>{

          })
      },
      getInput(e,n){
        if(n==11){
            if(e.target.value.length>=n){
              this.form.phone = e.target.value.substr(0,n)
            }
        }else if(n==6){
          this.isShow = true
          if(e.target.value.length>=n){
            this.form.code = e.target.value.substr(0,n)
          }
        }
      },
      goToStep() {
        this.$router.push({name:'settpass'})
      }
    }
  }
</script>

<style lang="less" scoped>
    .container{
        background-color: #fff;
        padding: 0 24px;
        .phone{
            margin-top: 24px;
            padding: 0 0 9px;
            position: relative;
            border-bottom: 1px dashed rgba(85, 46, 32, 0.2);
            input{
                width: 60%;
                margin-left: 24px;
                font-size:15px;
            }
            &::after{
                content:'';
                width: 21px;
                height: 20px;
                background: url("../../assets/image/mine/phone.png") no-repeat;
                background-size: cover;
                position: absolute;
                top: 2px;
                left: 0;
            }
        }
        .code{
            display: flex;
            align-items: center;
            margin-top: 24px;
            padding: 0 0 9px;
            position: relative;
            border-bottom: 1px dashed rgba(85, 46, 32, 0.2);
            .code-input{
                flex: 1;
                input{
                    font-size:15px;
                    margin-left: 24px;
                }
                &::after{
                    content:'';
                    width: 21px;
                    height: 20px;
                    background: url("../../assets/image/mine/passwoed.png") no-repeat;
                    background-size: cover;
                    position: absolute;
                    top: 2px;
                    left: 0;
                }
            }
            .get-code{
                width: 94px;
                height:21px;
                font-size:15px;
                font-family:PingFangSC-Regular,PingFang SC;
                font-weight:400;
                color:rgba(4,177,164,1);
                line-height:21px;
                padding-left: 20px;
                border-left: 1px dashed rgba(85, 46, 32, 0.2);
                text-align: center;
            }
        }
        .btn-is{
            margin-top: 106px;
            height:46px;
            line-height: 46px;
            text-align: center;
            background:rgba(4,177,164,0.3);
            border-radius:2px;
            font-size:18px;
            font-family:PingFangSC-Regular,PingFang SC;
            font-weight:400;
            color:rgba(255,255,255,1);
        }
        .btn{
            background:rgba(4,177,164,1);
            color:rgba(255,255,255,1);
        }
    }
    input{
        background:none;
        outline:none;
        border:none;
    }
    input:focus{
        border: none;
    }
    input::-webkit-input-placeholder {
        font-size:15px;
        font-family:PingFangSC-Light,PingFang SC;
        font-weight:300;
        color:rgba(204,204,204,1);
    }

</style>