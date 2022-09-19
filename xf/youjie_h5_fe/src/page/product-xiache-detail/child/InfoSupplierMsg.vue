<!-- Detailinfo.vue -->
<template>
  <div class="supplier-msg" v-if="detailInfo.supplier" v-stat="{ id: 'infos_btn_gostore' }">
    <div class="supplier-msg-body">
      <div class="supplier-msg-img" @click="getSupplierInfo">
        <img :src="getUrl" v-if="detailInfo.supplier.icon" />
        <img src="../../../assets/image/hh-icon/supplier/icon-shop.png" v-else />
      </div>
      <div class="supplier-msg-content" @click="getSupplierInfo">
        <div class="supplier-name">{{ detailInfo.supplier.shop_name }}</div>
        <div class="supplier-type">
          <span v-if="detailInfo.supplier.type == 1" class="type type1"><span>&nbsp;积分商家&nbsp;</span></span>
          <span v-if="detailInfo.supplier.type == 3" class="type type3"><span>&nbsp;积分商家&nbsp;</span></span>
          <span v-if="detailInfo.supplier.type == 5" class="type type5"><span>&nbsp;个人商家&nbsp;</span></span>
          <span class="supplier-desc">{{ detailInfo.supplier.personal_signature || '' }}</span>
        </div>
      </div>
      <p class="more-supplier" @click="getSupplier">进店逛逛</p>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  data() {
    return {}
  },
  computed: {
    ...mapState({
      detailInfo: state => state.detail.detailInfo
    }),
    getUrl() {
      return this.detailInfo.supplier.icon
    }
  },

  methods: {
    getSupplier() {
      this.$router.push({ name: 'Supplier', query: { id: this.detailInfo.supplier.sn } })
    },
    getSupplierInfo() {
      this.$router.push({ name: 'SupplierInfo', query: { id: this.detailInfo.supplier.sn } })
    }
  }
}
</script>

<style lang="scss" scoped>
.supplier-msg {
  margin-top: 10px;
  height: 60px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
  padding: 0 15px;
  .supplier-msg-body {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex: 1;
    .supplier-msg-img {
      width: 40px;
      height: 40px;
      margin-right: 10px;
      display: flex;
      align-items: center;
      box-shadow: 0px 2px 8px 0px rgba(216, 216, 216, 0.5);

      img {
        width: 100%;
      }
    }
    .supplier-msg-content {
      flex: 1;
    }
    .supplier-name {
      color: #404040;
      font-size: 13px;
      line-height: 1.6;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .supplier-type {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      margin-top: 4px;
      .type {
        height: 14px;
        padding: 0;
        border-radius: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-right: 5px;
        span {
          margin: 0 -6px;
          line-height: 1;
          @include sc(8px, #ffffff, center center);
        }
        &.type1 {
          background-color: #d8aab7;
        }
        &.type2 {
          background-color: #c2b5cf;
        }
        &.type3 {
          background-color: #d8aab7;
        }
        &.type5 {
          background-color: #b5c884;
        }
      }
      .supplier-desc {
        @include sc(11px, #999999, left center);
        width: 190px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }
  }
  .more-supplier {
    @include sc(11px, #b75800, right center);
  }
}
</style>
