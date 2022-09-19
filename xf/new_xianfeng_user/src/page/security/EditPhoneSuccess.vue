<template>
  <div id="editSuccess_canUse">
      <common-header title="修改绑定手机" :showArrow='false' style="border-bottom: 1px solid #F4F4F4;"></common-header>
      <div class="success_content">
        <img src="../../assets/editPhone/success_icon.png" alt="" srcset="">
        <h1>恭喜！</h1>
        <p>您绑定的手机已修改完成</p>
        <p>当前手机号为：{{currentPhone}}</p>
        <!-- <p style="margin-bottom:50px">请耐心等待</p> -->
        <van-button size="small" type="default" round @click="goBack">返回 {{countDown}}</van-button>
      </div>
  </div>
</template>

<script>
import commonHeader from "@/components/CommonHeader.vue";
export default {
  components: {
    commonHeader,
  },
  data () {
    return {
      countDown:10,
      currentPhone:this.$route.query.currentPhone,
    }
  },
  mounted () {
    let timer=setInterval(()=>{
      this.countDown--;
      if(this.countDown<=0){
        clearInterval(timer)
        this.countDown=0;
        this.$router.push({
          name: "editPhoneIndex", 
          query: {
                        userInfo:this.$route.query.userInfo,
                        currentPhone:this.currentPhone,
                    }
        });
      }
    },1000)
  },
  methods: {
    goBack(){
      this.$router.push({
          name: "editPhoneIndex", 
          query: {
                        userInfo:this.$route.query.userInfo,
                        currentPhone:this.currentPhone,
                    }
        });
    }
  }
}
</script>

<style lang="less" scoped>
.success_content{
  text-align: center;
  font-size: 18px;
  color: #4a4a4a;
  margin-top: 36px;
  img{
    margin-top: 50px;
  }
  h1{
    font-size: 24px;
    margin-top: 50px;
    margin-bottom: 25px;
  }
  p{
    line-height: 40px;
  }
}

</style>