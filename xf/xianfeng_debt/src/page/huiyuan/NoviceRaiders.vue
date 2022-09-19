<template>
  <div>
    <img src="../../assets/image/home/novice/noviceRaiders@2x.png" width="100%" alt="" />
    <div class="raiders">
      <div class="module-half">

        <div class="catalog">求购范围</div>
        <p>
          本专区的所有求购信息，仅针对借款人为“汇源”的债权。您可以在专区内出售您手中借款人为“汇源”的债权。
        </p>

        <div class="catalog">关键词解读</div>
        
        <div class="subcatalog">求购债权总额：</div>
        <p>是本次求购债权的债权总金额。</p>
        <div class="subcatalog">已购债权总额：</div>
        <p>指在本次求购债权总金额中，求购人已经成功求购到的总额。</p>
        <div class="subcatalog">剩余求购额度：</div>
        <p>本次求购中，剩余可收购的债权额度。</p>
      </div>
      <!--      流程-->
      <div class="module-half divide">
        <div class="catalog">相关流程</div>
      
        <div class="subcatalog">出售流程：</div>
        <img class="process-img" src="../../assets/image/huiyuan/xinshouyindao-1.png" width="100%" alt="" />
       
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
