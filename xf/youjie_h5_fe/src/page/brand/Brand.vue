<template>
  <div class="container">
    <mt-header class="header" title="品牌">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
      <header-item
        class="search-icon"
        slot="right"
        :icon="require('../../assets/image/hh-icon/brand/icon-search.png')"
        :isBack="false"
        v-on:onclick="goSearch"
      >
      </header-item>
    </mt-header>
    <brand-list v-if="listKeys.length" :list="list" :listKeys="listKeys" class="brand-list"></brand-list>
    <brand-alphabet v-if="listKeys.length" :list="listKeys"></brand-alphabet>
  </div>
</template>

<script>
import BrandList from './child/BrandList'
import BrandAlphabet from './child/BrandAlphabet'
import { getBrandList } from '../../api/brand'
import { Indicator } from 'mint-ui'
export default {
  name: 'brand',
  data() {
    return {
      list: {},
      isLoaded: false
    }
  },

  computed: {
    listKeys() {
      const arr = Object.keys(this.list).sort((a, b) => {
        return b > a ? -1 : 1
      })
      if (arr[0] == '#') {
        arr.shift()
        arr.push('#')
      }
      return arr
    }
  },

  created() {
    this.getList()
  },

  components: {
    BrandList,
    BrandAlphabet
  },

  methods: {
    goBack() {
      this.$_goBack()
    },

    goSearch() {
      this.$router.push({ name: 'brandSearch' })
    },

    getList() {
      Indicator.open()
      getBrandList()
        .then(res => {
          this.isLoaded = true
          this.list = res
        })
        .finally(() => {
          Indicator.close()
        })
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
    .search-icon {
      /deep/ .icon {
        width: 17px;
        height: 17px;
      }
    }
  }
  .brand-list {
    flex: 1;
  }
}
</style>
