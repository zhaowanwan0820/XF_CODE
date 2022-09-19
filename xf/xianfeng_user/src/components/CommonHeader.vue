<template>
  <div class="header">
    <div class="wrap">
      <div class="arrow"><img  @click="back" v-if="showArrow" src="../static/images/back-arrow.png" alt=""></div>
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
      }
    },
    data(){
      return{

      }
    },
    methods:{
      back(){
        window.history.go(-1);
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
