<template>
  <div class="container">
    <mt-header class="header" title="账单">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="filter">
      <div class="filter-type">
        <span @click="showChooseModel">{{ currentTypeName }}</span>
        <img :class="{ active: isShowTypePicker }" src="../../assets/image/hh-icon/f0-profile/icon-tip.png" alt="" />
      </div>
    </div>
    <div class="list-wrapper">
      <div class="filter-time">
        <div class="time-wrapper" @click="showTimePicker">
          <span>{{ currentMonth }}</span>
          <img src="../../assets/image/hh-icon/f0-profile/icon-tip.png" alt="" />
        </div>
        <div class="bill-wrapper">
          <span>支出￥{{ sum_pay }}</span>
          <span>收入￥{{ sum_income }}</span>
        </div>
      </div>
      <base-list class="items-wrapper" :items="items" :isMore="isMore" :isLoaded="isLoaded" v-on:loadMore="loadMore">
        <balance-item v-for="(item, index) in items" :key="index" :item="item"> </balance-item>
      </base-list>
      <div class="empty" v-if="isLoaded && items.length <= 0">
        <img src="../../assets/image/hh-icon/mlm/no-detail.png" alt="" />
        <p>暂无账单记录</p>
      </div>
      <div class="type-chooser" v-show="isShowTypePicker">
        <div class="chooser-wrapper">
          <div
            class="chooser-item"
            v-for="item in BILL_TYPE"
            :class="{ active: item.id == currentType }"
            :key="`bill_type_${item.id}`"
            @click="chooseBillType(item.id)"
          >
            {{ item.name }}
          </div>
        </div>
        <div class="chooser-bg" @click="hideChooseModel"></div>
      </div>
    </div>
    <mt-datetime-picker
      class="date-picker"
      v-model="pickerVisible"
      type="date"
      ref="timePickerRef"
      :start-date="START_TIME"
      :end-date="new Date()"
      year-format="{value}年"
      month-format="{value}月"
      @confirm="pickTimeConfirm"
    >
    </mt-datetime-picker>
  </div>
</template>

