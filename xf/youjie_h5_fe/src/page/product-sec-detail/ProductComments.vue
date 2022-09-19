<!-- 商品详情  -->
<template>
  <div class="prod-comments">
    <mt-header class="header" title="全部评论">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <product-review :id="id"></product-review>
  </div>
</template>

<script>
import ProductInfo from './child/ProductInfo'
import ProductDesc from './child/ProductDesc'
import ProductReview from './child/ProductReview'
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {}
  },

  props: ['isStock'],

  components: {
    ProductInfo,
    ProductDesc,
    ProductReview
  },

  computed: {
    ...mapState({
      index: state => state.detail.index,
      ispromotion: state => state.detail.ispromotion,
      id: state => state.detail.detailInfo.id
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
