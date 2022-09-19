<template>
  <div class="container">
    <mt-header class="header" :title="getTitle">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <form-input-item
      ref="name"
      class="item"
      title="收件人姓名"
      maxlength="15"
      :default="getName"
      placeholder="请如实填写收货人姓名"
    >
    </form-input-item>
    <form-input-item
      ref="mobile"
      class="item"
      title="手机号码"
      maxlength="11"
      :default="getMobile"
      placeholder="请如实填写手机号码"
    >
    </form-input-item>
    <form-text-item
      ref="region"
      class="item"
      title="所在地区"
      :default="getRegion"
      placeholder="市、区、街"
      v-on:onclick="onRegion"
    >
    </form-text-item>
    <form-input-item
      ref="address"
      class="item"
      title="详细地址"
      :isShowLine="false"
      :default="getAddress"
      placeholder="请填写详细地址"
    >
    </form-input-item>
    <gk-button
      class="button"
      :class="{ spacing: getSumitTitle.length < 3 }"
      type="primary-secondary"
      v-on:click="submit"
      >{{ getSumitTitle }}</gk-button
    >
    <region-picker ref="picker" :items="regions" v-on:onConfirm="onPickerConfirm"> </region-picker>
  </div>
</template>

<script>
import { HeaderItem, FormInputItem, FormTextItem, Button } from '../../components/common'
import { mapState, mapMutations, mapActions } from 'vuex'
import * as consignee from '../../api/consignee'
import RegionPicker from './RegionPicker'
export default {
  components: {
    RegionPicker,
    picker: ''
  },
  data() {
    return {
      templeRegion: null
    }
  },
  created: function() {
    this.fetchRegions()
    // this.isCitynNumber();
  },
  computed: {
    ...mapState({
      regions: state => state.region.items,
      regionsMap: state => state.address.regionsMap
    }),
    isAddMode() {
      let mode = this.$route.query.mode
      // add: 添加地址，edit: 编辑地址
      if (mode === 'add') {
        return true
      } else {
        return false
      }
    },
    getTitle() {
      if (this.isAddMode) {
        return '新增地址'
      } else {
        return '修改收货地址'
      }
    },
    getName() {
      if (!this.isAddMode && this.getItem) {
        return this.getItem.name
      } else {
        return null
      }
    },
    getMobile() {
      if (!this.isAddMode && this.getItem) {
        return this.getItem.mobile
      } else {
        return null
      }
    },
    getRegion() {
      let region = null
      if (this.isAddMode) {
        // region = '市，区，街'
      } else {
        if (this.getItem) {
          if (JSON.stringify(this.regionsMap) == '{}') {
            this.saveRegionsMap(this.regions)
          }
          // ${this.regionsMap[this.getItem.country]}
          region = `
            ${this.regionsMap[this.getItem.province]} 
            ${this.regionsMap[this.getItem.city]} 
            ${this.regionsMap[this.getItem.district]}
          `
        }
      }
      return region
    },
    getAddress() {
      if (!this.isAddMode && this.getItem) {
        return this.getItem.address
      } else {
        return null
      }
    },
    getItem() {
      return JSON.parse(decodeURIComponent(this.$route.query.item))
    },
    getSumitTitle() {
      let isFromCheckout = this.$route.query.isFromCheckout
      if (isFromCheckout) {
        return '保存并使用'
      } else {
        return '保存'
      }
    }
  },
  methods: {
    ...mapMutations(['addAddressItem', 'modifyAddressItem', 'selectAddressItem', 'saveRegionsMap']),
    ...mapActions({
      fetchRegions: 'fetchRegions'
    }),

    // 获取配置的国家个数判断是否显示国家一级
    isCitynNumber() {
      this.picker = Object.assign([], this.regions.length <= 1 ? this.regions[0].regions : this.regions)
    },

    goBack() {
      this.$_goBack()
    },

    onRegion(picker, values) {
      this.$refs.picker.currentValue = true
    },
    onPickerConfirm(values) {
      this.$refs.region.value = this.getRegionStr(values)
      this.templeRegion = values[2]
    },
    getRegionStr(values) {
      let title = ''
      for (let i = 0; i < values.length; i++) {
        const element = values[i]
        if (i !== 0) {
          title = title + ' ' + element.name
        } else {
          title = title + element.name
        }
      }
      return title
    },
    updateSelectedAddress(item) {
      // 从确认订单添加/编辑地址后，使用添加或编辑后的地址
      let isFromCheckout = this.$route.query.isFromCheckout
      let goBackLevel = this.$route.query.goBackLevel ? this.$route.query.goBackLevel : -1
      if (isFromCheckout) {
        this.selectAddressItem(item)
        this.$router.go(goBackLevel)
      } else {
        this.goBack()
      }
    },
    submit() {
      let name = this.$refs.name.value
      let mobile = this.$refs.mobile.value
      let address = this.$refs.address.value
      let region = null
      if (this.isAddMode) {
        region = this.templeRegion
      } else {
        if (this.templeRegion) {
          region = this.templeRegion
        } else {
          region = {
            id: this.getItem.district
          }
        }
      }
      if (name === null || name === undefined) {
        this.$toast('请填写收件人姓名')
        return
      }
      if (name.length === 0) {
        this.$toast('请填写收件人姓名')
        return
      }
      if (name.length < 2 || name.length > 15) {
        this.$toast('2-15个字符限制')
        return
      }
      if (mobile === null || mobile === undefined) {
        this.$toast('请填写手机号码')
        return
      }
      if (mobile.length === 0) {
        this.$toast('请填写手机号码')
        return
      }
      if (!this.validator.isNumber(mobile)) {
        this.$toast('请填写正确格式的手机号码')
        return
      }
      let telrule = /^1\d{10}$/
      if (!telrule.test(mobile)) {
        this.$toast({
          message: '请输入11位手机号'
        })
        return
      }
      if (region === null || region === undefined) {
        this.$toast('请选择所在地区')
        return
      }
      if (address === null || address === undefined) {
        this.$toast('请填写详细地址')
        return
      }
      if (address.length === 0) {
        this.$toast('请填写详细地址')
        return
      }
      if (this.isAddMode) {
        this.$indicator.open()
        consignee.consigneeAdd(name, mobile, null, region.id, address).then(
          res => {
            this.$indicator.close()
            this.addAddressItem(res)
            this.updateSelectedAddress(res)
          },
          error => {
            this.$indicator.close()
            this.$toast(error.errorMsg)
          }
        )
      } else {
        let item = this.getItem
        let consigneeId = item ? item.id : null
        this.$indicator.open()
        consignee.consigneeUpdate(consigneeId, name, mobile, null, region.id, address).then(
          res => {
            this.$indicator.close()
            this.modifyAddressItem(res)
            this.updateSelectedAddress(res)
          },
          error => {
            this.$indicator.close()
            this.$toast(error.errorMsg)
          }
        )
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
}
.header {
  @include header;
  @include thin-border(#f4f4f4, 0, 0);
}
.item {
  height: 53px;
}
.button {
  @include button($radius: 2px);
  margin-top: 80px;
  &.spacing {
    letter-spacing: 10px;
    text-indent: 10px;
  }
}
</style>
