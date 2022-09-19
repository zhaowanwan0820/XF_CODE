<template>
  <div class="ui-tabbar-wrapper" :style="styleBar">
    <div class="tabbar-wrapper">
      <ul>
        <li
          class="item"
          v-for="item in staticData.data"
          v-bind:key="item.key"
          v-on:click="setCurrentActive(item)"
          v-bind:class="{ currentavtive: currentItem == item.link }"
        >
          <img
            v-bind:src="currentItem != item.link ? item.bgurl : item.activeBgurl"
            :class="{ myStore: 'myStore' == item.link }"
          />
          <a :style="getLinkColor(item)">{{ item.name }}</a>

          <span class="number" v-if="cartNumber > 0 && item.link == 'cart' && currentItem != item.link && isOnline">
            <span class="val">{{ getCarCount }}</span>
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import { activity_tabar } from '../../config/activity'
import { mapState, mapGetters, mapMutations } from 'vuex'
export default {
  data() {
    return {
      staticData: activity_tabar,
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
    },
    styleBar() {
      if (this.staticData.barBg) {
        return {
          borderTop: 'none',
          background: `#fff url(${this.staticData.barBg}) no-repeat 0 100%/cover`
        }
      }
    }
  },

  watch: {
    currentTabBar: function(value) {
      let data = this.staticData.data
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
      // 进入购物车&我的页面 登录判断
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
    },

    getLinkColor(item) {
      const style = {}
      if (this.currentItem == item.link) {
        style.color = this.staticData.tabActiveColor
      } else {
        style.color = this.staticData.tabColor
      }
      return style
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-tabbar-wrapper {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  height: auto;
  padding: 0;
  margin: 0;
  z-index: 10000;
  background-color: #fff;
  border-top: 1px solid #f4f4f4;

  .tabbar-wrapper {
    ul {
      display: flex;
      -webkit-display: flex;
      -moz-display: flex;
      width: auto;
      justify-content: space-around;
      align-content: center;
      align-items: center;
      height: 49px;
      li {
        flex: 1;
        height: 100%;
        box-sizing: border-box;
        padding-bottom: 3px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        position: relative;

        img {
          @include wh(49px, 49px);
          z-index: 2;
          position: absolute;
          left: 50%;
          transform: translateX(-50%);
          bottom: 19px;

          &.myStore {
            @include wh(63px, 62px);
          }

          // &.bigCover {
          //   @include wh(70px, 70px);
          //   bottom: 3px;
          // }
        }
        a {
          @include sc(11px, #fff);
        }
        .number {
          z-index: 3;
          position: absolute;
          top: -22%;
          right: 8%;
          background: #ef3338;
          border-radius: 13px;
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
          color: #ff1e3d;
        }
      }
    }
  }
}
</style>
