<template>
  <div class="f-container">
    <div class="checked" :class="{ active: isCheckedAllForX }" @click="checkAll"></div>
    <div class="tag">批量选择</div>
    <div class="tag brown">总计</div>
    <div class="total">
      <p>确权金额：￥{{ utils.formatFloat(hasCheckProjectMoney) }}</p>
    </div>
    <button @click="checkConfirm">
      <span>确认</span>
    </button>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations } from 'vuex'
import { confirmTitle } from '../../../api/confirmation.js'
import $cookie from 'js-cookie'
import { MAXLENGTH, IGNORELIST } from '../static.js'
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
    ...mapGetters({
      isCheckedAllForX: 'isCheckedAllForX',
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
    }
  },
  methods: {
    ...mapMutations({
      setAllCheckedForX: 'setAllCheckedForX',
      saveWxAuthCheckCount: 'saveWxAuthCheckCount'
    }),
    checkAll() {
      this.setAllCheckedForX(!this.isCheckedAllForX)
    },
    checkConfirm() {
      if (!this.hasCheckedList.length || this.isLoad) return
      if (!this.isCheckedAllForX) {
        this.$toast('该兑付方案所持有的项目需全部确权，您还有未勾选的项目，请您全部勾选确权')
        return
      }
      this.confirm()
      // this.showMessageBox()
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
        type: 1,
        ids: this.hasCheckedIds
      }
      this.$indicator.open()
      confirmTitle(data)
        .then(res => {
          this.$toast('确权成功')
          this.saveWxAuthCheckCount(this.hasCheckProjectTotal)
          setTimeout(() => {
            this.$router.replace({
              name: this.$cookie.get('c_from_b'),
              query: {
                pas_agree: this.$route.query.pas_agree,
                id: this.$route.query.id,
                type: this.$route.query.type,
                pwd: '1'
              }
            })
          }, 1500)
        })
        .finally(() => {
          this.$indicator.close()
        })
    }
  },
  beforeRouteLeave(to, from, next) {
    // 如果去详情页不清除cookie
    if (IGNORELIST.indexOf(to.name) == -1) {
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
