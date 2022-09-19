<template>
  <div class="share-say">
    <div class="input-wrapper">
      <div class="border-wrapper border-vertical"></div>
      <div class="border-wrapper border-horizontal"></div>
      <div class="input-content">
        <pre class="same-text">{{ txt }}<template v-if="!txt || txt.match(/\n$/)"><br></template></pre>
        <textarea
          class="same-text"
          @input="inputSay"
          @blur="cannotEdit = true"
          v-model="txt"
          placeholder="分享一下商品的优点、亮点给好友吧，不超过50个字。"
          ref="textinput"
        ></textarea>
        <div class="shadow" v-if="cannotEdit" @click="shadowClick"></div>
      </div>
    </div>
    <mt-popup v-model="isShowPopup" style="height: 66%;" position="bottom" v-bind:close-on-click-modal="false">
      <div class="popup-wrapper">
        <div class="title">
          <span>选择自定义分享文案</span>
          <img src="../../../assets/image/hh-icon/detail/icon-close@3x.png" @click="popClose" alt="" />
        </div>
        <div class="list">
          <template v-for="item in LIST">
            <div class="item" @click="chooseItem(item)">{{ item }}</div>
          </template>
        </div>
      </div>
    </mt-popup>
  </div>
</template>

<script>
const LIST = [
  '考验我们友情的时候到了，给你分享一款好商品，拍下付款确认收货我帮你垫付XX元哦！',
  '我有这个平台内购价，相中哪款跟我说，可以便宜很多呦～～',
  '有想买的吗，我有内部大额优惠价，可以超低价购买！',
  '这里的价格本来就比X东X猫便宜，下单确认收货后，还能找我领XX元红包呦~',
  '确认收货后，找我红包返现XX元，机会有限，速来~~'
]
export default {
  data() {
    return {
      LIST,
      cannotEdit: true,
      isShowPopup: false,
      txt: ''
    }
  },
  props: ['comment'],
  model: {
    prop: 'comment',
    event: 'change'
  },
  mounted() {
    this.txt = this.comment
  },
  methods: {
    shadowClick() {
      this.isShowPopup = true
    },
    popClose() {
      this.isShowPopup = false
    },
    chooseItem(item) {
      this.isShowPopup = false
      this.$refs.textinput.focus()
      this.cannotEdit = false
      this.txt = item
    },
    inputSay() {
      if (this.txt.length > 50) {
        this.txt = this.txt.slice(0, 50)
      }
    }
  },
  watch: {
    txt(value) {
      this.$emit('change', value)
    }
  }
}
</script>

<style lang="scss" scoped>
.share-say {
  padding: 10px 15px;
  background: #ffffff;
  .input-wrapper {
    position: relative;
    box-sizing: border-box;
    overflow: hidden;
    // border: 1px solid rgba(85, 46, 32, 0.2);
    padding: 10px;
    background-color: #fbfbfb;
    .border-wrapper {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      &.border-vertical::before,
      &.border-vertical::after {
        content: '';
        height: 200%; //控制边框宽度
        width: 1px; //控制边框长度
        position: absolute;
        top: 0;
        bottom: 0;
        background-color: rgba(85, 46, 32, 0.2);
        border: 0 solid transparent;
        border-radius: 0;
        transform: scale(0.5); //缩放宽度，达到0.5px的效果
      }
      &.border-vertical::before {
        left: 0;
        transform-origin: top left; //定义缩放基点
      }
      &.border-vertical::after {
        right: 0;
        transform-origin: top right; //定义缩放基点
      }
      &.border-horizontal::before,
      &.border-horizontal::after {
        content: '';
        height: 1px; //控制边框宽度
        width: 200%; //控制边框长度
        position: absolute;
        left: 0;
        right: 0;
        background-color: rgba(85, 46, 32, 0.2);
        border: 0 solid transparent;
        border-radius: 0;
        transform: scale(0.5); //缩放宽度，达到0.5px的效果
      }
      &.border-horizontal::before {
        top: 0;
        transform-origin: top left; //定义缩放基点
      }
      &.border-horizontal::after {
        bottom: 0;
        transform-origin: bottom left; //定义缩放基点
      }
    }
  }
  .input-content {
    position: relative;
    .shadow {
      position: absolute;
      left: 0;
      right: 0;
      top: 0;
      bottom: 0;
      z-index: 2;
    }
    .same-text {
      margin: 0;
      border: none;
      font-size: 13px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: #404040;
      line-height: 18px;
    }
    pre {
      white-space: pre-line;
      visibility: hidden;
    }
    textarea {
      width: 100%;
      background-color: transparent;
      position: absolute;
      top: 0;
      left: 0;
      bottom: 0;
      right: 0;
      padding: 0;
      z-index: 1;
      box-sizing: border-box;
      border-radius: 0;
      resize: none;
    }
  }
}
.popup-wrapper {
  height: 100%;
  display: flex;
  flex-direction: column;
  .title {
    height: 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 15px;
    flex-shrink: 0;
    @include thin-border(#d8d8d8, 0, 0);
    span {
      font-size: 14px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: #404040;
    }
    img {
      width: 12px;
    }
  }
  .list {
    flex-grow: 1;
    overflow-y: auto;
    .item {
      padding: 20px 15px;
      font-size: 14px;
      font-family: PingFangSC-Regular;
      font-weight: 400;
      color: #552e20;
      line-height: 21px;
      @include thin-border(#f4f4f4, 0, 0);
      &:hover {
        background-color: rgba(244, 244, 244, 1);
      }
    }
  }
}
</style>
