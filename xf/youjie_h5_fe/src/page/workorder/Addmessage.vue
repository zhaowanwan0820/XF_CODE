<!--workOrderaddMessage -->
<template>
  <div class="container">
    <mt-header class="header" title="新增反馈">
      <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack()"></header-item>
    </mt-header>
    <div class="message_box">
      <textarea
        rows="4"
        cols="30"
        maxlength="500"
        v-model="sentMess.message"
        placeholder="请填写反馈内容"
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
  </div>
</template>
<script>
import { Header, Popup, Toast } from 'mint-ui'
import { HeaderItem } from '../../components/common'
import AliUpload from './AliUpload'
export default {
  data() {
    return {
      workOrderId: this.$route.params.id ? this.$route.params.id : '',
      sentMess: { message: '', serial_number: '', pics: '' },
      mesNone: false,
      name: { a: '', b: '', c: '' }
    }
  },
  created() {
    this.getInro()
  },
  methods: {
    getInro() {
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
    ShowMS() {
      var reg = /^\s*$/g
      if (reg.test(this.sentMess.message) || this.sentMess.message == '') {
        this.mesNone = true
      } else {
        this.mesNone = false
      }
    },
    goBack() {
      this.$router.replace({ name: 'WorkorderMessage', params: { id: this.workOrderId } })
    },
    sentMessage() {
      //    console.log(this.sentMess)
      let url = this.$refs.aliUpload.ImgUrl
      let file = this.$refs.aliUpload.files

      let pics = []
      pics = url.join(',')

      //    console.log(pics)
      this.sentMess.serial_number = this.workOrderId
      this.sentMess.pics = ''
      this.sentMess.pics = pics
      var reg = /^\s*$/g
      if (reg.test(this.sentMess.message) || this.sentMess.message == '') {
        this.mesNone = true
      } else {
        this.mesNone = false
        if (url.length == file.length) {
          this.$api.post('workOrderMessage', this.sentMess, r => {
            // console.log(r)
            this.list = r
            this.mesNone = true
            this.$refs.aliUpload.ImgUrl = []
            this.$refs.aliUpload.files = []
            this.sentMess = {}
            if (r.code == 0) {
              Toast(r.msg)
            } else {
              this.$router.replace({ name: 'WorkorderMessage', params: { id: this.workOrderId } })
            }
          })
        } else {
          Toast('图片上传中，请稍后提交')
        }
      }
    },
    ShouPup() {
      this.popupVisible = true
    },
    onValuesChange(picker, values) {
      this.message = values
      if (values[0] > values[1]) {
        picker.setSlotValue(1, values[0])
      }
    }
  },
  components: { AliUpload }
}
</script>
<style lang="scss" scoped>
.choise_box {
  height: 200px;
  font-size: 16px;
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
  font-size: 14px;
  overflow: hidden;
}
.header {
  @include header;
}
.message_box {
  padding: 10px;
  background: #fff;
  font-size: 14px;
  color: #333;
  .bn {
    border-width: 0px;
  }
  ::placeholder {
    color: #999;
  }
  p {
    font-size: 14px;
  }
  textarea {
    border: 0;
    width: 100%;
    box-sizing: border-box;
    background: #f4f4f4;
    padding: 10px;
    height: 120px;
  }
}
.btn_box {
  padding: 30px;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
  background-color: #f9f9f9;
  align-items: center;
  button {
    border-radius: 2px;
    background: #772508;
    color: #fff;
  }
}
.red {
  color: #f00;
  font-size: 10px;
}
</style>
