<template>
  <div class="content">
    <div class="c-left scroll-container-keepAlive">
      <ul>
        <li
          v-if="list && list.length"
          v-for="item in list"
          :key="item.id"
          @click="selectCategory(item.id)"
          :class="{ brown: item.id == select.id }"
          v-stat="{ id: `classify_category_${item.id}` }"
        >
          <div class="brown"></div>
          {{ item.name }}
        </li>
      </ul>
    </div>
    <div class="c-right scroll-container-keepAlive" ref="scrollContainer">
      <div class="banner" v-if="select.banner"></div>
      <div class="cate-se-wrapper" v-if="select_se.length">
        <div class="cate-se" v-for="(item, index) in select_se" :key="item.id">
          <div
            class="cate-header"
            @click="goProducts(item.id)"
            v-stat="{ id: `classify_category_${select.id}_${item.id}_all` }"
          >
            <h2>{{ item.name }}</h2>
            <div>
              <span>全部</span>
              <img src="../../../assets/image/hh-icon/supplier/icon-tip.png" alt="" />
            </div>
          </div>
          <div class="cate-th-wrapper" v-if="item.children.length">
            <div
              class="cate-th"
              v-for="(item0, index0) in item.children"
              :key="index0.id"
              @click="goProducts(item0.id)"
              v-stat="{ id: `classify_category_${select.id}_${item.id}_${item0.id}` }"
            >
              <img
                v-lazy="{
                  src: imgUrl(item0.id),
                  error: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png'),
                  loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
                }"
              />
              <p>{{ item0.name }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Indicator, Toast } from 'mint-ui'
import { mapState, mapMutations, mapActions } from 'vuex'
import { categoryList } from '../../../api/category'
export default {
  name: 'CategoryBodyNew',
  data() {
    return {
      select: [],
      select_se: [] //二级分类
    }
  },
  created() {
    this.getCategoryList(true)
  },
  computed: {
    ...mapState({
      list: state => state.category.list
    })
  },
  watch: {
    select_se: function(newv, oldv) {
      this.$refs.scrollContainer.scrollTop = 0
    }
  },
  methods: {
    ...mapMutations({
      saveCategoryList: 'saveCategoryList'
    }),
    getCategoryList(flag) {
      categoryList(3).then(
        res => {
          this.saveCategoryList(res)
          if (flag) {
            this.select = res[0]
            this.select_se = res[0].children ? res[0].children : []
          }
        },
        error => {
          console.log(error)
        }
      )
    },
    imgUrl(id) {
      return 'https://m.youjiemall.com/images/cat_image/' + id + '.png'
    },
    selectCategory(id) {
      if (this.select.id === id) return
      let cate = this.list.filter(val => {
        return val.id === id
      })
      this.select = cate[0]
      this.select_se = cate[0].children ? cate[0].children : []
    },
    goProducts(id) {
      this.$router.push({ name: 'products', query: { category: id } })
    }
  }
}
</script>

<style lang="scss" scoped>
.content {
  flex: 1;
  height: 100%;
  display: flex;
  overflow: auto;
  .c-left {
    width: 85px;
    height: 100%;
    overflow: auto;
    &::-webkit-scrollbar {
      display: none;
    }
    li {
      height: 50px;
      position: relative;
      font-size: 14px;
      font-weight: 400;
      color: #888;
      line-height: 20px;

      display: flex;
      align-items: center;
      justify-content: center;
      div {
        position: absolute;
        left: 0;
        top: 18px;
        width: 2px;
        height: 15px;
      }
      &.brown {
        background-color: #fff;
        color: #b75800;
        div {
          background-color: #b75800;
        }
      }
    }
  }
  .c-right {
    flex: 1;
    padding: 15px;
    background-color: #fff;
    overflow: auto;
    .banner {
      width: 260px;
      height: 95px;
      margin-bottom: 28px;
    }
    .cate-se {
      margin-bottom: 28px;
      .cate-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 7px;
        border-bottom: 1px dotted rgba(85, 46, 32, 0.2);
        div {
          display: flex;
          align-items: center;
          justify-content: center;
          span {
            display: inline-block;
            @include sc(10px, #404040);
          }
          img {
            width: 4px;
          }
        }
      }
      h2 {
        font-size: 13px;
        font-weight: 400;
        color: #404040;
        line-height: 18px;
      }
      .cate-th-wrapper {
        display: flex;
        flex-flow: row wrap;
        width: 100%;
        .cate-th {
          width: 70px;
          margin-right: 25px;
          &:nth-child(3n) {
            margin: 0;
          }
          img {
            width: 70px;
            height: 70px;
            padding: 15px 0 5px;
          }
          p {
            @include sc(11px, #404040) font-weight: 300;
            line-height: 16px;
            text-align: center;
          }
        }
      }
    }
  }
}
</style>