<script>
import { HeaderItem, BaseList } from '../../components/common'
import BalanceItem from './child/BalanceItem'
import { getMoneyHistory } from '../../api/bill'
import { ENUM } from '../../const/enum'
import { BILL_TYPE } from './static'
const START_TIME = new Date(2019, 0, 1, 0, 0, 0)
export default {
  name: 'HuankeBalanceHistory',
  components: {
    BalanceItem
  },
  data() {
    return {
      START_TIME,
      BILL_TYPE,
      balance: 0,
      currentIndex: 0,
      isLoaded: false,
      page: 1,
      items: [],
      isMore: 0,

      sum_pay: 0, // 当前时间范围内的总支出
      sum_income: 0, // 当前时间范围内的总收入
      currentTime: 0, // 当前选中的月份
      currentType: 0, // 当前选中的账单类型
      isShowTypePicker: false, // 账单类型选择蒙层是否显示
      pickerVisible: false
    }
  },
  created() {
    this.loadFirstPageData()
  },
  computed: {
    // 当前选择的账单类型
    currentTypeName() {
      let str
      this.BILL_TYPE.forEach(item => {
        if (this.currentType == item.id) {
          str = item.name
        }
      })
      return str
    },

    // 当前选择的查询月份
    currentMonth() {
      let str = '全部'
      const nowYear = new Date().getFullYear()
      if (this.currentTime) {
        str = `${this.currentTime.getMonth() + 1}月`
      }
      return str
    }
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    loadFirstPageData() {
      this.loadPageData(true)
    },
    loadMorePageData() {
      this.loadPageData(false)
    },
    loadPageData(isFirstPage) {
      this.page = isFirstPage ? 1 : this.page + 1
      let per_page = 10
      const params = {
        status: this.currentType,
        page: this.page,
        per_page,
        time_from: this.getMonthStart(),
        time_to: this.getMonthEnd()
      }

      getMoneyHistory(params).then(
        res => {
          if (isFirstPage) {
            document.querySelector('.items-wrapper').scrollTop = 0
            this.items = res.list
          } else {
            this.items = [...this.items, ...res.list]
          }
          this.sum_income = res.sum_income
          this.sum_pay = res.sum_pay
          this.isMore = res.list.length == per_page ? 1 : 0
          this.isLoaded = true
        },
        error => {
          console.log(error)
        }
      )
    },
    loadMore() {
      if (this.isMore) {
        this.loadMorePageData()
      }
    },

    // 显示类型选择蒙层
    showChooseModel() {
      this.isShowTypePicker = true
    },

    // 隐藏类型选择蒙层
    hideChooseModel() {
      this.isShowTypePicker = false
    },

    // 选择分类
    chooseBillType(id) {
      this.currentType = id
      this.hideChooseModel()
    },

    // 打开时间选择蒙层
    showTimePicker() {
      this.$refs.timePickerRef.open()
    },

    pickTimeConfirm(time) {
      this.currentTime = time
      this.loadFirstPageData()
    },

    /**
     * get the month start from a Date object
     *
     * @return     {Date}    The month start.
     */
    getMonthStart() {
      let timeStr
      if (this.currentTime) {
        let year = this.currentTime.getFullYear()
        let month = this.currentTime.getMonth() + 1
        timeStr = new Date(year, month - 1, 1, 0, 0, 0).getTime() / 1000
      } else {
        timeStr = this.START_TIME.getTime() / 1000
      }
      return timeStr
    },

    /**
     * get the month end from a Date object
     *
     * @return     {Date}    The month end.
     */
    getMonthEnd() {
      let timeStr
      const time = this.currentTime ? this.currentTime : new Date()
      let year = time.getFullYear()
      let month = time.getMonth() + 1
      timeStr = new Date(year, month, 0, 23, 59, 59).getTime() / 1000
      return timeStr
    }
  },
  watch: {
    currentType() {
      this.loadFirstPageData()
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 100%;
  overflow: hidden;
  display: flex;
  position: relative;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  background-color: #fff;
}
.header {
  @include header;
  @include thin-border(#f4f4f4, 15px);
}
.filter {
  .filter-type {
    height: 46px;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding: 0 15px;
    span {
      font-size: 14px;
      font-family: PingFangSC;
      font-weight: 400;
      color: rgba(112, 112, 112, 1);
      margin-right: 3px;
    }
    img {
      width: 5px;
      transform: rotate(90deg);
      &.active {
        transform: rotate(-90deg);
      }
    }
  }
}
.list-wrapper {
  flex: 1;
  overflow-y: auto;
  position: relative;
  display: flex;
  flex-direction: column;
  .filter-time {
    height: 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 15px;
    background-color: rgba(244, 244, 244, 1);
    .time-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 27px;
      padding: 0 15px;
      background-color: #ffffff;
      border-radius: 20px;
      color: rgba(112, 112, 112, 1);
      font-size: 14px;
      img {
        width: 5px;
        transform: rotate(90deg);
        margin-left: 3px;
      }
    }
    .bill-wrapper {
      color: rgba(153, 153, 153, 1);
      font-size: 13px;
      span + span {
        margin-left: 10px;
      }
    }
  }
  .items-wrapper {
    flex: 1;
    overflow-y: auto;
  }
  .type-chooser {
    position: absolute;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    .chooser-wrapper {
      background-color: rgba(244, 244, 244, 1);
      padding: 25px 15px 0 9px;
      display: flex;
      flex-wrap: wrap;
    }
    .chooser-item {
      background-color: #ffffff;
      width: 110px;
      height: 44px;
      line-height: 44px;
      text-align: center;
      font-size: 14px;
      border-radius: 3px;
      color: rgba(102, 102, 102, 1);
      margin-bottom: 25px;
      margin-left: 7px;
      &.active {
        color: #ffffff;
        background-color: rgba(119, 37, 8, 1);
      }
    }
    .chooser-bg {
      flex: 1;
      background-color: rgba(0, 0, 0, 0.5);
    }
  }
}
.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 5;
  img {
    width: 135px;
    height: 135px;
    margin-top: 30px;
  }
  p {
    font-size: 18px;
    color: #666666;
    line-height: 25px;
    margin-top: 15px;
  }
}
.date-picker {
  height: auto;
  /deep/ .picker-slot.picker-slot-center:nth-of-type(3) {
    display: none;
  }
  /deep/ .picker-toolbar {
    display: flex;
    justify-content: space-between;
    padding: 0 20px;
    .mint-datetime-action {
      width: auto;
    }
    .mint-datetime-cancel {
      color: rgba(51, 51, 51, 1);
    }
    .mint-datetime-confirm {
      color: rgba(85, 46, 32, 1);
    }
  }
}
</style>
