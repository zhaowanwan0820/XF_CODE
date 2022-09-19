<!-- promotions.vue -->

<template>
  <div class="ui-promotions-wrapper ui-detail-common" @click="openPopup" v-if="getDetailPromos.length > 0">
    <div class="promotions-header">促销</div>
    <!-- 展示促销信息 -->
    <div class="promotions-body">
      <!--  -->
      <template v-for="(item, index) in getDetailPromos">
        <div class="body-list" :key="index">
          <span class="promos-title">{{ utils.activityNameTmp }}</span>
          <!-- <img src="../../../assets/image/change-icon/c0_sale@2x.png" /> -->
          <span>{{ item.desc }}</span>
        </div>
      </template>
    </div>
    <img src="../../../assets/image/change-icon/icon_more.png" />
  </div>
</template>

<script>
import { mapState, mapMutations, mapGetters } from 'vuex'
export default {
  data() {
    return {}
  },

  computed: {
    ...mapState({}),
    ...mapGetters({
      getDetailPromos: 'getDetailPromos'
    })
  },

  methods: {
    ...mapMutations({
      savePromosPopupState: 'savePromosPopupState'
    }),

    /*
        openPopup:
      */
    openPopup() {
      this.savePromosPopupState(true)
    }
  }
}
</script>

<style lang="scss" scoped>
.ui-promotions-wrapper {
  height: auto;
  &.ui-detail-common {
    margin-top: 0;
    padding: 10px 15px;
    display: flex;
    align-items: baseline;
    @include thin-border(#dbdbdb, 15px, auto, true);
  }
  .promotions-header {
    text-align: left;
    line-height: 30px;
    font-size: 14px;
    color: #999999;
  }
  .promotions-body {
    background: #fff;
    flex: 1;
    .body-list {
      margin-left: 10px;
      line-height: 1.5;
      display: flex;
      flex-direction: row;
      align-items: flex-start;
      letter-spacing: 0;
      img {
        width: 15px;
        height: 15px;
      }
      span {
        display: inline-block;
        letter-spacing: normal;
        @include sc(13px, #666666);
        margin-left: 6px;
        &.promos-title {
          @include sc(11px, #ffffff);
          white-space: nowrap;
          white-space: nowrap;
          margin-left: 0;
          margin-top: 1px;
          padding: 0 8px;
          background: #e97c74;
          text-align: center;
          line-height: 18px;
          border-radius: 20px;
        }
      }
    }
  }
  & > img {
    width: 19px;
    margin-left: 10px;
    transform: translateY(-100%);
  }
}
</style>
