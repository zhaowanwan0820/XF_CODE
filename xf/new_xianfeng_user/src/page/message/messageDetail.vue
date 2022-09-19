<template>
  <div class="page-wrapper">
    <div class="header-container"> 
      <common-header :title="title"></common-header>  
    </div>
    <div class="container-wrapper"> 
      <div class="list">  
        <h3 class="title">{{detail.title}}</h3>
        <p class="time">{{detail.start_time|convertTime4}} <br>  <span v-show='detail.abstract'>摘要：{{detail.abstract}}</span> </p>
        <div class="con" v-html='detail.content'>
          {{detail.content}}
        </div> 
      </div> 
    </div> 
  </div>
</template>
<script>  
import commonHeader from "@/components/CommonHeader.vue"
import { getMessageInfo, getNoticeInfo } from '../../api/message'    
export default {
  name: 'messageDetail',
  data() {
    return {  
      id:this.$route.query.id,
      sq : this.$route.query.sq,
      detail:{
        // "id": "123", // 消息ID
        // "title": "123", // 消息标题
        // "abstract": "123", // 消息摘要
        // "content": "123", // 消息内容（含有图片的html代码）
        // "start_time": "1500000000" // 发布时间
      }, 
    }
  },
  computed: {  
    title(){
      return this.sq==1 ? '消息详情' : '公告详情' 
    },
  },
  components: {  
    commonHeader
  },
  created() { 
    this.sq==1 ? this.getInfo1() : this.getInfo3()
  },
  methods: {  
    getInfo1() {   
      getMessageInfo({ message_id:this.id}) 
      .then(
        res=>{
          if (res.code ==0) {  
            this.detail = res.data
          } 
        }) 
    }, 
    getInfo3() {   
      getNoticeInfo({ notice_id:this.id}) 
      .then(
        res=>{
          if (res.code ==0) {  
            this.detail = res.data
          } 
        }) 
    },    
  } 
}
</script>
<style lang="less" scoped>
.page-wrapper {
  width: 100%;
  min-height: 100%; 
  background:#fff;
  display: flex;
  flex-direction: column; 
  .header{
    border:0; 
    z-index:10;
  }
  .container-wrapper{  
    margin-top:30px; 
    z-index:0;
  }
  .list{    
    padding:15px;
  }
  .title{ 
      font-size:20px; 
      font-weight:600;
      color:rgba(51,51,51,1);
    }
  .time{ 
    font-size:12px; 
    line-height:20px;
    color:#999;
    letter-spacing:0.5px;
    margin-top:10px;
  }
  .con{  
    background:rgba(255,255,255,1);
    border-radius:4px;
    padding:20px 0 10px;
    box-sizing:border-box; 
    font-size:15px;
    font-family:PingFangSC-Regular,PingFang SC;
    font-weight:400;
    color:rgba(64,64,64,1);
    line-height:2em;
    img{
      margin: 10px 0;
    }
    
  }
}
</style>
