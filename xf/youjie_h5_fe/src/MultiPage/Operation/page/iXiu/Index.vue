<template>
  <div class="ixiu-container">
    <div id="ixiuMain" v-html="activity_content"></div>
    <div class="left-goback" @click="goBack">
      <img src="../../assets/image/ixiu/icon-goback@3x.png" />
    </div>
    <div :class="{ 'right-share': true, show: showShare }" @click="share">
      <img src="../../assets/image/ixiu/icon-share@3x.png" />
    </div>
  </div>
</template>

<script>
import { getEventContent } from '../../api/iXiu'

export default {
  name: 'IxiuIndex',
  data() {
    return {
      id: this.$route.params.id,
      token: this.$route.query.operation_page_token,
      activity_content: '',

      title: '',
      description: '',
      keywords: '',

      showShare: false,
      shareImg: ''
    }
  },
  created() {
    this.$indicator.open()
    getEventContent(this.id, this.token)
      .then(res => {
        // 页面title, description, keywords, html_wap
        const { title, description, keywords, html_wap } = res
        this.title = title
        this.description = description
        this.keywords = keywords
        this.activity_content = html_wap

        this.$nextTick(() => {
          this.pageInit()
        })
      })
      .catch(err => {
        console.log('get ixiu detail Error!!!')
        // this.goBack()
      })
      .finally(() => {
        this.$indicator.close()
      })
  },
  methods: {
    goBack() {
      this.$_goBack()
    },
    share() {
      this.hhApp.share(
        '换换商城 ' + (this.title || this.description),
        this.shareImg,
        'all',
        `staticActWap_${this.id}`,
        this.title,
        encodeURIComponent(location.href),
        this.description
      )
    },
    pageInit() {
      // 为按钮添加跳转功能
      var btns = document.getElementById('ixiuMain').querySelectorAll('a.js-staticAct-btn')
      for (var i = btns.length - 1; i >= 0; i--) {
        var url = btns[i].getAttribute('data-href')
        btns[i].setAttribute('target', '_top')
        btns[i].setAttribute('href', url)
      }

      // 是否显示分享按钮
      if (this.isHHApp) {
        this.showShare = true

        const pageFirstImgEle = document.querySelector('#ixiuMain img')
        const pageFirstImg = pageFirstImgEle && pageFirstImgEle.getAttribute('src')
        this.shareImg = pageFirstImg || 'https://itzstaticupyun.itzcdn.com/ecshop/huanhuanyiwu-icon.png'
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.ixiu-container {
  background-color: #fff;
}
a {
  color: inherit;
  text-decoration: none;
  outline: none;
}
a:active,
a:hover,
a:focus {
  outline: none;
  background-color: transparent;
}
a:active {
  opacity: 0.8;
}
#ixiuMain {
  position: relative;
}
.left-goback,
.right-share {
  width: 31px;
  height: 31px;
  position: fixed;
  top: 9px;
}
.left-goback {
  left: 9px;
}
.right-share {
  display: none;
  right: 9px;
  &.show {
    display: block;
  }
}
.left-goback img,
.right-share img {
  width: 100%;
}
</style>
