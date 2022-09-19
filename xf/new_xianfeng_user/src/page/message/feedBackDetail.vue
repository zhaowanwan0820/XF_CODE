<template>
  <div class="page-wrapper">
    <div class="header-container"> 
      <common-header title="咨询反馈"></common-header>  
    </div>
    <div class="container-wrapper"> 
      <div class="list">  
        <p class="time">{{detail.add_time|convertTime}} 提出</p>
        <div class="con">
          <h3 class="title">问题描述</h3>
          <p>{{detail.content}}</p>
        </div>
        <template v-if='detail.status == "3"'> 
          <p class="time">{{detail.re_time|convertTime}} 回复</p>
          <div class="con blue">
            <h3 class="title">问题解答</h3>
            <p>{{detail.re_content}}</p>
          </div>
        </template>
        <p class="time" v-else>{{descFormat(detail)}}</p>
      </div> 
    </div> 
  </div>
</template>
<script>  
import commonHeader from "@/components/CommonHeader.vue"
import { getFeedbackInfo } from '../../api/message'    
export default {
  name: 'messageDetail',
  data() {
    return {  
      id:this.$route.query.id,
      detail:{
        "id": "123", // 意见反馈ID
        "content": "123", // 提交内容
        "re_content": "123", // 回复内容
        "add_time": "1500000000", // 提交时间
        "re_time": "1500000000", // 回复时间
        "status": "1", // 状态：1-待回复，2-处理中，3-已回复
        "re_status": "1" // 回复内容状态：1-已读，2-未读
      }, 
    }
  },
  computed: {  
  },
  components: {  
    commonHeader
  },
  created() { 
    this.getInfo()    
  },
  methods: {  
    getInfo() {   
      getFeedbackInfo({ feedback_id:this.id}) 
      .then(
        res=>{
          if (res.code ==0) {  
            this.detail = res.data
          } 
        }) 
    },   
    descFormat(item){
      let desc = [{
        val:'1',
        con:'【待处理】等待服务人员接单'
      },{
        val:'2',
        con:'【处理中】服务人员正在处理'
      },{
        val:'3',
        con:`【已回复】${item.re_content}`
      }]
      let find = desc.find(desc=>{
        return item.status == desc.val ? desc:''
      })
      return find?find.con:''
    }
  } 
}
</script>
<style lang="less" scoped>
.page-wrapper {
  width: 100%;
  min-height: 100%; 
  background:#F4F4F4;
  display: flex;
  flex-direction: column;  
  
  .container-wrapper{ 
    margin-top:30px;
  }
  .list{    
    padding:0 12px;
  }
  .time{ 
    font-size:13px; 
    color:rgba(153,153,153,1); 
    margin:20px 0 10px;
  }
  .con{ 
    min-height:80px;
    background:rgba(255,255,255,1);
    border-radius:4px;
    line-height:20px;
    padding:10px;
    box-sizing:border-box;
    h3.title{ 
      font-size:15px; 
      font-weight:500;
      color:rgba(64,64,64,1); 
      padding:5px 0 12px;
    }
    p{
      font-size:13px;  
      color:rgba(112,112,112,1);
    } 
    &.blue h3.title,&.blue p{
      color:#3934DF
    }
  }
}
</style>
