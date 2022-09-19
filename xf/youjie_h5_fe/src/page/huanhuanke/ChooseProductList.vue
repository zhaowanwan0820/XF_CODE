<template>
  <div class="container">
    <div class="search-container">
      <img src="../../assets/image/hh-icon/icon-header-返回.svg" alt="" @click="goBack" />
      <input type="text" :placeholder="placeholder" v-model="keyword" @keyup.enter="search" />
    </div>

    <!-- 广告位banner -->
    <div class="banner" @click="goBanner">
      <img src="../../assets/image/hh-icon/mlm/banner_default.png" alt="" />
    </div>

    <!-- 分销商品列表 -->
    <goods-list ref="goodlist" :keyword="keyword" class="list-w"></goods-list>

    <goods-list-add-done :popupVisible="popupStatus" v-on:onclick="closePopup"></goods-list-add-done>
  </div>
</template>

<script>
import { BANNERLINK, PLACEHOLDER } from './static'
import GoodsList from './child/GoodsList'
import GoodsListAddDone from './child/GoodsListAddDone'
import { mapState, mapMutations, mapActions } from 'vuex'
export default {
  name: 'pickGoods',
  data() {
    return {
      placeholder: PLACEHOLDER,
      keyword: ''
    }
  },
  // keepAlive
  activated() {
    if (this.selectId) {
      this.$refs.goodlist.getList(false, this.selectId)
      this.clearId()
    }
  },
  components: {
    GoodsList,
    GoodsListAddDone
  },
  computed: {
    ...mapState({
      selectId: state => state.mlm.selectId,
      popupStatus: state => state.mlm.popupStatus
    })
  },
  methods: {
    ...mapMutations({
      clearId: 'clearId',
      setPopup: 'setPopup'
    }),
    search() {
      if (!this.keyword) {
        return
      }
      this.$refs.goodlist.getList(true)
    },
    goBanner() {
      window.location.href = BANNERLINK
    },
    goBack() {
      this.$_goBack()
    },
    closePopup() {
      this.setPopup(false)
      this.$refs.goodlist.close()
    }
  }
}
</script>
<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #fff;
}
.search-container {
  padding: 10px 15px 11px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  img {
    width: 9px;
    height: 16px;
    padding-right: 18px;
  }
  input {
    flex: 1;
    height: 28px;
    background: url('../../assets/image/hh-icon/b0-home/icon-搜索.svg') #f4f4f4 10px center no-repeat;
    background-size: 18px;
    text-indent: 31px;
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
.banner {
  width: 100%;
  img {
    width: 100%;
  }
}
.list-w {
  flex: 1;
}
</style>
