<!-- OrderTrack.vue -->
<template>
  <div class="container">
    <mt-header class="header" title="物流信息">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="order-track-body">
      <p class="source">物流公司：{{ shipName }}</p>
      <div class="number">
        <p class="number-title">运单号：</p>
        <div class="ship-wrapper">
          <p v-for="(item, index) in shipArr" :key="index">
            <span>{{ item }}</span>
            <span class="tag-read" :data-clipboard-text="item" v-on:click="getCopy">复制</span>
          </p>
        </div>
      </div>
    </div>
    <template>
      <div v-if="showflag" class="hint-message">
        <h4>物流信息查询方法</h4>
        <p><span>步骤1</span><span class="right">点击上方【复制】完成运单号复制</span></p>
        <p><span>步骤2</span><span class="right">通过浏览器百度物流公司进入物流查询页面</span></p>
        <p>
          <span>步骤3</span><span class="right">将复制的运单号粘贴至快递单号输入栏中，点击【查询】可获得物流信息</span>
        </p>
      </div>
      <ul v-else class="hint-list">
        <li v-if="Reason">{{ Reason }}</li>
        <li v-for="item in reverseList">
          <span class="dot"></span><span class="date">{{ item.AcceptTime }}</span
          ><span class="right">{{ item.AcceptStation }}</span>
        </li>
      </ul>
    </template>

    <!-- <div class="logistics">
        <div class="info">
          <div class="match" v-for="(item,index) in trackList.status" v-bind:key="item.id" v-bind:class="{'line': index == trackList.status.length-1}">
            <img class="imgone" src="../../../assets/image/change-icon/e5_dot_red@2x.png" v-if="index == 0">
            <img class="imgthree" src="../../../assets/image/change-icon/e5_dot@2x.png" v-if="index != 0">
            <div>
                <p>{{item.content}}</p>
                <span v-if="item.datetime != null">{{item.datetime | convertTime}}</span>
                <span v-if="item.datetime == null"></span>
            </div>
          </div>
        </div>
    </div> -->
  </div>
</template>

<script>
import { HeaderItem } from '../../../components/common'
import { Header } from 'mint-ui'
import { ENUM } from '../../../const/enum'

