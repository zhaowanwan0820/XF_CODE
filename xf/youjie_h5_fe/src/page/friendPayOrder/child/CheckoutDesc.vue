<template>
  <div class="container">
    <div>
      <label class="title">{{ title }}</label>
      <label class="warn" v-if="warn && status === 0">&nbsp;余额不足，需兑换&nbsp;</label>
    </div>
    <div>
      <label class="subtitle">
        <span v-if="isIcon">-</span>
        <img src="../../../assets/image/hh-icon/b0-home/money-icon.png" v-if="isIcon" />
        {{ subtitle }}
      </label>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    title: {
      type: String
    },
    subtitle: {
      type: String
    },
    warn: {
      type: Boolean
    },
    status: {
      type: Number
    },
    isIcon: {
      type: Boolean
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  height: 16px;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  background-color: #fff;
  padding-bottom: 7px;
}
.title {
  @include sc(11px, #888);
  line-height: 16px;
}
.subtitle {
  flex: 1;
  color: #404040;
  @include sc(11px, #404040);
  line-height: 16px;
  text-align: right;
  display: flex;
  align-items: center;
  img {
    display: block;
    width: 12px;
    height: 12px;
    margin-right: 2px;
  }
  label {
    line-height: 16px;
  }
}
.warn {
  width: 130px;
  text-align: center;
  font-size: 12px;
  margin-left: 10px;
  line-height: 21px;
  color: $deleteColor;
}
</style>
