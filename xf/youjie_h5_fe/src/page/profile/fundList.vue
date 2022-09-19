<template>
  <div class="container">
    <mt-header class="header" :title="title">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="fund">
      <mt-navbar v-model="selected">
        <mt-tab-item v-for="n in selList" :id="n.id" :key="n.id" @click.native="change">{{ n.name }}</mt-tab-item>
      </mt-navbar>
      <!-- tab-container -->
      <mt-tab-container v-model="selected">
        <mt-tab-container-item v-for="n in selList" :id="n.id" :key="n.id">
          <div class="massage" v-if="!isShow">
            <span>{{ isText }}</span>
          </div>
          <div class="fund-box" v-for="(item, index) in listArr" :key="index" v-else @click="goDetal(item.id)">
            <div class="box-title">
              <p>
                <span class="title-small-name"> {{ item.titleName }}</span>
              </p>
              <p>
                <span class="title-small-time">确权时间: </span>
                <span class="title-small-time"> {{ utils.formatDate('YYYY-MM-DD', item.debtConfirmTime) }}</span>
              </p>
            </div>
            <div class="box-foot">
              <div class="foot-box">
                <p>{{ toThousands(item.money) }}</p>
                <span>所有权益(元)</span>
              </div>
              <div class="foot-box">
                <p>{{ item.rate }}%</p>
                <span>年利率</span>
              </div>
              <div class="foot-box">
                <p>{{ toThousands(item.lendingAmount) }}</p>
                <span>出借金额(元)</span>
              </div>
            </div>
          </div>
        </mt-tab-container-item>
      </mt-tab-container>
    </div>
  </div>
</template>

<script>
import { getDateList } from '../../api/mineLoan'
export default {
  name: 'fundList',
  data() {
    return {
      selected: 1,
      title: '网信普惠',
      listArr: [],
      selList: [
        { id: 1, name: '全部' },
        { id: 2, name: '还款中' },
        { id: 3, name: '已结清' }
      ],
      isShow: true,
      isText: '',
      obj: {
        type: 1,
        debtConfirm: 1,
        page: 1,
        limit: 100
      }
    }
  },
  created() {
    this.title = this.$route.params.type == 2 ? '网信普惠' : '尊享'
    let id = this.$route.params.type
    this.obj.type = id
    this.getDate()
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    goDetal(id) {
      this.$router.push({ name: 'confirmationDetail', params: { type: this.$route.params.type, id: id } })
    },
    getDate() {
      this.obj.projectType = this.selected
      getDateList(this.obj)
        .then(res => {
          console.log(res)
          this.listArr = res.rows
          this.isShow = res.rows.length > 0 ? true : false
          this.isText = res.rows.length > 0 ? '' : '您还没有任何数据哦~'
        })
        .catch(err => {
          console.log(err)
        })
    },
    change() {
      console.log(this.selected)
      this.getDate()
    },
    toThousands(num) {
      if (num) {
        let c =
          num.toString().indexOf('.') !== -1
            ? num.toLocaleString()
            : num.toString().replace(/(\d)(?=(?:\d{3})+$)/g, '$1,')
        return c
      } else {
        return 0
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  height: 100%;
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
  }
  .fund {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    .fund-box {
      padding: 12px 0;
      margin: 0 15px;
      border-bottom: 1px solid #e9e9e9;
      .box-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        margin-bottom: 10px;
        .title-small-name {
          font-size: 9px;
        }
        .title-small-time {
          font-size: 12px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(153, 153, 153, 1);
        }
      }

      .box-foot {
        display: flex;
        align-items: flex-end;
        .foot-box {
          flex: 1;
          p {
            font-size: 16px;
            font-family: PingFangSC-Medium, PingFang SC;
            font-weight: 500;
            color: rgba(51, 51, 51, 1);
          }
          span {
            font-size: 11px;
            font-family: PingFangSC-Regular, PingFang SC;
            font-weight: 400;
            color: rgba(153, 153, 153, 1);
          }
        }
        .foot-box:nth-child(2) {
          text-align: center;
        }
        .foot-box:nth-child(3) {
          text-align: right;
          p {
            font-size: 16px;
            font-family: PingFangSC-Medium, PingFang SC;
            font-weight: 500;
            color: rgba(51, 51, 51, 1);
            margin-bottom: 2px;
          }
        }
      }
    }
  }
} // tab样式开始
.mint-tab-container {
  flex: 1;
  overflow: auto;
  background: #fff;
}
.mint-navbar {
  margin-bottom: 10px;
  justify-content: space-around;
}
.mint-tab-item {
  flex-grow: 0;
  flex-basis: auto;
  padding: 14px 0;
  border-bottom: #fff 2px solid;
  &.is-selected {
    color: #fc7f0c;
    border-bottom: #fc7f0c 2px solid;
  }
  .mint-tab-item-label {
    font-size: 14px;
    color: #666;
    font-weight: 400;
  }
}
// tab样式结束
</style>
