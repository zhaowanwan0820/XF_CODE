<template>
  <div class="list-item" @click="brandItemClick">
    <div class="img-wrapper">
      <img
        v-lazy="{
          src: item.thumb,
          error: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png'),
          loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
        }"
        alt=""
      />
    </div>
    <div class="title">{{ item.name }}</div>
  </div>
</template>

<script>
export default {
  name: 'BrandListItem',
  data() {
    return {}
  },

  props: ['item'],

  methods: {
    brandItemClick() {
      this.$router.push({ name: 'products', query: { brand_id: this.item.id } })
    }
  }
}
</script>

<style lang="scss" scoped="scoped">
.list-item {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  padding-left: 15px;
  height: 80px;
  @include thin-border(rgba(85, 46, 32, 0.1), 15px, 0, true);
  .img-wrapper {
    width: 50px;
    height: 50px;
    border-radius: 3px;
    border: 1px solid #f9f9f9;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    img {
      max-width: 100%;
      max-height: 100%;
    }
  }
  .title {
    margin-left: 15px;
    font-size: 13px;
    font-family: PingFangSC;
    font-weight: 400;
    color: rgba(64, 64, 64, 1);
    line-height: 18px;
  }
}
</style>
