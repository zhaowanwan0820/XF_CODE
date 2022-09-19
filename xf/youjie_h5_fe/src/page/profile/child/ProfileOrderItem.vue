<template>
  <div v-on:click="onclick">
    <img class="order-item-icon" v-bind:src="icon" />
    <label class="item-title">{{ title }}</label>
    <span class="number" v-if="orderNumber == 0 ? '' : orderNumber && isEmpty == false ? '' : orderNumber">
      <label class="val">{{ orderNumber }}</label>
    </span>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'

export default {
  props: {
    icon: {
      type: String
    },
    title: {
      type: String
    },
    testAttr: {
      type: String
    },
    id: {
      default: 0
    },
    orderNumber: {
      type: Number,
      default: 0
    },
    iconWidth: {
      // 兼容宽度不统一
      type: Number,
      default: 31
    }
  },
  data() {
    return {
      isEmpty: false
    }
  },
  computed: mapState({
    height: state => state.cart.height,
    isOnline: state => state.auth.isOnline
  }),
  created() {
    this.isSignin()
  },
  methods: {
    ...mapMutations({
      changeStatus: 'changeStatus'
    }),
    onclick() {
      // Code Review: 去掉testAttr
      if (this.isOnline) {
        if (this.testAttr == 'order') {
          this.changeStatus(this.id)
        }
        this.$router.push({ name: this.testAttr, params: { order: this.id } })
      } else {
        this.$router.push({ name: 'login' })
      }
    },
    // 是否登录
    isSignin() {
      if (this.isOnline) {
        this.isEmpty = true
      } else {
        this.isEmpty = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.order-item {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
  position: relative;
}
.item-title {
  font-size: 13px;
  color: #552e20;
  margin-top: 6px;
  line-height: 18px;
}
.order-item-icon {
  width: 30px;
  height: 30px;
  margin-top: 10px;
}
span.number {
  min-width: 13px;
  height: 13px;
  line-height: 13px;
  background: #fc4139;
  border-radius: 7px;
  font-weight: normal;
  position: absolute;
  top: 7px;
  left: 52px;
  .val {
    display: block;
    line-height: 13px;
    margin: 0 3.25px;
    @include sc(10px, #fff);
  }
}
</style>
