<template>
  <div class="container">
    <mt-header class="header" title="无锡高端MiNi商墅">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="guide-body">
      <div class="guide-con">
        <h2>中隆喜悦天地W3·锡东新城地标·无锡</h2>
        <p class="keywords">关键词：长三角最后的价值洼地，最疯狂的补涨通道已经开启！</p>
        <p class="red">抗通胀、低风险、高回报的项目，不限购、不限贷，投资价值非常大。</p>
        <p class="red">品牌价值：</p>
        <p>
          香港路劲、新城吾悦两家上市公司强强联合的倾力打造，全无锡首个TOD地标综合体，打造锡东新城唯一集吃、喝、玩、乐、购、住为一体的核心商业地标标杆，类似上海的长宁龙之梦、人民广场来福士，更有高铁站护航。
        </p>
        <p class="red">政府规划并已落地产业：</p>
        <p>
          锡东新城作为无锡政府十二五期间重点规划打造的城市新中心，依托京沪高铁站点，重点发展总部经济、高科技产业、现代物流、金融服务、国际社区等现代服务业.
        </p>
        <p class="red">已入驻独角兽企业有：</p>
        <p>
          华为、GOGEL、微软、谷歌加速器、阿里巴巴等，2017年引进超百亿重大项目就有12个：中国南山车联网小镇、北京复华未来世界文旅项目、国内首只百亿基金中感物联网基金等已纷纷入驻，锡东新城已成为来自北京、上海、杭州和南京等城市高新科创载体，行业覆盖文化科技、智能硬件、互联网+等多个领域。
        </p>
        <p class="red">地段好交通好：</p>
        <p>
          我们W3高端MINI商域项目，位于无锡锡东新城高铁商务区核心版块，交通网络十分发达。离无锡东站仅600米，步行8分钟即可到达，东站到上海最快只要28分钟，往苏州仅需9分钟，距离苏南硕放机场12公里，15分钟车程；形成长三角半小时经济圈。
        </p>
        <p class="red">稀缺资源价格洼地：</p>
        <p>
          恒大、碧桂园、融创、海尔、龙湖、中梁等等前50强开发商齐聚锡东，一手住宅价格已达2.2万-3.1万，并已一房难求，而本项目精装修1.5-1.6万左右附赠200%面积，总价只有70多万起，绝对的价格洼地，未来的升值空间巨大。
        </p>
        <p class="red">性价比高：</p>
        <p>总价70万左右，单价1.5-1.6万左右，主力面积44平米。</p>
        <p class="red">发展潜力大：</p>
        <p>地区导入人口80-100万，产业规模达千亿，交通网络四通八达。</p>
        <p class="red">十年无忧托管：</p>
        <p>
          世界500强温德姆酒店和访先进的运营管理模式，高品质服务定位人群为企业高管、涉外人士等，预计月租金在3900-5500元左右，年回报可高达6.5%左右。
        </p>
        <p class="red">联系人：</p>
        <p>张经理：177 6517 0834</p>
        <p>巢经理：137 6427 5880</p>
      </div>

      <img src="../../assets/image/hh-icon/pageMiniss/1.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/2.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/3.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/4.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/5.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/6.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/7.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/8.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/9.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/10.jpg" alt="" />
      <img src="../../assets/image/hh-icon/pageMiniss/11.jpg" alt="" />
    </div>
  </div>
</template>
<script>
import { Header, Indicator } from 'mint-ui'
import { HeaderItem } from '../../components/common'
export default {
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>
<style lang="scss" scoped>
.header {
  @include thin-border(#f4f4f4);
  box-shadow: 0px 0px 5px rgba(252, 127, 12, 0.1);
}
.container {
  height: 100%;
  background-color: #fff;
  display: flex;
  flex-direction: column;
}
.guide-body {
  flex: 1;
  font-size: 0;
  overflow: auto;
  img {
    width: 100%;
    margin-bottom: 5px;
  }
}
.guide-con {
  padding: 0 6% 30px;
  h2 {
    @include sc(18px, #333);
    text-align: center;
    line-height: 60px;
  }
  p {
    @include sc(14px, #404040);
    text-indent: 2em;
    line-height: 30px;
    &.keywords {
      text-indent: 0;
      background: rgba(252, 127, 12, 0.1);
      padding: 6px 10px;
      color: #000;
      margin-bottom: 10px;
    }
    &.red {
      text-indent: 0;
      color: #fc7f0c;
    }
    .red {
      color: #fc7f0c;
    }
  }
}
</style>
