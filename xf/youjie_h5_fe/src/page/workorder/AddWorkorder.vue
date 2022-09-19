<!--workOrderaddMessage -->
<template>
  <div class="container">
    <mt-header class="header" title="售后申请">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack()"></header-item>
    </mt-header>
    <div class="workmessage">
      <div class="products">
        <label>
          <img v-if="goodInfo.goodsThumb" :src="ProImg + goodInfo.goodsThumb" alt="产品图片" />
          <img v-else src="../../assets/image/change-icon/default_image_02@2x.png" alt="默认图片" />
          <p>
            <span>{{ goodInfo.goodsName }}</span> <span class="attrs">{{ goodInfo.goodsAttr }}</span>
          </p>
        </label>
      </div>
      <div class="message_box" @click="ShouPup">
        <div class="flex">
          <span style="margin-right: 0.6rem;">服务类型:</span
          ><span class="text-hui" @click="refundPopupFlag = true">{{ message }}</span>
        </div>
      </div>

      <mt-popup v-model="popupVisible" position="bottom" class="refund-pop" style="height: 40%;">
        <div class="refund">
          <div class="title-wrapper">
            <span></span>
            <span class="r-p-title">服务类型</span>
          </div>
          <div class="reason">
            <div class="list" v-for="item in reasonList" :key="item.id">
              <label :for="'refund' + item.id" class="item-title item-left-wrapper">{{ item.name }}</label>
              <input
                type="radio"
                :id="'refund' + item.id"
                class="item-input"
                @change="checkReason(item.id, item.name)"
                name="reason"
              />
              <label class="input-radius" placeholder="v" :for="'refund' + item.id"></label>
            </div>
          </div>
          <div class="refund-comfirm">
            <gk-button class="button" type="primary-secondary" @click="popupVisible = false">关闭</gk-button>
          </div>
        </div>
      </mt-popup>

      <div class="message_box">
        <p>申请说明：</p>
        <textarea
          rows="4"
          cols="30"
          placeholder="请填写反馈内容（必填）"
          maxlength="200"
          v-model="sentMess.description"
          v-on:input="ShowMS"
        ></textarea>
        <p class="red" v-if="mesNone">请填您的诉求</p>
      </div>
      <div class="message_box">
        <p>上传凭证</p>
        <ali-upload ref="aliUpload"></ali-upload>
      </div>
      <div class="btn_box">
        <mt-button type="danger" size="large" class="work_btn" v-on:click="sentMessage" :disabled="mesNone"
          >提交</mt-button
        >
      </div>
      <div class="reminder">
        <p>温馨提示：</p>
        <p>*商品回寄地址将在卖家确认后通过协商记录面进行查询，退货产生的运费由买家承担；</p>
        <p>商家同意退款后，款项原路返回卖家支付的微信钱包、支付宝或{{ utils.storeName }}账户中；</p>
        <p>提交退款申请后，售后服务人员可能与您电话沟通，请保持手机畅通</p>
      </div>
    </div>
  </div>
</template>
<script>
import { Header, Popup, Toast } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import AliUpload from './AliUpload'

