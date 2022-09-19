<template>
  <div v-if="popupFlag">
    <mt-popup v-model="popupFlag" position="bottom" v-bind:close-on-click-modal="true" class="photo-share-popup">
      <div
        class="p-s-img-wrapper"
        @click.self="close"
        :style="{ backgroundImage: `url('${share_options.info}')` }"
      ></div>
      <div class="p-s-container">
        <div class="p-s-body">
          <div class="title">通过以下方式，分销给好友该商品</div>
          <div class="subtitle"></div>
          <div class="share-types">
            <template v-for="item in share_items">
              <div class="item" :class="`item-${item.type}`" @click="app_share(item)">
                <img :src="item.url" alt="" />
                <span>{{ item.name }}</span>
              </div>
            </template>
          </div>
        </div>
        <div class="p-s-footer" @click="close">取消</div>
      </div>
    </mt-popup>
  </div>
</template>

<script>
import Clipboard from 'clipboard'
import { Toast } from 'mint-ui'
const share_types = [
  {
    type: 'QQ',
    url: require('../../assets/image/hh-icon/QQ.png'),
    name: 'QQ'
  },
  {
    type: 'Qzone',
    url: require('../../assets/image/hh-icon/Qzone.png'),
    name: 'QQ空间'
  },
  // {
  //   type: 'Sina',
  //   url: require('../../assets/image/hh-icon/Sina.png'),
  //   name: '微博'
  // },
  {
    type: 'WechatSession',
    url: require('../../assets/image/hh-icon/WechatSession.png'),
    name: '微信'
  },
  {
    type: 'WechatTimeline',
    url: require('../../assets/image/hh-icon/WechatTimeline.png'),
    name: '朋友圈'
  }
]

export default {
  name: 'PopupPhotoShare',
  data() {
    return {
      popupFlag: false,
      share_types
    }
  },
  props: {
    value: Boolean,
    options: Array,
    share_options: Object
  },
  model: {
    prop: 'value', //绑定的值，通过父组件传递
    event: 'toggle' //自定义时间名
  },
  computed: {
    share_items() {
      let arr = []
      if (this.isHHApp) {
        this.share_types.forEach((item, index) => {
          if (this.options.indexOf(item.type) > -1) {
            arr.push(item)
          }
        })
      }
      return arr
    }
  },
  methods: {
    open() {
      this.popupFlag = true
    },
    close() {
      this.popupFlag = false
    },
    app_share(item) {
      this.popupFlag = false
      this.hhApp.pureShare(
        this.share_options.info,
        item.type,
        1,
        this.share_options.thumb,
        this.share_options.actName,
        this.share_options.description
      )
    }
  },
  created() {
    this.popupFlag = this.value
  },
  watch: {
    popupFlag(value) {
      this.$emit('toggle', value) //子组件与父组件通讯，告知父组件更新绑定的值
      if (value == false) {
        this.$emit('close')
      }
      if (value == true) {
        this.$emit('open')
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.photo-share-popup {
  background: transparent;
  height: auto;
  display: flex;
  flex-direction: column;
  height: 100%;
  .p-s-img-wrapper {
    overflow: hidden;
    flex: 1;
    background-repeat: no-repeat;
    background-size: auto 90%;
    background-position: center;
    background-color: transparent;
  }
  .p-s-container {
    background: #ededed;
    width: 100%;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .p-s-body {
    background: #ffffff;
    flex: 1;
    padding: 0 15px;
    .title {
      font-size: 14px;
      color: #404040;
      margin-top: 20px;
      line-height: 1.5;
    }
    .subtitle {
      margin-top: 10px;
      line-height: 1.4;
      @include sc(11px, #999999, left);
    }
    .share-types {
      margin-top: 30px;
      display: flex;
      justify-content: flex-start;
      flex-wrap: wrap;
      .item {
        width: 50px;
        margin: 0 18px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        img {
          width: 45px;
          height: 45px;
        }
        span {
          font-size: 12px;
          color: #333333;
          margin-top: 10px;
        }
      }
    }
  }
  .p-s-footer {
    background: #fff;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 5px;
  }
}
</style>
