<template>
    <div class="container">
        <iframe :src="srcPDF" frameborder="0" width="100%" height="100%" style="z-index:9999;"></iframe>
    </div>
</template>

<script>
  export default {
    name: 'iframeView',
    data () {
      return {
        srcPDF:this.$route.query.src
      }
    },
    created() {
      console.log(this.$route.query)
    }
  }
</script>

<style scoped>

</style>
