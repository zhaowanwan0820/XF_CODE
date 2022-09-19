<template>
  <div class="ui-tabbar-wrapper">
    <div class="tabbar-wrapper">
      <ul>
        <li
          v-for="item in tabBar"
          :key="item.key"
          @click="setCurrentActive(item)"
          :class="{ item: true, currentavtive: currentItem == item.link }"
        >
          <img :src="currentItem != item.link ? item.bgurl : item.activeBgurl" />
          <a>{{ item.name }}</a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations } from 'vuex'
import { TAB_BAR } from './static'

export default {
  data() {
    return {
      tabBar: TAB_BAR,
      currentItem: ''
    }
  },
  computed: {
    ...mapState({
      currentTabBar: state => state.tabBar.currentTabBar,
      isOnline: state => state.auth.isOnline
    })
  },
  created() {
    this.currentItem = this.currentTabBar ? this.currentTabBar : 'debtMarket'
  },
  watch: {
    currentTabBar: function(value) {
      let data = this.tabBar
      for (let i = 0; i <= data.length - 1; i++) {
        if (value == data[i].link) {
          this.currentItem = data[i].link
        }
      }
    }
  },

  methods: {
    ...mapMutations({
      resetRouterStack: 'resetRouterStack'
    }),
    setCurrentActive(item) {
      // tabBar切换时，清除routerStack，强制为前进
      this.resetRouterStack()

      this.currentItem = item.link
      // 登录判断
      if (!this.isOnline) {
        if (item.link == 'mine') {
          this.$router.push({ name: 'login', params: item.params })
          return
        }
      }
      this.$router.replace({ name: item.link, params: item.params })
    }
  }
}
</script>

<style lang="less" scoped>
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
      height: 50px;

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
          width: 25px;
          height: 25px;
          z-index: 2;
          position: absolute;
          left: 50%;
          transform: translateX(-50%);
          bottom: 20px;
        }

        a {
          color: #999;
          .sc(11);
        }
      }
      li.currentavtive {
        a {
          color: #04b1a4;
        }
      }
    }
  }
}
</style>
