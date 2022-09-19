<template>
  <mt-popup v-model="currentValue" position="bottom" style="height: auto">
    <mt-picker
      ref="picker"
      class="picker"
      :slots="buildItems"
      valueKey="name"
      showToolbar
      :itemHeight="50"
      @change="onValuesChange"
    >
      <div class="toolbar">
        <button class="toolbar-item cancel-item" @click="cancel">取消</button>
        <div class="picker-header">
          请选择地区
        </div>
        <button class="toolbar-item confirm-item" @click="confirm">确定</button>
      </div>
    </mt-picker>
  </mt-popup>
</template>

<script>
import { Picker, Popup } from 'mint-ui'
export default {
  name: 'RegionPicker',
  props: {
    items: {
      type: Array
    },
    modal: {
      default: true
    },
    modalFade: {
      default: false
    },
    lockScroll: {
      default: false
    },
    closeOnClickModal: {
      default: true
    }
  },
  data() {
    return {
      currentValue: false
      // slots: this.buildItems
    }
  },
  computed: {
    buildItems: function() {
      let items = new Array()
      this.getDefaultItems(this.items[0].regions, items)
      return items
    }
  },
  methods: {
    /*
     * buildData: 构建数据
     */
    // buildData(values) {
    //     let data = this.item,
    //         showData = [],
    //         index = 0;
    //     showData[index] = data;
    //     for(var j = 0, length = data.length; j < length; j++){
    //         if (data[j].more) {
    //             index = ++index;
    //             showData[index] = data[j].regions;

    //         }
    //     }
    // },
    // @slotValueChange="onSlotValueChange"

    getDefaultItems(_item, defaultItems) {
      if (_item[0].more == 1) {
        let index = 1
        if (defaultItems && defaultItems.length == 0) {
          defaultItems.push({ flex: 1, values: _item, textAlign: 'center', valueKey: defaultItems.length })
          this.getDefaultItems(_item, defaultItems)
        } else if (defaultItems && defaultItems.length > 0) {
          defaultItems.push({ flex: 1, values: _item[0].regions, textAlign: 'center', valueKey: defaultItems.length })
          this.getDefaultItems(_item[0].regions, defaultItems)
        }
      }
    },

    onValuesChange(picker, values) {
      picker.setSlotValues(1, values[0] ? values[0].regions : [])
      picker.setSlotValues(2, values[1] ? values[1].regions : [])
      picker.setSlotValues(3, values[2] ? values[2].regions : [])
    },

    onclickMask() {
      this.currentValue = false
    },
    cancel() {
      this.currentValue = false
    },
    confirm() {
      this.currentValue = false
      let values = this.$refs.picker.getValues()
      // values.unshift(this.items[0])
      this.$emit('onConfirm', values)
    }
  }
}
</script>

<style lang="scss" scoped>
.picker {
  background-color: #fff;
  /deep/ .picker-item.picker-selected {
    font-weight: 600;
  }
}
.toolbar {
  height: 100%;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: stretch;
  background: #fff;
  border-bottom: 1px solid #eaebec;
}
.mint-popup-bottom {
  border: 0;
  overflow: auto;
}
.toolbar-item {
  font-size: 15px;
  border: none;
  border-radius: 0;
  background-color: #fff;
}
.cancel-item {
  margin-left: 15px;
  color: #4e545d;
}
.confirm-item {
  margin-right: 15px;
  color: rgba(85, 46, 32, 1);
}
.picker-header {
  color: #4e545d;
  line-height: 40px;
}
</style>
