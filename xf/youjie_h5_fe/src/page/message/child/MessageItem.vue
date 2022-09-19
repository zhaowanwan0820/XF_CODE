<template>
  <div class="msg-item" @click="onclick">
    <div class="img-wrapper">
      <div class="red" v-if="isRead"></div>
      <img v-if="img" :src="img" alt="" />
      <img v-else src="../../../assets/image/hh-icon/supplier/icon-shop.png" alt="" />
    </div>
    <div class="msg-content">
      <div class="title">
        <span>{{ title }}</span>
        <label>{{ timeTxt }}</label>
      </div>
      <span class="msg-detail">{{ content }}</span>
    </div>
  </div>
</template>

<script>
export default {
  props: ['img', 'isRead', 'title', 'timeTxt', 'content'],
  methods: {
    onclick() {
      this.$emit('click')
    }
  }
}
</script>

<style lang="scss" scoped>
.msg-item {
  box-sizing: border-box;
  width: 100%;
  height: 85px;
  padding: 20px 15px;
  background-color: #fff;

  display: flex;
  // align-items: center;
  .img-wrapper {
    @include wh(45px, 45px);
    position: relative;
    border-radius: 8px;
    border: 1px solid rgba(249, 249, 249, 1);
    .red {
      position: absolute;
      top: -1px;
      right: -5px;
      width: 8px;
      height: 8px;
      background: #9b210b;
      border-radius: 50%;
    }
    img {
      @include wh(45px, 45px);
      border-radius: 8px;
    }
  }
  .msg-content {
    flex: 1;
    margin-left: 18px;
    .title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      span {
        font-size: 16px;
        font-family: PingFangSC;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        line-height: 22px;

        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      label {
        display: inline-block;
        @include sc(11px, #999);
        font-weight: 500;
        line-height: 16px;
      }
    }
    .msg-detail {
      margin-top: 4px;
      font-size: 13px;
      font-weight: 400;
      color: #999;
      line-height: 18px;

      display: block;
      max-width: 280px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }
}
</style>
