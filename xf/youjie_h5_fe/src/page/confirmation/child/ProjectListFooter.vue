<template>
  <div class="f-container">
    <div class="checked" :class="{ active: isCheckedAll }" @click="checkAll"></div>
    <div class="tag">批量选择</div>
    <div class="tag brown">总计</div>
    <div class="total">
      <p>确权金额：￥{{ utils.formatFloat(hasCheckProjectMoney) }}</p>
    </div>
    <button @click="checkConfirm"><span>确认</span></button>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations } from 'vuex'
import { confirmTitle } from '../../../api/confirmation.js'
import $cookie from 'js-cookie'
import { MAXLENGTH } from '../static.js'
import { MessageBox } from 'mint-ui'
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
      hasConfirm: state => state.confirmation.hasConfirm
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
          str = str + ',' + item.id
        } else {
          str = item.id
        }
      })
      return String(str)
    },
    isFromBonddebt() {
      return ['bondDebt'].indexOf($cookie.get('c_from_b')) != -1
    }
  },
  methods: {
    ...mapMutations({
      setAllChecked: 'setAllChecked',
      setShowConfirmPopup: 'setShowConfirmPopup',
      setHasConfirm: 'setHasConfirm',
      saveWxAuthCheckCount: 'saveWxAuthCheckCount'
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
      MessageBox.confirm('确定要确权所选的资产？', '').then(
        action => {
          this.confirm()
        },
        cancel => {}
      )
    },
    confirm() {
      let data = {
        type: Number(this.$route.params.type),
        ids: this.hasCheckedIds
      }
      this.$indicator.open()
      confirmTitle(data)
        .then(res => {
          if (this.isFromBonddebt) {
            this.$toast('确权成功')
            setTimeout(() => {
              this.$_goBack()
            }, 1500)
          } else {
            this.saveWxAuthCheckCount(this.hasCheckProjectTotal)
            this.$router.push({ name: 'confirmatResult', params: { type: this.$route.params.type } })
          }
        })
        .finally(() => {
          this.$indicator.close()
        })
    }
  },
  beforeRouteLeave(to, from, next) {
    // 如果去详情页不清除cookie
    if (['confirmationDetail'].indexOf(to.name) == -1) {
      $cookie.remove('c_from_b')
    }
    next()
  }
}
</script>
<style lang="scss" scoped>
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
    background: url('../../../assets/image/hh-icon/confirmation/icon-checked-off.png') no-repeat;
    background-size: 22px;
    &.active {
      background-image: url('../../../assets/image/hh-icon/confirmation/icon-checked-on.png');
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
    background: $primaryColor;
    border-radius: 2px;
    span {
      font-size: 13px;
      font-weight: 400;
      color: #fff;
    }
  }
}
</style>
