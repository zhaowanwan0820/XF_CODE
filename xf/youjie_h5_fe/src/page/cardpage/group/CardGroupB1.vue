<template>
  <div class="group-b1-container" v-bind:style="getContainerStyle">
    <div v-if="isLeft" class="content-wrapper">
      <card-item v-bind:style="getRowItemStyle" v-if="getItemByIndex(0)" :item="getItemByIndex(0)"> </card-item>
      <div class="column-wrapper border-left">
        <card-item
          class="top-item"
          v-bind:style="getColumnItemStyle"
          v-if="getItemByIndex(1)"
          :item="getItemByIndex(1)"
        >
        </card-item>
        <card-item v-bind:style="getColumnItemStyle" v-if="getItemByIndex(2)" :item="getItemByIndex(2)"> </card-item>
      </div>
    </div>
    <div v-else class="content-wrapper">
      <div class="column-wrapper">
        <card-item
          class="top-item"
          v-bind:style="getColumnItemStyle"
          v-if="getItemByIndex(0)"
          :item="getItemByIndex(0)"
        >
        </card-item>
        <card-item v-bind:style="getColumnItemStyle" v-if="getItemByIndex(2)" :item="getItemByIndex(2)"> </card-item>
      </div>
      <card-item class="border-left" v-bind:style="getRowItemStyle" v-if="getItemByIndex(1)" :item="getItemByIndex(1)">
      </card-item>
    </div>
  </div>
</template>

<script>
import CardItem from '../card/CardItem'
import { ENUM } from '../../../const/enum'
import Common from './Common'
export default {
  name: 'CardGroupB1',
  mixins: [Common],
  components: {
    CardItem
  },
  computed: {
    getRowItemStyle: function() {
      const { width, height } = this.getLeftItemSize
      return {
        width: width + 'px',
        height: height + 'px'
      }
    },
    getColumnItemStyle: function() {
      const { width, height } = this.getRightItemSize
      return {
        width: width + 'px',
        height: height + 'px'
      }
    },
    getLeftItemSize: function() {
      const { width, height } = this.getContainerSize
      let ratio = 1
      if (this.isCardGroup('B1')) {
        ratio = 3.0 / 4.0
      } else {
        ratio = 1.0 / 1.0
      }
      return {
        width: height * ratio - 1,
        height: height
      }
    },
    getRightItemSize: function() {
      const { width, height } = this.getContainerSize
      let ratio = 1
      if (this.isCardGroup('B1')) {
        ratio = 9.0 / 4.0
      } else {
        ratio = 6.0 / 5.0
      }
      return {
        width: height * 0.5 * ratio,
        height: height * 0.5
      }
    },
    getContainerStyle: function() {
      const { width, height } = this.getContainerSize
      return {
        width: width + 'px',
        height: height + 'px'
      }
    },
    getContainerSize: function() {
      const { width, height } = window.screen
      let ratio = 1
      if (this.isCardGroup('B1')) {
        ratio = 8.0 / 15.0
      } else {
        ratio = 5.0 / 8.0
      }
      return {
        width: width,
        height: width * ratio
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.group-b1-container {
  display: flex;
}
.content-wrapper {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: stretch;
  background-color: $cardbgColor;
}
.column-wrapper {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
}
.border-left {
  border-left: 1px solid $lineColor;
}
.top-item {
  border-bottom: 1px solid $lineColor;
}
</style>
