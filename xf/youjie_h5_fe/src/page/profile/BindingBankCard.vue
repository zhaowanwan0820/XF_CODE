<template>
    <div class="container">
        <mt-header class="header" fixed :title="title">
            <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        </mt-header>
        <div class="binding">
            <p class="binding-text">您还没有绑定任何一张银行卡，请绑定。</p>
            <div class="binding-box">
                <div class="binding-item">
                    <span>持卡人</span>
                    <input class="binding-input" type="number" placeholder="请输入您本人的真实姓名">
                </div>
                <div class="binding-item">
                    <span>银行卡号</span>
                    <input class="binding-input" type="number" placeholder="请输入银行卡号">
                </div>
                <div class="binding-item">
                    <span>开户银行</span>
                    <input class="binding-input" type="number" placeholder="请输入开户行">
                </div>
            </div>
            <div class="binding-btn" @click="NextGo">下一步</div>
        </div>
    </div>
</template>

<script>
  export default {
    name: 'BindingBankCard',
    data () {
      return {
        title:'绑定银行卡'
      }
    },
    created() {
      // console.log(this.$route)
    },
    methods: {
      goBack() {
        this.$_goBack()
      },
      NextGo(){
        this.$router.push({name:'testphone'})
      }
    }
  }
</script>

<style lang="scss" scoped>
    header.mint-header.header.is-fixed {
        border-bottom: 1px solid #f4f4f4;
    }
    .container {
        height: calc( 100% - 54px);
        padding-top: 54px;
        background-color: #fff;
        .header {
            @include header;
        }
        .binding{
            .binding-text{
                font-size:12px;
                font-family:PingFangSC-Regular,PingFang SC;
                font-weight:400;
                color:rgba(252,127,12,1);
                margin: 17px 15px;
            }
            .binding-box{
                .binding-item{
                    display: flex;
                    align-items: center;
                    margin: 0 15px 20px;
                    span{
                        width: 70px;
                        text-align: right;
                        font-size:15px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:rgba(102,102,102,1);
                    }
                    .binding-input{
                        width: 90%;
                        height:40px;
                        line-height: 40px;
                        margin-left: 12px;
                        font-size:15px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:rgba(64,64,64,1);
                        border:1px solid rgba(221,221,221,1);
                        text-indent: 10px;
                    }
                }
            }
            .binding-btn{
                width: 90%;
                margin: 287px auto 0;
                height:46px;
                line-height: 46px;
                text-align: center;
                background:rgba(252,127,12,0.3);
                border-radius:2px;
                font-size:18px;
                font-family:PingFangSC-Regular,PingFang SC;
                font-weight:400;
                color:rgba(255,255,255,1);
            }
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
        text-indent: 10px;
    }
</style>