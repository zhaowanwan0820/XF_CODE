<template>
  <div class="agreement-wrapper">
    <common-header />
    <main class="main">
      <agreement-detail />
    </main>
    <footer class="footer">
      <van-button class="submit" @click="onSubmit()">知道了</van-button>
    </footer>
  </div>
</template>

<script>
import CommonHeader from 'components/common/Header'
import { mapGetters } from 'vuex'
import AgreementDetail from './child/AgreementDetail'

export default {
  name: 'Agreement',
  components: {
    CommonHeader,
    AgreementDetail,
  },
  computed: {
    ...mapGetters({
      platformName: 'platformName',
      isAgreement: 'isAgreement',
    }),
  },
  methods: {
    onSubmit() {
      this.$router.back(-1)
    },
  },
}
</script>

<style lang="scss">
@mixin inner {
  width: 100%;
  margin: 0 auto;
}

.agreement {
  &-wrapper {
    height: 100%;
    padding-top: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    .main {
      @include inner;
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }
    .footer {
      height: 84px;
      flex: 0 0 auto;
      padding: 24px 38px;
      margin-top: 10px;
      border-top: 1px solid #ededed;
      .van-button {
        width: 100%;
        height: 40px;
        border-radius: 100px;
        border-radius: 4px;
      }
    }
  }
}
</style>
