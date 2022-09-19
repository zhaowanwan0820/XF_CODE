<template>
	<div class="container">
		<mt-header class="header" title="执照展示">
			<header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
		</mt-header>
		<div class="img-wrapper license-wrapper">
			<img
				v-if="type == 1 && supplier.business_license"
				v-lazy="{
					src: supplier.business_license,
					error: require('../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png'),
					loading: require('../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
				}"
				alt=""
			/>
			<swiper
				class="swiper-wrapper"
				v-else-if="type == 2 && supplier.brand_license && supplier.brand_license.length"
				:options="swiperOption"
			>
				<swiper-slide
					class="license-wrapper"
					v-if="supplier.brand_license.length"
					v-for="(item, index) in supplier.brand_license"
					:key="index"
				>
					<img
						alt=""
						v-lazy="{
							src: item,
							error: require('../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png'),
							loading: require('../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
						}"
					/>
				</swiper-slide>
				<div class="swiper-button-prev" slot="button-prev"></div>
				<div class="swiper-button-next" slot="button-next"></div>
			</swiper>
		</div>
	</div>
</template>

<script>
import { shopGet } from '../../api/shop'
export default {
	name: 'LicenseShow',
	data() {
		return {
			swiperOption: {
				navigation: {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev'
				}
			},
			type: this.$route.query.type, // 标识营业执照(1) || 品牌授权(2)
			sn: this.$route.query.shop_sn,
			supplier: {}
		}
	},
	created() {
		// app跳转过来，只能通过接口获取数据，为了统一，都采用获取接口的方式
		shopGet(this.sn).then(
			res => {
				this.supplier = res
			},
			error => {
				console.log(error)
			}
		)
	},
	methods: {
		goBack() {
			this.$_goBack()
		}
	}
}
</script>

<style lang="scss" scoped>
.container {
	height: 100%;
	background: #fff;
	.img-wrapper {
		height: calc(100% - 44px);
		img {
			width: 100%;
		}
	}
	.license-wrapper {
		display: flex;
		align-items: center;
		justify-content: center;
	}
}
</style>
