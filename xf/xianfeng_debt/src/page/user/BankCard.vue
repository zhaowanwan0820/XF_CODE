<template>
  <div>
    <div class="banner-wrapper">
      <div class="img-wrapper">
        <img class="img" alt="" :src="img_src" />
      </div>
    </div>
  
    <van-list v-model="loading" :finished="finished" @load="onLoad">
      <div class="item-list" v-for="i in list" :key="i.bankcard_id">
        <van-cell class="title" :value="i.name" />
        <div class="panel">
          <van-row>
            <van-col span="24"><span class="text">银行卡号：</span> <span class="item-value">{{ i.bankcard }}</span></van-col>
          </van-row>
          <van-row>
            <van-col span="24"><span class="text">开户人姓名：</span> <span class="item-value">{{ i.card_name }}</span></van-col>
          </van-row>
        </div>
      </div>
    </van-list>
  </div>
</template>

<script>
import { getDebtList, cancelDebt, republish, viewPDF } from '../../api/mydebt'
import { getUserBankCard } from '../../api/user'

export default {
  name: 'BankCard',
  data() {
    return {
      img_src: require('../../assets/image/bank_card.png'),
      show: false,
      src: '',
      active: 0,
      list: [],
      loading: false,
      finished: false,
      page: 0, //onload会加1所以这里必须是0
      limit: 10
    }
  },
  computed: {
    finishedText() {
      return this.list.length ? '没有更多了' : '暂无数据'
    }
  },
  methods: {
    
    getList() {
      getUserBankCard().then(res => {
        if (res.code == 3029) {
          this.list = []
          this.loading = false
          this.finished = true
        } else {
          this.list = [...this.list, ...res.data]
          this.loading = false // 加载状态结束
           this.finished = true
        }
      })
    },
    onLoad() {
     
      this.getList()
    }
  },
  created() {}
}
</script>

<style lang="less" scoped>
.item-list {
  margin-top: 10px;
  
  .item-value {
    font-weight: 700;
    vertical-align:center;
      vertical-align: top;
      color: #333333;
  }
  .panel {
    background-color: #fff;
    padding: 15px;
    .text {
      color: #4a4a4a;
      display: inline-block;
      height: 100%;
      width: 100px;
      text-align: justify;
     
      &::after {
          display: inline-block;
          width: 100%;
          content: '';
          height: 0;
      }
    }
   

  }
  .opt-btn {
    background-color: #fff;
    padding-left: 16px;
    line-height: 60px;

    &:before {
      content: '';
      display: block;
      border-top: 1px solid #ebedf0;
      transform: scaleY(0.5);
    }
  }
}
.banner-wrapper {
  position: relative;
  background-color: #fff;
  height: 160px;
}
.img-wrapper {
  width: 100%;
  height: 160px;

  .img {
    margin: 0px auto 0px;
    display: flex;
    width: auto;
    height: 100%;
  }
}
</style>
