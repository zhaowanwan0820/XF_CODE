<template>
  <div>
    <mt-header class="header" title="消息">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <message-item
      :img="require('../../assets/image/hh-icon/message/icon-saleafter.png')"
      :isRead="isRead"
      title="售后提醒"
      :timeTxt="timeTxt"
      :content="isRead ? '您有新的售后提醒' : ''"
      v-on:click="goList"
    ></message-item>
  </div>
</template>

<script>
import { Header, Indicator } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import MessageItem from './child/MessageItem'
import { unReadMsg } from '../../api/message'
export default {
  data() {
    return {
      isRead: false,
      time: 0
    }
  },
  computed: {
    timeTxt() {
      if (!this.time) return ''
      return this.utils.formatDate('YYYY-MM-DD', this.time)
    }
  },
  created() {
    this.getUnReadMsg()
  },
  components: {
    MessageItem
  },
  methods: {
    getUnReadMsg() {
      unReadMsg().then(res => {
        this.isRead = res.isRead === 'yes' ? true : false
        this.time = res.time / 1e3
      })
    },
    goBack() {
      this.$_goBack()
    },
    goList() {
      this.$router.push({ name: 'messageList' })
    }
  }
}
</script>
