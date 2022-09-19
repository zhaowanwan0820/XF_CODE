<template>
  <div v-if="popupVisible" class="wrapper">
    <mt-popup v-model="popupVisible" position="center">
      <template v-if="troughInfo.train_sn">
        <div class="through-popup-wrapper">
          <div class="close-wrapper">
            <div class="close" @click="close"></div>
          </div>
          <h3>立减{{ utils.formatMoney(troughInfo.sub_money) }}元</h3>
          <p>有好心人愿意帮你代付积分</p>

          <div class="content-wrapper">
            <div class="img-wrapper">
              <img :src="cartGoods[0].goods.thumb" />
            </div>
            <div class="pro-info">
              <p>{{ cartGoods[0].goods.name }}</p>
              <div class="promos-wrapper" v-if="property.length">
                <span v-for="(item, index) in property" :key="index">{{ item }}</span>
              </div>
              <div class="price-wrapper" :class="{ isproms: !property.length }">
                <span class="icon">￥</span>
                <span class="num">{{ troughInfo.goods.shop_price }}</span>
                <span class="line">￥{{ cartGoods[0].price }}</span>
              </div>
            </div>
          </div>
          <button @click="confirm"><span>立即下单</span></button>
        </div>
      </template>
      <template v-else>
        <div class="defeat-wrapper">
          <div class="close-wrapper">
            <div class="close" @click="close"></div>
          </div>
          <p>抱歉，暂未帮您找到更便宜的价格<br />您可以稍后再试一试</p>
          <button @click="close"><span>继续下单</span></button>
        </div>
      </template>
    </mt-popup>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
export default {
  name: 'ThroughTrain',
  data() {
    return {
      popupVisible: false
    }
  },
  watch: {
    isShowConfirmTrough(val) {
      this.popupVisible = val
    },
    popupVisible(val) {
      // 触摸空白处关闭弹窗
      if (val) return
      this.changeShowConfirmTrough(val)
    }
  },
  computed: {
    ...mapState({
      cartGoods: state => state.checkout.cartGoods,
      isShowConfirmTrough: state => state.detail.isShowConfirmTrough,
      troughInfo: state => state.detail.troughInfo
    }),
    property() {
      let data = this.cartGoods[0].chooseinfo.specification.filter((item, index) => {
        return index % 2 === 0
      })
      return data
    }
  },
  methods: {
    ...mapMutations({
      setConfirmTrough: 'setConfirmTrough',
      changeShowConfirmTrough: 'changeShowConfirmTrough',
      saveSelectedCartGoods: 'saveSelectedCartGoods'
    }),
    close() {
      this.changeShowConfirmTrough(false)
    },
    confirm() {
      this.close()
      let cartGoods = { ...this.cartGoods[0] }
      cartGoods.train_sn = this.troughInfo.train_sn
      cartGoods = [cartGoods]
      this.saveSelectedCartGoods({ cartGoods: cartGoods })
      this.setConfirmTrough(true)
    }
  }
}
</script>

<style lang="scss" scoped>
.wrapper {
  .mint-popup {
    border-radius: 8px;
  }
}
.through-popup-wrapper {
  width: 331px;
  height: 376px;
  border-radius: 8px;

  background: url('../../../assets/image/hh-icon/detail/bg-through-train.png') rgba(255, 237, 218, 1) no-repeat;
  background-size: 375px 172px;

  display: flex;
  flex-direction: column;
  align-items: center;
  .close-wrapper {
    width: 100%;
    height: 32px;
    display: flex;
    justify-content: flex-end;
    .close {
      width: 32px;
      height: 32px;
      background: url('../../../assets/image/hh-icon/detail/icon-close-right.png') top right no-repeat;
      background-size: 28px 26px;
    }
  }
  h3 {
    font-size: 33px;
    font-weight: 500;
    color: #fbfcb8;
    line-height: 46px;
  }
  p {
    margin-top: 4px;
    font-size: 16px;
    font-weight: 400;
    color: #fbfcb8;
    line-height: 22px;
    opacity: 0.8;
  }
  .content-wrapper {
    margin-top: 99px;
    width: 100%;
    display: flex;
    .img-wrapper {
      margin-left: 23px;
      width: 80px;
      height: 80px;
      img {
        width: 100%;
        overflow: hidden;
        border-radius: 4px;
      }
    }
    .pro-info {
      margin-left: 10px;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      p {
        width: 190px;
        font-size: 15px;
        font-weight: 400;
        color: #67401b;
        line-height: 21px;

        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .promos-wrapper {
        margin-top: 7px;
        height: 17px;
        font-size: 0;
        span {
          margin-right: 8px;
          background: rgba(230, 202, 174, 1);
          border-radius: 1px;
          padding: 0 8px;

          display: inline-block;
          @include sc(10px, #a0673f, left);
          font-weight: 400;
          line-height: 17px;

          max-width: 40px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
      }
      .price-wrapper {
        font-size: 0;
        display: flex;
        align-items: baseline;
        &.isproms {
          margin-top: 7px;
        }
        .icon {
          font-size: 16px;
          font-weight: 600;
          color: #772508;
          line-height: 22px;
        }
        .num {
          font-size: 23px;
          font-weight: 600;
          color: #772508;
          line-height: 32px;
        }
        .line {
          margin-left: 6px;
          font-size: 12px;
          font-weight: 400;
          color: #999;
          line-height: 17px;
          text-decoration: line-through;
        }
      }
    }
  }
}
.defeat-wrapper {
  width: 292px;
  height: 220px;
  background: rgba(255, 237, 218, 1);
  border-radius: 8px;

  display: flex;
  flex-direction: column;
  align-items: center;
  .close-wrapper {
    width: 100%;
    height: 26px;
    display: flex;
    justify-content: flex-end;
    margin-bottom: 11px;
    .close {
      width: 28px;
      height: 26px;
      background: url('../../../assets/image/hh-icon/detail/icon-close-right.png') no-repeat;
      background-size: 28px 26px;
    }
  }
  p {
    margin-top: 27px;
    font-size: 16px;
    font-weight: 400;
    color: #67401b;
    line-height: 26px;
    text-align: center;
    opacity: 0.8;
  }
}
button {
  margin-top: 32px;
  width: 184px;
  height: 38px;
  background: #df3749;
  border-radius: 19px;
  span {
    font-size: 18px;
    font-weight: 400;
    color: #fff;
    line-height: 38px;
  }
}
</style>
