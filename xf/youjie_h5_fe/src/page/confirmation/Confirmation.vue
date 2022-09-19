<template>
  <div class="container">
    <mt-header class="header" title="确权项目">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
    </mt-header>
    <div class="content">
      <div class="steps">
        <steps :step="3" v-if="!isFromProfile"></steps>
      </div>
      <mt-popup class="mint-popup" v-model="showPopup" position="center" v-bind:close-on-click-modal="true">
        <div class="content">
          <div class="mint-msgbox-header">
            <div class="mint-msgbox-title">确权项目说明</div>
          </div>
          <div class="mint-msgbox-content">
            <div class="mint-msgbox-message">
              <p>资产确权支持项目：</p>
              <p>网信普惠：[供应链]、[个人经营贷]、[企业经营贷]</p>
              <p>尊享：[盈益]、[嘉汇]、[盈嘉]</p>
              <p>账户余额：[尊享账户]</p>
              <p>
                其他项目，包括
                <b>[消费贷]、[智多新]、[网信普惠现金账户余额]</b>的确权正在技术开发中，暂未启动确权，请留意机构公告。
              </p>
            </div>
          </div>
          <dir class="rules">
            <input type="checkbox" id="rules" name="rules" v-model="checkFlag" @change="changeRead" />
            <label for="rules" class="input-icon"></label>
            <label for="rules" class="rules-msg">
              以后不再显示
            </label>
          </dir>
          <div class="mint-msgbox-btns">
            <button class="mint-msgbox-btn mint-msgbox-confirm" @click="closePopup">我知道了</button>
          </div>
        </div>
      </mt-popup>
      <div class="c-project">
        <div class="project-title">所有权益确认</div>
        <div class="project-content">
          <!-- 网信普惠 -->
          <div class="project-item" @click="goConfirmProjectList(2)">
            <p class="project-title">网信普惠</p>
            <div class="has-confirm ml" v-if="info.phHasConfirmWaitCapital == 0 && info.phAllConfirmWaitCapital == 0">
              <span>无可确认权益</span>
            </div>
            <div class="has-confirm ml" v-else>
              <span>已确认</span>
              <div class="item-per">
                <label class="fc">￥{{ info.phHasConfirmWaitCapital }}</label>
                <label>/￥{{ info.phAllConfirmWaitCapital }}</label>
              </div>
            </div>
            <img src="../../assets/image/hh-icon/confirmation/icon-tip.png" alt="" />
          </div>
          <!-- 尊享 -->
          <div class="project-item" @click="goConfirmProjectList(1)">
            <p class="project-title">尊享</p>
            <div class="has-confirm ml" v-if="info.zxHasConfirmWaitCapital == 0 && info.zxAllConfirmWaitCapital == 0">
              <span>无可确认权益</span>
            </div>
            <div class="has-confirm ml" v-else>
              <span>已确认</span>
              <div class="item-per">
                <label class="fc">￥{{ info.zxHasConfirmWaitCapital }}</label>
                <label>/￥{{ info.zxAllConfirmWaitCapital }}</label>
              </div>
            </div>
            <img src="../../assets/image/hh-icon/confirmation/icon-tip.png" alt="" />
          </div>
        </div>
      </div>
      <!-- 余额确权 -->
      <div class="c-project">
        <div class="project-title">账户权益确认</div>
        <div class="project-content">
          <!-- 尊享 -->
          <div class="project-item" @click="goCashList(1)">
            <p class="project-title">尊享</p>
            <div class="has-confirm ml" v-if="info.zxHasConfirmAccount == 0 && info.zxAllConfirmAccount == 0">
              <span>无可确认权益</span>
            </div>
            <div class="has-confirm ml mt" v-else>
              <span>已确认</span>
              <div class="item-per">
                <label class="fc">￥{{ info.zxHasConfirmAccount }}</label>
                <label>/￥{{ info.zxAllConfirmAccount }}</label>
              </div>
            </div>
            <img src="../../assets/image/hh-icon/confirmation/icon-tip.png" alt="" />
          </div>
        </div>
      </div>
      <template v-if="!isNullTitle">
        <p class="gray-desc bottom">
          权益确认支持部分项目，其他项目的确权正在技术开发中。请留意机构公告。
        </p>
        <div class="latter-btn">
          <button @click="goProfile">稍后确认</button>
        </div>
      </template>
      <template v-else>
        <div class="null-btn">
          <button @click="goProfile">无可确认权益,进入有解</button>
        </div>
      </template>
    </div>
    <serve-icon right="12px" bottom="17px"></serve-icon>
  </div>
