<!-- SearchHeader.vue -->
<template>
  <div class="ui-search-header">
    <form v-on:submit.prevent="search($event, keywords)" action="#">
      <div>
        <input type="search" :placeholder="placeholderTxt" v-model="keywords" />
        <img src="../../../assets/image/change-icon/e2_delete@2x.png" @click="clear" v-if="keywords.length > 0" />
      </div>
      <span v-on:click="cancel">取消</span>
    </form>
  </div>
</template>

<script>
import { Toast, Indicator } from 'mint-ui'
import { mapState, mapMutations } from 'vuex'
import { sendBuryingPointInfo } from '../../../api/buryingPoint'

export default {
  data() {
    return {
      shop: this.$route.query.shop ? this.$route.query.shop : '',
      keywords: this.keyword ? this.keyword : '',
      currenKeywords: this.utils.fetch('keyword')
    }
  },
  props: ['keyword'],
  computed: {
    ...mapState({
      isTesMode: state => state.test.isTesMode
    }),
    placeholderTxt() {
      return this.shop ? '店内搜索' : '请输入您要搜索的商品'
    }
  },
  methods: {
    ...mapMutations({
      changeKey: 'changeKey',
      changeTest: 'changeTest'
    }),
    // 分类列表进入到搜索，完成后跳转到商品列表页面
    search(e, value) {
      if (value.replace(/\s+/g, '').length <= 0) {
        Toast('请输入您要搜索的关键字')
        return false
      } else {
        this.keywords = value
      }
      if (value) {
        this.currenKeywords.push(value)
        let data = this.utils.arrayUnique(this.currenKeywords)
        this.utils.save('keyword', data)
      }
      // 获取搜索框内容并发送
      // sendBuryingPointInfo({
      //   click_position: 'search_content_' + value
      // })
      this.$router.push({
        name: 'products',
        query: { keywords: this.keywords, shop: this.shop }
      })
      if (e) {
        this.utils.stopPrevent(e)
      }
    },

    // 取消返回上一级
    cancel() {
      if (this.isTesMode) {
        this.changeTest(false)
      }
      this.clear()
      this.$_goBack()
    },

    clear() {
      this.keywords = ''
      this.changeKey(this.keywords)
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-search-header {
  height: 50px;
  form {
    display: flex;
    height: 36px;
    // justify-content: space-between;
    align-content: center;
    align-items: center;
    background-color: #fff;
    padding: 6px 11px 8px 10px;
    div {
      flex: 1;
      position: relative;
      background-color: #e9ecf0;
      display: flex;
      flex-direction: row;
      justify-content: flex-start;
      align-items: center;
      border-radius: 4px;
      height: 36px;
      input::-webkit-input-placeholder {
        color: #b5b6b6;
        // line-height: 20px;
      }
      input:-moz-placeholder {
        /* Mozilla Firefox 4 to 18 */
        color: #b5b6b6;
        // line-height: 20px;
      }
      input::-moz-placeholder {
        /* Mozilla Firefox 4 to 18 */
        color: #b5b6b6;
        // line-height: 20px;
      }
      input:-ms-input-placeholder {
        /* Internet Explorer 10+ */
        color: #b5b6b6;
        // line-height: 20px;
      }
      input {
        width: 100%;
        height: 36px;
        background-color: rgba(0, 0, 0, 0);
        font-size: 15px;
        padding-left: 35px;
        border: 0;
        background: url('../../../assets/image/hh-icon/b0-home/icon-搜索.svg') no-repeat 10px center;
        &:focus {
          outline: none;
          outline-offset: 0;
        }
      }
      img {
        width: 16px;
        height: 16px;
        margin-right: 8px;
        cursor: pointer;
      }
    }
    span {
      font-size: 14px;
      line-height: 20px;
      padding-left: 11px;
      color: #5caaf8;
      float: right;
    }
  }
}
</style>
