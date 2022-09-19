<template>
    <div class="container">
        <div class="form-box">
            <div class="form-box-left">原密码</div>
            <div class="form-box-right">
                <input type="number" placeholder="请输入原6位数字密码" v-model="form.old_pay_password" @input="getInput($event,6,'old_pay_password')">
            </div>
        </div>
        <div class="form-box">
            <div class="form-box-left">新密码</div>
            <div class="form-box-right">
                <input type="number" placeholder="请在此输入6位数字的新密码" v-model="form.new_pay_password" @input="getInput($event,6,'new_pay_password')">
            </div>
        </div>
        <div class="form-box">
            <div class="form-box-left">确认密码</div>
            <div class="form-box-right">
                <input type="number" placeholder="请在此再次输入6位数字的新密码" v-model="form.confirm_pay_password" @input="getInput($event,6,'confirm_pay_password')">
            </div>
        </div>
        <div class="btn" @click="submit">确认</div>
    </div>
</template>

<script>
  import { Toast } from 'vant';
  import {editPassword} from '../../api/mine'
  export default {
    name: 'EditPassword',
    data () {
      return {
        form:{
          old_pay_password:'',
          new_pay_password:'',
          confirm_pay_password:''
        }
      }
    },
    computed: {},
    created() {},
    methods: {
      getInput(e,n,f){
        if(e.target.value.length>=n){
          this.form[f] = e.target.value.substr(0,n)
        }
      },
      submit(){
        if(this.form.password != this.form.ispassword){
          Toast(`密码输入不一致`);
          return false;
        }
        editPassword(this.form)
          .then(res=>{
            console.log(res)
            if(res.code == 0){
              Toast(`修改支付密码成功`);
              this.$router.go(-1);
            }else {
              Toast(res.info);
            }
          })
          .catch(err=>{

          })
      }
    }
  }
</script>

<style lang="less" scoped>
    .container{
        background-color: #fff;
        padding: 53px 18px 0;
        .form-box{
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            .form-box-left{
                width: 60px;
                font-size:15px;
                font-family:PingFangSC-Light,PingFang SC;
                font-weight:300;
                color:rgba(51,51,51,1);
                text-align: right;
            }
            .form-box-right{
                flex: 1;
                height:40px;
                line-height: 40px;
                border:1px solid rgba(221,221,221,1);
                margin-left: 9px;
                input{
                    width: 90%;
                    font-size:16px;
                    margin-left: 11px;
                }
            }

        }
        .btn{
            height:46px;
            background:rgba(4,177,164,1);
            border-radius:2px;
            font-size:18px;
            font-family:PingFangSC-Regular,PingFang SC;
            font-weight:400;
            color:rgba(255,255,255,1);
            line-height:46px;
            text-align: center;
            margin-top: 85px;
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
        font-size:16px;
        font-family:PingFangSC-Regular,PingFang SC;
        font-weight:400;
        color:rgba(204,204,204,1);
    }
</style>