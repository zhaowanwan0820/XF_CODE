<template>
  <div class="page-wrapper">
      <common-header title="咨询反馈"></common-header>
      <div v-show="nullData">
        <null-data></null-data>
      </div>
    <div class="container-wrapper"  v-show="!nullData">
      <van-list
          v-model="loading"
          :finished="finished"
          :finished-text=finishedText
          @load="onLoad"
          :class='list==0?"empty":"empty1"'
        >
          <div v-for="item in list" :key="item.id">
            <div class="list">
              <van-panel class="list-box" :title="item.content" :desc="descFormat(item)">
                <p :class="item.re_status==2?'status red':'status'">{{StatFormat(item.re_status)}}</p>
                <van-cell title="查看详情" is-link  :to="{name:'feedBackDetail',query:{id:item.id}}" />
              </van-panel>
            </div>
          </div>
        </van-list>
    </div>
  </div>
</template>
<script>
import commonHeader from "@/components/CommonHeader.vue"
import { getFeedback } from '../../api/message'
export default {
  name: 'messageList',
  data() {
    return {
      params:{
        limit:	10,   //每页数据显示量（不传时默认值为10，最大值50）
        page:	1	      //当前页数（不传时默认值为1）
      },
      list:[
        // {
        //   "id": "123", // 意见反馈ID
        //   "content": "123", // 提交内容
        //   "re_content": "123", // 回复内容
        //   "add_time": "1500000000", // 提交时间
        //   "re_time": "1500000000", // 回复时间
        //   "status": "1", // 状态：1-待回复，2-处理中，3-已回复
        //   "re_status": "1" // 回复内容状态：1-已读，2-未读 (备注：status等于2或3的时候才展示回复内容状态)
        // }
      ],
      loading: false,
      finished: false,
      nullData:false, //显示空数据界面标识 false为不显示
    }
  },
  computed: {
    finishedText() {
      return this.list.length ? '没有更多了' : '暂无数据'
    },
  },
  components: {
    commonHeader
  },
  created() {
  },
  methods: {
    getList() {
      this.nullData = false
      getFeedback(this.params)
      .then(
        res=>{
          if (res.code ==0) {
            console.log(res);
            if(res.data && res.data.length){
              console.log(this.params.page)
              if(this.params.page==2){ 
                this.list = res.data
              }else{
               this.list = [...this.list, ...res.data]
              }
              this.loading = false // 加载状态结束 //#endregion
              if ((res.data && res.count == this.list.length ) || !res.data.length) {
                this.finished = true // 数据全部加载完成
              }
            }else{
              console.log(this.nullData);
              this.nullData = true
            }
          }else{
            this.loading = false
            this.finished = true
          }
        })
    },
    onLoad() {
      this.params.page += 1
      this.getList()
    },
    StatFormat(n){
      return n == 0 ? '':n == 2?'未读':'已读'
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
  .header{
    z-index:10;
  }
  .container-wrapper{
    margin-top:40px;
    z-index:0;
  }
  .ico{
    line-height:75px;
    background: #fff;
    padding-left:15px;
    position:relative;
    img{
      width:44px;
      height:44px;
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
  .list{
    .list-box{
      width:351px;
      border-radius:4px;
      margin:10px 12px;
      position:relative;
      overflow:hidden;
      .van-cell__title{
        font-size:15px;
        font-weight:500;
        line-height:30px;
        color:rgba(64,64,64,1);
        span{
          display:block;
          width:280px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .van-cell__label{
          font-size:13px;
          margin-top:10px;
          font-weight:400;
          color:rgba(112,112,112,1);
          height:18px;
          width:300px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;

        }
      }
      .van-panel__content{
        .van-cell__title{
          font-size:13px;
          color:rgba(64,64,64,1);
        }
      }
    }
    .van-cell:not(:last-child):after {
          content: " ";
          right: 10px;
          left: 10px;
      }
    .status{
      position:absolute;
      top:16px; right:10px;
      color:#3934DF;
      font-size:12px;
      &.red{
        color:#FF3122;
      }
    }
  }
  .empty{
    padding-top:200px;
  }
}
</style>