import { Toast } from 'mint-ui'
import Clipboard from 'clipboard'
import { orderGet } from '../../../api/order'
export default {
  data() {
    return {
      shipSn: 0,
      shipName: '',
      shipArr: [],
      shipperCode: '',
      shipList: [],
      Reason: '',
      showflag: false
    }
  },
  created() {
    let id = this.$route.params.orderTrack ? this.$route.params.orderTrack : ''
    this.orderInfo(id)
  },
  computed: {
    reverseList() {
      return this.shipList.reverse()
    }
  },
  methods: {
    goBack() {
      let isTrack = this.$route.params.isTrack ? this.$route.params.isTrack : ''
      if (isTrack) {
        this.$_goBack()
      } else {
        this.$router.push({ name: 'order', params: { order: ENUM.ORDER_STATUS.DELIVERING } })
      }
    },
    // 获取订单物流状态查询
    orderInfo(id) {
      orderGet(id).then(res => {
        if (res) {
          this.shipSn = res.shipping.code
          this.splitShipNumber(this.shipSn)
          this.shipName = res.shipping.name
          this.getTrackList(this.shipSn, this.shipName)
        }
      })
    },
    // 复制
    getCopy() {
      var clipboard = new Clipboard('.tag-read')
      clipboard.on('success', e => {
        console.log('复制成功')
        // 释放内存
        clipboard.destroy()
      })
      clipboard.on('error', e => {
        // 不支持复制
        console.log('该浏览器不支持自动复制')
        // 释放内存
        clipboard.destroy()
      })
      Toast({
        message: '复制成功',
        iconClass: 'mintui mintui-field-success',
        duration: 2000
      })
    },
    splitShipNumber(shipNumber) {
      let shipArr = shipNumber.replace(/;/gi, ',').split(',')
      this.shipArr = shipArr
    },
    getTrackList(a, b) {
      if (b.indexOf('百世') != -1) {
        this.shipperCode = 'HTKY'
      } else if (b.indexOf('中通') != -1) {
        this.shipperCode = 'ZTO'
      } else if (b.indexOf('申通') != -1) {
        this.shipperCode = 'STO'
      } else if (b.indexOf('圆通') != -1) {
        this.shipperCode = 'YTO'
      } else if (b.indexOf('韵达') != -1) {
        this.shipperCode = 'YD'
      } else if (b.indexOf('邮政') != -1) {
        this.shipperCode = 'YZPY'
      } else if (b.indexOf('EMS') != -1) {
        this.shipperCode = 'EMS'
      } else if (b.indexOf('天天') != -1) {
        this.shipperCode = 'HHTT'
      } else if (b.indexOf('京东') != -1) {
        this.shipperCode = 'JD'
      } else if (b.indexOf('优速') != -1) {
        this.shipperCode = 'UC'
      } else if (b.indexOf('德邦') != -1) {
        this.shipperCode = 'DBL'
      } else if (b.indexOf('宅急送') != -1) {
        this.shipperCode = 'ZJS'
      } else if (b.indexOf('顺丰') != -1) {
        this.shipperCode = 'SF'
      }
      let str =
        'https://logistics.huanhuanyiwu.com/logistics/trajectory?shipperCode=' +
        this.shipperCode +
        '&logisticsCode=' +
        a
      // let str = 'http://47.104.177.224:8098/logistics/trajectory?shipperCode=YTO&logisticsCode=806383636359074390'
      this.$api.get(str, null, r => {
        if (r.code == 1) {
          this.showflag = false
          r.data = JSON.parse(r.data)
          if (r.data.Traces.length < 1) {
            this.showflag = true
          } else {
            this.shipList = r.data.Traces
            if (this.shipList[this.shipList.length - 1].AcceptStation.indexOf('签收图') != -1) {
              this.shipList.pop()
            }
          }
        } else {
          this.showflag = true
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  .header {
    @include header;
    border-bottom: 1px solid #e8eaed;
  }
  .order-track-body {
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 -0.5px 0 0 rgba(232, 234, 237, 1);
    padding-bottom: 10px;
    p {
      height: 20px;
      font-size: 14px;
      font-family: PingFangSC-Regular;
      color: rgba(124, 127, 136, 1);
      line-height: 20px;
    }
    .source {
      padding: 15px 0 10px 15px;
    }
    .number {
      padding: 0px 0 15px 15px;
      .number-title,
      .ship-wrapper {
        float: left;
      }
      .ship-wrapper {
        p {
          margin-bottom: 5px;
        }
      }
      .tag-read {
        width: 32px;
        height: 16px;
        font-size: 12px;
        line-height: 16px;
        text-align: center;
        color: $markColor;
        background-color: #fff;
        border: 1px solid $markColor;
        border-radius: 2px;
        display: inline-block;
      }
    }
  }
  .hint-message {
    margin-top: 10px;
    background: #fff;
    h4 {
      font-size: 14px;
      color: #ddd;
      padding: 0 10px;
      line-height: 34px;
    }
    p {
      font-size: 14px;
      color: #000;
      padding: 0 15px;
      margin-bottom: 10px;
      span {
        display: inline-block;
        max-width: 285px;
        vertical-align: text-top;
      }
      .right {
        margin-left: 20px;
      }
    }
  }
  .hint-list {
    margin-top: 10px;
    padding: 10px 0;
    background: #fff;
    li {
      font-size: 14px;
      padding: 0 20px 0 30px;
      line-height: 1.5em;
      position: relative;
      span {
        display: block;
        border-left: 1px solid #aaa;
      }
      .dot {
        position: absolute;
        left: 21px;
        top: 9px;
        width: 20px;
        height: 20px;
        border: 0;
        border-radius: 100%;
        background: #ddd;
      }
      .date {
        color: #545558;
        font-weight: bold;
        line-height: 2em;
        padding: 0 0 0 24px;
      }
      .right {
        color: #666;
        margin: 0;
        padding: 0 0 20px 24px;
      }
    }
    li:first-child {
      .date {
        border-left: 1px solid #fff;
      }
    }
    li:last-child {
      .right {
        border-left: 1px solid #fff;
      }
    }
  }
}
</style>
