<template>
  <div class="container">
    <mt-header class="header" :title="title">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <!-- <p>{{ paramas }}</p> -->
    <div class="search">
      <span class="search-item" v-for="item in selList" :key="item.name" @click="select(item)">
        {{ fitterName(item) }}
        <i class="ico"></i>
      </span>
    </div>
    <div class="sec-box" :name="ListName" v-if="selShow">
      <div
        class="list"
        v-for="item in selectList"
        :key="item.id"
        :id="item.id"
        :name="item.name"
        @click="checkItem(ListName, item.id)"
      >
        {{ item.name }}
      </div>
    </div>
    <div class="fund" v-else>
      <div class="massage" v-if="!isShow">
        <span>{{ isText }}</span>
      </div>
      <div
        class="fund-box"
        v-for="(item, index) in listArr"
        :key="index"
        v-else
        v-infinite-scroll="getMore"
        infinite-scroll-distance="10"
      >
        <div class="foot-box">
          <p>{{ format1(item.type) }}</p>
          <p class="orange" v-if="item.direction != 1">{{ toThousands(item.money) }}</p>
          <p v-else>-{{ toThousands(item.money) }}</p>
        </div>
        <div class="foot-box">
          <span> {{ format2(item.type) }}</span>
          <span class="ove"> 网信 {{ item.name }}</span>
          <span>{{ utils.formatDate('YYYY-MM-DD hh:mm:ss', item.time) }}</span>
        </div>
      </div>
      <!-- 表示是否还有更多数据的状态 -->
      <div class="loading-wrapper">
        <p v-if="!isMore && listArr.length > 0">没有更多了</p>
        <mt-spinner type="triple-bounce" color="#FD9F21" v-if="isMore"></mt-spinner>
      </div>
    </div>
  </div>
</template>

