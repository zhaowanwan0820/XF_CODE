<template>
  <div class="c-search-wrapper">
    <div class="s-header">
      <input type="text" placeholder="请输入搜索关键词" v-model="keywords" @keyup.enter="search(keywords)" />
      <span @click="close">取消</span>
    </div>
    <div class="has-searched-wrapper">
      <div class="has-head">
        <span>最近搜索</span>
        <img src="../../../assets/image/hh-icon/coupon/icon-delete.png" @click="deleteKeyword" alt="" />
      </div>
      <div class="keywords-wrapper">
        <div class="keyword-item" v-for="(item, index) in currenKeywords" :key="index" @click="selectKeyword(item)">
          <span>{{ item }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Toast } from 'mint-ui'
export default {
  data() {
    return {
      keywords: '',
      currenKeywords: this.utils.fetch('keyword')
    }
  },
  methods: {
    search(value) {
      if (value.replace(/\s+/g, '').length <= 0) {
        Toast('请输入您要搜索的关键字')
        return false
      }
      if (value) {
        this.currenKeywords.push(value)
        let data = this.utils.arrayUnique(this.currenKeywords)
        this.utils.save('keyword', data)
      }

      this.selectKeyword(value)
    },
    selectKeyword(value) {
      this.$parent.$emit('change-list', { keyword: value })
      this.close()
    },
    close() {
      this.keywords = ''
      this.$parent.changeSearch()
    },
    deleteKeyword() {
      this.utils.save('keyword', [])
      this.currenKeywords = this.utils.fetch('keyword')
    }
  }
}
</script>

<style lang="scss" scoped>
.c-search-wrapper {
  height: 100%;
  background-color: #fff;
  .s-header {
    height: 51px;
    padding: 0 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    @include thin-border(#f4f4f4);
    input {
      width: 300px;
      padding: 6px 0;
      border-radius: 18px;
      border: 0;
      outline: none;
      text-indent: 38px;
      background: url('../../../assets/image/hh-icon/coupon/icon-search.png') #f4f4f4 15px 8px no-repeat;

      background-size: 15px 15px;

      font-size: 15px;
      font-weight: 300;
      line-height: 17px;

      &::-webkit-input-placeholder {
        color: rgba(85, 46, 32, 0.7);
        font-size: 15px;
        font-weight: 300;
        line-height: 19px;
      }
    }
    span {
      font-size: 16px;
      font-weight: 400;
      color: #552e20;
      line-height: 20px;
    }
  }
  .has-searched-wrapper {
    // padding: 0 15px;
    padding-left: 15px;
    .has-head {
      margin-top: 30px;
      display: flex;
      justify-content: space-between;
      span {
        font-size: 12px;
        font-weight: 500;
        color: rgba(64, 64, 64, 1);
        line-height: 17px;
      }
      img {
        width: 15px;
        height: 15px;
        margin-right: 17px;
      }
    }
  }
  .keywords-wrapper {
    margin-top: 19px;
    display: flex;
    flex-wrap: wrap;
    overflow: auto;
    .keyword-item {
      min-width: 40px;
      background: rgba(244, 244, 244, 1);
      border-radius: 15px;
      padding: 7.5px 10px;
      margin-right: 10px;
      margin-bottom: 5px;

      display: flex;
      align-items: center;
      justify-content: center;
      span {
        font-size: 12px;
        font-weight: 400;
        color: #775a51;
        line-height: 1;
      }
    }
  }
}
</style>
