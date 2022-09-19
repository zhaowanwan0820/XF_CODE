<template>
  <div class="f-container">
    <div class="checked" :class="{ active: isCheckedAll }" @click="checkAll"></div>
    <div class="tag">全选</div>
    <div class="tag brown">总计</div>
    <div class="total">
      <p>确权金额：￥{{ utils.formatFloat(hasCheckProjectMoney) }}</p>
    </div>
    <button @click="checkConfirm"><span>确权</span></button>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'
import { confirmTitle } from '../../../api/confirmation.js'
import { getUser } from '../../../api/mine'
import { MAXLENGTH } from '../static.js'
export default {
  name: 'ProjectListFooter',
  props: {
    isLoad: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    ...mapState({
      hasConfirm: state => state.confirmation.hasConfirm,
      platInfo: state => state.auth.platInfo
    }),
    ...mapGetters({
      isCheckedAll: 'isCheckedAll',
      hasCheckedList: 'hasCheckedList',
      hasCheckProjectMoney: 'hasCheckProjectMoney',
      titleListLength: 'titleListLength'
    }),
    hasCheckedIds() {
      let str = ''
      this.hasCheckedList.forEach(item => {
        if (str) {
          str = str + ',' + item.tender_id
        } else {
          str = item.tender_id
        }
      })
      return String(str)
    },
    isFromBonddebt() {
      return ['bondDebt'].indexOf(this.$cookie.get('c_from_b')) != -1
    }
  },
  methods: {
    ...mapActions({
      fetchHasConfirmList: 'fetchHasConfirmList'
    }),
    ...mapMutations({
      setAllChecked: 'setAllChecked',
      setShowConfirmPopup: 'setShowConfirmPopup',
      setHasConfirm: 'setHasConfirm',
      savePlateInfo: 'savePlateInfo'
      // saveWxAuthCheckCount: 'saveWxAuthCheckCount'
    }),
    checkAll() {
      if (!this.isCheckedAll && this.titleListLength > MAXLENGTH) {
        this.$toast('抱歉，暂仅支持选中50条记录')
      }
      this.setAllChecked(!this.isCheckedAll)
    },
    checkConfirm() {
      if (!this.hasCheckedList.length || this.isLoad) return
      this.showMessageBox()
      // 取消授权弹窗
      // this.setShowConfirmPopup(true)
      //每一笔项目和金额都要 同意确权协议 保存授权协议状态代码暂时注释
      // if (this.hasConfirm) {
      //   this.confirm()
      // } else {
      //   this.setShowConfirmPopup(true)
      // }
    },
    showMessageBox() {
      this.$dialog
        .confirm({
          message: '确定要确权所选的资产？'
        })
        .then(() => {
          this.confirm()
        })
        .catch(() => {})
    },
    confirm() {
      this.$loading.open()
      confirmTitle(this.hasCheckedIds)
        .then(res => {
          if (res.code == 0) {
            if (this.isFromBonddebt) {
              this.$toast('确权成功')
              // 更新localStorage 确权项目列表
              this.fetchHasConfirmList()
              setTimeout(() => {
                this.$_goBack()
              }, 1500)
            } else {
              // 更新store中的授权状态
              if (!this.platInfo.confirm_status) {
                this.updatePlateInfo()
              } else {
                this.$router.push({ name: 'confirmatResult', params: { type: this.$route.params.type } })
              }
            }
          } else {
            this.$toast(res.info)
          }
        })
        .finally(() => {
          this.$loading.close()
        })
    },
    updatePlateInfo() {
      getUser().then(res => {
        let plat = res.data.bindPlatform.filter(item => {
          return item.platform_id == this.platInfo.platform_id
        })[0]
        this.savePlateInfo(plat)
        this.$router.push({ name: 'confirmatResult', params: { type: this.$route.params.type } })
      })
    }
  },
  beforeRouteLeave(to, from, next) {
    // 如果去详情页不清除cookie
    if (['confirmationDetail'].indexOf(to.name) == -1) {
      this.$cookie.remove('c_from_b')
    }
    next()
  }
}
</script>
<style lang="less" scoped>
.f-container {
  box-sizing: border-box;
  height: 60px;
  padding-left: 10px;
  padding-right: 15px;
  background-color: #fff;

  display: flex;
  align-items: center;
  .checked {
    width: 22px;
    height: 22px;
    background: url('../../../assets/image/confirmation/icon-checked-off.png') no-repeat;
    background-size: 22px;
    &.active {
      background-image: url('../../../assets/image/confirmation/icon-checked-on.png');
    }
  }
  .tag {
    margin-left: 6px;
    font-size: 12px;
    font-weight: 400;
    line-height: 17px;
    &.brown {
      color: #999;
      margin-left: 15px;
    }
  }
  .total {
    margin-left: 10px;
    flex: 1;
    p {
      display: block;
      font-size: 12px;
      font-weight: 400;
      line-height: 17px;
      white-space: normal;
    }
  }
  button {
    width: 84px;
    height: 30px;
    background: @themeColor;
    border-radius: 2px;
    span {
      font-size: 13px;
      font-weight: 400;
      color: #fff;
    }
  }
}
</style>
