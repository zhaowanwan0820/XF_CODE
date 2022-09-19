<template>
    <div class="container">
        <mt-header class="header" fixed :title="title">
            <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        </mt-header>
        <div class="bankcard">
            <div class="bank-box">
                <div class="bank-box-title">
                    <img src="../../assets/image/hh-icon/f0-profile/cmbc.png" alt="" width="42">
                    <div class="title-box">
                        <p class="title-box-name">招商银行</p>
                        <p class="title-box-card">储蓄卡</p>
                    </div>
                </div>
                <div class="card-number">6222 **** **** **** 229</div>
            </div>
            <div class="bank-foot">
                <div class="button" @click="goToBank">更换银行卡</div>
            </div>
        </div>
    </div>
</template>

<script>
  export default {
    name: 'BankCard',
    data() {
      return {
        title: '银行卡信息'
      }
    },
    created() {
      console.log(this.$route)
    },
    methods: {
      goBack() {
        this.$_goBack()
      },
      goToBank(){
        this.$router.push({ name: 'bankcardadd',params:{title:1} })
      }
    }
  }
</script>

<style lang="scss" scoped>
    .container {
        height: calc( 100% - 54px);
        padding-top: 54px;
        background-color: #fff;
        .header {
            @include header;
        }
        .bankcard{
            .bank-box{
                width: 335px;
                height: 122px;
                background: url("../../assets/image/hh-icon/f0-profile/bankcard.png") no-repeat;
                background-size: cover;
                margin: 0 auto;
                .bank-box-title{
                    display: flex;
                    align-items: center;
                    padding: 18px 25px;
                    img{
                        border: 2px solid #fff;
                        border-radius: 50%;
                    }
                    .title-box{
                        margin-left: 10px;
                        .title-box-name{
                            font-size:18px;
                            font-family:PingFangSC-Medium,PingFang SC;
                            font-weight:500;
                            color:rgba(255,255,255,1);
                        }
                        .title-box-card{
                            font-size:14px;
                            font-family:PingFangSC-Regular,PingFang SC;
                            font-weight:400;
                            color:rgba(255,255,255,0.7);
                        }
                    }
                }
                .card-number{
                    height:28px;
                    font-size:22px;
                    font-family:DINPro-Medium,DINPro;
                    font-weight:500;
                    color:rgba(255,255,255,1);
                    line-height:28px;
                    letter-spacing:2px;
                    margin: 0 25px;
                }
            }
            .bank-foot{
                padding-top: 56px;
                background:rgba(255,255,255,1);
                box-shadow:0px -5px 8px 0px rgba(67,67,67,0.25);
                .button{
                    width: 90%;
                    height:46px;
                    line-height: 46px;
                    background:rgba(252,127,12,1);
                    border-radius:2px;
                    margin: 0 auto;
                    text-align: center;
                    font-size:18px;
                    font-family:PingFangSC-Regular,PingFang SC;
                    font-weight:400;
                    color:rgba(255,255,255,1);
                    letter-spacing: 2px;
                }
            }
        }
    }
</style>