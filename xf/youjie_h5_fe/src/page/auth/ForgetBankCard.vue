<template>
  <div class="container">
    <div class="header-container">
      <mt-header class="header" :title="title">
        <header-item slot="left" v-bind:isBack="true" v-on:onclick="goBack"></header-item>
      </mt-header>
    </div>
    <p>请填写您本人的银行卡信息</p>
    <div class="card-info">
      <label>银行</label>
      <input type="text" placeholder="请输入…" v-model.trim="filter.bankIdName" @input="bankInput($event, 'bank')" />
      <div class="box" v-if="bankArr.length > 0 && isBankShow">
        <div class="box-item" style="color: #999;font-weight: 400;font-size: 15px;line-height: 40px;padding:0;">
          请选择银行
        </div>
        <div class="box-item" @click="bankClick(item)" v-for="(item, index) in bankArr" :key="index">
          {{ item.name }}
        </div>
      </div>
    </div>
    <div class="card-info">
      <label>支行</label>
      <input
        type="text"
        placeholder="请输入…"
        v-model.trim="filter.branchIdName"
        @input="bankInput($event, 'branch')"
      />
      <div class="box" v-if="branchArr.length > 0 && isBranchShow">
        <div class="box-item" style="color: #999;font-weight: 400;font-size: 15px;line-height: 40px;padding:0;">
          请选择支行
        </div>
        <div class="box-item" @click="branchClick(item)" v-for="(item, index) in branchArr" :key="index">
          {{ item.name }}
        </div>
      </div>
    </div>
    <div class="card-info">
      <label>持卡人</label>
      <input type="text" placeholder="请输入…" v-model.trim="params.cardholder" />
    </div>
    <div class="card-info">
      <label>卡号</label>
      <input
        type="text"
        placeholder="请输入…"
        v-model.trim="params.bankcard"
        @input="bankcardInput"
        @blur="bankCardBlur"
      />
    </div>
    <div class="card-box" v-if="isShowT != 'false'">
      <h4>身份证照片</h4>
      <div class="img-card">
        <div
          class="img-card-box"
          @click="add"
          :style="{
            backgroundImage: 'url(' + (zm_src ? zm_src : '') + ')',
            backgroundSize: 'cover',
            backgroundRepeat: 'no-repeat'
          }"
        >
          <h2 v-if="!zm_src">+</h2>
          <span v-if="!zm_src">正面</span>
          <img src="../../assets/image/hh-icon/icon-xx.png" alt="" v-else @click.stop="remove('zm_src')" />
          <input type="file" ref="file" @change="fileChange($event, 'file')" accept="image/jpg,image/png,image/jpeg" />
        </div>
        <div
          class="img-card-box"
          @click="adds"
          :style="{
            backgroundImage: 'url(' + (bm_src ? bm_src : '') + ')',
            backgroundSize: 'cover',
            backgroundRepeat: 'no-repeat'
          }"
        >
          <h2 v-if="!bm_src">+</h2>
          <span v-if="!bm_src">背面</span>
          <img src="../../assets/image/hh-icon/icon-xx.png" alt="" v-else @click.stop="remove('bm_src')" />
          <input
            type="file"
            ref="files"
            @change="fileChange($event, 'files')"
            accept="image/jpg,image/png,image/jpeg"
          />
        </div>
      </div>
      <p>1. 请上传账户本人的身份证照片</p>
      <p>2. 拍照是请确保身份证边框完整、图像清晰、光线均匀。</p>
    </div>
    <div class="card-btn" @click="Submit">提交</div>
  </div>
</template>

