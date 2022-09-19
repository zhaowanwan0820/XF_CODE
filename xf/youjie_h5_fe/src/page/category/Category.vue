<template>
  <div class="container">
    <div class="search-wrapper">
      <input
        type="text"
        v-model="keyWords"
        :placeholder="placeholder"
        @click="goIndexSearch"
        v-on:keyup.enter="search"
      />
    </div>
    <category-body></category-body>
  </div>
</template>

<script>
import { PLACEHOLDER } from '../huanhuanke/static'
import CategoryBody from './child/CategoryBody'
export default {
  name: 'category',
  data() {
    return {
      placeholder: PLACEHOLDER,
      keyWords: ''
    }
  },
  components: {
    CategoryBody
  },
  methods: {
    goIndexSearch() {
      this.$router.push({ name: 'search', params: { isFromHome: true } })
    },
    search() {
      if (!this.keyWords.length) return
      this.$router.push({ name: 'products', query: { keywords: this.keyWords } })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.search-wrapper {
  padding: 9px 15px 7px;
  background-color: #fff;
  margin-bottom: 10px;
  input {
    width: 100%;
    height: 28px;
    background: url('../../assets/image/hh-icon/b0-home/icon-搜索.svg') #f4f4f4 10px center no-repeat;
    background-size: 18px;
    text-indent: 36px;
    border: none;
    border-radius: 2px;
    opacity: 0.9012;
    &::-webkit-input-placeholder {
      font-size: 14px;
      color: #ccc;
      line-height: 14px;
    }
  }
}
</style>
