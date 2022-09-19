<template>
  <div class="choose-ognztion">
    <div class="header-container">
      <mt-header class="header" title="选择机构">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
    </div>
    <div class="choose-container">
      <mt-popup class="mint-popup" v-model="showPopup" position="center" v-bind:close-on-click-modal="true">
        <div class="content">
          <div class="mint-msgbox-header">
            <div class="mint-msgbox-title">资产确权说明</div>
          </div>
          <div class="mint-msgbox-content">
            <div class="mint-msgbox-message">
              <p>尊敬的用户您好</p>
              <p>有解公司作为确权平台等系统服务的提供方，旨在帮助合作机构的用户提供投资/出借权益的确权登记信息服务。</p>
              <p>
                确权后，用户原有权益不变，亦不会受到任何影响，原有债权与债务关系不变，用户与原机构的关系不变。借款企业或个人还款后，可直接通过有解查看还款信息，还款资金直接打款至用户在原机构绑定的银行卡。
              </p>
            </div>
          </div>
          <div class="mint-msgbox-btns">
            <button class="mint-msgbox-btn mint-msgbox-confirm" @click="close">我知道了</button>
          </div>
        </div>
      </mt-popup>
      <steps></steps>
      <div class="choose-content">
        <div class="text">
          欢迎来到有解确权平台！
          <br />
          有解得到您的授权，将为您提供投资权益的确权信息服务
        </div>

        <div class="search-wrapper">
          机构名称
          <div class="search-box">
            <input
              type="text"
              v-model="value"
              placeholder="请输入您的理财机构名称"
              @input="changed"
              @blur="blur"
              @focus="onFocus"
            />
            <img src="../../assets/image/change-icon/e2_delete@2x.png" @click="choose('')" v-if="value.length > 0" />
          </div>
        </div>
        <p class="example">例:网信用户可以输入“网”(不含引号)</p>
        <div class="result-list" v-if="value.length > 0 && showArr">
          <div class="list-box">
            <div class="list-item-title">请选择机构</div>
            <div class="list-item" v-for="(item, index) in result" :key="index" @click="choose(item)">{{ item }}</div>
          </div>
        </div>
      </div>
      <div class="btns-wrapper">
        <button @click="nextStep">下一步</button>
      </div>
    </div>
    <serve-icon></serve-icon>
  </div>
</template>

<script>
import Steps from '../../components/common/Steps'
import ServeIcon from '../../components/common/ServeIcon'
import { getAuthStatus } from '../../api/auth'
import { ROUT_ARR } from './static'
export default {
  name: 'AuthChooseOgnztion',
  data() {
    return {
      value: '',
      resultList: ROUT_ARR,
      result: [],
      showArr: false,
      showPopup: true
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
    onFocus() {
      // this.showArr = true
    },
    nextStep() {
      if (!this.value) {
        return this.$toast('请输入机构')
      }
      if (this.resultList.indexOf(this.value) > -1 && this.value == '网信（网信和网信普惠）') {
        getAuthStatus().then(res => {
          if (res.hasDebtAuthentication == 1) {
            this.$toast('您选择得机构暂时不存在，请重新输入！')
          } else {
            this.$router.push({ name: 'AuthCheck', query: { id: this.resultList.indexOf(this.value) + 1 } })
          }
        })
      } else {
        this.$toast('您选择得机构暂时不存在，请重新输入！')
      }
    },

    goBack() {
      this.$_goBack()
    },
    blur() {
      this.value = this.value.replace(/(^\s*)|(\s*$)/g, '')
    },
    changed() {
      this.result = this.resultList.filter(item => {
        return item.match(new RegExp(this.value, 'g'))
      })
      if (this.result.length) {
        this.showArr = true
      }
    },

    choose(item) {
      item = item || ''
      this.value = item
      this.changed()
      this.showArr = false
    },
    close() {
      this.showPopup = false
    }
  }
}
</script>

<style lang="scss" scoped>
.choose-ognztion {
  width: 100%;
  height: 100%;
  background: #fff;
  display: flex;
  flex-direction: column;
}
.header {
  @include thin-border();
}
.choose-container {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  padding: 15px;
  box-sizing: border-box;
  .choose-content {
    overflow: hidden;
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .text {
    // text-align: center;
    margin-top: 20px;
    // @include sc(12px, #fc7f0c);
    @include sc(14px, #404040);
    line-height: 1.5;
  }
  .example {
    @include sc(14px, #ccc);
    padding: 10px 0 0 70px;
  }
  .search-wrapper {
    width: 100%;
    height: 40px;
    box-sizing: border-box;
    margin-top: 22px;
    display: flex;
    @include sc(15px, #666);
    align-items: center;
    .search-box {
      border: 1px solid #dddddd;
      margin: 0 10px;
      flex: 1;
      display: flex;
    }
    input {
      flex: 1;
      height: 100%;
      box-shadow: none;
      box-sizing: border-box;
      border: none;
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
      margin: 8px;
    }
  }
  .result-list {
    flex: 1;
    margin-top: -25px;
    padding: 10px 10px 0 70px;
    .list-box {
      background: #fff;
      box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
      padding: 0 10px;
      max-height: 100%;
      overflow-y: auto;
      .list-item-title,
      .list-item {
        @include sc(15px, #999);
        line-height: 40px;
        @include thin-border(#e8eaed, 0, 0, true);
      }
      .list-item {
        @include sc(15px, #404040);
        font-weight: 600;
      }
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
      background-color: $primaryColor;
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
  .mint-popup {
    width: 80%;
    .mint-msgbox-title {
      font-weight: bold;
    }
    .mint-msgbox-btns {
      padding: 0 20px 20px;
    }
    .mint-msgbox-content {
      padding: 20px;
      border: 0;
    }
    p {
      @include sc(15px, #404040);
      text-align: left;
      margin-bottom: 14px;
    }
  }
}
</style>
