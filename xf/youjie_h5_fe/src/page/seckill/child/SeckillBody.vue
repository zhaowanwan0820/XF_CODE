<template>
  <div class="secc-list" v-infinite-scroll="getMore" infinite-scroll-distance="10">
    <div
      class="sec-pro-item"
      v-for="item in list"
      :key="item.id"
      @click="goProduct(item.id)"
      v-stat="{ id: `seckill_list_product_${item.id}` }"
    >
      <div class="img">
        <div class="discount" v-if="item.discount > 0 && item.discount < 10">
          <span>{{ utils.formatFloat(item.discount) }}折</span>
        </div>
        <img
          alt=""
          v-lazy="{
            src: item.goods.thumb,
            error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
            loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
          }"
        />
      </div>
      <div class="msg">
        <p class="name">{{ item.goods.name }}</p>
        <div class="price">
          <label class="normal">
            <span class="unit">￥</span>
            <span class="num">{{ utils.formatMoney(item.cash_price) }}</span>
          </label>
          <label class="huan" v-if="Number(item.money_line)">
            <span class="num">积分抵扣￥{{ utils.formatMoney(item.money_line) }}</span>
          </label>
        </div>
        <div class="un-line">
          <span>￥{{ utils.formatMoney(item.goods.price) }}</span>
        </div>
        <div class="sell">
          <template v-if="secCurrentItem.status === 0">
            <span class="waiting">即将开抢，敬请期待</span>
            <button v-stat="{ id: `seckill_list_goLook_${item.id}` }"><span>去看看</span></button>
          </template>
          <template v-else-if="secCurrentItem.status === 1">
            <div class="speed-wrapper">
              <div class="speed">
                <div class="speed-deep" :style="`width: ${item.percent}%`"></div>
              </div>
              <span>已售{{ item.percent }}%</span>
            </div>
            <button v-if="item.secbuy_sale >= item.secbuy_quantity || !item.is_on_sale" class="disabled">
              <span>已售罄</span>
            </button>
            <button v-else v-stat="{ id: `seckill_list_immediateBuy_${item.id}` }"><span>立即抢</span></button>
          </template>
          <template v-else-if="secCurrentItem.status === 2">
            <span class="waiting">该场秒杀已结束</span>
            <button class="disabled"><span>已结束</span></button>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Indicator } from 'mint-ui'
import { seckillList } from '../../../api/seckill'
import { mapState, mapMutations, mapActions } from 'vuex'
export default {
  name: 'SeckillBody',
  data() {
    return {
      isLoading: false
    }
  },
  computed: {
    ...mapState({
      secCurrentItem: state => state.seckillList.secCurrentItem,
      secProducts: state => state.seckillList.secProducts
    }),
    list() {
      return this.secProducts[this.secCurrentItem.id]
    }
  },
  methods: {
    ...mapMutations({
      setSeckillProducts: 'setSeckillProducts',
      changeItemPage: 'changeItemPage',
      changeItemTotal: 'changeItemTotal'
    }),
    getList(flag = false) {
      if (this.isLoading) return
      if (!flag) Indicator.open()
      seckillList(this.secCurrentItem.id, this.secCurrentItem.page)
        .then(
          res => {
            let list = this.addPercent(res.list)
            let obj = { ...this.secProducts }
            if (obj[this.secCurrentItem.id] && obj[this.secCurrentItem.id].length) {
              obj[this.secCurrentItem.id] = [...obj[this.secCurrentItem.id], ...list]
            } else {
              obj[this.secCurrentItem.id] = [...list]
            }
            this.setSeckillProducts(obj)
            if (!this.secCurrentItem.total) this.changeItemTotal(res.paged.total)
          },
          error => {
            console.log(error)
          }
        )
        .finally(() => {
          this.isLoading = false
          if (!flag) Indicator.close()
        })
    },
    addPercent(list) {
      if (!list.length) return
      list.forEach(item => {
        let percent = 0
        if (item.secbuy_quantity < 1 || !item.is_on_sale) {
          percent = 100
        } else {
          percent = parseInt((item.secbuy_sale / item.secbuy_quantity) * 100)
          percent = Math.max(0, percent)
          percent = Math.min(100, percent)
        }
        item.percent = percent
      })
      return list
    },
    getMore() {
      if (this.isLoading) return
      if (
        this.secCurrentItem.page &&
        this.secCurrentItem.total &&
        this.secCurrentItem.page < this.secCurrentItem.total
      ) {
        this.changeItemPage(this.secCurrentItem.page + 1)
        this.getList()
      }
    },
    goProduct(id) {
      this.$router.push({ name: 'SeckillProduct', query: { id: id } })
    }
  }
}
</script>

