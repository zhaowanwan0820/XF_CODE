<!-- PromotionPopup.vue -->
<template>
  <div v-if="detailInfo && detailInfo.promos && detailInfo.promos.length > 0">
    <mt-popup v-model="promoPopstatus" position="bottom" v-bind:close-on-click-modal="false">
      <div class="detail-promotions">
        <div class="header">
          <h3>促销信息</h3>
          <img src="../../../assets/image/hh-icon/icon-关闭.png" v-on:click="close" />
        </div>
        <div class="promotions-body">
          <div class="body-list" v-for="(item, index) in detailInfo.promos" :key="index">
            <span class="name">{{ item.name }}</span>
            <span class="title">{{ item.promo }}</span>
            <div class="content" v-if="item.desc">
              <p>{{ item.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </mt-popup>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
export default {
  data() {
    return {}
  },

  props: {
    promoPopstatus: {
      type: Boolean,
      default: false
    }
  },

  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    })
  },

  methods: {
    ...mapMutations({
      changePopstatus: 'changePopstatus'
    }),

    close() {
      this.changePopstatus(false)
    }
  }
}
</script>

<style lang="scss" scoped>
.detail-promotions {
  padding: 0 12px;
  div.header {
    position: relative;
    h3 {
      font-size: 15px;
      color: rgba(78, 84, 93, 1);
      padding: 0;
      margin: 0;
      height: 44px;
      line-height: 44px;
      text-align: center;
      border-bottom: 0.5px solid rgba(232, 234, 237, 1);
      width: 100%;
    }
    img {
      position: absolute;
      top: 14px;
      right: 10px;
      width: 12px;
      height: 12px;
      opacity: 1;
    }
  }
  .promotions-body {
    background: rgba(255, 255, 255, 1);
    padding: 0 0 12px 0;
    .body-list {
      margin-top: 12px;
      span.name {
        background: rgba(255, 255, 255, 1);
        border-radius: 2px;
        font-size: 10px;
        color: $primaryColor;
        line-height: 10px;
        padding: 3px 6px;
        display: inline-block;
        border: 1px solid $primaryColor;
        margin-right: 7px;
      }
      span.title {
        font-size: 12px;
        color: rgba(71, 76, 82, 1);
        line-height: 12px;
      }
      div.content {
        border-radius: 1px;
        padding: 12px 0 0 0;
        p {
          padding: 0;
          margin: 0;
          font-size: 11px;
          color: $primaryColor;
          line-height: 16px;
          display: -webkit-box;
          -webkit-box-orient: vertical;
          -webkit-line-clamp: 2;
          overflow: hidden;
          padding: 12px 12px 10px 12px;
          background: rgba(255, 244, 244, 1);
        }
      }
    }
  }
}
</style>
