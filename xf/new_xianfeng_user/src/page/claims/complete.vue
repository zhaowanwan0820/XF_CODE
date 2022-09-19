<template>
  <div class="page-wrapper">
    <common-header back-home title="签署协议" style="border-bottom: 1px solid #F4F4F4;"></common-header>
    <div class="wrapper">
      <h2>签署完成</h2>
      <div class="content">
        <p>您已完成法大大协议签署，待其他两方完成签署后，本协议正式生效。协议生效10工作日后，您可以登录乙方官方平台查询您的置换债权信息。</p>
        <p>请您保存乙方官方平台访问地址：m.wjzcgl.com<!--<a href="https://m.wjzcgl.com" target="_blank">m.wjzcgl.com</a>--> 。</p>
      </div>
    </div>
  </div>
</template>
<script>
import commonHeader from "@/components/CommonHeader.vue"
export default {
  name: 'complete',
  data() {
    return {
    }
  },
  components: {
    commonHeader
  },
  methods: {
    back(){
      this.$router.go(-1)
    },
  }
}
</script>
<style lang="less" scoped>
.page-wrapper {
  width: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  .header{
    z-index:10;
  }

  .wrapper {
    margin-top: 40px;
    z-index: 0;
    padding: 0 20px;

    h2 {
      padding: 20px 0;
      text-align: center;
      font-size: 18px;
    }
    p {
      font-size: 14px;
      line-height: 22px;
      color: #666666;
      text-indent: 28px;
      &+p {
        margin-top: 10px;
      }
    }
  }
}
</style>
