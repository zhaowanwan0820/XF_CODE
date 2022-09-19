<template>
  <div class="product-list" :style="{ height: winHeight + 'px' }">
    <a class="ad-img" :href="ad.link" v-if="ad.img"><img :src="ad.img"/></a>
    <div class="category-flex">
      <div class="menu-wrapper" ref="menuWrapperScroll">
        <div>
          <ul>
            <li
              v-for="(item, index) in categorylists"
              :key="item.cat_id"
              :ref="'menu' + item.cat_id"
              :class="{
                sidbaractive: currentIndex === index
              }"
              @click="menuSelected(item.cat_id)"
            >
              <a>{{ item.cat_name }}</a>
            </li>
          </ul>
        </div>
      </div>

      <div class="category-content" ref="productsScroll">
        <div>
          <div
            class="cat-wrapper"
            v-for="cat in productlists"
            :ref="'products' + cat.cat_id"
            :key="'products' + cat.cat_id"
          >
            <h3 class="cat-name">{{ cat.cat_name }}</h3>
            <div
              class="product-wrapper"
              v-for="(item, index) in cat.product"
              :key="cat.cat_id + index"
              @click="buyIt(item.mlm_id)"
            >
              <div class="maininfo-wrapper">
                <div class="image-wrapper">
                  <img
                    class="product-img"
                    v-lazy="{
                      src: item.thumb,
                      error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
                      loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
                    }"
                  />
                </div>
                <div class="word-wrapper">
                  <div>{{ item.name }}</div>
                  <div class="price">
                    <span class="icon">￥</span>
                    <span class="price-num">{{ utils.formatFloat(item.price) }}</span>
                    <div class="price-wrapper-flag">
                      <div class="left"></div>
                      <div class="price">
                        <span>可省￥{{ utils.formatFloat(item.save_money) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="price-wrapper">
                <span>销量{{ item.sales_count }}</span>
                <button class="btn-style">立即购买</button>
              </div>
            </div>
          </div>
          <div class="wrapper-list-empty" v-if="productlists.length <= 0 && !isMore">
            <div>
              <img src="../../../assets/image/hh-icon/empty-list-icon.png" />
              <p>暂无任何商品</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapState, mapMutations } from 'vuex'
import BScroll from '@better-scroll/core'
import { getHhkProductList } from '../../../api/huanhuanke'
import { Indicator } from 'mint-ui'

import goods from '../data-goods'

export default {
  name: 'shopProductList',
  data() {
    return {
      sn: this.$route.params.id,
      productlists: [],
      isMore: true,

      ad: {
        img: '',
        link: ''
      },

      // list滚动相关信息
      listHeight: [],
      scrollY: 0,
      menuWrapperScroll: null,
      productsScroll: null,
      // menu
      menuItemHeight: 0,
      menuContainerCenterPos: 0,

      // 可视窗口高度
      winHeight: 0,
      // 最外层 container 最大可滚动高度
      containerMaxScrollTop: 0
    }
  },
  computed: {
    categorylists() {
      let catList = []
      this.productlists.forEach(e => {
        catList.push({
          cat_id: e.cat_id,
          cat_name: e.cat_name
        })
      })
      return catList
    },
    menuRefsList() {
      let menuList = []
      this.productlists.forEach((value, key) => {
        menuList.push(this.$refs['menu' + value.cat_id])
      })
      return menuList
    },
    currentIndex() {
      for (let i = 0; i < this.listHeight.length; i++) {
        let h1 = this.listHeight[i]
        let h2 = this.listHeight[i + 1]
        if (!h2 || (this.scrollY >= h1 && this.scrollY < h2)) {
          return i
        }
      }
      return 0
    }
  },
  created() {
    this.winHeight = window.innerHeight
    this.getProductsList()
  },
  activated() {
    // 微信里边 产生浏览历史时 会在底部加一前进后退条 导致窗口高度变化，此时需重新处理BScroll
    this.winHeight = window.innerHeight
  },
  watch: {
    currentIndex: function(newV) {
      this.menuScroll(newV)
    },
    winHeight: function(newV, oldV) {
      if (oldV > 0) {
        this.menuWrapperScroll.destroy()
        this.productsScroll.destroy()
        this.$nextTick(() => {
          this.initScroll()
          this.$nextTick(() => {
            // 滚动至历史位置
            this.productsScroll.scrollBy(0, -this.scrollY, 0)
          })
        })
      }
    }
  },
  methods: {
    ...mapMutations(['changeContainerScrollTop']),
    getProductsList() {
      let that = this

      Indicator.open()
      let data = { sn: this.sn }
      getHhkProductList(data).then(
        res => {
          Indicator.close()
          this.productlists = res
          that.$nextTick(() => {
            that.initScroll()
            that.caculateHeight()
          })
        },
        error => {
          console.log(error)
        }
      )
    },
    // 立即购买
    buyIt(mlm_id) {
      this.$router.push({ name: 'buyerProduct', params: { mlmId: mlm_id } })
    },
    initScroll() {
      this.menuWrapperScroll = new BScroll(this.$refs.menuWrapperScroll, {
        click: true
      })

      this.productsScroll = new BScroll(this.$refs.productsScroll, {
        click: true,
        probeType: 3
      })

      this.productsScroll.on('scroll', pos => {
        if (1 === this.productsScroll.movingDirectionY && pos.y <= 0) {
          this.changeContainerScrollTop(this.containerMaxScrollTop)
        }
        if (-1 === this.productsScroll.movingDirectionY && pos.y >= 0) {
          this.changeContainerScrollTop(0)
        }

        this.scrollY = Math.abs(Math.round(pos.y))
      })
    },
    caculateHeight() {
      // 计算右侧 list相关高度数据
      let height = 0
      let goodList = []
      this.listHeight.push(height)
      this.productlists.forEach((value, key) => {
        goodList.push(this.$refs['products' + value.cat_id])
      })
      for (let i = 0; i < goodList.length; i++) {
        let item = goodList[i][0]
        height += item.clientHeight
        this.listHeight.push(height)
      }

      // 计算左侧 menu高度
      this.menuItemHeight = this.menuRefsList[0][0].clientHeight
      // menuContainer最中间位置一个menu的坐标
      const menuContainerHeight = this.$refs.menuWrapperScroll.clientHeight
      this.menuContainerCenterPos = [0, menuContainerHeight / 2 - this.menuItemHeight / 2]

      // 计算最外层 container 的最大可滚动高度
      this.containerMaxScrollTop = this.$parent.$refs.container.scrollHeight - window.innerHeight
    },
    menuSelected(id) {
      let el = this.$refs['products' + id][0]
      this.productsScroll.scrollToElement(el, 0)
    },
    /**
     * 通过二维坐标系[x, y](右下为正) 标识元素的位置
     * 使得当前currentIndex menu尽量处于menuContainer中间位置
     */
    menuScroll(index) {
      // 当前currentIndex的坐标
      const currentIndexPos = [0, index * this.menuItemHeight + this.menuWrapperScroll.y]

      // Y轴需要滚动的距离 和 方向
      let scrollY = 0
      // this.menuWrapperScroll.y 为负数，所以Y轴的正方向是垂直向下
      // 从上边往下滚动 可滚动的最大距离是 -this.menuWrapperScroll.y
      const maxCanScrollDown = -this.menuWrapperScroll.y
      // 从下边往上滚动 可滚动的最大距离是 -this.menuWrapperScroll.maxScrollY - (-this.menuWrapperScroll.y)
      const maxCanScrollUp = -this.menuWrapperScroll.maxScrollY - -this.menuWrapperScroll.y

      if (this.menuContainerCenterPos[1] > currentIndexPos[1]) {
        // 如果 currentIndex在 center 上边
        if (this.menuContainerCenterPos[1] - currentIndexPos[1] <= maxCanScrollDown) {
          // 往下为正
          scrollY = this.menuContainerCenterPos[1] - currentIndexPos[1]
        } else {
          scrollY = maxCanScrollDown
        }
      } else {
        // 如果 currentIndex在 center 下边
        if (currentIndexPos[1] - this.menuContainerCenterPos[1] <= maxCanScrollUp) {
          // 往上为负
          scrollY = this.menuContainerCenterPos[1] - currentIndexPos[1]
        } else {
          scrollY = -maxCanScrollUp
        }
      }
      if (0 != scrollY) {
        this.menuWrapperScroll.scrollBy(0, scrollY, 300)
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.product-list {
  position: relative;
  margin-top: -11px;
  background-color: #fff;
  border-radius: 8px 8px 0px 0px;
  overflow: hidden;

  .ad-img {
    display: block;
    width: 345px;
    height: 80px;

    img {
      display: block;
      width: 100%;
      height: 100%;
    }
  }

  .category-flex {
    display: flex;
    width: 100%;
    position: absolute;
    left: 0;
    bottom: 0;
    top: 0;
    align-items: stretch;

    .menu-wrapper {
      flex: 0 0 85px;
      background-color: $mainbgColor;

      ul {
        li {
          display: block;
          padding: 15px 0;
          text-align: center;

          a {
            display: block;
            color: #888888;
            overflow: hidden;
            font-size: 14px;
            position: relative;
          }
        }

        li {
          background-color: $mainbgColor;
        }

        li.sidbaractive {
          background-color: #fff;

          a {
            color: #552e20;

            &:after {
              content: '';
              position: absolute;
              left: 0;
              top: 0;
              height: 100%;
              width: 3px;
              background: rgb(85, 46, 32);
            }
          }
        }
      }
    }

    .category-content {
      flex: 1 0 0;
      padding: 0 15px 0 10px;

      .cat-wrapper h3 {
        font-size: 14px;
        font-weight: bold;
        color: rgba(64, 64, 64, 1);
        line-height: 20px;
        padding-top: 11px;
        margin-bottom: -4px;
      }
      .product-wrapper {
        width: 100%;
        padding: 15px 0 10px 0;
        border-bottom: 1px dotted rgba(85, 46, 32, 0.2);
        background-color: #fff;

        .maininfo-wrapper {
          display: flex;

          .image-wrapper {
            position: relative;
            width: 85px;
            height: 85px;

            img {
              width: 100%;
              height: 100%;
            }

            img.sticky-top {
              position: absolute;
              top: 0;
              width: 30px;
              height: 30px;
            }

            img.reduce-price {
              position: absolute;
              bottom: 0;
              right: 0;
              width: 32px;
              height: 12px;
            }
          }

          .word-wrapper {
            width: 170px;
            font-size: 13px;
            font-weight: 400;
            color: rgba(64, 64, 64, 1);
            line-height: 18px;
            margin-left: 10px;

            > div:first-child {
              overflow: hidden;
              height: 35px;
              display: -webkit-box;
              -webkit-line-clamp: 2;
              -webkit-box-orient: vertical;
              margin-bottom: 17px;
            }

            .price {
              display: flex;
              align-items: center;

              .icon {
                @include sc(10px, #772508);
                font-weight: bold;
                line-height: 11px;
                padding-top: 4px;
              }

              .price-num {
                font-size: 18px;
                font-weight: bold;
                color: #772508;
                line-height: 21px;
                padding-right: 8px;
              }

              .price-wrapper-flag {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: flex-start;
                min-width: 86px;
                padding-left: 7px;

                .price {
                  // width: 60%;
                  box-sizing: border-box;
                  width: 75px;
                  height: 14px;
                  line-height: 14px;
                  background: rgba(201, 181, 148, 1);
                  border-radius: 2px 100px 100px 2px;
                  padding-left: 11px;

                  span {
                    white-space: nowrap;
                    display: inline-block;
                    @include sc(9px, #fff);
                  }
                }
                .left {
                  position: absolute;
                  width: 16px;
                  height: 16px;
                  left: 0;
                  top: 50%;
                  transform: translateY(-50%);
                  box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0.2);
                  border-radius: 8px;
                  background: rgba(201, 181, 148, 1) url('../../../assets/image/hh-icon/shop4buyer/mini-hand@2x.png')
                    no-repeat center center;
                  background-size: 9px 10px;
                }
              }
            }
          }
        }

        .price-wrapper {
          padding-top: 10px;
          overflow: hidden;
          font-weight: 400;

          span {
            padding-top: 4px;
            font-size: 11px;
            color: rgba(136, 136, 136, 1);
            line-height: 16px;
          }

          .btn-style {
            background: rgba(119, 37, 8, 1);
            padding: 4px 9px;
            line-height: 16px;
            font-size: 11px;
            color: #fff;
            border-radius: 2px;
            float: right;
          }
        }
      }
    }
  }
}

.wrapper-list-empty {
  display: flex;
  justify-content: center;
  align-content: center;
  align-items: center;
  padding-top: 45%;

  div {
    display: flex;
    flex-direction: column;
    align-items: center;

    img {
      width: 75px;
      height: 75px;
    }

    p {
      text-align: center;
      margin-top: 11px;
      color: #a4aab3;
    }
  }
}
</style>
