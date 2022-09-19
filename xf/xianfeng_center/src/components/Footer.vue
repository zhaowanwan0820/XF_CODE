<template>
  <footer class="footer">
    <div>
      <van-button class="submit-btn" :disabled="isdisabled" color="#3833de" :type="type" @click="$emit('submit')">
        签署协议并转让
      </van-button>
    </div>
    <p>
      点击签署协议后输入交易密码，您将跳转至法大大平台进行线上签约，您也可以点击<a
        @click="$router.push({ name: 'debt_agreement_v2' })"
        >《债权转让协议范本》</a
      >预览协议。
    </p>
  </footer>
</template>

<script>
export default {
  name: 'Footer',
  props: {
    isdisabled: {
      type: Boolean,
      default: false,
    },
    type: {
      type: String,
      default: 'primary',
    },
  },
}
</script>

<style scoped lang="scss">
.footer {
  padding: 0 13px;

  .submit-btn {
    width: 100%;
    font-size: 16px;
    height: 40px;
    border-radius: 7px;
  }

  p {
    padding: 20px 0;
    font-size: 13px;
    line-height: 24px;
    color: #555;

    a {
      color: #3833de;
    }
  }
}
</style>
