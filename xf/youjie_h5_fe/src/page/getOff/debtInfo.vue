<template>
    <div class="container">
        <div class="h-container">
            <mt-header class="header" :title="title">
                <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
            </mt-header>
        </div>
        <div class="box" v-for="(item,index) in detail.detail" :key="index">
            <h4>项目名称：{{item.name}}</h4>
            <p>债权金额：{{item.account}}</p>
        </div>
    </div>
</template>

<script>
  export default {
    name: 'debtInfo',
    data(){
      return {
        title:'',
        detail:[]
      }
    },
    components:{},
    created() {
      console.log(this.$route)
      this.title = this.$route.meta.title
      this.detail = this.$route.params.detail
    },
    computed:{},
    methods:{
      goBack(){
        this.$router.go(-1)
      }
    }
  }
</script>

<style lang="scss" scoped>
.container{
    .h-container {
        height: 44px;
        width: 100%;
        border-bottom: 1px solid #f4f4f4;
    }
    .header {
        @include header;
        @include thin-border(#f4f4f4, 15px);
        width: 100%;
    }
    .box{
        background-color: #fff;
        padding: 20px 15px;
        margin-bottom: 10px;
        h4{
            padding-bottom: 20px;
        }
    }
}
</style>