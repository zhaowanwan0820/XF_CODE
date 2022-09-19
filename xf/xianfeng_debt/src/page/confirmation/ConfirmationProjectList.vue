<template>
  <div class="container">
    <div class="content">
      <div class="confirm-tab">
        <div class="confirm-tab-item" :class="{ active: !hasConfirm }" @click="changeTab(false)">
          <span v-if="projectInfo">未确权({{ confirm_count.unconfirm }})</span>
          <div class="line"></div>
        </div>
        <div class="confirm-tab-item" :class="{ active: hasConfirm }" @click="changeTab(true)">
          <span v-if="projectInfo">已确权({{ confirm_count.confirmed }})</span>
          <div class="line"></div>
        </div>
      </div>
      <div class="wait">
        <span v-if="hasConfirm">已确权待还本金总额:</span>
        <span v-else>未确权待还本金总额:</span>
        <span>￥{{ total }}</span>
      </div>
      <template v-if="isLoading || list.length">
        <div class="projet-list">
          <van-list v-model="isLoading" :finished="finished" :finished-text="finishedText" :offset="50" @load="getMore">
            <project-list-item
              v-for="item in list"
              :key="item.tender_id"
              :item="item"
              :status="hasConfirm"
            ></project-list-item>
          </van-list>
        </div>
      </template>
      <template v-else>
        <div class="null-body">
          <img src="../../assets/image/confirmation/img-null.png" alt="" />
          <span>暂无{{ nullTxt }}的债权，快去新增确权机构吧</span>
          <button @click="addNew">新增确权机构</button>
        </div>
      </template>
      <!-- <div class="projet-list" v-infinite-scroll="getMore" infinite-scroll-distance="10">
        <div v-if="list.length">
          <project-list-item v-for="item in list" :key="item.id" :item="item" :status="hasConfirm"></project-list-item>
        </div>
        <div v-else class="null-info"><p>您还没有任何数据哦~</p></div>
      </div> -->
    </div>
    <project-list-footer v-if="!hasConfirm" ref="footer" :isLoad="isLoading"></project-list-footer>
    <!-- <confirm-auth-popup @confirm="confirm"></confirm-auth-popup> -->
  </div>
</template>
<script>
import ProjectListItem from './child/ProjectListItem'
import ProjectListFooter from './child/ProjectListFooter'
// import ConfirmAuthPopup from './child/ConfirmAuthPopup'

