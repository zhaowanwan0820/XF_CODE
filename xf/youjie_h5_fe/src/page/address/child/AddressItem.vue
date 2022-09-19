<template>
  <div class="container" @click="onclick">
    <div class="left-wrapper">
      <div class="title-wrapper">
        <label class="title">{{ item.name }}</label>
        <label class="title">{{ item.mobile }}</label>
        <label class="default" v-if="isDefault">默认</label>
      </div>
      <label class="desc address-text" style="-webkit-box-orient:vertical">{{ detailAddress }}</label>
    </div>
    <img class="indicator" v-bind:src="iconUrl" />
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
export default {
  props: {
    item: {
      type: Object
    },
    isSelected: {
      type: Boolean,
      default: false
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
      if (this.isSelected) {
        return require('../../../assets/image/change-icon/multiple_choice@2x.png')
      } else {
        return require('../../../assets/image/change-icon/choice@2x.png')
      }
    },
    isDefault() {
      if (this.item) {
        return this.item.is_default
      }
      return false
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
    onclick() {
      this.$emit('onclick')
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  background-color: #fff;
  border-bottom: 1px solid #e8eaed;
}
.left-wrapper {
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
  margin-top: 10px;
  margin-left: 10px;
}
.icon {
  width: 16px;
  height: 16px;
  margin-left: 10px;
}
.title {
  font-size: 16px;
  color: #4e545d;
  margin-left: 10px;
}
.default {
  padding: 0 3px;
  margin-left: 10px;
  margin-right: 10px;
  border: 1px solid $primaryColor;
  color: $primaryColor;
  font-size: 10px;
  text-align: center;
  border-radius: 2px;
}
.desc {
  color: #7c7f88;
  font-size: 14px;
}
.address-text {
  margin-top: 10px;
  margin-bottom: 10px;
  margin-left: 20px;
  @include limit-line(2);
}
.unselected-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
.indicator {
  width: 19px;
  height: 19px;
  margin-left: 10px;
  margin-right: 10px;
}
</style>
