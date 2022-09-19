<template>
  <div>
    <img src="../../assets/image/home/novice/noviceRaiders@2x.png" width="100%" alt="" />
    <div class="raiders">
      <div class="module-half">
        <div class="catalog">关键词解读</div>
        <div class="subcatalog">确权：</div>
        <p>
          确认原机构的债权。确权后，用户原有权益不变，亦不会受任何影响，原有债权与债务关系不变，用户与原机构的关系不变。
        </p>
        <div class="subcatalog">债权：</div>
        <p>
          是按照合同的约定或者依照法律的规定，在当事人间产生的特定的权利义务关系。享有权利的人是债权人，负有义务的人是债务人。
        </p>
        <div class="subcatalog">债权转让：</div>
        <p>是指不改变债权关系的内容，债权人通过协议而将其债权全部或部分转移于第三人的行为。</p>
        <!--        <div class="subcatalog">债权求购：</div>-->
        <!--        <p>有资质收购债权的机构通过有解债转信息平台发布求购计划，用户需自主发起转让求购计划内自己所拥有的债权。</p>-->
        <div class="subcatalog">发布转让：</div>
        <p>用户在有解债转信息平台自主发布债权转让信息。</p>

        <div class="subcatalog">认购债权：</div>
        <p>用户在有解债转信息平台购买债权。</p>
        <div class="subcatalog">转让金额：</div>
        <p>发起/发布债权转让的金额（待还本金）。</p>
        <div class="subcatalog">转让折扣：</div>
        <p>发起/发布债权转让的折扣。</p>
        <div class="subcatalog">转让价格：</div>
        <p>转让金额*转让折扣=转让价格</p>
        <div class="subcatalog">回本金额：</div>
        <p>回本金额：转让金额*转让折扣=回本金额</p>
      </div>
      <!--      流程-->
      <div class="module-half divide">
        <div class="catalog">相关流程</div>
        <div class="subcatalog">确权流程：</div>
        <img class="process-img" src="../../assets/image/home/novice/process1@2x.png" width="100%" alt="" />
        <div class="subcatalog">债权转让流程：</div>
        <img class="process-img" src="../../assets/image/home/novice/progress-debt-sell.png" width="100%" alt="" />
        <div class="subcatalog">债权认购流程：</div>
        <img class="process-img" src="../../assets/image/home/novice/progress-debt-buy.png" width="100%" alt="" />
      </div>
    </div>
    <div class="bottom">
      <img src="../../assets/image/home/novice/noviceRaiders-bottom@2x.png" width="100%" alt="" />
      <div class="bottom-text">- 感谢阅读 -</div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NoviceRaiders'
}
</script>

<style lang="less" scoped>
.raiders {
  background-color: #fff;
  border-radius: 8px;
  margin-top: -10px;
  margin-bottom: -10px;
  position: relative;
  z-index: 10;
  .module-half {
    padding: 25px 15px;
  }
  .catalog {
    font-size: 20px;
    line-height: 28px;
    text-align: center;
    &:before {
      content: '';
      display: inline-block;
      width: 15px;
      height: 15px;
      background-image: url('../../assets/image/home/novice/decoration1@2x.png');
      background-size: contain;
      margin-right: 7px;
    }
    &:after {
      content: '';
      display: inline-block;
      width: 15px;
      height: 15px;
      background-image: url('../../assets/image/home/novice/decoration2@2x.png');
      background-size: contain;
      margin-left: 7px;
    }
  }
  .subcatalog {
    display: inline-block;
    font-size: 16px;
    line-height: 24px;
    color: #fff;
    margin-top: 20px;
    background-color: #ffc058;
    border-radius: 3px;
    padding: 0 15px 0 12px;
    position: relative;
    left: 10px;
    &:before {
      content: '';
      display: inline-block;
      width: 2px;
      height: 24px;
      background-color: #ffc058;
      border-radius: 3px;
      position: relative;
      left: -22px;
      vertical-align: top;
    }
  }
  p {
    color: #666666;
    line-height: 24px;
    font-size: 15px;
    padding: 10px 0;
  }
  .divide {
    box-shadow: 0px -5px 16px 0px rgba(255, 192, 88, 0.11);
  }
  .process-img {
    margin-top: 20px;
  }
  .mode {
    color: #404040;
    font-size: 16px;
    font-weight: 700;
    line-height: 50px;
    margin-top: 10px;
  }
}
.bottom {
  position: relative;
  .bottom-text {
    position: absolute;
    left: 0;
    bottom: 24px;
    width: 100%;
    text-align: center;
    font-size: 14px;
    color: #fff;
  }
}
</style>
