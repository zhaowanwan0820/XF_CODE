<template>
  <div class="container">
    <div class="top-wrapper">
      <div class="title-wrapper">
        <label class="title">{{ item.name }}</label>
        <label class="title">{{ item.mobile }}</label>
      </div>
      <label class="desc address-text" style="-webkit-box-orient:vertical">{{ detailAddress }}</label>
      <div class="bottom-line"></div>
    </div>
    <div class="bottom-wrapper">
      <div class="bottom-left-wrapper" @click="onDefault">
        <img class="indicator" v-bind:src="iconUrl" />
        <label class="subtitle">默认地址</label>
      </div>
      <div class="bottom-right-wrapper">
        <div class="edit-wrapper" @click="onEdit">
          <img class="indicator" src="../../../assets/image/hh-icon/address/icon编辑.svg" />
          <label class="subtitle">编辑</label>
        </div>
        <div class="edit-wrapper delete-wrapper" @click="onDelete">
          <img class="indicator" src="../../../assets/image/hh-icon/address/icon垃圾桶@2x.png" />
          <label class="subtitle">删除</label>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
export default {
  props: {
    isDefault: {
      type: Boolean,
      default: false
    },
    item: {
      type: Object
    }
  },
  created() {
    if (JSON.stringify(this.regionsMap) == '{}') {
      this.saveRegionsMap(this.regionsItems)
    }
  },
  computed: {
    ...mapState({
      regionsItems: state => state.region.items,
      regionsMap: state => state.address.regionsMap
    }),
    iconUrl() {
      if (this.isDefault) {
        return require('../../../assets/image/hh-icon/icon-checkbox-active.png')
      } else {
        return require('../../../assets/image/hh-icon/icon-checkbox.png')
      }
    },
    detailAddress() {
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
    // onclick() {
    //   this.$emit('onclick')
    // },
    onDefault() {
      this.$emit('onDefault')
    },
    onEdit() {
      this.$emit('onEdit')
    },
    onDelete() {
      this.$emit('onDelete')
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  width: 100%;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: #fff;
  border-bottom: 1px solid $lineColor;
  margin-top: 10px;
}
.top-wrapper {
  position: relative;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
}
.title-wrapper {
  height: 20px;
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  margin-top: 25px;
  margin-left: 10px;
}
.title {
  font-size: 16px;
  color: $baseColor;
  margin-left: 10px;
  &:nth-last-child(1) {
    margin-left: 18px;
  }
}
.default {
  width: 28px;
  margin-left: 10px;
  margin-right: 10px;
  border: 1px solid $primaryColor;
  color: $primaryColor;
  font-size: 10px;
  text-align: center;
  border-radius: 2px;
}
.desc {
  color: #999;
  font-size: 14px;
  line-height: 20px;
}
.address-text {
  margin: 10px 30px 17px 20px;
  @include limit-line(2);
}
.bottom-line {
  position: absolute;
  height: 1px;
  left: 10px;
  bottom: 0;
  right: 10px;
  background-color: $lineColor;
}
.bottom-wrapper {
  height: 45px;
  display: flex;
  flex-direction: row;
  justify-content: space-around;
  align-items: stretch;
}
.bottom-left-wrapper {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
}
.bottom-right-wrapper {
  flex: 1;
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
  align-items: stretch;
}
.edit-wrapper {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
}
.delete-wrapper {
  margin-right: 10px;
}
.indicator {
  width: 16px;
  height: 16px;
  margin-left: 20px;
  margin-right: 7px;
}
.icon {
  width: 18px;
  height: 18px;
  margin-left: 10px;
}
.subtitle {
  font-size: 14px;
  line-height: 16px;
  color: #7c7f88;
  margin-right: 10px;
}
</style>
