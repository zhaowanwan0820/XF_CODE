<template>
  <div class="container">
    <mt-header class="header" fixed>
      <header-item slot="left" v-bind:isClose="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="img-wrapper">
      <img src="../../assets/image/hh-icon/auth/icon-auth.png" alt />
    </div>
    <div class="content-wrapper">
      <div class="content">
        <div class="left">
          <div class="star"></div>
          <div class="line"></div>
          <div class="star"></div>
          <div class="line"></div>
          <div class="star"></div>
        </div>
        <div class="right">
          <h3>第1步&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;签署债权兑换服务协议</h3>
          <p>{{ utils.storeNameForShort }}需要得到您的授权，完成债转转让和给与您等额的积分。</p>
          <h3>第2步&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;上传手持身份证照片</h3>
          <p>上传手持身份证照片告诉{{ utils.storeNameForShort }}您本人同意服务协议。</p>
          <h3 class="h3-footer">已完成 {{ utils.storeName }} 积分购物</h3>
        </div>
      </div>
      <div class="button">
        <button @click="goAgreement">开始授权</button>
      </div>
    </div>
  </div>
</template>

<script>
import store from '../../store/index'
import $cookie from 'js-cookie'
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
export default {
  name: 'Auth',
  beforeRouteEnter(to, from, next) {
    $cookie.set('fromAuth', from['fullPath'])
    next()
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    goAgreement() {
      // 'https://m.huanhuanyiwu.com/h5/#/agreementPage'
      this.$router.push({ name: 'agreementPage' })
    }
  }
}
</script>
<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .header {
    background: rgba(0, 0, 0, 0);
  }
  .img-wrapper {
    width: 100%;
    height: 210px;
    background: url('../../assets/image/hh-icon/auth/bg-auth.png') no-repeat;
    background-size: 375px 210px;
    display: flex;
    flex-direction: column;
    align-items: center;
    img {
      width: 234apx;
      height: 34px;
      margin-top: 111px;
    }
  }
  .content-wrapper {
    border-radius: 15px;
    margin-top: -22px;
    background: #fffef3;
    flex: 1;
    .content {
      padding: 32px 24px 0 29px;
      display: flex;
      .left {
        width: 11px;
        padding-top: 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        .star {
          width: 11px;
          height: 11px;
          background: url('../../assets/image/hh-icon/auth/icon-star.png') no-repeat;
          background-size: 11px;
        }
        .line {
          width: 1px;
          height: 83px;
          border-left: 1px dashed #fde4a8;
          margin: 3px 0;
        }
      }
      .right {
        padding-left: 10px;
        h3 {
          font-size: 16px;
          line-height: 22px;
          font-weight: 600;
          color: #552e20;
        }
        p {
          font-size: 14px;
          line-height: 20px;
          color: rgba(85, 46, 32, 0.4);
          padding: 8px 0 29px 64px;
        }
        .h3-footer {
          margin-top: 6px;
        }
      }
    }
    .button {
      display: flex;
      justify-content: center;
      margin-top: 50px;
      button {
        width: 327px;
        height: 46px;
        background: rgba(119, 37, 8, 1);
        border: none;
        border-radius: 2px;
        outline: none;
        color: #fff;
        font-size: 18px;
      }
    }
  }
}
</style>
