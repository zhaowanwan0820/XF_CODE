<template>
  <div class="container1">
    <mt-header class="header" title="收货地址">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
      <header-item slot="right" title="管理" v-on:onclick="goManage"> </header-item>
    </mt-header>
    <div class="list">
      <address-item
        v-for="item in items"
        :key="item.id"
        :item="item"
        :isSelected="isSelectedItem(item)"
        v-on:onclick="onclick(item)"
      >
      </address-item>
    </div>
  </div>
</template>

<script>
import { HeaderItem } from '../../components/common'
import AddressItem from './child/AddressItem'
import { mapState, mapMutations, mapActions } from 'vuex'
import * as consignee from '../../api/consignee'
export default {
  components: {
    AddressItem
  },
  computed: {
    ...mapState({
      selectedItem: state => state.address.selectedItem,
      items: state => state.address.items
    })
  },
  created: function() {
    this.fetchRegions()
    consignee.consigneeList().then(
      res => {
        this.saveAddressItems(res)
      },
      error => {
        this.$toast(error.errorMsg)
      }
    )
  },
  methods: {
    ...mapMutations(['selectAddressItem', 'saveAddressItems']),
    ...mapActions({
      fetchRegions: 'fetchRegions'
    }),
    isSelectedItem(item) {
      if (item && this.selectedItem) {
        if (item.id === this.selectedItem.id) {
          return true
        }
      }
      return false
    },
    goBack() {
      this.$_goBack()
    },
    goManage() {
      this.$router.push({ name: 'addressManage', query: { isFromCheckout: true } })
    },
    onclick(item) {
      this.selectAddressItem(item)
      this.goBack()
    }
  }
}
</script>

<style lang="scss" scoped>
.container1 {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
}
.header {
  @include header;
  border-bottom: 1px solid $lineColor;
}
.list {
  width: 100%;
  flex: 1 0 0;
  overflow-y: auto;
}
</style>
