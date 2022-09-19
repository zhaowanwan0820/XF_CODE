<template>
  <div></div>
</template>

<script>
import $cookie from 'js-cookie'
import { mapState } from 'vuex'
import { Toast, Indicator } from 'mint-ui'
import { authSettingURL } from '../../api/auth-base'
let noprev
export default {
  name: 'AuthPage',
  beforeRouteEnter(to, from, next) {
    noprev = !from['name']
    next(vm => {
      if (from['name']) {
        vm.utils.setCookie('authForm', { path: from['path'], query: from['query'] })
      }
    })
  },
  computed: {
    ...mapState({
      isOnline: state => state.auth.isOnline
    })
  },
  created() {
    if (noprev) {
      let from
      if ((from = this.utils.getCookie('authForm'))) {
        this.utils.removeCookie('authForm')
        this.$router.replace(JSON.parse(from))
      } else {
        this.$router.replace('/profile')
      }
    } else if (this.isOnline) {
      this.goAuthSetting()
    } else {
      this.$router.replace({ name: 'profile' })
    }
  },
  methods: {
    goAuthSetting() {
      Indicator.open()
      let time = new Date().getTime()
      authSettingURL().then(
        res => {
          const timer = setTimeout(() => {
            location.href = res
          }, time + 2e3 - new Date().getTime())
          this.$once('hook:beforeDestroy', () => {
            Indicator.close()
            clearTimeout(timer)
          })
        },
        error => {
          Toast(error.errorMsg)
        }
      )
    }
  }
}
</script>
