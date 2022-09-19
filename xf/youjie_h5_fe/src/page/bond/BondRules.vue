<template>
  <div class="container">
    <mt-header class="header" fixed title="积分的兑换规则"> </mt-header>
    <div class="bond-wrapper">
      <div class="bond-content-wrapper">
        <div class="content-wrapper" v-on:scroll.passive="handleScroll">
          <div class="content">
            <section v-for="(a1, i1) in rules" :key="i1">
              <template v-for="(a2, i2) in a1">
                <p class="head" v-if="typeof a2 == 'string'" :key="`${i1}${i2}`">{{ a2 }}：</p>
                <template v-for="(a3, i3) in a2" v-else>
                  <p v-html="`${i3 + 1}.${a3}${i3 == a2.length - 1 ? '。' : '；'}`" :key="i3"></p>
                </template>
              </template>
            </section>
          </div>
        </div>
        <div class="content-mask mask-1" v-if="maskStatus > 0"></div>
        <div class="content-mask mask-2" v-if="maskStatus < 2"></div>
      </div>
      <div class="button" v-on:click="goBack">
        <img class="icon-close" src="../../assets/image/hh-icon/icon-关闭.svg" />
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { Toast, Header } from 'mint-ui'
export default {
  name: 'bondRules',
  data() {
    return {
      maskStatus: 0,
      rules: [
        [
          [
            '可兑换积分的债权项目类型，有「省心计划」、「智选系列」两类',
            '可兑换积分的债权项目状态，有「展期中」、「处置中」、「还款中」三种',
            '债权的转让状态为「转让中」、「已转让」状态时，该笔债权不支持兑换积分',
            '单笔债权余额不足100元的，或者单笔债权兑换积分后剩余余额不足100元的，在兑换积分时需一次性兑换该笔债权的全部金额'
          ]
        ] /*,
        [
          '认购规则',
          [
            '兑换积分申请提交后，已选择的债权将自动发起债权转让，债权受让人将以0.01元进行认购',
            `认购成功后，${this.utils.storeNameForShort}立即发送相应积分至用户账户`,
            '（已兑换债权金额+可兑换债权金额）*10%<=2万，则单人累计兑换限额=2万；<br />（已兑换债权金额+可兑换债权金额）*10%>2万，则单人累计兑换限额=（已兑换债权金额+可兑换债权金额）*10%，不足100的部分向上取整百'
          ]
        ]*/
      ]
    }
  },
  methods: {
    handleScroll(e) {
      this.maskStatus = Math.ceil(
        e.target.scrollTop / (e.target.scrollHeight - Math.max(e.target.clientHeight, e.target.offsetHeight) - 9)
      )
    },
    goBack() {
      if (window.history.length <= 1) {
        this.$router.push({ path: '/' })
        return false
      } else {
        this.$_goBack()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  background-color: $mainbgColor;
  .header {
    @include header;
    padding-top: 10px;
    height: 70px;
  }
  .button {
    height: 75px;
    .icon-close {
      width: 24px;
      height: 24px;
      display: block;
      margin: 0 auto;
    }
  }
  .bond-wrapper {
    height: 100%;
    margin-top: 45px;
    padding-top: 26px;
    background-color: #fff;
    display: flex;
    flex-direction: column;
  }
  .bond-content-wrapper {
    margin: 0 9px 0 15px;
    flex: 1;
    display: flex;
    position: relative;
  }
  .content-wrapper {
    -webkit-overflow-scrolling: touch;
    overflow-y: scroll;
    // 滚动条整体部分
    &::-webkit-scrollbar {
      width: 6px;
    }
    // 滑块
    &::-webkit-scrollbar-thumb {
      background: #b6bbd0;
      border-radius: 5px;
    }
    // 滚动条的轨道的两端按钮，允许通过点击微调小方块的位置
    &::-webkit-scrollbar-button {
      height: 0;
    }
    // 滚动条的轨道（里面装有Thumb）
    &::-webkit-scrollbar-track {
      background: #fff;
    }
    &::-webkit-scrollbar-track-piece {
      background: #fff;
    }
  }
  .content {
    font-size: 14px;
    line-height: 24px;
    color: $subbaseColor;
    text-align: justify;
    padding-right: 10px;
    text-indent: -15px;
    margin-left: 15px;
    .head {
      color: $baseColor;
      margin: 12px 0;
    }
    p + p {
      margin-top: 15px;
    }
  }
  .content-mask {
    width: 100%;
    height: 20px;
    background-color: #fff;
    position: absolute;
    left: -6px;
    z-index: 1;
    &.mask-1 {
      @include linear-vgradient(#fff, rgba(255, 255, 255, 0));
      top: 0;
    }
    &.mask-2 {
      @include linear-vgradient(rgba(255, 255, 255, 0), #fff);
      bottom: 0;
    }
  }
}
</style>
