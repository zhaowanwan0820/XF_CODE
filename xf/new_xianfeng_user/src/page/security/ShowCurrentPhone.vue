<template>
  <div id="editIndex">
      <common-header title="手机号" :showArrow='true' :toEditPhoneIndex='true' style="border-bottom: 1px solid #F4F4F4;"></common-header>
      <div class="show_content">
          <img src="../../assets/editPhone/phone_icon.png" alt="" srcset="">
          <p>当前手机号：{{this.$route.query.currentPhone?this.$route.query.currentPhone:userInfoObj.mobile_o}}</p>
          <div style="margin: 25px;">
            <van-button color="#4d58f9"  round block type="info" @click="goSetNewPhone">修改绑定手机</van-button>
          </div>
      </div>
  </div>
</template>

<script>
import commonHeader from "@/components/CommonHeader.vue";
export default {
    created() {
        document.getElementsByTagName("html")[0].style.backgroundColor = "#ebebeb";
    },
    mounted(){
        this.userInfoObj = JSON.parse(this.$route.query.userInfo);
    },
    data () {
        return {
            userInfoObj:{},
        }
    },
    components: {
        commonHeader,
    },
    methods: {
        goSetNewPhone(){
            this.$router.push({
                path: "/editPhone",
                query: {
                    userInfo:this.$route.query.userInfo
                }
            });
        }
    }
}
</script>

<style lang="less" scoped>
    #editIndex{
        background: #fff;
        padding-bottom: 25px;
    }
    .show_content{
        margin-top: 36px;
        text-align: center;

        img{
            vertical-align: top;
            margin: 33px 0;
            width: 34px;
            height: 69px;
        }
    }
</style>