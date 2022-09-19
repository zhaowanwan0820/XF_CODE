<template>
  <div class="container1">
    <mt-header class="header" fixed title="管理收货地址">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <div class="list">
      <!-- <div class="empty-wrapper" v-if="isEmpty">
        <img src="../../assets/image/change-icon/address_empty@2x.png">
        <label>您还没有添加收货地址</label>
      </div> -->
      <manage-item
        v-for="item in items"
        :key="item.id"
        :item="item"
        :isDefault="isDefaultItem(item)"
        v-on:onDefault="onDefault(item)"
        v-on:onEdit="onEdit(item)"
        v-on:onDelete="onDelete(item)"
      >
      </manage-item>
      <div class="add-address-wrapper" v-on:click="addAddress" v-if="isEmpty">
        <img class="add-address" src="../../assets/image/hh-icon/icon-添加-加号.svg" />
        <label class="desc">添加收货地址</label>
      </div>
      <div class="add-button-wrapper" v-else>
        <gk-button class="button" type="primary-secondary" v-on:click="addAddress">添加收货地址</gk-button>
      </div>
    </div>
  </div>
</template>

<script>
import { HeaderItem, Button } from '../../components/common'
import ManageItem from './child/ManageItem'
import { mapState, mapMutations, mapActions } from 'vuex'
import * as consignee from '../../api/consignee'
export default {
  components: {
    ManageItem
  },
  computed: {
    ...mapState({
      defaultItem: state => state.address.defaultItem,
      items: state => state.address.items
    }),
    isEmpty() {
      if (this.items && this.items.length === 0) {
        return true
      }
      return false
    }
  },
  created() {
    consignee.consigneeList().then(
      res => {
        this.saveAddressItems(res)
      },
      error => {
        this.$toast(error.errorMsg)
      }
    )
    this.fetchRegions()
  },
  methods: {
    ...mapMutations(['saveAddressItems', 'setDefaultAddress', 'removeAddressItem', 'selectAddressItem']),
    ...mapActions({
      fetchRegions: 'fetchRegions'
    }),
    isDefaultItem(item) {
      if (item && this.defaultItem) {
        if (item.id === this.defaultItem.id) {
          return true
        }
      }
      return false
    },
    goBack() {
      this.$_goBack()
    },
    onDefault(item) {
      let defaultItem = this.defaultItem
      if (defaultItem && defaultItem.id === item.id) {
        return
      }
      this.$messagebox.confirm('是否确认更改默认地址？', '').then(action => {
        if (action === 'confirm') {
          this.$indicator.open()
          consignee.consigneeSetdefault(item.id).then(
            res => {
              this.$indicator.close()
              this.setDefaultAddress(item)
              this.selectAddressItem(item)
            },
            error => {
              this.$indicator.close()
              this.$toast(error.errorMsg)
            }
          )
        }
      })
    },
    onEdit(item) {
      this.goAddressEdit('edit', encodeURIComponent(JSON.stringify(item)))
    },
    onDelete(item) {
      this.$messagebox.confirm('是否确认删除该地址？', '').then(action => {
        if (action === 'confirm') {
          this.$indicator.open()
          consignee.consigneeDelete(item.id).then(
            res => {
              this.removeAddressItem(item.id)
              this.$indicator.close()
            },
            error => {
              this.$indicator.close()
              this.$toast(error.errorMsg)
            }
          )
        }
      })
    },
    addAddress() {
      this.goAddressEdit('add', null)
    },
    goAddressEdit(mode, item) {
      let isFromCheckout = this.$route.query.isFromCheckout
      let goBackLevel = isFromCheckout ? -3 : -1
      this.$router.push({
        name: 'addressEdit',
        query: { mode: mode, item: item, isFromCheckout: isFromCheckout, goBackLevel: goBackLevel }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container1 {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;

  .header {
    @include header;
  }
  .list {
    margin-bottom: 69px;
  }
  .add-button-wrapper {
    width: 100%;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #f2f3f4;
    .button {
      width: 335px;
      @include button($margin: 10px 20px 15px, $radius: 2px);
    }
  }
  .add-address-wrapper {
    margin-top: 10px;
    -webkit-box-flex: 1;
    flex: 1;
    display: flex;
    -webkit-box-pack: center;
    justify-content: center;
    -webkit-box-align: center;
    align-items: center;
    background: #fff;
    height: 100px;
    img {
      width: 14px;
      height: 14px;
      margin-right: 7px;
    }
    .desc {
      color: #666;
      font-size: 15px;
    }
  }
}

.list {
  width: 100%;
  margin-top: 44px;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
}
// .empty-wrapper {
//   margin-top: 40px;
//   height: 260px;
//   display: flex;
//   flex-direction: column;
//   justify-content: flex-start;
//   align-items: center;
// }
// .photo {
//   width: 112px;
//   height: 112px;
// }
.title {
  font-size: 16px;
  color: #8f8e94;
  text-align: center;
  margin-top: 30px;
}
</style>
