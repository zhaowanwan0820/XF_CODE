<template>
  <div class="youjie_feature">
    <div class="feature_wrap">
      <module-title :titleContent="titleContent"></module-title>
      <div class="content">
        <div class="item" v-for="(item, index) in Youjie_Feature" :key="'feature' + index" @click="jumpFn(item)">
          <p class="titleStyle">{{ item.title }}</p>
          <p class="describeStyle">{{ item.describe }}</p>
          <div class="img_wrap">
            <img alt="" :src="item.imgUrl" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ModuleTitle from './ModuleTitle'
import { Youjie_Feature } from './const.js'
export default {
  // title:"优质房产",
  // describe:"网罗优质房源 尽享都市繁华",
  // imgUrl:"../../../assets/image/hh-icon/new-home/feature-icon-1.png",
  // jumpUrl:""
  data() {
    return {
      titleContent: {
        title1: '特色推荐专区',
        title2: '选你所爱',
        rightMore: false,
        jumpUrl: null
      },
      Youjie_Feature
    }
  },
  components: {
    ModuleTitle
  },
  methods: {
    jumpFn: function(item) {
      let { jumpUrl } = item
      if (jumpUrl.name) {
        this.$router.push({ name: jumpUrl.name, query: jumpUrl.query })
      } else if (jumpUrl.url) {
        window.location.href = jumpUrl.url
      } else {
        this.$toast('即将推出 ，敬请期待！')
      }
      // console.log({ name: jumpUrl.name,  query: jumpUrl.query});
    }
  }
}
</script>

<style lang="scss" scoped>
.youjie_feature {
  margin-top: 10px;
  padding: 0 10px;
  box-sizing: border-box;
  .feature_wrap {
    padding: 16px 8px 8px;
    box-sizing: border-box;
    background: #fff;
    .content {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
      .item {
        padding-top: 4px;
        box-sizing: border-box;
        p {
          margin-left: 14px;
        }
        .titleStyle {
          font-size: 14px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          line-height: 14px;
          margin-top: 5px;
        }
        .describeStyle {
          @include sc(10px, #ccc, bottom left);
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          margin-top: 2px;
        }
        .img_wrap {
          margin-top: 16px;
          img {
            display: block;
            width: 100%;
          }
        }
      }
      .item:nth-child(1) {
        background: #f4eeed;
        margin-right: 9px;
        p {
          color: #72514c;
        }
      }
      .item:nth-child(2) {
        background: #e5eaf0;
        p {
          color: #6d7989;
        }
      }
    }
  }
}
</style>
