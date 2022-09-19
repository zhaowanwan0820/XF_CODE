<template>
  <div class="container">
    <mt-header class="header" :title="title">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"> </header-item>
    </mt-header>
    <div class="body-wrapper" v-infinite-scroll="loadMore">
      <!-- 关于商城增加顶部LOGO与版本 -->
      <div class="section-aid-1" v-if="articleData.length && aid == 1">
        <img src="../../assets/image/hh-icon/icon-logo-large.svg" />
        <p>Beta {{ version }}</p>
      </div>
      <link-column
        v-for="(item, index) in articleData"
        :key="index"
        v-on:onclick="getHelpInfo(item.id, item.title, item.url)"
        :title="item.title"
      >
        <!-- , 'section-footer': index == Object.keys(articleData).length - 1 -->
      </link-column>

      <div v-if="aid == 1" class="copyrights">
        <p>©@2019All Rights Reserved <br />换东换西（北京）信息技术有限公司</p>
      </div>

      <!-- 在线客服 -->
      <link-column v-on:onclick="goWebServer" class="section-footer" title="在线客服" v-if="articleData.length && !aid">
      </link-column>

      <!-- 首页增加客服信息 -->
      <div class="help-footer" v-if="articleData.length && !aid">
        <p class="help-title">
          客服电话<a :href="'tel:' + service_tel">{{ service_tel }}</a>
        </p>
        <p class="help-info section-header">周一至周五9:00-18:00</p>
        <!-- <p class="help-info">节假日： 09:00-18:00</p> -->
      </div>
    </div>
  </div>
</template>

<script>
import LinkColumn from '../../components/common/LinkColumn'
import { HeaderItem } from '../../components/common'
import { Header } from 'mint-ui'
import { articleList } from '../../api/article' //文章列表
import { ENUM } from '../../const/enum'
export default {
  name: 'help',
  data() {
    return {
      articleData: [],
      page: 1,
      isMore: 0,
      version: process.env.VUE_APP_APPSERVER_VERSION,
      service_tel: ENUM.SERVICE.MASTER_TEL
    }
  },
  watch: {
    aid(newName, oldName) {
      this.loadFirstPageData()
    }
  },
  computed: {
    aid: function() {
      return parseInt(this.$route.query.aid || 0)
    },
    title: function() {
      return this.$route.query.title || '帮助中心'
    }
  },
  components: {
    LinkColumn
  },
  mounted() {
    this.loadFirstPageData()
  },
  methods: {
    goGuide() {
      this.$router.push('/shopGuide')
    },
    goWebServer() {
      this.$router.push({ name: 'webPage', query: { url: ENUM.SERVICE.MASTER_H5, title: '客服中心' } })
    },
    goBack() {
      this.$_goBack()
    },
    loadFirstPageData() {
      this.articleData = []
      this.getArticleList(true)
    },
    loadMore() {
      if (this.isMore) {
        this.getArticleList(false)
      }
    },
    getArticleList(isFirstPage) {
      this.page = isFirstPage ? 1 : this.page + 1
      articleList(this.aid, this.page, 10).then(res => {
        if (isFirstPage) {
          this.articleData = res.list
        } else {
          this.articleData = [...this.articleData, ...res.list]
        }
        this.isMore = res.paged.more
      })
    },
    getHelpInfo(aid, title, url) {
      if (url) {
        this.$router.push({ name: 'webPage', query: { url: url, title: title } })
      } else {
        this.$router.push({ name: 'help', query: { aid: aid, title: title } })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: stretch;
  height: 100%;
  .header {
    @include header;
    @include thin-border(#f4f4f4, 0, 0);
  }
  .body-wrapper {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: stretch;
    position: relative;
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
  }
}
.section-aid-1 {
  background-color: #fff;
  text-align: center;
  padding: 39px 0 35px;
  margin-top: 10px;
  img {
    width: 92px;
    height: 96px;
  }
  p {
    @include sc(14px, $baseColor);
    height: 20px;
    line-height: 20px;
    margin-top: 14px;
  }
}
.help-footer {
  margin-top: 80px;
  padding-bottom: 20px;
  color: $baseColor;
  text-align: center;
  .help-title {
    font-size: 13px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 8px 15px;
    display: inline-block;
    border-radius: 100px;
    background-color: #ffffff;
  }
  a {
    font-size: 13px;
    line-height: 1;
    margin-left: 8px;
    color: #5caaf8;
  }
  .help-info {
    @include sc(11px, $subbaseColor);
    margin-top: 8px;
  }
}
.copyrights {
  padding: 90px 20px;

  p {
    font-size: 12px;
    color: $baseColor;
    text-align: center;
  }
}
</style>
