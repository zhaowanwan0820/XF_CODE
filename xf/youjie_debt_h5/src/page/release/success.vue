<template>
  <div class="success">
    <img :src="img" alt="" />
    <div class="succ-text">
      <p class="p-title">发布成功</p>
      <p class="p-text" v-if='code'>认购码：{{ code }}</p>
      <p class="p-text" v-if='code'>交易前请将认购码告知认购方</p>
    </div>
    <van-button type="info" @click="$router.push({name:'projectList'})">继续债转</van-button>
    <div class="btn-gto" @click="$router.push({name:'mytransfer'})">查看我的债转</div>
  </div>
</template>

<script>
export default {
  name: 'success',
  data() {
    return {
      img: require('../../assets/image/success/success-1.png'),
      number_text: '--'
    }
  },
  created() {
    this.number_text = this.$route.params.id
    this.code = this.$route.query.code !=0?this.$route.query.code:false
  },
  methods: {

  }
}
</script>
<style lang="less" scoped>
.success {
  display: flex;
  flex-direction: column;
  align-items: center;
  img {
    width: 61px;
    height: 61px;
    margin-top: 67px;
  }
  .succ-text {
    text-align: center;
    margin: 22px 0 66px;
    .p-title {
      font-size:16px;
      font-family:PingFangSC-Regular,PingFang SC;
      font-weight:400;
      color:rgba(51,51,51,1);
      margin-bottom: 10px;
    }
    .p-text {
      font-size:12px;
      font-family:PingFangSC-Regular,PingFang SC;
      font-weight:400;
      color:rgba(153,153,153,1);
      margin-bottom: 10px;
    }
  }
  .van-button {
    width: 309px;
    height: 46px;
    background: rgba(252, 127, 12, 1);
    border-radius: 2px;
    border: 0;
    font-size:18px;
    font-family:PingFangSC-Regular,PingFang SC;
    font-weight:400;
    color: rgba(255, 255, 255, 1);
    letter-spacing: 1px;
    margin-bottom: 20px;
  }
  .btn-gto{
    width: 309px;
    height:46px;
    border-radius:2px;
    border:1px solid rgba(252,127,12,1);
    line-height: 46px;
    text-align: center;
    color: rgba(252, 127, 12, 1);
    font-size:18px;
    font-family:PingFangSC-Regular,PingFang SC;
    font-weight:400;
  }
}
</style>
