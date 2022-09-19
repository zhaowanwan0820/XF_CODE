import {getVerifiedInfoRequest} from "../../api/home";
import {Toast} from "vant";
// 实名认证弹窗条件：
// fdd_real_status=0  && fdd_real_url !=''
//
// 集约诉讼弹窗条件：
// intensive_sign_status=0 && fdd_real_status=1 && intensive_sign_url !=''
//
// 置换弹窗条件：
//  is_displace=0 && intensive_sign_status=1 && fdd_real_status=1

const LAST_LOCAL_TIME_KEY = 'LAST_LOCAL_TIME'
// 弹框间隔3min
const INTERVAL = 180000
export default {
  data() {
    return {
      visible: false,
      verified: {},
      visibleDelegationOfAuthority: false,
      visibleDelegationOfAuthoritySupplement: false,
      // 债权弹框
      claims: {
        // is_displace 是否可以置换 0可置换 1已置换 2当前不可置换
        visible: false,
        // 置换方式 1法大大签约(跳转到displace_url)置换  2请求置换接口（/user/XFUser/displace）置换   置换前提(is_displace=0)
        displaceType: 0
      }
    }
  },
  created() {
    this.queryVerifiedInfo()
  },
  methods: {
    // 实名认证信息
    queryVerifiedInfo() {
      const {return_url} = this.$route.query || {}
      getVerifiedInfoRequest({return_url})
        .then(({
                 code,
                 data: {
                   fdd_real_status,
                   fdd_real_url,
                   intensive_sign_url,
                   intensive_sign_status,
                   mobile,
                   is_displace,
                   displace_type, displace_url,
                   fdd_real_suffix, intensive_idcard_time
                 }
               }) => {
          if (!code) {
            this.verified = {fdd_real_status, fdd_real_url, intensive_sign_url, mobile, fdd_real_suffix}
            // 集约诉讼弹窗条件
            this.visibleDelegationOfAuthority = intensive_sign_status === '0' && fdd_real_status === '1' && !!intensive_sign_url
            // 置换弹窗条件：
            this.claims.visible = is_displace === '0' && intensive_sign_status === '1' && fdd_real_status === '1'
            this.claims.displaceType = parseInt(displace_type, 10)
            this.claims.displaceUrl = displace_url
            // 当前时间
            const now = Date.now()
            // 上次请求时间
            const last = Number(localStorage.getItem(LAST_LOCAL_TIME_KEY))
            if (now - last >= INTERVAL || !last) {
              // 委托授权资料补充
              this.visibleDelegationOfAuthoritySupplement = intensive_idcard_time === '0' && intensive_sign_status === '1' && fdd_real_status === '1'
              localStorage.setItem(LAST_LOCAL_TIME_KEY, now)
            } else {
              this.visibleDelegationOfAuthoritySupplement = false
            }
          }
        })
    },
    // 跳转实名认证页面
    goVerifiedPage(params = {}) {
      const {fdd_real_url, fdd_real_status} = this.verified

      if (fdd_real_status !== '0' || !fdd_real_url) {
        this.visible = false
        if (params.isToast) {
          Toast({
            message: '您已完成实名认证！'
          })
        }
        return null
      }
      window.location.href = fdd_real_url
    },
    // 显示弹框
    showVerifiedModal() {
      const {fdd_real_url, fdd_real_status} = this.verified
      // 实名认证弹窗条件：
      if (fdd_real_status === '0' && !!fdd_real_url) {
        this.visible = true
      } else {
        this.visible = false
        return null
      }
    }
  }
}
