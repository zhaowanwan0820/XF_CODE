<template>
  <div class="page-wrapper">
    <div class="header-container">
      <common-header :title="title"></common-header>
    </div>
    <div v-show="nullData">
      <null-data></null-data>
    </div>
    <div class="container-wrapper" v-show="!nullData">
      <van-list
          v-model="loading"
          :finished="finished"
          :finished-text=finishedText
          @load="onLoad"
          :class='list==0?"empty":"empty1"'
        >
          <div v-for="item in list" :key="item.id">
            <div class="list">
              <p class='time'>{{item.start_time | convertTime3}}</p>
              <van-panel class="list-box" :title="item.title" :desc="item.abstract">
                <p :class="item.status==2?'status red':'status'" v-if='sq==1'>{{StatFormat(item.status)}}</p>
                <van-cell title="查看详情" is-link :to="{name:'messageDetail',query:{id:item.id,sq:sq}}" />
              </van-panel>
            </div>
          </div>
        </van-list>
    </div>
  </div>
</template>
<script>
import commonHeader from "@/components/CommonHeader.vue"
import { getMessageList, getNoticeList } from '../../api/message'
export default {
  name: 'messageList',
  data() {
    return {
      sq : this.$route.query.sq,
      params:{
        limit:	10,   //每页数据显示量（不传时默认值为10，最大值50）
        page:	1	      //当前页数（不传时默认值为1）
      },
      list:[
        // {
        //   "id": "123", // 消息ID
        //   "title": "123", // 消息标题
        //   "abstract": "123", // 消息摘要
        //   "start_time": "1500000000", // 发布时间
        //   "status": "1" // 状态：1-已读，2-未读
        // }
      ],
      loading: false,
      finished: false,
      nullData:false, //显示空数据界面标识 false为不显示
    }
  },
  computed: {
    title(){
      return this.sq==1 ? '消息通知' : '系统公告'
    },
    finishedText() {
      return this.list.length ? '没有更多了' : '暂无数据'
    }
  },
  components: {
    commonHeader
  },
  created() {
    console.log(this.sq)
  },
  methods: {
    getList(num) {
      this.sq==1 ? this.MessageList() : this.NoticeList()
    },
    MessageList(){
       this.nullData = false
       getMessageList(this.params)
      .then(
        res=>{
          if (res.code ==0) {
            console.log(this.params.page)
            if(res.data && res.data.length){
              if(this.params.page==2){
                this.list = res.data
              }else{
               this.list = [...this.list, ...res.data]
              }
            }else{
              this.nullData = true
            }
          }
          this.loading = false // 加载状态结束 //#endregion
          if ((res.data && res.count == this.list.length ) || !res.data) {
            this.finished = true // 数据全部加载完成
          }
        })
    },
    NoticeList(){
       this.nullData = false
       getNoticeList(this.params)
      .then(
        res=>{
          if (res.code ==0) {
            console.log(this.params.page)
            if(res.data && res.data.length){
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
               this.nullData = true
            }
          }else{
            this.loading = false
            this.finished = true
          }
        })
    },
    onLoad() {
      this.getList(this.sq)
      this.params.page += 1
    },
    StatFormat(n){
      return n == 1 ? '已读':'未读'
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
    margin-top:30px;
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
      margin:0 12px;
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
          width:280px;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
      }
      .van-panel__header-value{
        font-size:12px;
        color:rgba(255,49,34,1);
      }
      .van-panel__content{
        .van-cell{
          padding-right:6px;
        }
        .van-cell__title{
          font-size:13px;
          color:#707070;
        }
      }
    }
    .van-cell:not(:last-child):after {
          content: " ";
          right: 10px;
          left: 10px;
      }
    .time{
      width:99px;
      height:23px;font-size:14px;
      line-height:23px;
      color:#fff;
      text-align:center;
      margin:20px auto 10px;
      background:rgba(214,215,218,1);
      border-radius:12px;
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
