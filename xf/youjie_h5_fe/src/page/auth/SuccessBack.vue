<template>
    <div class="container">
        <div class="header-container">
            <mt-header class="header" :title="title">
                <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
            </mt-header>
        </div>
        <div class="success-box">
            <img src="../../assets/image/change-icon/b0_collect@2x.png" alt="">
            <p>您的资料已提交成功，客服会在3个工作日内与您电话联系进行身份核实，核实成功后，将会更新您的银行卡信息并短信告知您。更多帮助可以拨打我们的客服电话010-89929966。</p>
        </div>
<!--        <div class="success-btn">返回</div>-->
    </div>
</template>

<script>
  export default {
    name: 'SuccessBack',
    created(){
      this.title = this.$route.meta.title
    },
    methods:{
      goBack() {
        this.$router.go(-2);
      }
    }
  }
</script>

<style lang="scss" scoped>
.container{
    background-color: #fff;
    height: 100%;
    .header {
        @include thin-border();
    }
    .success-box{
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 25px;
        img{
            margin-top: 50px;
        }
        p{
            margin-top: 40px;
            height:21px;
            font-size:15px;
            font-family:PingFangSC-Regular,PingFang SC;
            font-weight:400;
            color:rgba(64,64,64,1);
            line-height:21px;
        }
    }
}
</style>
