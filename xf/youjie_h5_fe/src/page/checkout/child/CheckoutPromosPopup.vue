<!-- Shopping.vue -->
<template>
  <mt-popup v-model="isShowSeckillPopup" position="bottom" v-bind:close-on-click-modal="false">
    <div class="seckill-container" v-if="getUsableList.length > 0">
      <div class="seckill-header">
        <span>优惠券</span>
        <img src="../../../assets/image/hh-icon/icon-关闭.svg" v-on:click="closePromoPop(false)" />
      </div>
      <div class="seckill-body">
        <label class="body-item" v-for="(item, index) in getUsableList" :for="`seckill${index}`">
          <div class="item-left">
            <promos-item :item="item" :key="index" class="checkable"></promos-item>
          </div>
          <div class="item-right">
            <input
              :ref="`radio${index}`"
              :checked="item.id == selectedItem.id"
              type="checkbox"
              :id="`seckill${index}`"
              class="seckill-input"
              @change="changeIndex(index)"
              name="changeIndex"
            />
            <label class="seckill-radius" placeholder="v" :for="`seckill${index}`"></label>
          </div>
        </label>
      </div>
      <div class="seckill-footer">
        <button @click="checkNo">不使用优惠券</button>
      </div>
    </div>
  </mt-popup>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { Toast, MessageBox, Button } from 'mint-ui'
import PromosItem from '../../product-detail/child/PromosItem'

export default {
  data() {
    return {
      promotion: [],
      activeIndex: 0
    }
  },

  props: {
    isShowSeckillPopup: {
      type: Boolean,
      default: false
    }
  },

  components: {
    PromosItem
  },

  created() {},

  computed: {
    ...mapGetters({
      getUsableList: 'getUsableList'
    }),
    ...mapState({
      selectedItem: state => state.seckill.selectedItem
    })
  },

  mounted() {
    console.log(this.getUsableList)
  },

  methods: {
    ...mapMutations({
      saveSelectedItem: 'saveSelectedItem',
      unsaveSelectedItem: 'unsaveSelectedItem'
    }),

    // 关闭购物车浮层
    closePromoPop() {
      this.$emit('close', false)
    },

    /**
     * 不是用优惠券
     */
    checkNo() {
      this.unsaveSelectedItem()
      this.closePromoPop()
    },

    changeIndex(index) {
      const status = this.$refs[`radio${index}`][0].checked
      if (!status) {
        this.unsaveSelectedItem()
      } else {
        this.saveSelectedItem(this.getUsableList[index])
      }
      this.closePromoPop()
    }
  }
}
</script>
<style lang="scss" scoped>
.seckill-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .seckill-header {
    display: flex;
    justify-content: space-between;
    height: 50px;
    align-items: center;
    padding: 0 15px;
    @include thin-border();
    span {
      color: #404040;
      font-size: 14px;
    }
    img {
      width: 12px;
    }
  }
  .seckill-body {
    flex: 1;
    overflow: auto;
    padding: 0 15px;
    .body-item {
      display: flex;
      margin-top: 15px;
      .item-left {
        flex: 1;
      }
      .item-right {
        width: 40px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        .seckill-input {
          display: none;
          &:checked + .seckill-radius {
            @include wh(22px, 22px);
            background: #d0b482 url('../../../assets/image/hh-icon/icon-checkbox-勾.svg') center no-repeat;
            background-size: 12px 12px;
            border: 0;
          }
          &:disabled + .seckill-radius {
            visibility: hidden;
          }
        }
        .seckill-radius {
          @include wh(22px, 22px);
          @include borderRadius(50%);
          border: 1px solid #d0b482;
        }
      }
    }
  }
  .seckill-footer {
    padding: 15px;
    button {
      display: block;
      width: 100%;
      background: #ffffff;
      border: 1px solid #552e20;
      height: 36px;
      line-height: 34px;
      font-size: 14px;
      color: #552e20;
      border-radius: 2px;
    }
  }
}
</style>
