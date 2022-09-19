<template>
  <div class="container">
    <div class="getoff">
      <img src="../../assets/image/getoff/back-title-3.png" alt="" />
      <div class="getoff-text">
        <p>您的 {{ account }} 元网信债权可换购新年超值大礼包，请即刻领取。</p>
        <span @click="goInfo" v-if="status <= 1">查看债权明细</span>
      </div>
    </div>
    <!--    商品列表    -->
    <gift-package :goodslist="goodsList" v-if="status <= 1" @func="Father"></gift-package>
    <div class="mesage" v-else>您已参加过活动啦!</div>
    <!--    玩法规则    -->
    <activity-rules v-if="status <= 1"></activity-rules>
    <div class="go-btn">
      <div class="btn" @click="goToHome">返回首页</div>
<!--      <div class="return-btn" @click="GiveUp" v-if="status <= 1">放弃礼包</div>-->
    </div>
    <!--   模态框   -->
    <div class="cover" v-if="isCover"></div>
    <!--    询问框    -->
    <div class="getoff-dialog" v-if="isTost">
      <div class="getoff-dialog_header">提示</div>
      <div class="getoff-dialog_content">
        <div class="getoff-dialog_box">
          <h3>您的{{account}}元债权即将升级为新年超值礼包， 是否同意？</h3>
        </div>
      </div>
      <div class="getoff-dialog_btn">
        <div class="getoff-dialog_btn-t" @click="AgreeClick">
          是
        </div>
        <div class="getoff-dialog_btn-x" @click="RefuseClick">
          否
        </div>
      </div>
    </div>
    <!--  离开提示  -->
    <div class="getoff-dialog" v-if="isGo">
      <div class="getoff-dialog_header">提示</div>
      <div class="getoff-dialog_content">
        <div class="getoff-dialog_box">
          <span>确认要离开吗？</span>
          <p>离开后就无法再领取超值礼包了哦。</p>
        </div>
      </div>
      <div class="getoff-dialog_btn">
        <div class="getoff-dialog_btn-t" @click="TrueClick">
          是的
        </div>
        <div class="getoff-dialog_btn-x" @click="NoClick">
          再想想
        </div>
      </div>
    </div>

<!--    <button-module :content='html_box' :btnText='btn_text' ></button-module>-->
  </div>
</template>

