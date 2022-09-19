<template>
  <div class="workmessage">
    <p class="status">工单状态：{{ transStatus(list.workOrder.status) }}</p>
    <div class="apply_info">
      <h4>申请信息</h4>
      <div class="products">
        <label>
          <img
            v-if="list.orderGoodsDetails.goodsThumb"
            :src="ProImg + list.orderGoodsDetails.goodsThumb"
            alt="商品图片"
          />
          <img v-else src="../../assets/image/change-icon/default_image_02@2x.png" alt="默认图片" />
          <div class="cell">
            <p>{{ list.orderGoodsDetails.goodsName }}</p>
            <P
              ><span>{{ list.orderGoodsDetails.goodsAttr }}</span></P
            >
          </div>
        </label>
      </div>
      <p>申请原因：{{ transId(list.workOrder.orderTypeId) }}</p>
      <p>申请时间：{{ format(list.workOrder.createTime) }}</p>
      <p>
        申请说明：<span class="break">{{ list.workOrder.description }}</span>
      </p>
      <p v-if="list.workOrder.picArr">凭证：</p>
      <div class="imgthumbnail">
        <span v-for="imgItem in list.workOrder.picArr">
          <img :src="substring(imgItem)" alt="用户上传图片" />
        </span>
      </div>
      <div class="btnbox">
        <mt-button
          :type="list.workOrder.status == 2 ? 'info' : 'danger'"
          class="work_btn"
          v-on:click="goAdd"
          :disabled="list.workOrder.status == 2"
          >添加反馈</mt-button
        >
      </div>
    </div>
    <div class="workorder" v-for="(msg, index) in list.workOrderMessage" :key="`workorder${index}`">
      <label>
        <img v-if="msg.icon" :src="msg.messageRole == 1 ? ProImg + msg.icon : msg.icon" />
        <img v-else src="../../assets/image/workorder-icon/user_ico.png" alt="默认头像" />
        <div class="cell">
          <span>{{ msg.showName }}</span>
          <span class="apply_data">{{ format(msg.createTime) }} </span>
        </div>
      </label>
      <p class="break">{{ msg.message }}</p>
      <div class="imgbox" v-if="msg.pics">
        <span v-for="(imgItem, picIndex) in msg.picArr" :key="`pic${picIndex}`">
          <img :src="substring(imgItem)" alt="用户上传图片" />
        </span>
      </div>
    </div>
  </div>
</template>
<script>
import { Button, Tost } from 'mint-ui'

export default {
  data() {
    return {
      workOrderId: this.$route.params.id ? this.$route.params.id : '',
      list: {},
      ProImg: 'https://shop.itzcdn.com/'
    }
  },
  created() {
    // 获取列表数据
    this.getData()
  },
  methods: {
    getData() {
      // console.log(this.workOrderId);
      this.$api.get('workOrder/' + this.workOrderId, null, r => {
        if (r.code == 1) {
          this.list = r.data
        } else {
          Toast(r.msg)
        }
      })
    },
    goAdd() {
      this.$router.replace({ name: 'Addmessage', params: this.workOrderId })
    },
    substring(str) {
      if (str.indexOf('uploadId') != -1) {
        str = str.match(/(\S*)uploadId/)[1]
      }
      return str
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
      return (
        y + '年' + this.Add0(m) + '月' + this.Add0(d) + '日 ' + this.Add0(h) + ':' + this.Add0(mm) + ':' + this.Add0(s)
      )
    },
    Add0: function(m) {
      return m < 10 ? '0' + m : m
    },
    transId(a) {
      if (a == 1) {
        return '退款'
      } else {
        return '退货退款'
      }
    },
    transStatus(a) {
      if (a == 0) {
        return '待处理'
      } else if (a == 1) {
        return '处理中'
      } else {
        return '已关闭'
      }
    }
  }
}
</script>
<style lang="scss" scoped>
.imgthumbnail img {
  width: 17.5%;
  border: 2px solid #ccc;
  margin-right: 2%;
}
.apply_info {
  background: #fff;
  padding: 6px;
  border-radius: 8px 8px 0 0;
  margin-top: -70px;
}
.status {
  padding: 10px;
  background-image: url('../../assets/image/hh-icon/e5-orderDetail/orderDetail-bg@3x.png');
  background-size: cover;
  height: 108px;
  color: #552e20;
  font-size: 15px;
}
.workmessage {
  position: fixed;
  width: 100%;
  top: 11.733vw;
  bottom: 0;
  overflow: auto;

  font-size: 14px;
  line-height: 2em;
  color: #404040;
  .apply_info {
    color: #999;
    h4 {
      font-weight: bold;
      color: #333;
      padding: 6px;
    }
  }
}
.products label {
  display: flex;
  justify-content: flex-start;
  img {
    width: 80px;
    height: 80px;
  }
  div p {
    padding-left: 10px;
  }
}
.btnbox {
  text-align: right;
  padding: 20px 6px;
  width: auto;
  z-index: 2;
  .work_btn {
    line-height: 30px;
    height: 30px;
    border-radius: 2px;
    width: 30%;
    font-size: 13px;
    background: #fff;
    margin-top: 8px;
    display: inline-block;
    border: 1px solid #552e20;
    color: #552e20;
  }
}
.workorder {
  align-items: center;
  height: auto;
  padding: 8px 12px;
  background-color: #fff;
  margin-top: 8px;
  label {
    display: flex;
    justify-content: flex-start;
    align-items: center;
  }
  .cell {
    font-size: 16px;
    padding-left: 6px;
    color: #404040;
    span {
      display: block;
    }
    .apply_data {
      color: #999;
      font-size: 14px;
    }
  }

  img {
    width: 16%;
    height: 0%;
  }
  p {
    padding-top: 6px;
  }
}
.img_con {
  display: flex;
  justify-content: space-between;
  padding-top: 6px;
  .photo {
    margin: 0;
    border: 2px solid #eee;
  }
}
.imgbox img {
  width: 100px;
  height: 100px;
  border: 2px solid #aaa;
  margin-right: 2%;
}
.break {
  word-break: break-all;
}
</style>
