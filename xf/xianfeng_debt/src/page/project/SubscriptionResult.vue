<template>
  <div>
    <van-nav-bar :title="title" left-arrow left-text="返回首页" @click-left="onClickLeft" />
    <div class="result">
      <div class="result-status">
        <div v-if="$route.query.code == 0">
          <van-icon name="more" color="#04B1A4" size="60px" />
          <div class="result-text">认购已提交</div>
          <div class="hint">
            您需向出让方转账支付，并在系统中提交转账信息。 超时未完成，系统将取消交易，定对您的账号进行处罚。
          </div>
          <div>
            剩余时间：<van-count-down class="countdown" :time="$route.query.time * 1e3" format="HH 时 mm 分 ss 秒" />
          </div>
        </div>
        <div v-else>
          <van-icon name="clear" color="#DE7474" size="60px" />
          <div class="result-text">认购失败</div>
          <div class="hint">认购失败原因：{{ $route.query.reason }}</div>
        </div>
      </div>
      <div v-if="$route.query.code == 0">
        <van-button
          class="submit-btn"
          type="primary"
          @click="
            $router.push({
              name: 'transferpayments',
              query: { products: $route.query.products, id: $route.query.id }
            })
          "
          >去支付</van-button
        >
        <van-button class="submit-btn" type="default" @click="$router.push('/')">稍后支付</van-button>
      </div>
      <div v-else>
        <van-button class="submit-btn" type="primary" @click="$router.push({ name: 'targetList' })"
          >继续认购</van-button
        >
        <van-button class="submit-btn" type="default" @click="$_goBack()">返回</van-button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SubscriptionResult',
  data() {
    return {
      title: '认购结果'
    }
  },
  methods: {
    onClickLeft() {
      this.$router.push({
        path: '/debtMarket'
      })
    }
  }
}
</script>

<style lang="less" scoped>
.result {
  height: 100vh;
  background-color: #fff;
  padding: 0 40px;
  text-align: center;
  .result-status {
    padding-top: 60px;
    margin-bottom: 30px;
    .result-text {
      font-size: 24px;
      margin-top: 10px;
    }
    .hint {
      font-size: 14px;
      margin-top: 10px;
    }
  }
  .submit-btn {
    width: 100%;
    margin-top: 20px;
  }
  .countdown {
    display: inline-block;
  }
}
</style>
