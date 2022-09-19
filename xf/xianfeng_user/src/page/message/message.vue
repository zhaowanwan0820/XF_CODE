<template>
  <div class="page-wrapper">
    <common-header title="消息中心" style="border-bottom: 1px solid #F4F4F4;"></common-header> 
    <div class="wrapper">   
      <van-row>
        <van-col span="4">
          <div :class="new_message?'new ico':'ico'"> <img src="../../static/images/icon1.png" > </div>
        </van-col>
        <van-col span="20">
         <van-cell title="消息通知" is-link  :to="{ path: 'messageList', query: { sq: 1 }}"/>
        </van-col> 
      </van-row>    
      <van-row>
        <van-col span="4">
          <div :class="new_feedback?'new ico':'ico'"> <img src="../../static/images/icon2.png" > </div>
        </van-col>
        <van-col span="20">
         <van-cell title="咨询反馈" is-link  to="feedBackList"/>
        </van-col> 
      </van-row>    
      <van-row>
        <van-col span="4">
          <div class="ico"> <img src="../../static/images/icon3.png" > </div>
        </van-col>
        <van-col span="20">
         <van-cell title="系统公告" is-link  :to="{ path: 'messageList', query: { sq: 3 }}"/>
        </van-col> 
      </van-row> 
    </div> 
  </div>
</template>
<script>  
import commonHeader from "@/components/CommonHeader.vue"
import { getNew } from '../../api/message'    
export default {
  name: 'exchange',
  data() {
    return {  
      new_feedback:false,
      new_message:false, 
    }
  }, 
  components: { 
    commonHeader
  },
  created() {   
    getNew().then(res=>{
      if(res.code==0){
        this.new_feedback = res.data.new_feedback ==1 ? true : false
        this.new_message = res.data.new_message ==1 ? true : false
      } 
    })
  },
  methods: {  
    back(){
      this.$router.go(-1)
    }, 
  } 
}
</script>
<style lang="less" scoped>
.page-wrapper {
  width: 100%;
  min-height: 100%; 
  display: flex;
  flex-direction: column;
  .header{
    z-index:10;
  }
  .wrapper{  
    margin-top:40px; 
    z-index:0;
  }
  .ico{  
    line-height:75px;
    background: #fff;
    padding-left:15px;  
    position:relative;  
    img{
      width:40px;
      height:40px;    
      vertical-align: middle; 
    }
  }
  .new:after{
    content:'';
    display:block;
    position:absolute;
    width:10px;
    line-height:0;
    border-radius:10px;
    height:10px;
    background:rgba(255,49,34,1);
    top:20px;
    right:4px;
  }
   .van-cell{
     line-height:55px;
     border-bottom:1px dashed rgba(211, 211, 211, 0.5);
     font-size:16px;
     .van-cell__right-icon{ 
       line-height:55px;
     }
   }
}
</style>
