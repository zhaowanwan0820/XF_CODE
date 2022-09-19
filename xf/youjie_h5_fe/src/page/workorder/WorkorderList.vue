<!--workOrderList -->
<template>
  <div class="container">
    <mt-header class="header" title="申请售后">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack()"></header-item>
    </mt-header>
    <div class="workorderbody">
      <div class="body-top">
        <ul class="worklist" v-for="(lists, index) in list" :key="index">
          <li v-on:click="ToworkInf(lists.workOrder.serialNumber, lists.workOrder.status)">
            <h5>
              工单状态：<span>{{ changstu(lists.workOrder.status) }}</span>
            </h5>
            <label>
              <img
                v-if="lists.orderGoodsDetails.goodsThumb"
                :src="ProImg + lists.orderGoodsDetails.goodsThumb"
                alt="产品图片"
              />
              <img v-else src="../../assets/image/change-icon/default_image_02@2x.png" alt="默认图片" />
              <p>
                {{ lists.orderGoodsDetails.goodsName }}<span>{{ lists.orderGoodsDetails.goodsAttr }}</span
                ><span>{{ format(lists.workOrder.createTime) }}</span>
              </p>
            </label>
          </li>
        </ul>
      </div>
      <!-- 再次申请 -->
      <div class="btn_box">
        <mt-button
          :type="Flag ? 'info' : 'danger'"
          :disabled="Flag"
          size="large"
          class="work_btn"
          v-on:click="sentMessage"
          >再次申请</mt-button
        >
      </div>
    </div>
  </div>
</template>

<script>
import { Header, Lazyload } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import WorkMessageBody from './MessageBody'
export default {
  data() {
    return {
      orderSn: this.$route.params.id ? this.$route.params.id : '',
      list: {},
      Flag: true,
      orderid: 0,
      ProImg: 'https://shop.itzcdn.com/'
    }
  },
  created() {
    // 获取列表数据
    this.getData()
    this.getFlag()
  },

  methods: {
    getFlag() {
      this.$api.get('workOrderMessage/' + this.orderSn, null, r => {
        // console.log(r)
        if (r.code == 1) {
          r.data.workOrderFlag == 2 ? (this.Flag = true) : (this.Flag = false)
        } else {
          Toast(r.msg)
        }
      })
    },
    goBack() {
      this.$_goBack()
    },
    getData() {
      // console.log(this.orderSn);
      this.$api.get('workOrder/all/' + this.orderSn, null, r => {
        // console.log(r)
        if (r.code == 1) {
          this.list = r.data.list
          this.orderid = r.data.orderId
        } else {
          Toast(r.msg)
        }
      })
    },
    changstu(a) {
      if (a == 0) {
        return '待处理'
      } else if (a == 1) {
        return '处理中'
      } else {
        return '已关闭'
      }
    },
    sentMessage() {
      // console.log("再次申请")
      // console.log(this.orderSn)
      this.$router.push({ name: 'AddWorkorder', params: { id: this.orderSn } })
    },
    format(shijianchuo) {
      //shijianchuo是整数，否则要parseInt转换
      var time = new Date(shijianchuo)
      var y = time.getFullYear()
      var m = time.getMonth() + 1
      var d = time.getDate()
      var h = time.getHours()
      var mm = time.getMinutes()
      var s = time.getSeconds()
      return y + '年' + m + '月' + d + '日 ' + h + ':' + mm + ':' + s
    },
    ToworkInf(id, num) {
      this.$router.replace({ name: 'WorkorderMessage', params: { id: id, status: num } })
    }
  }
}
</script>

<style lang="scss" scoped>
.header {
  @include header;
  border-bottom: 1px solid #e8eaed;
}
.worklist li {
  background: #fff;
  font-size: 14px;
  margin-bottom: 8px;
}
.worklist li h5 {
  font-size: 14px;
  padding: 6px 10px;
  color: #404040;
  display: flex;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
  span {
    color: #552e20;
  }
}
.worklist label {
  display: flex;
  justify-content: flex-start;
  padding: 10px;
  background: #fff;
  img {
    width: 80px;
    height: 80px;
    border: 3px solid #ddd;
  }
  p {
    padding-left: 10px;
    line-height: 2em;
  }
  p span {
    display: block;
    color: #999;
  }
}
.workorderbody {
  position: fixed;
  width: 100%;
  top: 11.733vw;
  bottom: 0;
  overflow: auto;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-flex-direction: column;
  -ms-flex-direction: column;
  flex-direction: column;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
  content: 'viewport-units-buggyfill; top: 11.733vw';
}
.body-top {
  overflow-y: auto;
  content: 'viewport-units-buggyfill; padding-bottom: 5.333vw';
}
.btn_box {
  padding: 30px;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
  background-color: #f9f9f9;
  align-items: center;
  button {
    border-radius: 2px;
    background: #772508;
    color: #fff;
  }
}
</style>
