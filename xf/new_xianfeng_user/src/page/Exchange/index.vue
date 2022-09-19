<template>
  <div class="page-wrapper">
    <div class="header-container">
      <div class="header">
        <div class="wrap">
          <div class="arrow" @click="back"><img src="../../static/images/white-back-arrow.png" alt=""></div>
          <div class="title">积分兑换记录</div>
        </div>
      </div>
        <div class="header-sear">
          <van-row>
            <van-col span="5">
              <span>所属平台</span>
            </van-col>
            <van-col span="15">
              <van-dropdown-menu :overlay='false' @click='showChannel(0)'>
                <van-dropdown-item v-model="params.type" :options="option1" @change='change' />
              </van-dropdown-menu>
            </van-col>
            <van-col span="4" id='channel'><span @click.prevent='showChannel'>渠道</span></van-col>
          </van-row>
        </div>
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
          <list-item v-for="item in list" :key="item.id" :info="item"></list-item>
        </van-list>
    </div>
    <van-popup v-model="show" class="channerBox" get-container="#channel" :overlay='false' >
      <van-radio-group v-model="params.platform_no" checked-color="#ff0" @change='change'>
        <van-radio name="0">全部
          <template #icon="props"> <img src="" alt=""> </template>
        </van-radio>
        <van-radio name="1">有解商城
          <template #icon="props"><img src="" alt="">  </template></van-radio>
      </van-radio-group>
    </van-popup>
  </div>
</template>
<script>
import { getExchangeList } from '../../api/user'
import listItem from './itemlist.vue'
export default {
  name: 'exchange',
  data() {
    return {
      params:{
        type: 1,      //所属平台 1尊享 2普惠 (默认1)
        platform_no:0,	//渠道 0全部 1有解 （目前线上仅有1个有解渠道，默认0全部）
        limit:	10,   //每页数据显示量（不传时默认值为10，最大值50）
        page:	1	      //当前页数（不传时默认值为1）
      },
      show:false,
      list:[
        // {
        //   "id": "123", // ID
        //   "debt_account": "30234.00", // 兑换金额(元)
        //   "deal_name": "供应链A00709847", // 标的编号
        //   "exchange_time": "1500000000", // 兑换时间
        //   "platform_no": "1" // 兑换渠道 渠道 1有解
        // },
        // {
        //   "id": "231", // ID
        //   "debt_account": "30234.00", // 兑换金额(元)
        //   "deal_name": "供应链A00709847", // 标的编号
        //   "exchange_time": "1500000000", // 兑换时间
        //   "platform_no": "1" // 兑换渠道 渠道 1有解
        // }
      ],
      loading: false,
      finished: false,
      option1: [
        { text: '尊享平台', value: 1 },
        { text: '普惠平台', value: 2 },
      ],
      nullData:false, //显示空数据界面标识 false为不显示
    }
  },
  computed: {
    finishedText() {
      return this.list.length ? '没有更多了' : '暂无数据'
    }
  },
  components: {
    listItem
  },
  created() {
  },
  methods: {
    getList() {
      this.nullData = false
      getExchangeList(this.params)
      .then(
        res=>{
          console.log(res.data)
          if (res.code == 0) {
           if(res.data && res.data.length){
             if(this.params.page==2){
               this.list = res.data
             }else{
              this.list = [...this.list, ...res.data]
             }
             this.loading = false
             if ((res.data && res.count == this.list.length) || !res.data.length ) {
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
        .finally(() => {})
    },
    onLoad() {
      this.getList()
      this.params.page += 1
    },
    back(){
      this.$router.go(-1)
    },
    change(value){
      this.params.page =1
      this.onLoad()
      this.show = false;
    },
    showChannel(n){
      if(n==0){  this.show = false}
      this.show = !this.show;
    }
  }
}
</script>
<style lang="less" scoped>
.page-wrapper {
  width: 100%;
  min-height: 100%;
  background: #F4F4F4;
  display: flex;
  flex-direction: column;
}
.header-container{
  background:url(../../static/images/top-banner.png) no-repeat left bottom;
  background-size:cover;
  color: #fff;
  font-size:13px;
  /deep/.header {
    height:50px;
    text-align:center;
    background:none;
    border:0;
    line-height:50px;
    .wrap {
      padding: 0 9px;
      display: flex;
      flex-direction: row;
      align-items: center;

      .arrow {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        img {
          width: 100%;
          height: 100%;
        }
      }

      .title {
        text-align: center;
        font-size: 18px;
        flex: 1;
        padding-right: 30px;
      }
    }

  }
  .header-sear{
    padding:20px 0 10px;
  }
  .van-col{
    line-height:28px;
    text-align:center;
  }
}
.van-dropdown-menu{
  position:relative;
  z-index:10;
}
  /deep/.van-dropdown-menu__bar{
    height:28px;
    background:rgba(241,241,241,1);
    border-radius:14px;
    margin:0;
    position:relative;
    z-index: 99999 !important;
    .van-dropdown-menu__title{
      display:block;
      width:90%;
      font-size:13px;
      color:#3934DF;
      text-align:left;
    }
    .van-dropdown-menu__title:after{
      background:url(../../static/images/arrow.png) no-repeat;
      background-size:100%;
      width:8px; height:12px;
      border:0;
      transform:none;
      opacity:1;margin-top:-5px;
      content:'';
    }
    .van-dropdown-menu__title--down:after{
      margin-top:-5px;
      content:'';
    }
  }
/deep/.van-dropdown-menu__bar--opened{
  background: #fff;
}
/deep/.van-dropdown-item--down{
  margin-top:-28px;
  width: 62.5%;
  left: 21%;
  border-radius:14px;
}
/deep/.van-popup--top {
  padding-top: 28px;
  z-index:0;
  background:rgba(241,241,241,1);
  box-shadow:0px 3px 6px 0px rgba(58,56,118,0.14);
  border-radius:14px;
  .van-cell{
    background:none;
  }
}
#channel {
  position:relative;
  span{
    width:20px;
    vertical-align: -10px;
    font-size:10px;
    padding-top:22px;
    margin-top:20px;
    background:url(../../static/images/channl.png) no-repeat;
    background-size: 20px 20px;
  }
}
.van-popup{
  position:absolute;
  width:88px;
  background:rgba(255,255,255,1);
  box-shadow:0px 2px 4px 0px rgba(92,91,141,0.19);
  border-radius:4px;
  top:95px;
  left:0;
  max-height:90px;
  margin:0;    overflow: inherit;
  line-height:45px;
  .van-radio{
    line-height:46px;
    height:46px;
    color:#404040;
    text-align:center;
    position:relative;
    &:first-child{
      border-bottom:1px dashed rgba(211, 211, 211, 0.5);
    }
  }
  &:before{
    content:'';
    width: 0;
    height: 0;
    position:absolute;
    top:-14px;
    right:6px;
    border-width: 7px;
    border-style: solid;
    border-color: transparent transparent #fff transparent;
  }
  /deep/.van-radio__label {
    color: #404040;
    display: block;
    width: 100%;
    text-align: center;
    margin:0;}
  /deep/.van-radio__icon{
    width:100%;
    height:46px;
    border-bottom:1px dashed rgba(211, 211, 211, 0.5);
    z-index:-1;
    position:absolute;
    &.van-radio__icon--checked{
    background:#DBE3FF
    }
  }
}
.container-wrapper {
  flex:110px 1;
  overflow:auto;
  .empty{
    padding-top:200px;
  }
}
</style>
