<template>
    <div class="container">
        <mt-header class="header" fixed :title="title">
            <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        </mt-header>
        <div class="test">
            <div class="test-box">
                <div class="test-item">
                    <span>手机号</span>
                    <input class="test-input" type="number" placeholder="请输入银行预留的手机号">
                </div>
                <div class="test-item code">
                    <span>验证码</span>
                    <input class="test-input input-code" type="text" placeholder="请输入短信验证码">
                    <div class="btn-code" >{{auth_time}}</div>
                </div>
            </div>
            <div class="test-btn">提交</div>
        </div>
    </div>
</template>

<script>
  export default {
    name: 'TestPhone',
    data () {
      return {
        title:'验证手机号',
        auth_time:'点击获取'
      }
    },
    created() {
      // console.log(this.$route)
    },
    methods: {
      goBack() {
        this.$_goBack()
      },
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
        .test{
            margin: 51px 0 0;
            .test-box{
                .test-item{
                    display: flex;
                    align-items: center;
                    margin: 0 35px 20px 15px;
                    span{
                        width: 50px;
                        text-align: right;
                        font-size:15px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:rgba(102,102,102,1);
                    }
                    .test-input{
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
                    .input-code{
                        width: 60%;
                    }
                    .btn-code{
                        width: 98px;
                        height:44px;
                        line-height: 44px;
                        background:rgba(252,127,12,.3);
                        font-size:15px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:rgba(255,255,255,1);
                        text-align: center;
                    }
                }
            }
            .test-btn{
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