<template>
  <div>
    <div class="header">
      <div class="wrap">
        <div class="arrow" @click="goBack"><img src="../../static/images/back-arrow.png" alt=""></div>
        <div class="title">{{title}}</div>
      </div>
    </div>
    <div class="tab-box" >
      <div :class="activeTabClass == index?'active tab' : 'tab'" v-for="(val,index) in tabList" @click="tab(index)">{{val}}</div>
    </div>
    <van-list v-show="!nullData" v-model="loading" :finished="finished" finished-text="没有更多了" @load="onLoad">
      <div class="content">
        <property-list v-for="(val,index) in list" :key="index" :type='params.type' :listValue="val" @goLend="goLend(val.id)"></property-list>
      </div>
    </van-list>
    <div v-show="nullData">
      <null-data></null-data>
    </div>
  </div>
</template>

<script>
  import commonHeader from "@/components/CommonHeader.vue";
  import propertyList from "@/components/PropertyList.vue";
  import {
    List
  } from 'vant';
  import {
    getPropertyDetailRequest
  } from '../../api/propertyCompose.js'
  export default {
    components: {
      commonHeader,
      propertyList
    },
    data() {
      return {
        title: '',
        params: {
          type: 1, //所属平台 1尊享 2普惠 3金融工场（工场微金） 4智多新 (默认1)
          status: 0, //状态 0全部 1还款中 2已结清 （默认0全部）
          limit: 10, //每页数据显示量（不传时默认值为10，最大值50）
          page: 1 //当前页数（不传时默认值为1）
        },
        tabList: ["全部", "还款中", "已结清"],
        activeTabClass: 0,
        list: [],
        loading: false,
        finished: false,
        isClick:false,
        nullData:false, //显示空数据界面标识 false为不显示
      }
    },
    created() {
      this.getRouterParams();
    },
    mounted() {
      document.querySelector('body').setAttribute('style', 'background-color:#F4F4F4')
    },
    beforeDestroy() {
      document.querySelector('body').removeAttribute('style')
    },
    methods: {
      getRouterParams() {
        this.params.type = this.$route.query.type;
        if (this.params.type === 1) {
          this.title = '尊享'
        } else if (this.params.type === 2) {
          this.title = '普惠'
        }else if (this.params.type === 3) {
          this.title = '工场微金'
        }else if (this.params.type === 4) {
          this.title = '智多新'
        }
      },
      tab(index) {
        if(this.activeTabClass == index || this.isClick){
          return
        }
        this.isClick = true;
        this.list = [];
        this.params.page = 1;
        this.finished = false
        this.getPropertyDetail(index);
      },
      getPropertyDetail(index) {
        console.log(index+'index')
        this.activeTabClass = index;
        this.params.status = index;
        this.loading = true;
        this.nullData = false
        getPropertyDetailRequest(this.params).then(res => { //nullData
        console.log(res)
          if (res.code === 0) {
            // console.log(this.list.length+'*'+res.count)
            if(res.data && res.data.length){
              this.list = this.list.concat(res.data);
              console.log(this.list)
              this.params.page++;
              this.loading = false;
              if (res.count == this.list.length || !res.data.length) {
                this.finished = true // 数据全部加载完成
              }
            }else{
              this.nullData = true
            }

          }else{
            this.loading = false
            this.finished = true
          }
          this.isClick = false
        })
      },
      onLoad(){
        this.getPropertyDetail(this.activeTabClass);
      },
      goLend(id){
        this.$router.push({
          path:'/lendingDetails',
          query:{
            id:id,
            platform_id:this.params.type
          }
        })
      },
      goBack(){
        this.$router.replace({
          path:'/propertyCompose'
        })
      }
    }
  }
</script>

<style lang="less" scoped>
  .tab-box {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 17px 40px 0 40px;
    background: #FFFFFF;
    position: fixed;
    top: 36px;
    left: 0;
    right: 0;
    .tab {
      padding-bottom: 10px;
      font-family:PingFangSC-Regular,PingFang SC;
      font-weight:400;
      color:rgba(51,51,51,1);
      font-size:13px;
    }

    .tab.active {
      font-family:PingFangSC-Medium,PingFang SC;
      border-bottom: 3px solid #3934DF;
      color:rgba(57,52,223,1);
    }
  }

  .content {
    margin-top: 97px;
  }
  .header {
    height: 36px;
    line-height: 36px;
    border-bottom: 1px solid #F4F4F4;
    background-color: #ffffff;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;

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
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(51, 51, 51, 1);
        flex: 1;
        padding-right: 30px;
      }
    }

  }
</style>
