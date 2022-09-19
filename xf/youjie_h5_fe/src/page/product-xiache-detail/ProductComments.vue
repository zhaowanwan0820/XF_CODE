<!-- 商品详情  -->
<template>
  <div class="prod-comments">
    <mt-header class="header" title="全部评论">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <product-review></product-review>
    <!-- 预览图片 -->
    <preview-picture v-if="isPreviewPicture" :defaultindex="swipeId" :isshow="isPreviewPicture"></preview-picture>
  </div>
</template>

<script>
import ProductReview from './child/ProductReview'
import PreviewPicture from './child/PreviewPicture'
import { mapState, mapMutations } from 'vuex'
export default {
  props: ['isStock'],

  components: {
    ProductReview,
    PreviewPicture
  },

  computed: {
    ...mapState({
      index: state => state.detail.index,
      ispromotion: state => state.detail.ispromotion,
      isPreviewPicture: state => state.detail.isPreviewPicture,
      swipeId: state => state.detail.swipeId
    })
  },

  watch: {},

  mounted() {},

  created() {},

  methods: {
    ...mapMutations({}),

    goBack() {
      this.$_goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.prod-comments {
  height: 100%;
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
}
.header {
  @include thin-border();
}
</style>
