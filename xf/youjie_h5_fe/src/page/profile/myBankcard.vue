<template>
    <div class="container">
        <mt-header class="header" fixed :title="title">
            <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
        </mt-header>
        <div class="bankcard">
            <p class="bankcard-text">您还没有绑定任何一张银行卡，请绑定。</p>
            <div class="bankcard-btn" @click="bindBank">绑定银行卡</div>
        </div>
    </div>
</template>

<script>
  export default {
    name: 'myBankcard',
    data () {
      return {
        title:'银行卡信息'
      }
    },
    created() {
      // console.log(this.$route)
    },
    methods: {
      goBack() {
        this.$_goBack()
      },
      bindBank(){
        this.$router.push({name:'bindbankcard'})
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
        .bankcard{
            .bankcard-text{
                font-size:12px;
                font-family:PingFangSC-Regular,PingFang SC;
                font-weight:400;
                color:rgba(252,127,12,1);
                margin: 17px 15px 26px;
            }
            .bankcard-btn{
                width: 90%;
                height:46px;
                line-height: 46px;
                text-align: center;
                background:rgba(252,127,12,1);
                border-radius:2px;
                font-size:18px;
                font-family:PingFangSC-Regular,PingFang SC;
                font-weight:400;
                color:rgba(255,255,255,1);
                margin: 0 auto;
                letter-spacing: 1px;
            }
        }
    }
</style>