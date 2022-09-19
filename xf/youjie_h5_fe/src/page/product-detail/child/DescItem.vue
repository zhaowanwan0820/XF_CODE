<!-- Details.vue -->
<template>
  <div class="show-iframe">
    <webview :url.sync="getUrl" :padding="100" :isAutoHeight="true"></webview>

    <div class="list-empty" v-if="!getUrl">
      <img src="../../../assets/image/change-icon/empty_details@2x.png" />
      <p>本商品暂无详情</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {}
  },
  computed: {
    getUrl: function() {
      return this.url
    }
  },
  props: ['url', 'unqie']
}
</script>

<style lang="scss">
.show-iframe {
  min-height: 32vw;
  height: 100%;
  /deep/ .container {
    padding: 0 5px !important;
  }
}
.ui-iframe {
  height: 100%;
  width: 100%;
  -webkit-overflow-scrolling: touch;
  overflow-y: scroll;
  &.hastop {
    padding-top: 44px;
  }
  iframe {
    height: 100%;
    width: 100%;
    border: 0;
  }
}
.list-empty {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  img {
    width: 55px;
  }
  p {
    color: #7c7f88;
    font-size: 17px;
    padding: 0;
    margin: 0;
    font-weight: normal;
  }
}
</style>