<script>
import GiftPackage from './child/giftPackage'
import ActivityRules from './child/ActivityRules'
import { getGoodsList, setGiveUp,postUserAction,postDebt } from '../../api/getOff'
import { mapActions, mapMutations } from 'vuex'
export default {
  name: 'getOff',
  data() {
    return {
      isCover: false,
      isTost: false,
      isGo:false,
      goodsList: [],
      account: '', //债权
      status: '', // 若大于1为已参加活动
      radio_3: require('../../assets/image/getoff/radio-3.png'),
      radio_4: require('../../assets/image/getoff/radio-4.png'),
      params: {
        type: 1
      },
      detail: [],
      goodsId:'',
      is_agree:''//1 不用再弹这个同意框了 0需要弹
    }
  },
  beforeRouteEnter(to, from, next) {
    next(vm => {
      if (!vm.$store.state.auth.isOnline) {
        vm.$router.replace({ name: 'login', params: {} })
      }
    })
  },
  components: {
    GiftPackage,
    ActivityRules
  },
  created() {
    this.getLsit()
    this.postUser()
  },
  mounted() {
    // this.saveHasEnterXiache(false)
  },
  methods: {
    ...mapActions({
      fetchUserInfos: 'fetchUserInfos'
    }),
    ...mapMutations({
      saveHasEnterXiache: 'saveHasEnterXiache'
    }),
    postUser(){
      postUserAction({event_code:'youjianxiache',event_name:'有解一键下车活动页'}).then(res=>{})
    },
    getLsit() {
      getGoodsList()
        .then(res => {
          console.log(res)
          this.goodsList = res.data
          this.account = res.account
          this.status = res.status
          this.detail = res.detail
          this.is_agree = res.is_agree
          if(res.is_agree == 0){
            this.saveHasEnterXiache(false)
          }else {
            this.saveHasEnterXiache(true)
          }

        })
        .finally(() => {})
    },
    goToHome() {
      if(this.is_agree == 0){
        this.isGo = true
        this.isCover = true
      }else if(this.is_agree == 1) {
        this.$router.replace({ name: 'home' })
      } else if(this.status > 1){
        this.$router.replace({ name: 'home' })
      }
    },
    //放弃礼包
    GiveUp() {
      this.isTost = true
      this.isCover = true
      //
    },
    goInfo() {
      this.$router.push({ name: 'debtinfo', params: { detail: this.detail } })
    },
    //同意按钮
    AgreeClick() {
      this.isCover = false
      this.isTost = false
      postDebt().then(res => {
        console.log(res)
        //更新用户状态
        this.saveHasEnterXiache(true)
        this.$router.push({name:`expandProduct`,query:{id:this.goodsId}})
      })
    },
    //拒绝按钮
    RefuseClick() {
      this.isTost = false
      this.isCover = false
    },
    //返回首页同意按钮
    TrueClick(){
      setGiveUp({type:2}).then(res=>{
        this.isGo = false
        this.isCover = false
        //同步用户信息
        this.fetchUserInfos()
        //更新用户状态
        this.saveHasEnterXiache(true)
        this.$router.replace({ name: 'home' })
      }).catch(err=>{
        this.isGo = false
        this.isCover = false
      })
    },
    //返回首页再想想按钮
    NoClick(){
      this.isGo = false
      this.isCover = false
    },
    //获取子组件传过来的ID
    Father(n){
      console.log(n)
      this.goodsId = n
      if(this.is_agree == 0){
        this.isTost = true
        this.isCover = true
      }else {
        this.$router.push({name:`expandProduct`,query:{id:n}})
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  padding: 0 8px;
  min-height: 100%;
  background: url('../../assets/image/getoff/back-1.png') no-repeat;
  background-size: cover;
  .mesage {
    height: 26px;
    font-size: 24px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(220, 109, 34, 1);
    margin: 52px 0 62px;
    text-align: center;
  }
  .getoff {
    img {
      width: 100%;
      display: block;
    }
    .getoff-text {
      height: 110px;
      border: 4px solid rgba(6, 94, 111, 1);
      background: rgba(222, 63, 63, 1) url('../../assets/image/getoff/back-2.png') no-repeat;
      background-size: contain;
      text-align: center;
      p {
        height: 52px;
        padding: 20px 26px 4px;
        font-size: 15px;
        font-family: PingFangSC-Medium, PingFang SC;
        font-weight: 500;
        color: rgba(255, 217, 128, 1);
        line-height: 26px;
      }
      span {
        color: #0b5f6e;
        font-size: 14px;
        font-weight: 600;
      }
    }
  }
  .go-btn {
    padding-bottom: 42px;
    .btn {
      width: 173px;
      height: 30px;
      line-height: 30px;
      border-radius: 20px;
      border: 1px solid rgba(185, 133, 133, 1);
      margin: 30px auto 0px;
      text-align: center;
      font-size: 16px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(185, 133, 133, 1);
    }
    .return-btn {
      text-align: center;
      padding-bottom: 42px;
      height: 18px;
      font-size: 13px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(112, 112, 112, 1);
      line-height: 18px;
    }
  }
  .cover {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
  }
  .getoff-dialog {
    position: fixed;
    top: 45%;
    left: 50%;
    width: 320px;
    overflow: hidden;
    font-size: 16px;
    background-color: rgba(255, 221, 198, 1);
    border-radius: 8px;
    -webkit-transform: translate3d(-50%, -50%, 0);
    transform: translate3d(-50%, -50%, 0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
    -webkit-transition: 0.3s;
    transition: 0.3s;
    -webkit-transition-property: opacity, -webkit-transform;
    transition-property: opacity, -webkit-transform;
    transition-property: transform, opacity;
    transition-property: transform, opacity, -webkit-transform;
    .getoff-dialog_header {
      padding-top: 17px;
      font-size: 18px;
      font-family: PingFangSC-Medium, PingFang SC;
      font-weight: 500;
      color: rgba(6, 94, 111, 1);
      text-align: center;
    }
    .getoff-dialog_content {
      .message-title {
        height: 21px;
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(6, 94, 111, 1);
        line-height: 21px;
        text-align: center;
        margin-top: 17px;
      }
      .getoff-dialog_box {
        font-size: 14px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(6, 94, 111, 1);
        padding: 0 20px;
        h3 {
          text-align: center;
          font-size: 14px;
          padding: 20px 0;
        }
        span{
          display: block;
          text-align: center;
          margin-top: 20px;
        }
        p{
          margin: 10px 0 20px;
          text-align: center;
        }
      }
    }
    .getoff-dialog_btn {
      display: flex;
      text-align: center;
      background-color: rgba(255, 221, 198, 1);
      border-top: 1px solid rgba(255, 182, 182, 1);
      .getoff-dialog_btn-x {
        flex: 1;
        height: 50px;
        line-height: 48px;
      }
      .getoff-dialog_btn-t {
        flex: 1;
        height: 50px;
        line-height: 48px;
        background-color: rgba(222, 63, 63, 1);
        font-size: 16px;
        color: rgba(255, 255, 255, 1);
      }
    }
  }
}
</style>
