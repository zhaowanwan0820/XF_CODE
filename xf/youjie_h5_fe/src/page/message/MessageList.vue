<template>
  <div class="container">
    <mt-header class="header" title="售后提醒">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <template v-if="!isLoading">
      <template v-if="isShowMsg">
        <template v-for="(item, index) in messageList">
          <message-item
            :key="index"
            v-if="item && Object.keys(item).length > 0"
            :img="item.icon"
            :isRead="item.isRead === 'yes' ? true : false"
            :title="item.suppliersName"
            :timeTxt="formatTime(item.time)"
            :content="item.message"
            v-on:click="goWorkOrder(item)"
          ></message-item>
        </template>
      </template>
      <template v-else>
        <div class="null">
          <img src="../../assets/image/hh-icon/empty-list-icon.png" alt="" />
          <span>暂无消息</span>
        </div>
      </template>
    </template>
  </div>
</template>

<script>
import { Header, Indicator } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import MessageItem from './child/MessageItem'
import { unReadMsgList, setReadMsg } from '../../api/message'
export default {
  data() {
    return {
      message: {},
      isLoading: false
    }
  },
  components: {
    MessageItem
  },
  created() {
    this.getMsgList()
  },
  computed: {
    messageList() {
      return Object.values(this.message)
    },
    isShowMsg() {
      let length = 0
      this.messageList.forEach(item => {
        if (item && Object.keys(item).length) length += Object.keys(item).length
      })
      return length
    }
  },
  methods: {
    getMsgList() {
      this.isLoading = true
      Indicator.open()
      unReadMsgList()
        .then(res => {
          this.message = res
        })
        .finally(() => {
          Indicator.close()
          this.isLoading = false
        })
    },
    formatTime(time) {
      return this.utils.formatDate('YYYY-MM-DD HH:mm:ss', time / 1e3)
    },
    goBack() {
      this.$_goBack()
    },
    goWorkOrder(item) {
      if (item.isRead === 'yes') {
        setReadMsg(item.orderSn).then(res => {
          this.goWorkMessage(item.serialNumber)
        })
      } else {
        this.goWorkMessage(item.serialNumber)
      }
    },
    goWorkMessage(id) {
      this.$router.push({ name: 'WorkorderMessage', params: { id, from: 'message' } })
    }
  }
}
</script>

<style lang="scss" scoped>
.header {
  @include header;
  @include thin-border();
}
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
  .null {
    flex: 1;
    background-color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    img {
      @include wh(135px, 135px);
      margin-top: 30px;
    }
    span {
      margin-top: 10px;
      font-size: 18px;
      font-weight: 500;
      color: rgba(102, 102, 102, 1);
      line-height: 25px;
    }
  }
}
</style>
