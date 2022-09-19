<template>
    <div class="container">
        <mt-header class="header" fixed :title="title">
            <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        </mt-header>
        <div class="bankcard">
            <div class="bankcard-box" >
                <h1>网信</h1>
                <div class="no-box" @click="goToService(false)" v-if="!bankName">
                    <img src="../../assets/image/hh-icon/jia-tt.png" alt="">
                    <p>添加银行卡</p>
                </div>
                <div class="bankcard-box-count" v-else>
<!--                    <img src="../../assets/image/hh-icon/f0-profile/cmbc.png" alt="" >-->
                    <div class="bank-count-name">
                        <h4>{{bankName}}</h4>
                        <p>**** **** **** **** {{card}}</p>
                    </div>
                    <div class="bank-btn" @click="goToService(true)">解绑</div>
                </div>

            </div>

        </div>
        <mt-popup class="mint-popup" v-model="showPopup" position="center">
            <div class="content">
                <div class="mint-msgbox-header">
                    <div class="mint-msgbox-title">请输入交易密码，以验证身份。</div>
                </div>
                <div class="mint-msgbox-content">
                    <div class="mint-msgbox-input">
                        <div class="set" v-show="codeNon">
                            您还未设置交易密码请先
                            <span class="goset" @click="goSetcode">去设置 >></span>
                        </div>
                        <div class="tel-wrapper">
                            <span class="tabName">交易密码</span><input type="password" v-model="params.transactionPassword" @input="checkPas" />
                            <div class="vel" @click="goSetcode" v-show="!codeNon">忘记密码?</div>
                        </div>
                        <!--<div class="mint-msgbox-errormsg" v-show="errorshow">{{ errorHost }}</div>-->
                    </div>
                </div>
                <div class="mint-msgbox-btns">
                    <button class="mint-msgbox-btn mint-msgbox-confirm" @click="submit">确 定</button>
                </div>
            </div>
        </mt-popup>
    </div>
</template>

<script>
  import {getbankCard} from '../../api/mineLoan'
  import { getDetail, getCode, getVelCode, submitPlan, checkCode, checkPass } from '../../api/newplane'
  import {UnbindPost,UserBankGet,UserPawPost} from '../../api/bankCard'
  import { Toast,MessageBox  } from 'mint-ui'
  export default {
    name: 'siBankCard',
    data() {
      return {
        title: '银行卡信息',
        bankName:'',
        card:'****',
        showPopup:false,
        codeNon:false,
        params:{
          transactionPassword: '', //交易密码
        },
        isTase:false
      }
    },
    created() {
      this.getbank()
      this.getCodes()
    },

    methods: {
      goBack() {
        this.$_goBack()
      },
      goToBank(){
        this.$router.push({ name: 'bankcardadd',params:{title:1} })
      },
      getbank(){
        UserBankGet().then(res=>{
          // console.log(res)
          console.log(res.data == '' || res.data == undefined)
          if(res == '' ){
            this.bankName = ''
            this.card = ''
          }else {
            this.bankName = res.bankzone
            this.card = res.bankcard
          }
        }).catch(err=>{
            console.log(err)
          })
      },
      goToService(n){
        this.isTase = n
        // this.$router.push({name:'service'})
        if(this.codeNon){
          MessageBox({
            title: '提示',
            message: '您未设置交易密码，请先去设置',
            showCancelButton: true
          }).then(action => {
            this.$router.push({ name: 'transPwdSet', query: { pas_agree: this.type } })
          });
        }else{
          this.showPopup = true
        }
      },
      getCodes() {
        // 判断是否已设置交易密码
        getCode().then(res => {
          if (res.transactionPassword == '0') {
            this.codeNon = true
          }
        })
      },
      goSetcode() {
        // alert('跳转设置交易密码')
        this.$router.push({ name: 'transPwdSet', query: { pas_agree: this.type } })
      },
      checkPas() {
        if (this.params.transactionPassword.length > 6) {
          this.params.transactionPassword = this.params.transactionPassword.slice(0, 6)
        }
        let velrule = /^\d{6}$/
        if (velrule.test(this.params.transactionPassword)) {
          // this.velIsOk = true
        } else {
          return
        }
      },
      submit(){
        if(this.isTase){
            UnbindPost(this.params).then(res=>{
              if(res.status == 200){
                if(this.bankName){
                  Toast('解绑成功')
                  this.showPopup = false
                  this.getbank()
                }
              }else {
                this.showPopup = false
                Toast(res.message)
              }
            })
        }else {
          UserPawPost(this.params).then(res=>{
            console.log(res)
            if(res.status == 200){
              this.$router.push({name:'forgetbankcard',query:{is:'false'}})
            }else {
              this.showPopup = false
              Toast(res.message)
            }
          })
        }
        this.params.transactionPassword = ''
      }
    }
  }
