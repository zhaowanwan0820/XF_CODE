<script>
import { openLink } from '../deeplink'
export default {
  props: {
    item: {
      type: Object
    }
  },
  data() {
    return {
      photoWidth: 0,
      photoHeight: 0,
      defaultImage: 'this.src="' + require('../../../assets/image/change-icon/default_image_02@2x.png') + '"'
    }
  },
  computed: {
    getTitle: function() {
      return this.getItemByKey('title')
    },
    getSubtitle: function() {
      return this.getItemByKey('subtitle')
    },
    getDesc: function() {
      return this.getItemByKey('label1')
    },
    getPhotoUrl: function() {
      let url = null
      let photo = this.item ? this.item.photo : null
      if (photo) {
        if (photo.large) {
          url = photo.large
        } else if (photo.thumb) {
          url = photo.thumb
        }
      }
      if (url === null) {
        url = require('../../../assets/image/change-icon/default_image_02@2x.png')
      }
      return url
    }
  },
  methods: {
    getItemByKey(key) {
      if (this.item && this.item[key]) {
        return this.item[key]
      }
      return ''
    },
    onClick() {
      let link = this.item.link
      // if (window.WebViewJavascriptBridge && window.WebViewJavascriptBridge.isInApp()) {
      //   wenchaoApp.openLink(link)
      // } else {
      openLink(this.$router, link)
      // }
    }
  }
}
</script>
