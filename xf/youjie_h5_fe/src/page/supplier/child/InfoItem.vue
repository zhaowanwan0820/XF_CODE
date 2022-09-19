<template>
	<div class="info-item-wrapper">
		<span class="title">{{ title }}</span>
		<div class="right-wrapper" @click="showLicense">
			<img class="thumb-wrapper" v-if="goto" src="../../../assets/image/hh-icon/supplier/brand-license.png" alt="" />
			<span class="content" v-else>{{ content }}</span>
			<img class="arrow-head" v-if="goto" src="../../../assets/image/change-icon/right-arrow@2x.png" alt="" />
		</div>
	</div>
</template>

<script>
export default {
	name: 'InfoItem',
	props: {
		title: String,
		content: String,
		goto: Boolean,
		type: String
	},
	/*computed: {
		isBrand() {
			return this.type === 'brand'
		},
		isBusiness() {
			return this.type === 'business'
		}
	},*/
	methods: {
		showLicense() {
			if (!this.goto) return
			this.$router.push({
				name: 'LicenseShow',
				query: { shop_sn: this.$route.query.id, type: this.type === 'business' ? 1 : 2 }
			})
		}
	}
}
</script>

<style lang="scss" scoped>
.info-item-wrapper {
	margin-top: 15px;
	display: flex;
	justify-content: space-between;
	align-items: center;

	font-size: 12px;
	font-weight: 400;
	.title {
		color: #707070;
		line-height: 20px;
	}
	.right-wrapper {
		display: flex;
		align-items: center;
		.thumb-wrapper {
			width: 26px;
			height: 26px;
			margin-right: 7px;
		}
		.content {
			color: #404040;
			line-height: 17px;
		}
		.arrow-head {
			width: 7px;
		}
	}
}
</style>
