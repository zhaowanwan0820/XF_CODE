<template>
  <div class="set-price-container">
    <div class="hb-price-choose">
      <div class="icon">
        <img src="../../../assets/image/hh-icon/mlm/hh-price-icon.png" alt="" />
      </div>
      <div class="price-swiper" v-if="slots.length > 0">
        <mt-picker ref="swiper" :slots="slots" :visibleItemCount="3" @change="onValuesChange"></mt-picker>
      </div>
      <div class="edit">
        <img src="../../../assets/image/hh-icon/mlm/icon-edit.png" alt="" @click="editPrice" />
      </div>
    </div>
    <div class="hh-price-portion">
      <div class="item">
        <div class="item-wrapper">
          <div class="title">分销佣金</div>
          <div class="content">{{ isMoving ? '- -' : `￥${retail_charge || 0}` }}</div>
        </div>
      </div>
      <div class="item" v-if="virtual_coin_price > 0">
        <div class="item-wrapper">
          <div class="title">积分变现</div>
          <div class="content">
            <template v-if="isMoving">
              - -
            </template>
            <template v-else>
              <img src="../../../assets/image/hh-icon/b0-home/money-icon.png" alt="" />
              {{ data.virtual_coin_num }}
              <img src="../../../assets/image/hh-icon/mlm/arrow-right-four.png" alt="" class="arrow" />
              ￥{{ virtual_coin_price }}
            </template>
          </div>
        </div>
      </div>
      <div class="item" v-if="data.new_people_charge > 0">
        <div class="item-wrapper">
          <div class="title">拉新奖励</div>
          <div class="content">{{ isMoving ? '- -' : `￥${data.new_people_charge || 0}` }}</div>
        </div>
        <div class="subtitle">*当被邀请买家为首次购买商城商品的用户时可获得</div>
      </div>
      <div class="item" v-if="data.activity_price[0] > 0">
        <div class="item-wrapper">
          <div class="title">活动奖励</div>
          <div class="content">{{ isMoving ? '- -' : `￥${activity_price || 0}` }}</div>
        </div>
        <div class="subtitle">{{ act_desc }}</div>
      </div>
      <div class="item total-item">
        <div class="item-wrapper">
          <div class="title">成单后最高可获得</div>
          <div class="content">{{ isMoving ? '- -' : `￥${total_price}` }}</div>
        </div>
      </div>
    </div>

    <div v-if="popupVisible" class="set-price-popup-wrapper">
      <mt-popup v-model="popupVisible" position="center" close-on-click-modal="false" class="s-p-p-container">
        <div class="s-p-p-body">
          <div class="title">自定义分销价格</div>
          <input class="input" type="number" @input="inputPrice" v-model="inputedPrice" />
          <div class="err-tips" v-if="errMsg">{{ errMsg }}</div>
          <div class="tips">有效价格区间￥{{ min_price }}~￥{{ max_price }}</div>
        </div>
        <div class="s-p-p-footer">
          <div class="cancel" @click="close">取消</div>
          <div class="confirm" @click="confirm">确定</div>
        </div>
      </mt-popup>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isMoving: true,
      crrentPrice: 0,
      popupVisible: false,
      inputedPrice: '',
      errMsg: ''
    }
  },

  props: ['data', 'slots', 'act_desc'],

  created() {
    this.crrentPrice = this.data.base_price
  },

  mounted() {
    this.$nextTick(() => {
      let ele = document.querySelector('.picker-slot-wrapper')
      ele.addEventListener('touchmove', this.moveFn)
      ele.addEventListener('touchend', this.endFn)
      ele.addEventListener('touchcancel', this.endFn)
      let div1 = document.createElement('div'),
        div2 = document.createElement('div')
      div1.className = 'picker-layer top'
      div2.className = 'picker-layer bottom'
      document.querySelector('.picker-center-highlight').appendChild(div1)
      document.querySelector('.picker-center-highlight').appendChild(div2)
      this.isMoving = false
    })
  },

  computed: {
    // 分销佣金
    retail_charge() {
      return this.utils.formatFloat(this.data.retail_charge[this.getIndex()])
    },

    // 积分变现
    virtual_coin_price() {
      if (typeof this.data.virtual_coin_price === 'object') {
        return this.data.virtual_coin_price[this.getIndex()]
      } else {
        return 0
      }
    },

    // 活动奖励金额
    activity_price() {
      return this.data.activity_price[this.getIndex()]
    },

    // 返佣总额
    total_price() {
      return this.data.total_price[this.getIndex()]
    },

    // 有效价格最低值
    min_price() {
      return this.data.exc_price[this.data.exc_price.length - 1]
    },

    // 有效价格最高值
    max_price() {
      return this.data.exc_price[0]
    }
  },

  methods: {
    // 获取价格所在的index
    getIndex() {
      return this.data.exc_price.indexOf(this.crrentPrice)
    },

    moveFn(event) {
      this.isMoving = true
    },

    endFn(event) {
      this.isMoving = false
    },

    onValuesChange(picker, values) {
      this.crrentPrice = values[0]
      this.$emit('change', values[0])
    },

    editPrice() {
      this.popupVisible = true
      // this.$emit('update', this.crrentPrice + 0.1)
    },

    inputPrice() {
      this.inputedPrice = parseFloat(parseFloat(this.inputedPrice).toFixed(1))
      this.errMsg = ''
    },

    confirm() {
      if (this.inputedPrice < this.min_price || this.inputedPrice > this.max_price) {
        this.errMsg = '价格输入错误'
        return
      }
      this.$emit('update', this.inputedPrice)
      this.close()
    },

    close() {
      this.errMsg = ''
      this.popupVisible = false
      this.inputedPrice = ''
    }
  }
}
</script>

