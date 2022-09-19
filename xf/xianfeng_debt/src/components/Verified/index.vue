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
        <h2>实名认证</h2>
        <div class="modal-text">
          根据要求，您需要先完成实名认证才可在平台进行后续操作，您可以点击下方按钮进行认证，也可以在【安全设置】中进行实名认证。
        </div>
        <div class="modal-confirm">
          <a @click="$emit('confirm')">{{ confirmText }}</a>
        </div>
      </div>
      <slot v-else />
      <!--   关闭   -->
      <footer>
        <div class="modal-footer-margin" />
        <div class="modal-footer-close" @click="hide">
          <img src="./images/close.png" alt="关闭弹框" title="关闭弹框">
        </div>
      </footer>
    </main>

  </section>
</template>

<script>
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
    }
  },
  methods: {
    hide() {
      this.$emit('input', false)
    }
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
      margin: 30px 0;
    }

    .modal-confirm {
      a {
        display: block;
        background: linear-gradient(90deg, #e34545 0%, #fc7777 100%);
        height: 45px;
        line-height: 45px;
        border-radius: 40px;
        font-size: 15px;
        color: @color-bg;
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