<style lang="scss" scoped>
.secc-list {
  padding: 0 10px;
  flex: 1;
  overflow: auto;
  .sec-pro-item {
    display: flex;
    padding: 15px 0;
    border-bottom: 1px rgba(85, 46, 32, 0.2) dotted;
    overflow: hidden;
    .img {
      width: 120px;
      height: 120px;
      border-radius: 2px;
      padding-right: 10px;
      position: relative;
      .discount {
        position: absolute;
        left: 0;
        top: 8px;
        width: 47px;
        height: 20px;
        background: linear-gradient(90deg, rgba(247, 61, 231, 1) 0%, rgba(165, 66, 240, 1) 100%);
        border-radius: 0px 39px 39px 0px;

        display: flex;
        align-items: center;
        justify-content: center;
        span {
          font-size: 13px;
          font-weight: 500;
          color: #fff;
          line-height: 20px;
        }
      }
      img {
        width: 100%;
      }
    }
    .msg {
      flex: 1;
      .name {
        height: 30px;
        font-size: 13px;
        font-weight: 500;
        color: #404040;
        line-height: 16px;
        overflow: hidden;
      }
      .price {
        margin-top: 10px;
        font-size: 0;
        .normal {
          color: #772508;
          margin-right: 6px;
          display: inline-block;
          .unit {
            font-size: 13px;
            font-weight: bold;
          }
          .num {
            font-size: 18px;
            font-weight: 500;
          }
        }
        .huan {
          height: 16px;
          background-color: #ef7e2f;
          border-radius: 8px 8px 8px 0px;

          display: inline-block;
          vertical-align: text-bottom;
          .num {
            display: inline-block;
            @include sc(10px, #fff, 50% 75%);
            font-weight: 400;
            line-height: 16px;
          }
        }
      }
      .un-line {
        margin-top: 2px;
        font-size: 12px;
        font-weight: 400;
        color: #999;
        line-height: 18px;
        text-decoration: line-through;
      }
      .sell {
        margin-top: 13px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        .speed-wrapper {
          padding-top: 5px;
          .speed {
            width: 105px;
            height: 10px;
            border-radius: 6px;
            border: 1px solid #8a2222;
            overflow: hidden;
            .speed-deep {
              width: 0%;
              height: 100%;
              background-color: #ab3535;
              background-image: url('../../../assets/image/hh-icon/seckill/bg-progress.png');
              background-size: 6px 10px;
              background-repeat: repeat-x;
              animation: progress 3s linear infinite;
            }
          }
          span {
            margin-top: 3px;
            display: inline-block;
            @include sc(10px, #772508, left);
            font-weight: 300;
            line-height: 14px;
          }
        }
        .waiting {
          @include sc(10px, #772508);
          font-weight: 300;
          line-height: 14px;
        }
        button {
          width: 84px;
          height: 30px;
          background: #772508;
          border-radius: 2px;

          display: flex;
          align-items: center;
          justify-content: center;
          span {
            font-size: 13px;
            font-weight: 400;
            color: #fff;
          }
          &.disabled {
            background: #c0c0c0;
          }
        }
      }
    }
  }
}
@keyframes progress {
  0% {
    background-position: 0 0;
  }
  50% {
    background-position: 9px 0px;
  }
  100% {
    background-position: 18px 0px;
  }
}
</style>
