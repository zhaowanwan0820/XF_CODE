<template>
  <div class="ui-tabbar-wrapper">
    <div class="tabbar-wrapper">
      <ul>
        <li
          v-for="item in staticData"
          :key="item.key"
          @click="setCurrentActive(item)"
          :class="{ item: true, currentavtive: currentItem == item.link }"
          v-stat="{ id: `tabbar_${item.link}` }"
        >
          <img :src="currentItem != item.link ? item.bgurl : item.activeBgurl" />
          <a>{{ item.name }}</a>

          <span class="number" v-if="cartNumber > 0 && item.link == 'cart' && isOnline">
            <span class="val">{{ getCarCount }}</span>
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations } from 'vuex'
export default {
  data() {
    return {
      staticData: [
        {
          name: '首页',
          link: 'home',
          key: 0,
          bgurl: require('../../assets/image/tabbar-icon/icon-home.png'),
          activeBgurl: require('../../assets/image/tabbar-icon/icon-home-active.png'),
          isActive: true
        },
        {
          name: '分类',
          link: 'category',
          key: 1,
          bgurl: require('../../assets/image/tabbar-icon/icon-category.png'),
          activeBgurl: require('../../assets/image/tabbar-icon/icon-category-active.png'),
          isActive: false
        },
        // {
        //   name: '我的小店',
        //   link: 'myStore',
        //   key: 2,
        //   bgurl: require('../../assets/image/tabbar-icon/icon-my-store.png'),
        //   activeBgurl: require('../../assets/image/tabbar-icon/icon-my-store-active.png'),
        //   isActive: false
        // },
        {
          name: '购物车',
          link: 'cart',
          params: { type: 1 },
          key: 3,
          bgurl: require('../../assets/image/tabbar-icon/icon-cars.png'),
          activeBgurl: require('../../assets/image/tabbar-icon/icon-cars-active.png'),
          isActive: false
        },
        {
          name: '我的',
          link: 'profile',
          key: 4,
          bgurl: require('../../assets/image/tabbar-icon/icon-profile.png'),
          activeBgurl: require('../../assets/image/tabbar-icon/icon-profile-active.png'),
          isActive: false
        }
      ],
      currentItem: this.$store.state.tabBar.currentTabBar ? this.$store.state.tabBar.currentTabBar : 'home'
    }
  },

  computed: {
    ...mapState({
      currentTabBar: state => state.tabBar.currentTabBar,
      cartNumber: state => state.tabBar.cartNumber,
      isOnline: state => state.auth.isOnline,
      messageHint: state => state.tabBar.messageHint
    }),
    // ...mapGetters({
    //   isHasUnreadCount: 'isHasUnreadCount'
    // }),
    getCarCount() {
      if (this.cartNumber > 0 && this.cartNumber < 100) {
        return this.cartNumber
      } else if (this.cartNumber >= 100) {
        return '99+'
      }
    }
  },

  watch: {
    currentTabBar: function(value) {
      let data = this.staticData
      for (let i = 0; i <= data.length - 1; i++) {
        if (value == data[i].link) {
          this.currentItem = data[i].link
        }
      }
      if (value !== 'category') {
        this.resetCurrentCategoryItem()
      }
    }
  },

  methods: {
    ...mapMutations({
      resetCurrentCategoryItem: 'resetCurrentCategoryItem',
      resetRouterStack: 'resetRouterStack'
    }),
    setCurrentActive(item) {
      // tabBar切换时，清除routerStack，强制为前进
      this.resetRouterStack()

      this.currentItem = item.link
      // 进入购物车&我的页面&我的小店 登录判断
      if (!this.isOnline) {
        if (item.link == 'cart' || item.link == 'profile' || item.link == 'myStore') {
          this.$router.push({ name: 'login', params: item.params })
          return
        }
      }
      if (item.link == 'cart') {
        this.$router.replace({ name: item.link, params: item.params, query: { hideLeave: true } })
        return
      }
      this.$router.replace({ name: item.link, params: item.params })
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-tabbar-wrapper {
  box-shadow: 0px -1px 3px 0px rgba(0, 0, 0, 0.04);
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  height: auto;
  box-sizing: border-box;
  padding: 0;
  margin: 0;
  z-index: 10000;
  background-color: #fff;

  .tabbar-wrapper {
    ul {
      display: flex;
      width: 100%;
      justify-content: flex-start;
      align-items: center;
      height: 48px;

      li {
        flex: 1 0 0;
        height: 100%;
        box-sizing: border-box;
        padding-bottom: 3px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        position: relative;

        img {
          @include wh(25px, 25px);
          z-index: 2;
          position: absolute;
          left: 50%;
          transform: translateX(-50%);
          bottom: 20px;
        }

        a {
          @include sc(11px, #999999);
        }
        .number {
          z-index: 3;
          position: absolute;
          top: 2px;
          right: 18px;
          background: #ef3338;
          border-radius: 7px;
          min-width: 13px;
          line-height: 13px;
          height: 13px;
          font-weight: normal;
          text-align: center;

          .val {
            display: block;
            margin: 0 3.25px;
            @include sc(10px, #fff);
          }
        }
        p {
          width: 7px;
          height: 7px;
          position: absolute;
          right: 0;
          top: 8%;
          background-color: $primaryColor;
          border-radius: 50%;
        }
      }
      li.currentavtive {
        a {
          color: #fc7f0c;
        }
        .circle {
          box-shadow: 0px -2px 6px 0px rgba(206, 206, 206, 0.5);
        }
      }
    }
  }
}
</style>
