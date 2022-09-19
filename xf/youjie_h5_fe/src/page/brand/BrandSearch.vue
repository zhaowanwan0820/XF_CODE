<template>
  <div class="container">
    <div class="search-header-wrapper">
      <form v-on:submit.prevent="search($event, keywords)" action="#">
        <div>
          <input type="search" placeholder="请输入您要搜索的商品" v-model="keywords" />
          <img src="../../assets/image/change-icon/e2_delete@2x.png" @click="clear" v-if="keywords.length > 0" />
        </div>
        <span v-on:click="goBack">取消</span>
      </form>
    </div>
    <div class="search-body-wrapper">
      <div class="keywords-wrapper" v-if="!showSearch">
        <div class="list current-search" v-if="recentKeywords.length > 0">
          <div class="list-header">
            <span>最近搜索</span>
            <img src="../../assets/image/hh-icon/brand/icon-dustbin.png" @click="deleteCurrent" />
          </div>
          <ul>
            <li class="item" v-for="(item, index) in recentKeywords" @click="getKey(item)" :key="index">
              {{ item }}
            </li>
          </ul>
        </div>
        <div class="list hot-wrapper">
          <div class="list-header">
            <span>热门搜索</span>
          </div>
          <ul>
            <li
              class="item"
              v-for="(item, index) in hotKeywordsList"
              :key="index"
              v-stat="{ id: `brand_search_keyword_${index}` }"
              v-on:click="getKey(item.content)"
            >
              {{ item.content }}
            </li>
          </ul>
        </div>
      </div>
      <div class="list-wrapper" v-if="showSearch">
        <div class="list-result" v-if="brandList.length > 0">
          <template v-for="item in brandList">
            <brand-list-item :item="item"></brand-list-item>
          </template>
          <div class="no-more"><span>没有更多内容了</span></div>
        </div>
        <div class="empty-results" v-if="isLoaded && brandList.length == 0">
          <img src="../../assets/image/hh-icon/brand/search-empty.png" alt="" />
          <p class="empty-tip-wrapper">
            未找到“<span>{{ noResultKeywords }}</span
            >”相关品牌
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import BrandListItem from './child/BrandListItem'
import { getBrandList, getHotKeywordList } from '../../api/brand'
import { Toast, Indicator } from 'mint-ui'
export default {
  name: 'brandSearch',
  data() {
    return {
      list: {},
      isLoaded: false,
      showSearch: false,
      recentKeywords: this.utils.fetch('brandKeywords'),
      keywords: '',
      hotKeywordsList: [],
      noResultKeywords: ''
    }
  },

  components: {
    BrandListItem
  },

  computed: {
    brandList() {
      const arr = []
      for (let key in this.list) {
        this.list[key].forEach(item => {
          arr.push(item)
        })
      }
      return arr
    }
  },

  created() {
    // this.getList()
    this.getHotKeywords()
    this.getrecentKey()
  },

  methods: {
    goBack() {
      this.$_goBack()
    },

    getHotKeywords() {
      getHotKeywordList().then(res => {
        this.hotKeywordsList = Object.assign([], res, this.hotKeywordsList)
      })
    },

    getrecentKey() {
      this.recentKeywords = this.utils.fetch('brandKeywords')
    },

    getList() {
      this.showSearch = true
      this.isLoaded = false
      Indicator.open()
      getBrandList(this.keywords)
        .then(res => {
          this.isLoaded = true
          this.list = res
          if (this.brandList.length == 0) {
            this.noResultKeywords = this.keywords
          }
        })
        .finally(() => {
          Indicator.close()
        })
    },

    // 分类列表进入到搜索，完成后跳转到商品列表页面
    search(e, value) {
      if (value.replace(/\s+/g, '').length <= 0) {
        Toast('请输入您要搜索的关键字')
        return false
      } else {
        this.keywords = value
      }
      this.getList()

      if (value) {
        this.recentKeywords.unshift(value)
        let data = this.utils.arrayUnique(this.recentKeywords)
        this.utils.save('brandKeywords', data)
      }
      if (e) {
        this.utils.stopPrevent(e)
      }
    },

    // 清空输入框
    clear() {
      this.keywords = ''
    },

    deleteCurrent() {
      this.utils.save('brandKeywords', [])
      this.recentKeywords = this.utils.fetch('brandKeywords')
    },

    /**
     * 点击搜索词进行快捷搜索
     *
     * @param      {<type>}  item    The item
     */
    getKey(item) {
      this.search(null, item)
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  position: relative;
  .header {
    flex-shrink: 0;
    height: 44px;
    @include thin-border();
  }
  .brand-list {
    flex: 1;
  }
  .search-header-wrapper {
    height: 50px;
    @include thin-border(rgba(244, 244, 244, 1), 0, 0);
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
        display: flex;
        flex-direction: row;
        justify-content: flex-start;
        align-items: center;
        height: 32px;
        background: rgba(244, 244, 244, 1);
        border-radius: 16px;
        input::-webkit-input-placeholder {
          color: rgba(85, 46, 32, 0.7);
          // line-height: 20px;
        }
        input:-moz-placeholder {
          /* Mozilla Firefox 4 to 18 */
          color: rgba(85, 46, 32, 0.7);
          // line-height: 20px;
        }
        input::-moz-placeholder {
          /* Mozilla Firefox 4 to 18 */
          color: rgba(85, 46, 32, 0.7);
          // line-height: 20px;
        }
        input:-ms-input-placeholder {
          /* Internet Explorer 10+ */
          color: rgba(85, 46, 32, 0.7);
          // line-height: 20px;
        }
        input {
          opacity: 0.5;
          width: 100%;
          height: 36px;
          font-size: 15px;
          padding-left: 38px;
          border: 0;
          color: rgba(64, 64, 64, 1);
          background: url('../../assets/image/hh-icon/brand/icon-search.png') no-repeat 15px center;
          background-size: 15px 15px;
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
        line-height: 20px;
        padding-left: 12px;
        float: right;
        font-size: 16px;
        font-weight: 400;
        color: rgba(85, 46, 32, 1);
      }
    }
  }
  .search-body-wrapper {
    background: #fff;
    flex-grow: 1;
    overflow-y: auto;
    overflow-x: hidden;
    .keywords-wrapper {
      padding: 0 15px;
    }
    div.list {
      padding-top: 30px;
      &.hot-wrapper {
        padding-top: 40px;
      }
      .list-header {
        display: flex;
        justify-content: space-between;
        align-content: center;
        align-items: center;
        span {
          font-size: 12px;
          font-family: PingFangSC;
          font-weight: 700;
          color: rgba(64, 64, 64, 1);
          line-height: 1;
        }
        img {
          width: 15px;
          height: 15px;
          cursor: pointer;
        }
      }
      ul {
        display: flex;
        flex-wrap: wrap;
        li {
          padding: 0 10px;
          min-width: 52px;
          background-color: rgba(244, 244, 244, 1);
          color: rgba(119, 90, 81, 1);
          text-align: center;
          font-size: 12px;
          line-height: 28px;
          font-family: 'PingFangSC';
          margin-right: 20px;
          margin-top: 20px;
          cursor: pointer;
          border-radius: 20px;
        }
      }
    }
    .list-wrapper {
      .no-more {
        text-align: center;
        margin-top: 25px;
        padding-bottom: 25px;
        span {
          font-size: 12px;
          font-family: PingFangSC;
          font-weight: 400;
          color: rgba(185, 185, 185, 1);
          line-height: 17px;
          position: relative;
          display: inline-block;
          padding: 0 30px;
        }
        span:before,
        span:after {
          position: absolute;
          background: #ccc;
          content: '';
          height: 1px;
          top: 50%;
          width: 48px;
          transform: scale(0.5);
        }
        span:before {
          left: 0;
          transform-origin: left center;
        }
        span:after {
          right: 0;
          transform-origin: right center;
        }
      }
    }
    div.empty-results {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      margin-top: 130px;
      img {
        width: 120px;
      }
      .empty-tip-wrapper {
        margin-top: 20px;
        font-size: 17px;
        color: rgba(102, 102, 102, 1);
        span {
          color: rgba(119, 37, 8, 1);
        }
      }
    }
  }
}
</style>
