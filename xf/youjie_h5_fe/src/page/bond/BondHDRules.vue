<template>
  <div class="container">
    <mt-header class="header" title="积分使用说明">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="introduce-wrapper">
      <div class="what-is-hd">
        <p class="title-wrapper">积分是什么</p>
        <p>
          积分是用户在{{ utils.storeName }}签到、分享、评论产生的积分，在{{
            utils.storeName
          }}消费可使用积分享受折扣优惠。
        </p>
      </div>
      <div class="how-to-get-hd">
        <p class="title-wrapper">积分如何获取</p>
        <p>积分可通过注册、登陆、完善个人信息、签到、抽奖、购物、评价、晒单、拉新等方式获得。</p>
        <!-- <table>
          <thead>
            <tr class="table-head">
              <th>积分获得</th>
              <th>数额</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>注册</td>
              <td>注册送50积分</td>
            </tr>
            <tr>
              <td>登录</td>
              <td>登陆赠送0.1积分</td>
            </tr>
            <tr>
              <td>首次填写性别</td>
              <td>送5积分</td>
            </tr>
            <tr>
              <td>首次填写生日</td>
              <td>送5积分</td>
            </tr>
            <tr>
              <td>首次上传头像</td>
              <td>送5积分</td>
            </tr>
            <tr>
              <td>签到</td>
              <td>每连续签到3日，送2积分；</td>
            </tr>
            <tr>
              <td>抽奖</td>
              <td>每次消耗10积分（奖品额度为50、100、200,中奖概率为3%/1%/0.5%;其他奖品为实物）</td>
            </tr>
            <tr>
              <td>购物</td>
              <td>购物1/100返积分（以实付金额为准，不包括余额、优惠券、打折卡、满减卡等减免金额）</td>
            </tr>
            <tr>
              <td>文字评价</td>
              <td>文字好评送5积分</td>
            </tr>
            <tr>
              <td>晒单评价</td>
              <td>文字+图片晒单送10积分</td>
            </tr>
            <tr>
              <td>邀新奖励</td>
              <td>升级成为尊享会员，赠送200积分</td>
            </tr>
            <tr>
              <td>升级奖励</td>
              <td>升级成为尊享会员，赠送200积分</td>
            </tr>
          </tbody>
        </table> -->
      </div>
      <div class="how-to-use-hd">
        <p class="title-wrapper">积分如何使用</p>
        <div class="use-content">
          <p>
            <span>1、</span>
            <span>用户购物时在支付页面选择使用积分抵扣，并根据支付 订单详情确定积分可使用的数量；</span>
          </p>
          <p>
            <span>2、</span>
            <span
              >用户在支付页面根据订单详情和自己的支付情况可请求 他人代为支付积分。用户在订单支付页面可选取代支付
              账号，在用户点击支付时完成积分代支付；
            </span>
          </p>
          <p>
            <span>3、</span>
            <span>用户使用积分抵扣额度与人民币比例为1:1；</span>
          </p>
          <p>
            <span>4、</span>
            <span>用户账户内的积分仅支持当前账户支付使用以及代替他 人支付，不支持转赠他人、交易流通；</span>
          </p>
          <p><span>5、</span><span>订单中积分优惠折扣支付部分不开具发票。</span></p>
        </div>
      </div>
      <div class="hd-use-range">
        <p class="title-wrapper">积分适用范围</p>
        <div class="use-content">
          <p>
            <span>1、</span>
            <span
              >积分可用与于{{ utils.storeName }}第三方【所有商品】及自营商 品；具体使用范围以实际可支付订单为准；</span
            >
          </p>
          <p>
            <span>2、</span>
            <span>积分抵扣使用上限为商品支付价格的99%；</span>
          </p>
          <p>
            <span>3、</span>
            <span
              >{{ utils.storeName }}会定期推出积分换购、抽奖、拼团、夺宝等
              活动，用户在可使用自己账户里的积分参与任意活动。</span
            >
          </p>
        </div>
      </div>
      <div class="hd-surplus">
        <p class="title-wrapper">积分有效期</p>
        <p>积分属于{{ utils.storeName }}高级通用积分，积分有效期限规则如下：</p>
        <div class="use-content">
          <p>
            <span>1、</span>
            <span>用户通过第二条规定(积分如何获取)的方式获得的积分 有效期：</span>
          </p>
          <p>
            <span></span>
            <span class="word-color"
              >积分有效期最长为1年，从获得积分开始至次年末，逾 期自动清零；后续积分以自然年清零；</span
            >
          </p>
          <p>
            <span>2、</span>
            <span>用户通过其它方式获得的积分有效期：</span>
          </p>
          <p>
            <span></span>
            <span class="word-color">积分无有效期。即从积分获得开始一直有效，直至用户 将积分支付消耗完毕。</span>
          </p>
        </div>
      </div>
      <div class="hd-expires">
        <p class="title-wrapper">积分余额及明细查询</p>
        <div>
          <p>用户账户里的积分余额、收支明细可以通过{{ utils.storeName }}App或h5页面查询。</p>
          <p>查询具体路径：{{ utils.storeName }} - 我的 - 我的积分 - 查询余额 - 查看明细。</p>
        </div>
      </div>
      <div class="hd-use-persons">
        <p class="title-wrapper">积分使用人群</p>
        <div class="use-content">
          <p>
            <span>1、</span>
            <span>积分只限{{ utils.storeName }}的实名手机号注册用户使用；</span>
          </p>
          <p>
            <span>2、</span>
            <span>若未验证手机号用户，首次获得积分需要验收注册用户手机号；</span>
          </p>
          <p>
            <span>3、</span>
            <span>合作平台用户，需要在{{ utils.storeName }}验证身份后，方可使用。</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  name: 'BindHDRules',
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>
<style lang="scss" scoped>
.introduce-wrapper {
  height: 100%;
  padding: 15px;
  background-color: #fff;
  p {
    font-size: 13px;
    font-weight: 400;
    color: #666666;
    margin-bottom: 10px;
    span:first-child {
      // display: inline-flex;
      width: 25px;
    }
  }
  p.title-wrapper {
    height: 22px;
    font-size: 16px;
    font-weight: bold;
    color: #404040;
    line-height: 22px;
  }
  .use-content p {
    display: flex;
  }
  table {
    tr {
      th,
      td {
        padding: 6px 10px;
      }
      height: 27px;
      @include sc(11px, #75522f);
      th {
        text-align: left;
        font-size: 13px;
        background-color: #f2ebdf;
      }
      td:first-child {
        width: 98px;
      }
    }
    tbody {
      tr:nth-child(odd) {
        background-color: #fdf9f3;
      }
    }
  }
  .word-color {
    color: #999;
  }
}
</style>
