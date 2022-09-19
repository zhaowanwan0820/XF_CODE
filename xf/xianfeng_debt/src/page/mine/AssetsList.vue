<template>
    <div class="container">
        <div class="title">机构</div>
        <div class="box" @click="goToMechanism">
            <div class="box-name">{{ArrList.name}}</div>
            <div class="box-info">
                <p><span>¥</span>{{utils.toThousands(ArrList.confirm)}}</p>
                <img src="../../assets/image/mine/right-3.png" alt="">
            </div>
        </div>
    </div>
</template>

<script>
  import {getTotal} from '../../api/mine'
  export default {
    name: 'AssetsList',
    data () {
      return {
        ArrList:[]
      }
    },
    computed: {},
    created() {
      this.getData();
    },
    methods: {
      getData(){
        getTotal()
          .then(res=>{
            if(res.code == 0){
              this.ArrList = res.data[1];
            }
          })
          .catch(ree=>{

          })
      },
      goToMechanism(){
        this.$router.push({path:`mechanism/${this.ArrList.platform_id+'&'+this.ArrList.platform_user_id}`})
      }
    }
  }
</script>

<style lang="less" scoped>
.container{
    background-color: #fff;
    padding: 0 20px;
    .title{
        height:22px;
        font-size:16px;
        font-family:PingFangSC-Medium,PingFang SC;
        font-weight:500;
        color:rgba(64,64,64,1);
        line-height:22px;
        padding: 15px 0 10px;
        border-bottom: 1px dashed rgba(244, 244, 244, 1);
    }
    .box{
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 0;
        border-bottom: 1px dashed rgba(244, 244, 244, 1);
        .box-name{
            height:20px;
            font-size:14px;
            font-family:PingFangSC-Regular,PingFang SC;
            font-weight:400;
            color:rgba(102,102,102,1);
            line-height:20px;
        }
        .box-info{
            display: flex;
            align-items: center;
            p{
                font-size:16px;
                font-family:DINPro-Regular,DINPro;
                font-weight:400;
                color:rgba(102,102,102,1);
                margin-right: 10px;
                span{
                    font-size:14px;
                    font-family:PingFangSC-Regular,PingFang SC;
                    font-weight:400;
                    color:rgba(102,102,102,1);
                    margin-right: 1px;
                }
            }
            img{
                width: 8px;
                height: 13px;
            }
        }
    }
}
</style>