<template>
  <div class="cs-item">
    <span>{{ title }}:</span>
    <div class="star" v-for="(item, index) in list" :key="index" @click="selectStar(index)">
      <img src="../../../assets/image/hh-icon/comment/icon-star-on.png" v-if="index < score" alt="" />
      <img src="../../../assets/image/hh-icon/comment/icon-star-off.png" v-else alt="" />
    </div>
    <label>{{ starTxt }}</label>
  </div>
</template>
<script>
import { SCORE } from '../static'
export default {
  props: ['title', 'score', 'keyword'],
  data() {
    return {
      list: [1, 2, 3, 4, 5]
    }
  },
  computed: {
    starTxt() {
      return SCORE[this.score - 1]
    }
  },
  methods: {
    selectStar(index) {
      this.$emit('set', this.keyword, index)
    }
  }
}
</script>
<style lang="scss" scoped>
.cs-item {
  width: 100%;
  display: flex;
  align-items: center;
  font-size: 0;
  span,
  label {
    font-size: 14px;
    font-weight: 400;
    line-height: 20px;
  }
  span {
    color: rgba(64, 64, 64, 1);
    margin-right: 15px;
  }
  label {
    color: #c4c4c4;
  }
  .star {
    @include wh(17px, 17px);
    margin-right: 10px;
    img {
      @include wh(17px, 17px);
    }
  }
}
</style>
