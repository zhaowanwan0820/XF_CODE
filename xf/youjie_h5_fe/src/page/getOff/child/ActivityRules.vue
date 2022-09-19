<template>
  <div class="rules">
    <h4>活动规则：</h4>
    <p>1、每位用户只可参加一次活动，只能领取一份礼包；</p>
    <p>
      2、礼包订单发生退款，不可再次领取礼包；退款金额将以有解积分形式退至您的有解积分账户，积分数为升级礼包所用的债权金额；
    </p>
    <p>
      3、同意升级新年超值礼包，升级礼包所用债权不支持退回；
    </p>
    <p>4、若离开活动页，则不可再次参加活动。</p>
    <p>5、如遇其他问题，请联系客服：010-8992-0001</p>
  </div>
</template>

<script>
export default {
  name: 'ActivityRules'
}
</script>

<style lang="scss" scoped>
.rules {
  padding: 0 4px;
  h4 {
    height: 17px;
    font-size: 12px;
    font-family: PingFangSC-Medium, PingFang SC;
    font-weight: 500;
    color: rgba(112, 112, 112, 1);
    line-height: 17px;
    margin-bottom: 15px;
  }
  p {
    font-size: 12px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(112, 112, 112, 1);
    line-height: 20px;
    margin-bottom: 9px;
  }
}
</style>
