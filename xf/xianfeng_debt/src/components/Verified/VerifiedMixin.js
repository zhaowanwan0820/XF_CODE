import {getVerifiedInfoRequest} from "@/api/mine";
import Vue from 'vue'

export default {
	data() {
		return {
			visible: false,
			verified: {}
		}
	},
	provide() {
		return {
			verifiedModal: this
		}
	},
	created() {
		this.queryVerifiedInfo()
	},
	methods: {
		// 实名认证信息
		queryVerifiedInfo() {
			const {return_url} = this.$route.query||{}
			getVerifiedInfoRequest({return_url})
				.then(({code, data: {fdd_real_status, fdd_real_url}}) => {
					if (!code) {
						this.verified = {fdd_real_status, fdd_real_url}
					}
				})
		},
		// 跳转实名认证页面
		goVerifiedPage() {
			const {fdd_real_url, fdd_real_status} = this.verified
			if (fdd_real_status !== '0' || !fdd_real_url) {
				this.visible = false
				return null
			}
			window.location.href = fdd_real_url
		},
		// 显示弹框
		showVerifiedModal() {
			const {fdd_real_url, fdd_real_status} = this.verified

			if (fdd_real_status === '0' && !!fdd_real_url) {
				this.visible = true
				return true
			} else {
				this.visible = false
				return false
			}
		}
	}
}
