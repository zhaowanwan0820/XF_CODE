<template>
	<div class="container">
		<img src="../../assets/image/hh-icon/app/downloadapp@2x.png" alt="" @click="downloadApp" />
	</div>
</template>

<script>
export default {
	name: 'DownLoadLead',
	methods: {
		downloadApp() {
			window.location.href = 'https://a.app.qq.com/o/simple.jsp?pkgname=com.huanhuanyiwu.mall&fromcase=40003'
		}
	}
}
</script>

<style lang="scss" scoped>
img {
	width: 100%;
}
</style>