import { mapState, mapMutations } from 'vuex'
import { getTitleList, getConfirmInfo } from '../../api/confirmation.js'
import $cookie from 'js-cookie'
export default {
  name: 'ConfirmationProjectList',
  data() {
    return {
      hasConfirm: this.$route.params.status || false, //当前处于 未确权/已确权 tab（可通过地址访问指定tab）
      isLoading: false,
      currentProject: {}, //项目信息
      confirm_count: {}, //列表数量
      params: {
        //请求参数
        un_confirm: {
          debt_confirm: 0,
          page: 1,
          size: 10
        },
        confirm: {
          debt_confirm: 1,
          page: 1,
          size: 10
        }
      },
      finished: true
    }
  },
  beforeRouteEnter(to, from, next) {
    // 去详情页不存cookie
    if (['confirmationDetail'].indexOf(from.name) == -1) {
      $cookie.set('c_from_b', from.name)
    }
    next()
  },
  components: {
    ProjectListItem,
    ProjectListFooter
    // ConfirmAuthPopup
  },
  created() {
    this.getList()
  },
  computed: {
    ...mapState({
      projectInfo: state => state.confirmation.projectInfo,
      projectList: state => state.confirmation.projectList
    }),
    list() {
      return this.projectInfo ? (this.hasConfirm ? this.projectInfo.confirm.list : this.projectList) : []
    },
    un_confirm_return_total() {
      // 未确认代还本金总额
      return this.currentProject ? this.utils.formatFloat(this.currentProject.total - this.currentProject.confirm) : 0
    },
    confirm_return_total() {
      // 已确认代还本金总额
      return this.currentProject ? this.currentProject.confirm : 0
    },
    total() {
      return this.hasConfirm ? this.confirm_return_total : this.un_confirm_return_total
    },
    nullTxt() {
      return this.hasConfirm ? '已确权' : '未确权'
    },
	finishedText() {
      return this.list.length ? '没有更多了' : '暂无数据'
    }
  },
  methods: {
    ...mapMutations({
      saveProjectInfo: 'saveProjectInfo',
      saveProjectList: 'saveProjectList',
      clearProjectList: 'clearProjectList',
      clearProjectInfo: 'clearProjectInfo'
    }),
    getList() {
      let p1 = getTitleList(this.params.un_confirm)
      let p2 = getTitleList(this.params.confirm)
      let p3 = getConfirmInfo()
      if (this.isLoading) return
      this.$loading.open()
      Promise.all([p1, p2, p3])
        .then(res => {
          let data = {
            confirm: res[1].data,
            un_confirm: res[0].data
          }
          this.saveProjectList(res[0].data.list)
          this.saveProjectInfo(data)
          this.confirm_count = res[0].data.confirm_count
          this.fixFinshed()
          this.currentProject = res[2].data.project[0]
        })
        .finally(() => {
          this.isLoading = false
          this.$loading.close()
        })
    },
    getMore() {
      // type 0未确认 1已确认
      // if (this.isLoading) return
      let [total, page] = this.hasConfirm
        ? [this.confirm_count.confirmed, this.params.confirm.page]
        : [this.confirm_count.unconfirm, this.params.un_confirm.page]
      if (page >= total / 10) return
      if (this.hasConfirm) {
        ++this.params.confirm.page
      } else {
        ++this.params.un_confirm.page
      }
      let p = this.hasConfirm ? getTitleList(this.params.confirm) : getTitleList(this.params.un_confirm)

      this.$loading.open()
      p.then(res => {
        if (this.hasConfirm) {
          this.projectInfo.confirm.list = [...this.projectInfo.confirm.list, ...res.data.list]
        } else {
          this.saveProjectList(res.data.list)
          this.projectInfo.un_confirm.list = [...this.projectInfo.un_confirm.list, ...res.data.list]
        }
        this.fixFinshed()
      }).finally(() => {
        this.isLoading = false
        this.$loading.close()
      })
    },
    changeTab(status) {
      // if (this.isLoading) return
      if (this.hasConfirm != status) this.hasConfirm = status
      this.fixFinshed()
    },
    fixFinshed() {
      this.finished = this.hasConfirm
        ? this.params.confirm.page * 10 >= this.confirm_count.confirmed
        : this.params.un_confirm.page * 10 >= this.confirm_count.unconfirm
    },
    confirm() {
      this.$refs.footer.confirm()
    },
    addNew() {
      this.$router.push({ name: 'AuthChooseOgnztion' })
    }
  },
  beforeDestroy() {
    this.clearProjectList()
    this.clearProjectInfo()
  }
}
</script>
<style lang="less" scoped>
.container {
  height: 100%;
  display: flex;
  flex-direction: column;
}
.content {
  flex: 1;
  overflow: auto;
  display: flex;
  flex-direction: column;
}
.projet-list {
  flex: 1;
  overflow: auto;
}
.null-info {
  height: 100%;
  background-color: #fff;
  p {
    text-align: center;
    padding: 10px;
    font-size: 15px;
  }
}
.confirm-tab {
  padding: 0 80px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #fff;
  .confirm-tab-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    span {
      display: block;
      padding: 12px 0 11px 0;
      font-size: 12px;
      font-weight: 400;
      color: #666;
      line-height: 17px;
    }
    .line {
      width: 48px;
      height: 2px;
      background: @themeColor;
      opacity: 0;
    }
    &.active {
      span {
        color: @themeColor;
      }
      .line {
        opacity: 1;
      }
    }
  }
}
.wait {
  height: 49px;
  padding: 0 10px;
  background: rgba(255, 255, 255, 1);
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  span {
    display: block;
    font-size: 15px;
    font-weight: 400;
    line-height: 21px;
    margin-right: 7px;
  }
}
.null-body {
  flex: 1;
  background-color: #fff;
  display: flex;
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
    background: @themeColor;
    border-radius: 2px;

    font-size: 14px;
    color: #fff;
  }
}
</style>
