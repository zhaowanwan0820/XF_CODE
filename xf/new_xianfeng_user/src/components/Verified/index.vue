<!-- 实名认证提示 -->
<template>
  <section v-show="value" class="modal">
    <main class="modal-body">
      <!--   头部   -->
      <header v-if="!$slots.header" class="modal-title">
        <h1>{{ title }}</h1>
      </header>
      <slot v-else name="header" />
      <!--   内容   -->
      <div v-if="!$slots.default" class="modal-content">
        <h2 v-if="showTitle">实名认证</h2>
        <div class="modal-text">
          根据工信部要求及《网络安全法》相关规定为保障落实实名制要求，建立健全用户信息保护制度，配合信息安全管理制度实施，现需要您通过实名制认证完善个人信息。
        </div>
        <div class="modal-confirm">
          <a @click="$emit('confirm')">{{ confirmText }}</a>
          <span class="quit" @click="quit">退出></span>
        </div>
      </div>
      <slot v-else />
      <slot name="footer"/>
      <!--   关闭   -->
      <footer v-if="hasClose">
        <div class="modal-footer-margin" />
        <div class="modal-footer-close" @click="hide">
          <img src="./images/close.png" alt="关闭弹框" title="关闭弹框">
        </div>
      </footer>
    </main>

  </section>
</template>

<script>
import {mapMutations} from "vuex";

export default {
  name: "Verified",
  props: {
    title: {
      type: String,
      default: '提示'
    },
    value: {
      type: Boolean,
      default: false
    },
    confirmText: {
      type: String,
      default: '立即认证'
    },
    showTitle:{
      type:Boolean,
      default:false
    },
    hasClose:{
      type:Boolean,
      default:false
    }
  },
  methods: {
    ...mapMutations({
      clearToken: 'clearToken'
    }),
    hide() {
      this.$emit('input', false)
    },
    quit() {
      this.clearToken();
      localStorage.removeItem('m_assets_garden', {});
      localStorage.removeItem('is_set_pay_password', '');
      localStorage.removeItem('xianfeng', '');
      this.$router.push({
        path: "/login"
      });
    },
  }
}
</script>

<style scoped lang="less">
@color-bg: #fff;
.center {
  top: 50%;
  left: 50%;
  transform: translate3d(-50%, -50%, 0);
}

.modal {
  position: fixed;
  width: 100%;
  height: 100%;
  z-index: 999;
  background: rgba(#000000, .7);
  &:extend(.center);

  .modal-body {
    position: absolute;
    &:extend(.center);
    z-index: 20;
  }

  .modal-title {
    position: relative;
    margin: 0 -20px;
    z-index: 12;

    h1 {
      width: 346px;
      background: url("./images/title.png") no-repeat center center;
      background-size: 100% auto;
      text-align: center;
      color: @color-bg;
      height: 64px;
      line-height: 52px;
      font-size: 24px;
      text-shadow: 2px 0 1px rgba(#651c03, .5);
    }
  }

  .modal-content {
    background: @color-bg;
    border-radius: 5px;
    position: relative;
    padding: 40px;
    text-align: center;

    &::before {
      content: '';
      position: absolute;
      background: @color-bg;
      height: 40px;
      width: 100%;
      top: -30px;
      left: 0;
    }

    h2 {
      position: relative;
      color: #333;
      font-size: 15px;
      margin-bottom: 30px;

      &::before, &::after {
        content: '';
        position: absolute;
        background: #333333;
        height: 1px;
        width: 59px;
        z-index: 12;
        top: 50%;
        transform: translateY(-50%);
      }

      &::before {
        left: 0;
      }

      &::after {
        right: 0;
      }
    }

    .modal-text {
      color: #666;
      line-height: 22px;
      font-size: 13px;
      text-align: left;
      text-indent: 26px;
    }

    .modal-confirm {
      margin: 52px 0 0;
      text-align: center;
      a {
        display: block;
        background: linear-gradient(90deg, #d42424 0%, #ff3a3a 100%);
        height: 45px;
        line-height: 45px;
        border-radius: 40px;
        font-size: 15px;
        color: @color-bg;
        margin-bottom: 23px;
      }
      .quit {
        display: inline;
        color: #513aff;
        font-size: 12px;
      }
    }
  }

  .modal-footer-margin {
    width: 1px;
    height: 64px;
    margin: 0 auto;
    background: @color-bg;
  }

  .modal-footer-close {
    width: 36px;
    height: 36px;
    margin: 0 auto;

    img {
      width: 100%;
    }
  }
}
</style>
