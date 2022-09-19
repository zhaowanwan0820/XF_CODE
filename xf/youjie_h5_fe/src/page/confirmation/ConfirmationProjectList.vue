<template>
  <div class="container">
    <mt-header class="header" :title="title">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="confirm-tab">
        <div class="confirm-tab-item" :class="{ active: !hasConfirm }" @click="changeTab(false)">
          <span v-if="projectInfo && u_total">未确认({{ u_total }})</span>
          <span v-if="projectInfo && !u_total">未确认</span>
          <div class="line"></div>
        </div>
        <div class="confirm-tab-item" :class="{ active: hasConfirm }" @click="changeTab(true)">
          <span v-if="projectInfo">已确认({{ c_total }})</span>
          <div class="line"></div>
        </div>
      </div>
      <div class="wait">
        <span v-if="hasConfirm">已确认所有权益总额:</span>
        <span v-if="!hasConfirm && total">未确认所有权益总额:</span>
        <span v-if="hasConfirm || total">￥{{ total }}</span>
      </div>
      <div class="projet-list" v-infinite-scroll="getMore" infinite-scroll-distance="10">
        <template v-if="list.length || isLoading">
          <project-list-item v-for="item in list" :key="item.id" :item="item" :status="hasConfirm"></project-list-item>
        </template>
        <template v-else>
          <div class="null-body">
            <img src="../../assets/image/hh-icon/confirmation/img-null.png" alt="" />
            <span v-if="hasConfirm">暂无已确认的债权，快去做资产确权吧</span>
            <span v-else>无可确权资产</span>
            <button v-show="hasConfirm" @click="goConfirmation">开始资产确权</button>
          </div>
        </template>
      </div>
    </div>
    <project-list-footer v-if="!hasConfirm" ref="footer" :isLoad="isLoading"></project-list-footer>
    <confirm-auth-popup @confirm="confirm"></confirm-auth-popup>
  </div>
</template>
<script>
import { Header, Indicator } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import ProjectListItem from './child/ProjectListItem'
import ProjectListFooter from './child/ProjectListFooter'
import ConfirmAuthPopup from './child/ConfirmAuthPopup'

import { mapState, mapMutations } from 'vuex'
import { getTitleList } from '../../api/confirmation.js'
import $cookie from 'js-cookie'
export default {
  name: 'ConfirmationProjectList',
  data() {
    return {
      type: this.$route.params.type,
      hasConfirm: this.$route.params.status || false,
      title: this.$route.params.type == 1 ? '尊享' : '网信普惠',
      isLoading: false,
      u_list_total: 0, //
      c_list_total: 0,
      u_total: 0,
      c_total: 0,
      params: {
        un_confirm: {
          type: this.$route.params.type,
          debtConfirm: 0,
          page: 1,
          limit: 10
        },
        confirm: {
          type: this.$route.params.type,
          debtConfirm: 1,
          page: 1,
          limit: 10
        }
      }
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
    ProjectListFooter,
    ConfirmAuthPopup
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
      return this.projectInfo ? (this.hasConfirm ? this.projectInfo.confirm.rows : this.projectList) : []
    },
    un_confirm_return_total() {
      // 未确认代还本息总额
      return this.projectInfo && this.projectInfo.un_confirm.totalAmount ? this.projectInfo.un_confirm.totalAmount : 0
    },
    confirm_return_total() {
      // 未确认代还本息总额
      return this.projectInfo && this.projectInfo.confirm.totalAmount ? this.projectInfo.confirm.totalAmount : 0
    },
    total() {
      return this.hasConfirm ? this.confirm_return_total : this.un_confirm_return_total
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
      if (this.isLoading) return
      this.isLoading = true
      this.$indicator.open()
      Promise.all([p1, p2])
        .then(res => {
          let data = {
            confirm: res[1],
            un_confirm: res[0]
          }
          this.u_total = res[0].total
          this.c_total = res[1].total
          this.saveProjectList(res[0].rows)
          this.saveProjectInfo(data)
        })
        .finally(() => {
          this.$indicator.close()
          this.isLoading = false
        })
    },
    getMore() {
      // type 0未确认 1已确认
      if (this.isLoading) return
      let [total, page] = this.hasConfirm
        ? [this.c_total, this.params.confirm.page]
        : [this.u_total, this.params.un_confirm.page]
      if (page >= total / 10) return
      if (this.hasConfirm) {
        ++this.params.confirm.page
      } else {
        ++this.params.un_confirm.page
      }
      let p = this.hasConfirm ? getTitleList(this.params.confirm) : getTitleList(this.params.un_confirm)
      this.isLoading = true
      this.$indicator.open()
      p.then(res => {
        if (this.hasConfirm) {
          this.projectInfo.confirm.rows = [...this.projectInfo.confirm.rows, ...res.rows]
        } else {
          this.saveProjectList(res.rows)
          this.projectInfo.un_confirm.rows = [...this.projectInfo.un_confirm.rows, ...res.rows]
        }
      }).finally(() => {
        this.$indicator.close()
        this.isLoading = false
      })
    },
    changeTab(status) {
      if (this.hasConfirm != status) this.hasConfirm = status
    },
    confirm() {
      this.$refs.footer.confirm()
    },
    goConfirmation() {
      this.$router.push({ name: 'confirmation' })
    },
    goBack() {
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
  display: flex;
  flex-direction: column;
}
.projet-list {
  flex: 1;
  overflow: auto;
  margin-bottom: 10px;
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
      background: rgba(183, 88, 0, 1);
      opacity: 0;
    }
    &.active {
      span {
        color: $markColor;
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
  height: 100%;
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
