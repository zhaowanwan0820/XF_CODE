<template>
  <div class="header">
    <div class="wrap">
      <div :class="['arrow',{'back-home':backHome}]"><img  @click="back" v-if="showArrow" src="../static/images/back-arrow.png" alt=""><span v-if="backHome">返回首页</span></div>
      <div class="title">{{title}}</div>
    </div>
  </div>
</template>

<script>
  export default {
    // props:["title"],
    props:{
      title:{
        type:String,
        default:function(){
          return ''
        }
      },
      showArrow:{
        type:Boolean,
        default:function(){
          return true
        }
      },
      toEditPhoneIndex:{
        type:Boolean,
        default:function(){
          return false
        }
      },
      backHome:{
        type:Boolean,
        default:false
      }
    },
    data(){
      return{

      }
    },
    methods:{
      back(){
        if(this.toEditPhoneIndex){
          this.$router.push({
                path: "/security",
                query: {
                    userInfoObj:this.$route.query.userInfo,
                    currentPhone:this.$route.query.currentPhone,
                }
            });
        } else if (this.backHome) {
          this.$router.push({name:'home'})
          // this.$router.go(-2)
          // setTimeout(()=>{
          //   window.location.reload()
          // },100)
        }else{
          window.history.go(-1);
        }

      }
    }
  }
</script>

<style lang="less" scoped>
  .header {
    height: 36px;
    line-height: 36px;
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
        display: flex;
        align-items: center;
        &.back-home {
          color: #666;
        }

        img {
          width: 30px;
          height: 30px;
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