export default {
  data() {
    return {
      order_sn: this.$route.params.id ? this.$route.params.id : '',
      introduction: '',
      showToolbar: true,
      message: '请选择',
      goodInfo: { img: '', name: '' },
      popupVisible: false,
      sentMess: { description: '', pics: '', order_type_id: null },
      workOrderId: '',
      reasonList: [{ id: 1, name: '退款' }, { id: 2, name: '退货退款' }],
      name: { a: '', b: '', c: '' },
      reasonName: '',
      ProImg: 'https://shop.itzcdn.com/',
      mesNone: false
    }
  },
  created() {
    this.getInro()
  },
  methods: {
    getInro() {
      this.$api.get('order/' + this.order_sn, null, r => {
        if (r.code == 1) {
          this.goodInfo = r.data
        } else {
          Toast(r.msg)
        }
        // this.goodInfo = r
      })
      this.$api.get('userInfo', null, r => {
        // console.log(r)
        if (r.code == 1) {
          var n3 = r.data.indexOf('$z6d')
          var n4 = r.data.indexOf('!d2f')
          var n5 = r.data.indexOf('?#d5')
          var n6 = r.data.indexOf('!w$d')
          var n7 = r.data.indexOf('$d2g')
          var n8 = r.data.indexOf('#vhf')
          this.name.a = r.data.slice(n3 + 4, n4)
          this.name.b = r.data.slice(n5 + 4, n6)
          this.name.c = r.data.slice(n7 + 4, n8)
        } else {
          Toast(r.msg)
        }
      })
    },
    goBack() {
      this.$_goBack()
    },
    ShowMS() {
      var reg = /^\s*$/g
      if (reg.test(this.sentMess.description) || this.sentMess.description == '') {
        this.mesNone = true
      } else {
        this.mesNone = false
      }
    },
    sentMessage() {
      //提交工单详情
      // console.log(this.message[0]);  //退款
      // console.log(this.sentMess.description);
      this.sentMess.order_sn = this.order_sn //'2016051166250'//
      this.sentMess.goods_id = this.goods_id //'2016051166250'
      // console.log(this.sentMess);
      let url = this.$refs.aliUpload.ImgUrl
      let file = this.$refs.aliUpload.files
      let pics = []
      pics = url.join(',')
      console.log('pics' + pics)
      this.sentMess.serial_number = this.workOrderId
      this.sentMess.pics = pics
      var reg = /^\s*$/g
      if (reg.test(this.sentMess.description) || this.sentMess.description == '') {
        this.mesNone = true
      } else {
        this.mesNone = false
        if (this.sentMess.order_type_id) {
          if (url.length == file.length) {
            this.$api.post('/workOrder', this.sentMess, r => {
              if (r.code == 1) {
                this.workOrderId = r.data.serialNumber
                this.mesNone = true
                // console.log(this.workOrderId);
                this.$refs.aliUpload.ImgUrl = []
                this.$refs.aliUpload.files = []
                this.sentMess = {}
                this.$router.replace({
                  name: 'WorkorderMessage',
                  params: { id: this.workOrderId, satus: r.data.status }
                })
              } else {
                Toast(r.msg)
              }
            })
          } else {
            Toast('图片上传中，请稍后提交')
          }
        } else {
          Toast('请选择申请原因')
        }
      }
      // 跳转到消息列表
    },
    ShouPup() {
      this.popupVisible = true
    },
    onValuesChange(picker, values) {
      this.message = values
      if (values[0] > values[1]) {
        picker.setSlotValue(1, values[0])
      }
    },
    // 选择退款原因
    checkReason(id, name) {
      this.sentMess.order_type_id = id
      this.popupVisible = false
      this.message = name
    }
  },
  components: { AliUpload }
}
</script>
<style lang="scss" scoped>
.workmessage {
  position: fixed;
  width: 100%;
  top: 11.733vw;
  bottom: 0;
  overflow: auto;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-flex-direction: column;
  -ms-flex-direction: column;
  flex-direction: column;
  -webkit-justify-content: space-between;
  -ms-flex-pack: justify;
  justify-content: space-between;
  content: 'viewport-units-buggyfill; top: 11.733vw';
}
.reminder {
  font-size: 12px;
  line-height: 2em;
  padding: 0 20px;
  color: #999;
}
.container {
  font-size: 14px;
  position: relative;
  z-index: 10;
}
.choise_box {
  height: 200px;
  font-size: 16px;
  background: #fff;
}
.flex {
  display: flex;
  justify-content: space-between;
  .text-hui {
    border: 0;
    text-align: right;
    background: url(../../assets/image/change-icon/b0_arrow_all@2x.png) no-repeat right top;
    padding-right: 20px;
    background-size: contain;
  }
}
.picker-toolbar {
  height: 0;
}
.picker-items {
  height: 160px;
}
.header {
  @include header;
  border-bottom: 1px solid #ddd;
}
.products label {
  display: flex;
  justify-content: flex-start;
  padding: 6px;
  background: #fff;
  img {
    width: 80px;
    height: 80px;
  }
  span {
    display: block;
  }
}
.message_box {
  padding: 10px;
  margin-top: 8px;
  background: #fff;
  color: #333;
  .bn {
    border-width: 0px;
  }
  ::placeholder {
    color: #999;
  }
  textarea {
    border: 0;
    width: 100%;
  }
}
.btn_box {
  padding: 30px 20px;
  z-index: 10;
  position: relative;
}
.work_btn {
  background: #772508;
}
.attrs {
  color: #999;
}

.refund-pop {
  .refund {
    padding-left: 15px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .title-wrapper {
    line-height: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 15px 20px 0;
    span {
      font-size: 16px;
      color: #333333;
    }
    img {
      width: 14px;
      height: 14px;
    }
  }
  .content-title {
    @include sc(14px, #888888);
    line-height: 20px;
    margin-top: 10px;
  }
  .reason {
    margin-top: 5px;
    flex: 1;
    overflow-y: auto;
  }
  .list {
    height: 50px;
    display: flex;
    align-items: center;
    @include thin-border();
    padding-right: 15px;
    justify-content: space-between;
    @include sc(14px, #404040);
    .list-item {
      // flex: 1;
      // display: flex;
      // align-items: center;
      // flex-direction: row;
      // justify-content: space-between;
      // @include sc(14px, #404040);
    }
    .item-input {
      display: none;
      &:checked + .input-radius {
        background-image: url('../../assets/image/hh-icon/icon-checkbox-active.png');
      }
      &:disabled + .input-radius {
        visibility: hidden;
      }
    }
    .input-radius {
      @include wh(22px, 22px);
      background-size: 95%;
      background-repeat: no-repeat;
      background-position: center;
      background-image: url('../../assets/image/hh-icon/icon-checkbox.png');
      display: inline-block;
    }
  }
  .content-tips {
    font-weight: 300;
    margin-right: 15px;
    border-radius: 2px;
    background-color: #f9f9f9;
    padding: 10px;
    .tips-title {
      @include sc(12px, #888888);
    }
    .tips-body {
      margin-top: 8px;
      p {
        @include sc(11px, #888888, left center);
        margin-right: -35px;
        line-height: 19px;
      }
    }
  }
  .refund-comfirm {
    padding: 0 25px 15px 10px;
    margin-top: 25px;
    .button {
      width: 100%;
      font-size: 18px;
      @include button($margin: 0, $radius: 2px, $spacing: 2px);
      & + .button {
        margin-top: 25px;
      }
    }
  }
}
.red {
  color: #f00;
  font-size: 10px;
}
</style>
