<template>
  <div class="address-container" @click="onclick">
    <div class="top-wrapper">
      <div v-if="hasAddress" class="selected-wrapper">
        <div class="left">
          <div class="title-wrapper">
            <label class="title-name">{{ item.name }}</label>
            <label class="title-phone">{{ item.mobile }}</label>
            <label class="default" v-if="isDefault"><span>默认地址</span></label>
          </div>
          <label class="desc address-text" style="-webkit-box-orient:vertical">{{ detailAddress }}</label>
        </div>
        <img class="indicator" src="../../../assets/image/change-icon/enter@2x.png" />
      </div>
      <div v-else class="unselected-wrapper">
        <img class="add-address" src="../../../assets/image/hh-icon/icon-添加-加号.svg" />
        <label class="desc">添加收货地址</label>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
export default {
  props: {
    item: {
      type: Object
    }
  },
  computed: {
    ...mapState({
      regions: state => state.region.items,
      regionsMap: state => state.address.regionsMap
    }),
    hasAddress() {
      if (this.item) {
        return true
      }
      return false
    },
    isDefault() {
      if (this.item && this.item.is_default) {
        return true
      }
      return false
    },
    detailAddress() {
      if (JSON.stringify(this.regionsMap) == '{}') {
        this.saveRegionsMap(this.regions)
      }
      let address = ''
      // address += this.regionsMap[this.item.country] || ''
      address += this.regionsMap[this.item.province] || ''
      address += this.regionsMap[this.item.city] || ''
      address += this.regionsMap[this.item.district] || ''
      address += this.item.address
      return address
    }
  },
  methods: {
    ...mapMutations({
      saveRegionsMap: 'saveRegionsMap'
    }),
    onclick() {
      this.$emit('onclick')
    }
  }
}
</script>

<style lang="scss" scoped>
.address-container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  background-color: #fff;
}
.top-wrapper {
  flex: 1;
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  background-color: #fff;
  background-image: url('../../../assets/image/hh-icon/c10-checkout/bottom-line.png');
  background-size: 100%;
  background-position: left bottom;
  background-repeat: no-repeat;
  padding: 0 15px 5px;
  min-height: 45px;
}
.selected-wrapper {
  flex: 1;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  min-height: 45px;
  padding: 15px 0;
  .left {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .title-wrapper {
    height: 20px;
    display: flex;
    flex-direction: row;
    justify-content: flex-start;
    align-items: center;
    color: #404040;
    font-size: 13px;
    line-height: 1.5;
    .title-name {
      margin-right: 15px;
    }
    .title-phone {
      margin-right: 10px;
    }
    .default {
      height: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: rgba(183, 88, 0, 0.5);
      span {
        line-height: 1.2;
        @include sc(9px, #ffffff);
      }
    }
  }
  .address-text {
    @include sc(13px, #999999);
    margin-top: 7px;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: normal;
    display: -webkit-box;
    /*! autoprefixer: ignore next */
    -webkit-box-orient: vertical;
  }
  .indicator {
    margin-left: 10px;
    width: 7px;
  }
}
.unselected-wrapper {
  flex: 1;
  display: flex;
  // flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 45px;
  img {
    width: 14px;
    height: 14px;
    margin-right: 7px;
  }
  .desc {
    color: #fc7f0c;
    font-size: 15px;
    border-radius: 1px;
  }
}
.line-wrapper {
  // position: relative;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  height: 2px;
}
</style>