<script>
import { getAssetList } from '../../../api/mineLoan'
import { ALDATE, ALORG, ALASS, ALTYP } from './static.js'
export default {
  name: 'AssetsLists',
  data() {
    return {
      title: '资产收支明细',
      listArr: [],
      isShow: true,
      isText: '',
      obj: {
        type: 1,
        debtConfirm: 1,
        page: 1,
        limit: 100
      },
      ListName: '',
      selShow: false,
      dataList: ALDATE,
      orginList: ALORG,
      assetsList: ALASS,
      typeList: ALTYP,
      selList: [
        { name: 'time', list: ALDATE },
        { name: 'orgain', list: ALORG },
        { name: 'assets', list: ALASS },
        { name: 'type', list: ALTYP }
      ],
      selectList: [],
      paramas: {
        time: '',
        orgain: null,
        assets: null,
        type: null,
        limit: 10,
        page: 1
      },
      selected: false,
      loading: false,
      isMore: true
    }
  },
  created() {
    this.getList(true)
  },
  // keepAlive 被唤醒时
  activated() {
    const scrollTop = this.$route.meta.scrollTop
    console.log(scrollTop)
    if (scrollTop > 0) {
      document.querySelector('.container').scrollTop = scrollTop
    } else {
      this.getList(true)
    }
  },
  computed: {
    fitterName(list) {
      return function(list) {
        let find = list.list.find(val => {
          if (val.id == this.paramas[list.name]) {
            return val
          }
        })
        return find ? find.name : ''
      }
    }
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    getList(isFirst) {
      if (isFirst) {
        this.paramas.page = 1
      } else {
        this.paramas.page += 1
      }
      getAssetList(this.paramas)
        .then(res => {
          this.loading = false
          if (isFirst) {
            this.listArr = res.rows
          } else {
            this.listArr = [...this.listArr, ...res.rows]
          }
          this.isMore = res.total / 10 < this.paramas.page ? false : true
          this.isShow = res.rows.length > 0 ? true : false
          this.isText = res.rows.length > 0 ? '' : '您还没有任何数据哦~'
        })
        .catch(err => {
          this.loading = false
          this.isMore = false
        })
    },
    getMore() {
      if (this.loading) return

      if (this.isMore) {
        this.loading = true
        this.getList(false)
      }
    },
    toThousands(num) {
      if (num) {
        let c =
          num.toString().indexOf('.') !== -1
            ? num.toLocaleString()
            : num.toString().replace(/(\d)(?=(?:\d{3})+$)/g, '$1,')
        return Math.abs(c)
      } else {
        return 0
      }
    },
    select(item) {
      this.selShow = true
      console.log(item)
      this.ListName = item.name
      this.selectList = item.list
    },
    checkItem(name, item) {
      this.paramas[name] = item
      this.selShow = false
      this.getList(true)
    },
    format1(val) {
      let opt = {
        exchange_capital: '本金',
        exchange_interest: '利息',
        return_exchange_capital: '本金',
        return_exchange_interest: '利息',
        repayment_capital: '本金',
        repayment_interest: '利息',
        repayment_part_capital: '本金',
        repayment_part_interest: '利息',
        98: '有解积分',
        3: '有解积分',
        5: '有解积分'
      }
      return val ? opt[val] : ''
    },
    format2(val) {
      let opt = {
        exchange_capital: '积分兑换',
        exchange_interest: '积分兑换',
        return_exchange_capital: '积分退回债权',
        return_exchange_interest: '积分退回债权',
        repayment_capital: '还款',
        repayment_interest: '还款',
        repayment_part_capital: '还款',
        repayment_part_interest: '还款',
        98: '积分兑换',
        3: '购物消费',
        5: '购物退款'
      }
      return val ? opt[val] : ''
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .massage {
    width: 80%;
    margin: 50px auto 0;
    font-size: 16px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: #999;
    text-align: center;
  }
  .header {
    @include header;
    margin-bottom: 10px;
  }
  .search {
    padding: 0 15px;
    text-align: center;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    .search-item {
      @include sc(12px, #666);
      border-radius: 10px;
      line-height: 20px;
      height: 20px;
      text-align: left;
      max-width: 28%;
      overflow: hidden;
      text-overflow: ellipsis;
      padding: 0 20px 0 10px;
      box-sizing: border-box;
      display: inline-block;
      background: #fff;
      position: relative;
      cursor: pointer;
      &.active:after {
        content: ' ';
        display: block;
        position: absolute;
        bottom: -10px;
        right: 10px;
        width: 0;
        height: 0;
        border-width: 6px 4px;
        border-style: solid;
        border-color: transparent transparent #fff transparent;
      }
      .ico {
        width: 8px;
        height: 8px;
        right: 8px;
        top: 8px;
        position: absolute;
        background: url('../../../assets/image/hh-icon/f0-profile/tip.png') no-repeat;
        background-size: 100%;
      }
    }
  }
  .sec-box {
    @include sc(14px, #666);
    background-color: #fff;
    line-height: 40px;
    flex: 1;
    .list {
      cursor: pointer;
      padding: 0 15px;
      border-bottom: 1px #e9e9e9 solid;
      &:hover {
        background: rgba(252, 127, 12, 0.13);
        color: #fc7f0c;
      }
    }
  }
  .fund {
    flex: 1;
    overflow: auto;
    padding: 0 15px;
    background-color: #fff;
    .fund-box {
      padding: 15px 0;
      border-bottom: 1px solid #e9e9e9;
      .foot-box {
        display: flex;
        justify-content: space-between;
        p {
          flex: 1;
          font-size: 16px;
          font-family: PingFangSC-Medium, PingFang SC;
          font-weight: 500;
          padding-bottom: 10px;
          color: #404040;
          &:last-child {
            text-align: right;
          }
        }
        .orange {
          color: #fc7f0c;
        }
        span {
          font-size: 12px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          padding-right: 6px;
          color: rgba(153, 153, 153, 1);
          text-overflow: ellipsis;
          word-break: break-all;
          height: 16px;
          overflow: hidden;
          &:last-child {
            text-align: right;
            padding-right: 0;
          }
          &.ove {
            max-width: 45%;
          }
        }
      }
    }

    .loading-wrapper {
      text-align: center;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 44px;
      p {
        color: #7c7f88;
        font-size: 12px;
        font-weight: 'Regular';
        padding: 0;
        margin: 0;
      }
      span {
        display: inline-block;
      }
      /deep/ .mint-spinner-triple-bounce-bounce1,
      /deep/ .mint-spinner-triple-bounce-bounce2,
      /deep/ .mint-spinner-triple-bounce-bounce3 {
        background-color: #f0f0f0 !important;
      }
    }
  }
}
</style>