<style lang="scss" scoped>
.set-price-container {
  .hb-price-choose {
    position: absolute;
    top: -47px;
    left: 0;
    width: 345px;
    height: 110px;
    background: rgba(255, 255, 255, 1);
    box-shadow: 0px 0px 7px 0px rgba(242, 242, 242, 1);
    border-radius: 55px;
    filter: blur(0px);
    display: flex;
    align-items: center;
    overflow: hidden;
    .icon {
      margin-left: 20px;
      padding-top: 15px;
      img {
        width: 150px;
      }
    }
    .unit {
      margin-left: 10px;
      height: 30px;
      font-size: 22px;
      font-family: PingFangSC-Semibold;
      font-weight: 600;
      color: #772508;
      line-height: 30px;
    }
    .price-swiper {
      width: 130px;
      /deep/ .picker-center-highlight {
        height: 100%;
        &::before,
        &::after {
          display: none;
        }
        .picker-layer {
          position: absolute;
          height: 100%;
          width: 100%;
          left: 0;
          background-color: rgba(255, 255, 255, 0.92);
          box-sizing: border-box;
          z-index: 10;
          &.top {
            top: -100%;
            @include thin-border(rgba(119, 37, 8, 0.1), 0, 0, true);
          }
          &.bottom {
            top: 0;
            @include thin-border(rgba(119, 37, 8, 0.1), 0, 0, true, false);
          }
        }
      }
      /deep/ .price-swiper-list {
        font-size: 20px;
        font-family: DINAlternate-Bold;
        font-weight: bold;
        .picker-item {
          color: #772508;
          padding: 0;
          display: flex;
          align-items: baseline;
          justify-content: center;
          &.picker-selected {
            font-size: 27px;
            &:before {
              content: '￥';
              font-size: 20px;
              // margin-right: -10px;
            }
          }
        }
      }
    }
    .edit {
      height: 36px;
      display: flex;
      align-items: center;
      img {
        width: 20px;
        margin-left: 5px;
      }
    }
  }
  .hh-price-portion {
    padding: 70px 15px 0;
    .item {
      @include thin-border(#f4f4f4, 0, 0);
      padding: 12px 0 5px;
      &.total-item {
        padding-bottom: 0;
        .title {
          font-size: 14px;
          font-family: PingFangSC;
          font-weight: 600;
          color: #404040;
          line-height: 20px;
        }
        .content {
          font-size: 17px;
          font-family: PingFangSC;
          font-weight: 600;
          color: #772508;
          line-height: 24px;
        }
      }
      .item-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 12px;
      }
      .title {
        font-size: 14px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #707070;
        line-height: 20px;
      }
      .content {
        font-size: 14px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: #404040;
        line-height: 20px;
        display: flex;
        align-items: center;
      }
      img {
        width: 11px;
        &.arrow {
          width: auto;
          height: 5px;
          margin: 0 4px;
        }
      }
      .subtitle {
        font-family: PingFangSC-Regular;
        font-weight: 400;
        line-height: 14px;
        @include sc(10px, #999999, left center);
        margin-top: -7px;
      }
    }
  }
  .s-p-p-container {
    border-radius: 2px;
    background-color: transparent;
    overflow: hidden;
    .s-p-p-body {
      background-color: #ffffff;
      padding: 12px 25px;
      @include thin-border(#efefef, 0, 0);
      text-align: center;
      .title {
        font-size: 16px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        line-height: 28px;
      }
      .input {
        margin-top: 15px;
        width: 162px;
        height: 40px;
        border-radius: 6px;
        border: 1px solid rgba(244, 244, 244, 1);
        text-align: center;
        font-size: 23px;
        font-family: PingFangSC-Medium;
        font-weight: 500;
        color: rgba(102, 102, 102, 1);
        line-height: 32px;
        &:before {
          content: '￥';
          font-size: 22px;
        }
      }
      .err-tips {
        color: #9b210b;
        margin-top: 10px;
        font-size: 12px;
      }
      .tips {
        margin-top: 5px;
        font-size: 12px;
        font-family: PingFangSC-Regular;
        font-weight: 400;
        color: rgba(153, 153, 153, 1);
        line-height: 28px;
      }
    }
    .s-p-p-footer {
      background: transparent;
      display: flex;
      height: 48px;
      div {
        flex: 1;
        width: 148px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        &.cancel {
          background-color: #ffffff;
          color: #666666;
        }
        &.confirm {
          background-color: #772508;
          color: #ffffff;
        }
      }
    }
  }
}
</style>
