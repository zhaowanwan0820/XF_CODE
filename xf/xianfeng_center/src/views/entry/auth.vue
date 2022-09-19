<template>
  <div class="auth-wrapper">
    <common-header />
    <main class="main">
      <section class="context">
        <p class="nickname">尊敬用户您好：</p>
        <p>
          您将授权登录 <span class="title">【{{ platformName }}】</span>
        </p>
      </section>
    </main>
    <footer class="footer">
      <van-button class="submit" @click="onSubmit">好的</van-button>
      <van-button class="cancel" @click="onCancel">取消</van-button>
    </footer>
  </div>
</template>

<script>
import CommonHeader from 'components/common/Header'
import { mapActions, mapGetters } from 'vuex'

export default {
  name: 'Auth',
  components: {
    CommonHeader,
  },
  computed: {
    ...mapGetters({
      platformName: 'platformName',
      redirectUrl: 'redirectUrl',
    }),
  },
  mounted() {
    // appid: "666"
    // redirect_url: "https://m.youjiemall.com"
    // response_type: "code"
    this.changeEnv(this.$route.query)
  },
  methods: {
    ...mapActions({
      changeEnv: 'changeEnv',
    }),
    onSubmit() {
      this.$router.replace({ name: 'login' })
    },
    onCancel() {
      this.__goBack()
    },
  },
}
</script>

<style lang="scss">
@mixin inner {
  width: 95%;
  max-width: 330px;
  margin: 0 auto;
}

.auth {
  &-wrapper {
    height: 100%;
    padding-top: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    .main {
      @include inner;
      flex: 1;
      overflow-x: hidden;
      .title {
        font-size: 18px;
        color: #3833df;
        text-align: center;
        margin-top: 30px;
      }
      .nickname {
        font-size: 14px;
        text-align: left;
        margin-left: 55px;
        margin-top: 30px;
      }
      .context {
        font-size: 14px;
        color: #4a4a4a;
        text-align: center;
      }
    }
    .footer {
      height: 84px;
      flex: 0 0 auto;
      padding-left: 38px;
      padding-top: 24px;
      margin-top: 10px;
      border-top: 1px solid #ededed;
      .van-button {
        width: 140px;
        height: 40px;
        border-radius: 100px;
        border-radius: 4px;
        &.submit {
          color: #fff;
          background: #3834df;
        }
        &:last-child {
          color: #9b9b9b;
          margin-left: 20px;
        }
      }
    }
  }
}
</style>
