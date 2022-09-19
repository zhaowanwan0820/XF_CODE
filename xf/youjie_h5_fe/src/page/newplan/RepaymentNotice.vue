<template>
  <div class="container">
    <mt-header class="header" title="还款兑付通知">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="desc">
      静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容静态内容
    </div>
  </div>
</template>

<script>
  export default {
    name: 'RepaymentNotice',
    data(){
      return{

      }
    },
    methods:{
      goBack() {
        this.$_goBack()
      },
    }
  }
</script>

<style lang="scss" scoped>
  .container{
    background-color: #fff;
    height: 100%;
    .desc{
      padding: 20px 15px;
      font-size: 14px;
      line-height: 22px;
      color: #666;
    }
  }
</style>