</script>

<style lang="scss" scoped>
    .container {
        height: calc( 100% - 54px);
        padding-top: 54px;
        background-color: RGBA(246, 244, 245, 1);
        .header {
            @include header;
        }
        .bankcard{
            .bankcard-box{
                h1{
                    height:22px;
                    font-size:16px;
                    font-family:PingFangSC-Medium,PingFang SC;
                    font-weight:500;
                    color:rgba(64,64,64,1);
                    line-height:22px;
                    margin-bottom: 11px;
                    margin-left: 15px;
                    margin-top: 20px;
                }
                .bankcard-box-count{
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    height:60px;
                    background:rgba(255,255,255,1);
                    box-shadow:0px 0px 4px 0px rgba(254,129,61,0.2);
                    border-radius:2px;
                    margin: 0 15px;
                    padding: 20px 0;
                    /*img{*/
                    /*    width: 39.5px;*/
                    /*    margin: 0 11px;*/
                    /*}*/
                    .bank-count-name{
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        width: 64%;
                        margin-left: 20px;
                        h4{
                            height:28px;
                            font-size:18px;
                            font-family:PingFangSC-Medium,PingFang SC;
                            font-weight:500;
                            color:rgba(51,51,51,1);
                            line-height:28px;
                        }
                        p{
                            height:30px;
                            font-size:20px;
                            font-family:PingFangSC-Medium,PingFang SC;
                            font-weight:500;
                            color:rgba(51,51,51,1);
                            line-height:30px;
                            margin-top: 8px;
                        }
                    }
                    .bank-btn{
                        width:78px;
                        height:28px;
                        line-height: 28px;
                        text-align: center;
                        background:rgba(254,129,61,1);
                        border-radius:15px;
                        font-size:14px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:rgba(255,255,255,1);
                        margin-right: 11px;
                    }
                }
                .no-box{
                    height:60px;
                    background:rgba(255,255,255,1);
                    box-shadow:0px 2px 4px 0px rgba(111,130,141,0.05);
                    border-radius:4px;
                    margin: 0 15px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    img{
                        width: 13px;
                        height: 13px;
                        border: 1px dashed #000;
                    }
                    p{
                        font-size:15px;
                        font-family:PingFangSC-Medium,PingFang SC;
                        font-weight:500;
                        color:rgba(64,64,64,1);
                        margin-left: 6px;
                    }
                }
            }
        }
        .mint-popup {
            border-radius: 3px;
            width: 90%;
            overflow: hidden;
            .mint-msgbox-title {
                padding: 0 20px;
                line-height: 30px;
            }
            .mint-msgbox-content {
                padding: 0 20px 20px;
                @include sc(16px, #707070);
                .tel-wrapper {
                    position: relative;
                    display: flex;
                    padding: 10px 0 24px;
                    align-items: flex-end;
                    .tabName {
                        width: 80px;
                        text-align: left;
                        flex-shrink: 0;
                    }
                    .phone {
                        color: #999;
                    }
                    input {
                        border: none;
                        flex-shrink: 1;
                        border-radius: 0;
                        border-bottom: 1px solid #bababa;
                        box-shadow: none;
                    }
                    .vel {
                        width: 70px;
                        height: 16px;
                        margin-left: 5px;
                        text-align: center;
                        color: #fc810c;
                        font-size: 14px;
                        flex-shrink: 0;
                    }
                    .deep {
                        color: $markColor;
                        pointer-events: auto;
                    }
                }
            }
            /deep/.mint-msgbox-errormsg {
                text-align: center;
                display: block;
            }
        }
    }
</style>