<script>
const OSS = require('ali-oss')
import { Toast } from 'mint-ui'
import { SubmitPost, BankGet, BranchGet, UpDataPost } from '../../api/bankCard'
import { Indicator } from 'mint-ui'
export default {
  name: 'ForgetBankCard',
  data() {
    return {
      title: '',
      files: [],
      fileUpload: '',
      imgFile: '',
      zm_src: '',
      bm_src: '',
      ImgUrl: [],
      params: {
        bankId: '',
        branchId: '',
        cardholder: '',
        bankcard: '',
        idcardPics: '',
        transactionPassword: ''
      },
      filter: {
        bankIdName: '',
        branchIdName: ''
      },
      bankArr: [],
      branchArr: [],
      name: { a: '', b: '', c: '' },
      isBankShow: false,
      isBranchShow: false,
      timesInter: null,
      timesInters: null,
      isShowT: ''
    }
  },
  created() {
    console.log(this.$route.query)
    this.title = this.$route.meta.title
    this.isShowT = this.$route.query.is
    this.getInro()
  },
  computed: {},
  methods: {
    goBack() {
      this.$_goBack()
    },
    bankcardInput(e) {
      if (e.target.value.length > 18) {
        this.params.bankcard = e.target.value.substr(0, 19)
      }
    },
    bankCardBlur() {
      if (this.params.bankcard.length < 16) {
        Toast('请输入正确银行卡号')
      }
    },
    getInro() {
      this.$api.get('userInfo', null, r => {
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
    getBank(n) {
      BankGet({ name: n }).then(res => {
        // console.log(res.rows)
        if (res.rows.length > 0) {
          this.isBankShow = true
          this.bankArr = res.rows
        } else {
          this.isBankShow = false
          this.bankArr = []
        }
      })
    },
    getBranch(n) {
      BranchGet({ name: n, branchName: this.filter.bankIdName }).then(res => {
        console.log(res)
        if (res.rows && res.rows.length > 0) {
          this.isBranchShow = true
          this.branchArr = res.rows
        } else {
          this.isBranchShow = false
          this.branchArr = []
        }
      })
    },
    bankBlur() {
      this.isBankShow = false
    },
    bankInput(e, n) {
      // console.log(e.target.value)
      let reg = /[\u4e00-\u9fa5]/g
      if (n === 'bank' && e.target.value && reg.test(e.target.value)) {
        this.getBank(e.target.value)
      } else if (n === 'branch' && e.target.value && reg.test(e.target.value)) {
        this.getBranch(e.target.value)
      }
    },
    bankClick(i) {
      this.filter.bankIdName = i.name
      this.params.bankId = i.id
      this.isBankShow = false
    },
    branchClick(i) {
      this.filter.branchIdName = i.name
      this.params.branchId = i.id
      this.isBranchShow = false
    },
    add() {
      this.$refs.file.click()
    },
    adds() {
      this.$refs.files.click()
    },
    remove(n) {
      this[n] = ''
    },
    fileChange(e, n) {
      const list = e.target.files
      const item = {
        name: list[0].name,
        size: list[0].size,
        file: list[0]
      }
      let reader = new FileReader() //异步读取计算机文件信息
      let file = e.target.files[0]
      this.fileUpload = e.target.files[0]
      let name = e.target.files[0].name
      let that = this
      reader.readAsDataURL(file)
      reader.onload = function(e) {
        that.$set(item, 'src', e.target.result)
        that.files.push(item)
        that.imgFile = e.target.result
        that.getImgToBase64(that.imgFile, function(data) {
          var myFile = that.dataURLtoFile(data, '/ss')
          // console.log(myFile)
          that.getUp(myFile, n)
        })
      }

      this.$refs.file.value = ''
    },
    // 使用canvas压缩图片并转换base64
    getImgToBase64(url, callback) {
      //将图片转换为Base64并压缩
      var canvas = document.createElement('canvas'),
        ctx = canvas.getContext('2d'),
        img = new Image()
      img.crossOrigin = 'Anonymous' //表示允许跨域
      img.onload = function() {
        var width = img.width // 图片原始宽度
        var height = img.height // 图片原始长度
        // 宽高比例
        var scale = width / height
        var widthResult = 500
        var heightResult = parseInt(widthResult / scale)
        canvas.height = heightResult // 转换图片像素大小
        canvas.width = widthResult
        // 将图片的（0, 0）坐标到(0 + width , 0+ height)坐标也就是整张图片 画到 canvas（0, 0）到（widthResult，heightResult）也就是整个canvas内
        //drawImage是canvas绘制图案的API
        ctx.drawImage(img, 0, 0, width, height, 0, 0, widthResult, heightResult) //drawImage是canvas绘制图案的API
        var dataURL = canvas.toDataURL('image/png', 0.7) //通过canvas获取图片的base64的URL
        callback(dataURL)
        canvas = null
      }
      img.src = url
    },
    // base64转换文件
    dataURLtoFile(dataurl, filename) {
      //将base64转换为文件
      var arr = dataurl.split(','),
        mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]), //base64解码JS API(还有一个JS API做base64转码：btoa())
        n = bstr.length,
        u8arr = new Uint8Array(n)
      while (n--) {
        u8arr[n] = bstr.charCodeAt(n)
      }
      //   return new File([u8arr], filename, { type: 'image/jpeg' });
      return new Blob([u8arr], {
        type: 'image/jpeg'
      })
    },
    guid() {
      //获取uuid
      return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (Math.random() * 16) | 0,
          v = c == 'x' ? r : (r & 0x3) | 0x8
        return v.toString(16)
      })
    },
    getUp(imgfile, n) {
      let that = this
      const client = new OSS({
        region: 'oss-cn-beijing',
        accessKeyId: that.name.a,
        accessKeySecret: that.name.b,
        bucket: that.name.c,
        secure: true //是否开启HTTPS
      })
      client.multipartUpload('/workorderUser/' + that.guid() + that.fileUpload.name, imgfile).then(function(result) {
        console.log(result.res.requestUrls)
        let ll = result.res.requestUrls
        if (n === 'file') {
          console.log('正面')
          that.zm_src = ll[0].split('?')[0]
        } else if (n === 'files') {
          console.log('北面')
          that.bm_src = ll[0].split('?')[0]
        }
        that.ImgUrl.push(ll)
      })
    },
    Submit() {
      if (this.params.bankcard) {
        let regExp = /^([1-9]{1})(\d{15}|\d{18})$/
        if (!regExp.test(this.params.bankcard)) {
          Toast('请输入正确的银行卡号')
          return false
        }
      } else {
        Toast('请输入银行卡号')
        return false
      }
      if (this.isShowT == 'true') {
        if (!this.zm_src) {
          Toast('请上传身份证正面')
          return false
        }
        if (!this.bm_src) {
          Toast('请上传身份证背面')
          return false
        }
        let str = `${this.zm_src},${this.bm_src}`
        this.params.idcardPics = str
        Indicator.open()
        SubmitPost(this.params)
          .then(res => {
            // console.log(res)
            if (res.status == 200) {
              this.$router.push({ name: 'successbank' })
            } else {
              Toast(`${res.message}`)
            }
          })
          .finally(() => {
            Indicator.close()
          })
      } else {
        Indicator.open()
        UpDataPost(this.params)
          .then(res => {
            // console.log(res)
            if (res.status == 200) {
              Toast('提交成功')
              setTimeout(() => {
                this.$_goBack()
              }, 1000)
            } else {
              Toast(`${res.message}`)
            }
          })
          .finally(() => {
            Indicator.close()
          })
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.container {
  .header {
    @include thin-border();
  }
  p {
    margin-left: 15px;
    height: 43px;
    line-height: 43px;
    font-size: 13px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(102, 102, 102, 1);
  }
  .card-info {
    background-color: #fff;
    padding: 0 15px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(244, 244, 244, 1);
    position: relative;
    label {
      @include sc(15px, #666666);
      margin-right: 15px;
      width: 65px;
      text-align: left;
    }
    input {
      box-shadow: none;
      border: none;
      padding: 0 10px;
      height: 50px;
      font-size: 15px;
      flex: 1;
      text-align: right;
      &::-webkit-input-placeholder {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(204, 204, 204, 1);
      }
      &:-moz-placeholder {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(204, 204, 204, 1);
      }
      &::-moz-placeholder {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(204, 204, 204, 1);
      }
      &:-ms-input-placeholder {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(204, 204, 204, 1);
      }
    }
    .box {
      width: 240px;
      position: absolute;
      right: 15px;
      bottom: -242%;
      background: #fff;
      box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
      padding: 0 10px;
      height: 120px;
      overflow-y: auto;
      text-align: center;
      z-index: 99;
      .box-item {
        @include sc(15px, #999);
        padding: 10px 0;
        @include thin-border(#e8eaed, 0, 0, true);
      }
      .box-item {
        @include sc(15px, #404040);
        font-weight: 600;
      }
    }
  }
  .card-box {
    padding: 0 15px 26px;
    background-color: #fff;
    h4 {
      padding: 16px 0 15px;
      font-size: 15px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(64, 64, 64, 1);
    }
    .img-card {
      display: flex;
      align-items: center;
      justify-content: space-evenly;
      margin-bottom: 20px;
      .img-card-box {
        width: 130px;
        height: 75px;
        border: 1px solid rgba(153, 153, 153, 0.5);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        h2 {
          color: rgba(216, 216, 216, 1);
          font-size: 26px;
        }
        span {
          font-size: 15px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(204, 204, 204, 1);
        }
        input {
          display: none;
        }
        img {
          width: 18px;
          height: 18px;
          position: absolute;
          right: -9px;
          top: -9px;
        }
      }
    }
    p {
      height: 21px;
      line-height: 21px;
      font-size: 12px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(153, 153, 153, 1);
      padding: 0 0 0 14px;
      margin: 0;
    }
  }
  .card-btn {
    width: 327px;
    height: 46px;
    line-height: 46px;
    margin: 40px auto 0;
    background: rgba(252, 127, 12, 1);
    border-radius: 2px;
    font-size: 18px;
    font-family: PingFangSC-Regular, PingFang SC;
    font-weight: 400;
    color: rgba(255, 255, 255, 1);
    text-align: center;
  }
}
</style>
