<template>
  <div class="container">
    <mt-header class="header" title="权益确认">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="projet-list" v-infinite-scroll="getMore" infinite-scroll-distance="10">
        <template v-if="projectList.length || isLoading">
          <project-list-item
            v-for="item in projectList"
            :key="item.id"
            :item="item"
            :status="false"
          ></project-list-item>
        </template>
        <template v-else>
          <div class="null-body">
            <img src="../../assets/image/hh-icon/confirmation/img-null.png" alt />
            <span>暂无可确权的债权</span>
          </div>
        </template>
      </div>
    </div>
    <confirmation-footer-for-x-a-r-h ref="footer" :isLoad="isLoading"></confirmation-footer-for-x-a-r-h>
  </div>
</template>
<script>
import { Header, Indicator } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import ProjectListItem from './child/ProjectListItem'
import ConfirmationFooterForXARH from './child/ConfirmationFooterForXARH'

import { mapState, mapMutations } from 'vuex'
import { getTitleList } from '../../api/confirmation.js'
import $cookie from 'js-cookie'
import { IGNORELIST } from './static.js'
export default {
  name: 'ConfirmationProjectList',
  data() {
    return {
      pas_agree: this.$route.query.pas_agree ? this.$route.query.pas_agree : '',
      isLoading: false,
      params: {
        type: 1,
        debtConfirm: 0,
        page: 1,
        limit: 9999
      },
      id: this.$route.query.id ? this.$route.query.id : '',
      type: this.$route.query.type ? this.$route.query.type : ''
    }
  },
  beforeRouteEnter(to, from, next) {
    // 去详情页不存cookie
    if (IGNORELIST.indexOf(from.name) == -1) {
      $cookie.set('c_from_b', from.name)
    }
    next()
  },
  components: {
    ProjectListItem,
    ConfirmationFooterForXARH
  },
  created() {
    this.getList()
  },
  computed: {
    ...mapState({
      projectInfo: state => state.confirmation.projectInfo,
      projectList: state => state.confirmation.projectList
    })
  },
  methods: {
    ...mapMutations({
      saveProjectInfo: 'saveProjectInfo',
      saveProjectList: 'saveProjectList',
      clearProjectList: 'clearProjectList',
      clearProjectInfo: 'clearProjectInfo'
    }),
    getList() {
      if (this.isLoading) return
      this.isLoading = true
      this.$indicator.open()
      getTitleList(this.params)
        .then(res => {
          let data = {
            confirm: [],
            un_confirm: res
          }
          let arr = res.rows.filter(item => {
            return item.flag == 1
          })
          this.saveProjectList(arr)
          this.saveProjectInfo(data)
        })
        .finally(() => {
          this.$indicator.close()
          this.isLoading = false
        })
    },
    getMore() {
      console.log('没有更多了')
    },
    confirm() {
      this.$refs.footer.confirm()
    },
    goConfirmation() {
      this.$router.push({ name: 'confirmation' })
    },
    goBack() {
      if (this.pas_agree) {
        this.$router.replace({
          name: 'newPlanVote',
          query: {
            pas_agree: this.pas_agree,
            id: this.id,
            type: this.type
          }
        })
        return
      }
      this.$_goBack()
    }
  },
  beforeDestroy() {
    this.clearProjectList()
    this.clearProjectInfo()
  }
}
</script>
<style lang="scss" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.content {
  flex: 1;
  overflow: auto;
}
.projet-list {
  margin-top: 10px;
  height: 100%;
  overflow: auto;
  display: flex;
  flex-direction: column;
}
.null-body {
  flex: 1;
  background-color: #fff;
  display: flex;
  overflow: auto;
  flex-direction: column;
  align-items: center;
  img {
    margin-top: 60px;
    width: 135px;
    height: 135px;
  }
  span {
    margin-top: 14px;
    font-size: 14px;
    color: #666;
    line-height: 20px;
  }
  button {
    margin-top: 40px;
    width: 140px;
    height: 36px;
    background: $primaryColor;
    border-radius: 2px;

    font-size: 14px;
    color: #fff;
  }
}
</style>
