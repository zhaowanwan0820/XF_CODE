<template>
  <div class="recommend-container">
    <div class="recommend-title">
      <img src="../../../assets/image/hh-icon/b0-home/recommend-title.png" />
    </div>
    <div class="recommend-body">
      <div class="flex" v-for="(subArr, subArrIndex) in RECOMMEND_CONFIG" :key="`0${subArrIndex}`">
        <template v-for="(item, index) in subArr">
          <div
            :class="[item.type, `${item.layout}-item`]"
            @click="itemClick(item)"
            v-stat="{ id: `index_recommend_${subArrIndex}_${index}` }"
          >
            <div v-if="item.title" class="title">
              <span>{{ item.title }}</span>
            </div>
            <div v-if="item.subtitle" class="subtitle">
              <span v-html="item.subtitle"></span>
            </div>
            <div v-if="item.subtitleDesc" class="subtitle-desc">
              <span>{{ item.subtitleDesc }}</span>
            </div>
            <div class="bg-wrapper">
              <template v-for="img in item.bg">
                <div><img v-lazy="img" /></div>
              </template>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { RECOMMEND_CONFIG } from './const'
export default {
  data() {
    return {
      RECOMMEND_CONFIG
    }
  },

  methods: {
    itemClick(item) {
      if (item.url) {
        this.Jump(item.url)
      } else {
        this.$router.push(item.router)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.recommend-container {
  padding: 15px 10px;
  background-color: #ffffff;
  margin-bottom: 10px;
  .recommend-title {
    font-size: 0;
    img {
      height: 20px;
    }
  }
  .recommend-body {
    margin-top: 15px;
    border-radius: 3px;
    overflow: hidden;
    .flex {
      display: flex;
      justify-content: space-between;
    }
    .bg-wrapper {
      display: flex;
      justify-content: space-between;
      font-size: 0;
      div {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        img {
          max-width: 100%;
          max-height: 100%;
        }
      }
    }
    .f-t-two-item {
      height: 127px;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      flex: 1;
      & + .f-t-two-item {
        margin-left: 4px;
      }
      .title {
        margin-top: 10px;
        padding-left: 11px;
        font-size: 14px;
        font-family: PingFangSC-Regular;
        color: rgba(174, 102, 77, 1);
        line-height: 20px;
      }
      .subtitle {
        flex: 1;
        padding-left: 10px;
        font-family: PingFangSC-Regular;
        line-height: 13px;
        span {
          display: inline-block;
          @include sc(9px, rgba(174, 102, 77, 0.5), left top);
        }
      }
      .bg-wrapper {
      }
      &.huanhuanke {
        background-color: #fff4ef;
        .title {
          color: #ae664d;
        }
        .subtitle span {
          color: rgba(174, 102, 77, 0.5);
        }
      }
      &.quanyi {
        background-color: rgba(242, 246, 248, 1);
        .title {
          color: rgba(68, 105, 120, 1);
        }
        .subtitle span {
          color: rgba(68, 105, 120, 0.5);
        }
      }
    }
    .f-mdl-two-item {
      padding-left: 10px;
      margin-top: 4px;
      position: relative;
      box-sizing: border-box;
      height: 84px;
      background: rgba(249, 249, 249, 1);
      flex: 1;
      & + .f-mdl-two-item {
        margin-left: 4px;
      }
      .title {
        margin-top: 10px;
        height: 16px;
        font-family: PingFangSC-Regular;
        line-height: 16px;
        span {
          display: inline-block;
          @include sc(11px, rgba(64, 64, 64, 1), left center);
        }
      }
      .subtitle,
      .subtitle-desc {
        font-family: PingFangSC-Regular;
        margin-top: 2px;
        height: 13px;
        line-height: 13px;
        span {
          display: inline-block;
          @include sc(9px, rgba(153, 153, 153, 1), left center);
        }
      }
      &.high-hb {
        .subtitle-desc {
          margin-top: 0;
          span {
            color: rgba(155, 33, 11, 1);
          }
        }
      }
      &.special-offer {
        .subtitle-desc {
          margin-top: 0;
          span {
            color: rgba(179, 126, 41, 1);
          }
        }
      }
      .bg-wrapper {
        position: absolute;
        right: 0;
        bottom: 0;
        height: 54px;
        width: 104px;
      }
    }
    .f-btm-four-item {
      flex: 1;
      margin-top: 4px;
      height: 113px;
      background: rgba(249, 249, 249, 1);
      text-align: center;
      position: relative;
      & + .f-btm-four-item {
        margin-left: 4px;
      }
      .title {
        height: 15px;
        font-family: PingFangSC-Regular;
        line-height: 15px;
        margin-top: 10px;
        span {
          display: inline-block;
          @include sc(11px, rgba(64, 64, 64, 1));
        }
      }
      .subtitle {
        height: 13px;
        font-family: PingFangSC-Regular;
        line-height: 13px;
        margin-top: 3px;
        span {
          display: inline-block;
          @include sc(9px, rgba(153, 153, 153, 1));
        }
      }
      .bg-wrapper {
        position: absolute;
        bottom: 0;
        width: 100%;
      }
    }
  }
}
</style>
