<template>
  <div class="choose-ognztion">
    <div class="choose-container">
      <van-dialog v-model="showPopup" :show-confirm-button="false">
        <div class="content">
          <div class="msgbox-header">
            <div class="msgbox-title">资产确权说明</div>
          </div>
          <div class="msgbox-message">
            <p>
              尊敬的用户您好，有解债转信息平台作为受托方，代为委托方提供确权平台等系统相关服务，旨在帮助委托方的用户提供投资/出借权益的确权登记信息服务。
            </p>
            <br />
            <p>
              确权后，用户原有权益不变，亦不会受到任何影响，原有债权与债务关系不变，用户与原机构的关系不变。借款企业或个人还款后，可直接通过有解债转信息平台查看还款信息
            </p>
          </div>
          <div class="msgbox-btns">
            <button @click="close">我知道了</button>
          </div>
        </div>
      </van-dialog>

      <steps></steps>
      <div class="choose-content">
        <div class="text">
          欢迎来到有解债转信息平台！<br />有解债转信息平台得到您的授权，将为您提供投资权益的确权信息服务
        </div>
        <div class="search-wrapper">
          <input type="text" v-model="value" placeholder="请输入您的机构名称" @input="changed" @focus="onFocus" />
          <img src="../../assets/image/confirmation/e2_delete@2x.png" @click="choose(false)" v-if="value.length > 0" />
        </div>
        <div class="result-list" v-if="value.length > 0 && list.length && showArr">
          <div class="list-item" v-for="item in list" :key="item.id" @click="choose(item)">{{ item.name }}</div>
        </div>
      </div>
      <div class="btns-wrapper">
        <button @click="nextStep">下一步</button>
      </div>
    </div>
    <!-- <serve-icon></serve-icon> -->
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
import Steps from '../../components/common/Steps'
import ServeIcon from '../../components/common/ServeIcon'
import { getPlatformList, choosePlatform } from '../../api/auth'
import { ROUT_ARR } from './static'
export default {
  name: 'AuthChooseOgnztion',
  data() {
    return {
      value: '',
      p_id: null,
      resultList: [],
      showArr: false,
      showPopup: true,
      timer: null
    }
  },
  computed: {
    list() {
      return this.resultList.length
        ? this.resultList.filter(item => {
            return item && item.id
          })
        : []
    }
  },
  components: {
    ServeIcon,
    Steps
  },
  // created() {
  //   MessageBox({
  //     title: '充值成功',
  //     message: '123',
  //     closeOnClickModal: false,
  //     confirmButtonText: '去商城逛逛'
  //   })
  // },
  methods: {
    ...mapMutations({
      saveAuthPlateInfo: 'saveAuthPlateInfo'
    }),
    onFocus() {
      this.showArr = true
    },
    nextStep() {
      if (!this.value) {
        return this.$toast('请输入机构')
      }
      let item = this.resultList.filter(i => {
        return this.value === i.name
      })

      if (item.length) {
        this.p_id = item[0].id
        this.$loading.open()

        choosePlatform(this.p_id)
          .then(() => {
            this.saveAuthPlateInfo(item[0])
            this.$router.push({ name: 'AuthCheck' })
          })
          .finally(() => {
            this.$loading.close()
          })
      } else {
        this.$toast('您选择得机构暂时不存在，请重新输入！')
      }
    },

    goBack() {
      this.$router.push({ name: 'mine' })
    },

    changed() {
      if (this.timer) clearTimeout(this.timer)
      this.value = this.value.replace(/\s+/g, '')
      if (this.value.length <= 0) return

      this.timer = setTimeout(() => {
        getPlatformList(this.value)
          .then(res => {
            this.resultList = res.data
          })
          .finally(() => {})
      }, 1000)
    },

    choose(item) {
      if (item) {
        this.value = item.name
        this.p_id = item.id
        this.showArr = false
      } else {
        this.value = ''
        this.p_id = null
      }
    },
    close() {
      this.showPopup = false
      this.resultList = []
    }
  }
}
</script>

<style lang="less" scoped>
.choose-ognztion {
  width: 100%;
  height: 100%;
  background: #fff;
  display: flex;
  flex-direction: column;
}
.choose-container {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  padding: 25px 18px;
  box-sizing: border-box;
  .choose-content {
    overflow: hidden;
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .text {
    margin-top: 20px;
    font-size: 12px;
    line-height: 20px;
  }
  .search-wrapper {
    width: 100%;
    height: 40px;
    box-sizing: border-box;
    margin-top: 22px;
    border: 1px solid #dddddd;
    display: flex;
    align-items: center;
    input {
      flex: 1;
      height: 100%;
      box-shadow: none;
      border: none;
      box-sizing: border-box;
      padding: 10px 7px;
      font-size: 15px;
      &::-webkit-input-placeholder {
        color: #cccccc;
      }
      &:-moz-placeholder {
        color: #cccccc;
      }
      &::-moz-placeholder {
        color: #cccccc;
      }
      &:-ms-input-placeholder {
        color: #cccccc;
      }
    }
    img {
      width: 18px;
      height: 18px;
      cursor: pointer;
      margin-right: 12px;
    }
  }
  .result-list {
    flex: 1;
    overflow-y: auto;
    margin-top: 15px;
    .list-item {
      padding: 0 7px;
      font-size: 15px;
      line-height: 40px;
      border-bottom: 0.5px dotted rgba(85, 46, 32, 0.2);
    }
  }
  .btns-wrapper {
    width: 100%;
    text-align: center;
    margin-bottom: 10px;
    button {
      width: 327px;
      height: 46px;
      border: 0;
      color: #fff;
      background-color: @themeColor;
      border-radius: 2px;
      font-size: 16px;
      margin-top: 27px;
      outline: none;
    }
    .disabled {
      background: rgba(252, 127, 12, 0.3);
      pointer-events: none;
    }
  }
}
// 弹窗样式
.van-dialog {
  width: 320px;
  top: 50%;
  border-radius: 0;
}
.content {
  box-sizing: border-box;
  width: 320px;
  height: 426px;
  padding: 29px 17px 36px;
  background: #fff;
  border-radius: none;
  .msgbox-title {
    font-size: 16px;
    font-weight: 500;
    line-height: 22px;
    text-align: center;
  }
  .msgbox-message {
    margin-top: 37px;
    p {
      font-size: 15px;
      font-weight: 300;
      line-height: 21px;
    }
  }
  .msgbox-btns {
    margin-top: 68px;
    text-align: center;
    button {
      width: 280px;
      height: 45px;
      background: @themeColor;
      border-radius: 2px;
      font-size: 16px;
      color: #fff;
    }
  }
}
</style>
