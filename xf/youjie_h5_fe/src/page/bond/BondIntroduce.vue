<template>
  <div class="container">
    <mt-header class="header" title="如何兑换积分？">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="content1"></div>
      <div class="content2"></div>
      <div class="content3"></div>
      <div class="content4"></div>
      <div class="content5">
        <div class="bottom">
          <p>
            客服电话：<a :href="'tel:' + service_tel">{{ service_tel }}</a>
          </p>
          <p>周一到周日9:30-18:30</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
import { ENUM } from '../../const/enum'
export default {
  name: 'BondIntroduce',
  data() {
    return {
      service_tel: ENUM.SERVICE.MASTER_TEL
    }
  },
  methods: {
    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.content {
  flex: 1;
  overflow: auto;
  background-color: #fff;
}
.content1,
.content2,
.content3,
.content4,
.content5 {
  width: 100%;
}
.content1 {
  height: 233px;
  background: url('../../assets/image/hh-icon/h0-bond/1@2x.png') no-repeat;
  background-size: 375px 233px;
}
.content2 {
  height: 557px;
  background: url('../../assets/image/hh-icon/h0-bond/2@2x.png') no-repeat;
  background-size: 375px 557px;
}
.content3 {
  height: 605px;
  background: url('../../assets/image/hh-icon/h0-bond/3@2x.png') no-repeat;
  background-size: 375px 605px;
}
.content4 {
  height: 501px;
  background: url('../../assets/image/hh-icon/h0-bond/4@2x.png') no-repeat;
  background-size: 375px 501px;
}
.content5 {
  height: 686px;
  background: url('../../assets/image/hh-icon/h0-bond/5@2x.png') no-repeat;
  background-size: 375px 686px;

  position: relative;
  .bottom {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    margin-bottom: 10px;
    p {
      font-size: 12px;
      font-weight: 400;
      color: #b5b6b6;
      line-height: 17px;
      text-align: center;
      margin-bottom: 17px;
      a {
        color: #5b3527;
      }
    }
  }
}
</style>