</template>
<script>
import { Header } from 'mint-ui'
import Steps from '../../components/common/Steps'
import ServeIcon from '../../components/common/ServeIcon'
import { HeaderItem } from '../../components/common'
import { mapMutations } from 'vuex'
import { getConfirmInfo, changeReads } from '../../api/confirmation.js'
import $cookie from 'js-cookie'
export default {
  name: 'Confirmation',
  data() {
    return {
      from: '',
      info: {},
      showPopup: false,
      checkFlag: false,
      is_debt: this.$cookie.get('is_debt')
    }
  },
  components: {
    Steps,
    ServeIcon
  },
  created() {
    this.getInfo()
  },
  beforeRouteEnter(to, from, next) {
    $cookie.set('fromConfirmation', from['name'])
    next()
  },
  computed: {
    isFromProfile() {
      return ['confirmationList', 'profile', 'confirmationCashList'].indexOf($cookie.get('fromConfirmation')) != -1
    },
    isNullTitle() {
      return (
        Number(this.info.phHasConfirmWaitCapital) == Number(this.info.phAllConfirmWaitCapital) &&
        Number(this.info.zxHasConfirmWaitCapital) == Number(this.info.zxAllConfirmWaitCapital) &&
        Number(this.info.zxHasConfirmAccount) == Number(this.info.zxAllConfirmAccount)
      )
    }
  },
  methods: {
    ...mapMutations({
      saveCurrentProject: 'saveCurrentProject'
    }),
    getInfo() {
      this.$indicator.open()
      getConfirmInfo()
        .then(res => {
          this.info = res
          // console.log(res)
          if (res.readFlag != 1) {
            this.showPopup = true
          }
        })
        .finally(() => {
          this.$indicator.close()
        })
    },
    goConfirmProjectList(type) {
      console.log(this.info)
      if (type == 2) {
        if (
          !this.info.phHasConfirmWaitCapita &&
          this.info.phHasConfirmWaitCapital == 0 &&
          this.info.phAllConfirmWaitCapital == 0
        ) {
          console.log(2)
          return
        }
      } else {
        if (
          !this.info.zxHasConfirmWaitCapital &&
          this.info.zxHasConfirmWaitCapital == 0 &&
          this.info.zxAllConfirmWaitCapital == 0
        ) {
          console.log(1)
          return
        }
      }
      this.$router.push({ name: 'confirmationList', params: { type } })
    },
    goCashList(type) {
      if (type == 1) {
        if (
          !this.info.zxHasConfirmAccount &&
          this.info.zxHasConfirmAccount == 0 &&
          this.info.zxAllConfirmAccount == 0
        ) {
          return
        }
      }
      this.$router.push({ name: 'confirmationCashList', params: { type } })
    },
    goBack() {
      if (this.isFromProfile) {
        this.$_goBack()
      } else {
        this.$router.push({ name: 'home' })
      }
    },
    closePopup() {
      this.showPopup = false
      if (this.checkFlag) {
        changeReads('1')
      }
    },
    goProfile() {
      if (this.is_debt) {
        window.location.href = `/debt/#/debtMarket`
      } else {
        this.$router.push('profile')
      }
    },
    changeRead(val) {
      this.checkFlag = val
    }
  },
  beforeDestroy() {
    $cookie.remove('fromConfirmation')
    $cookie.remove('is_debt')
  },
  beforeRouteLeave(to, from, next) {
    if (to.name === 'AuthCheckResult') {
      this.$router.push({ name: 'home' })
    }
    next()
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
.steps {
  margin: 22px 0 5px;
}
.confirm-title {
  margin: 15px 7px 0 12px;
  font-size: 15px;
  line-height: 17px;
  text-align: center;
}
.gray-desc {
  margin: 15px 7px 0 12px;
  font-size: 12px;
  line-height: 17px;
  color: #999;
}
.null-btn,
.latter-btn {
  margin-top: 63px;
  text-align: center;
  button {
    font-size: 14px;
    font-weight: 400;
    color: #3574fa;
    background: none;
  }
}
.null-btn {
  button {
    width: 280px;
    height: 45px;
    background: $primaryColor;
    border-radius: 6px;
    font-size: 16px;
    color: #fff;
  }
}
.c-project {
  padding: 16px 12px 4px;
  .project-title {
    font-size: 14px;
    font-weight: 500;
    color: #404040;
    line-height: 20px;
  }
  .project-content {
    margin-top: 11px;
    background: rgba(255, 255, 255, 1);
    border-radius: 6px;
    .project-item {
      padding: 12px 15px;
      position: relative;
      &:first-child {
        @include thin-border(#e5e5e5);
      }
      img {
        width: 7px;
        height: 12px;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
      }
      .item-line {
        margin-top: 6px;
        display: flex;
        align-items: center;
        .has-confirm {
          margin-left: 30px;
        }
      }
      .has-confirm {
        display: flex;
        align-items: center;
        span {
          @include sc(10px, #666);
          font-weight: 400;
          line-height: 14px;
        }
        .item-per {
          margin-left: 5px;
          font-size: 0;
          label {
            font-size: 15px;
            font-weight: 500;
            color: #999;
            line-height: 21px;
            &.fc {
              color: $primaryColor;
            }
          }
        }
        &.ml {
          margin-top: 4px;
        }
        &.mt {
          margin-top: 10px;
        }
        &.ml,
        &.mt {
          label:last-child {
            margin-left: 3px;
          }
        }
      }
    }
  }
}
.mint-popup {
  width: 80%;
  overflow: auto;
  p {
    @include sc(14px, #333);
    text-align: left;
    margin-bottom: 10px;
  }
  .rules {
    @include sc(14px, #333);
    text-align: center;
    padding: 0;
  }
}
</style>
