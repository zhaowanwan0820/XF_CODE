<template>
    <div class="container">
        <van-tabs v-model="active" @click="TableClick">
            <van-tab title="全部">
                <project-child :listArr="params"></project-child>
            </van-tab>
            <van-tab title="还款中">
                <project-child :listArr="params"></project-child>
            </van-tab>
            <van-tab title="已还清">
                <project-child :listArr="params"></project-child>
            </van-tab>
        </van-tabs>
    </div>
</template>

<script>
    import ProjectChild from './child/projectChild'
    import { Toast } from 'vant';
  export default {
    name: 'ProjectList',
    components: {
      ProjectChild
    },
    data () {
      return {
        active:0,
        params:{
          platform_id:0, //必传 平台id
          platform_user_id:0, //必传 平台user_id
          status:0, //必传 0:全部 1:还款中 15:已结清
          page:0, //必传
          size:10, //必传
        },
        childList:[]
      }
    },
    computed: {},
    created() {
      this.params.platform_id = this.$route.params.id.platform_id;
      this.params.platform_user_id = this.$route.params.id.platform_user_id;
    },
    methods: {
      TableClick(e){
        if(e == 2){
          this.params.status = 15;
        }else {
          this.params.status = e;
        }
      },

    }
  }
</script>

<style lang="less" scoped>
    /deep/.van-tabs__content{
        padding-top: 10px;
        background:rgba(244,244,244,1);
    }
.container{
    background-color: #fff;
}
</style>