<template>
  <div class="webview-container">
    <div v-if="isHtml" class="content-wrapper">
      <p v-html="html"></p>
    </div>
    <div v-else class="content-wrapper">
      <iframe
        allowfullscreen="true"
        class="content-wrapper"
        :scrolling="isAutoHeight ? 'no' : 'auto'"
        :style="getFrameStyle"
        :src="url"
        @load="iframeOnload()"
        ref="iframe"
      ></iframe>
    </div>
  </div>
</template>
<script>
import axios from 'axios'

export default {
  name: 'Webview',
  props: {
    url: {
      required: true
    },
    isHtml: {
      type: Boolean,
      default: false
    },
    isAutoHeight: {
      type: Boolean,
      default: false
    },
    padding: {
      type: Number,
      default: 50
    }
  },
  data() {
    return {
      loading: false,
      html: '',
      frameHeight: 1000
    }
  },
  watch: {
    url(value) {
      this.load(value)
    }
  },
  computed: {
    getFrameStyle: function() {
      return {
        height: this.frameHeight + 'px'
      }
    }
  },
  mounted() {
    this.load(this.url)
  },
  methods: {
    iframeOnload() {
      if (this.isAutoHeight) {
        if (process.env.NODE_ENV === 'production') {
          document.domain = this.utils.getDomainA.domain
          const iframe = this.$refs.iframe
          let doc = iframe.contentDocument || iframe.contentWindow.document
          this.frameHeight = doc.querySelector('body').scrollHeight
          document.domain = window.location.hostname
        }
      } else {
        this.frameHeight = document.body.scrollHeight - this.padding
      }
    },
    load(url) {
      if (url && url.length > 0) {
        // 加载中
        this.loading = true
        axios
          .get(url, {
            headers: { accept: 'text/html, text/plain' }
          })
          .then(response => {
            this.loading = false
            // 处理HTML显示
            this.html = response.data
          })
          .catch(() => {
            this.loading = false
            this.html = '加载失败'
          })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.webview-container {
  width: 100%;
  height: 100%;
}
.content-wrapper {
  border: none;
  width: 100%;
  height: 100%;
  /deep/ p {
    margin-bottom: 0;
  }
}
</style>
