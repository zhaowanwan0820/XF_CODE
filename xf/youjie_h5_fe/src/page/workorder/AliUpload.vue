<template>
  <div class="vue-uploader">
    <div class="file-list">
      <section v-for="(file, index) of files" class="file-item draggable-item" :key="file.name">
        <img :src="file.src" alt="" ondragstart="return false;" />
        <span class="file-remove" @click="remove(index)"></span>
      </section>
      <section v-if="files.length < 3" class="file-item">
        <div @click="add" class="add"><span></span>上传凭证<br />最多3张</div>
      </section>
    </div>
    <section v-if="files.length != 0" class="upload-func">
      <div class="progress-bar">
        <section v-if="uploading" :width="percent * 100 + '%'">{{ percent * 100 + '%' }}</section>
      </div>
    </section>
    <input type="file" @change="fileChange" ref="file" accept="image/jpg,image/png,image/jpeg" />
  </div>
</template>
<script>
const OSS = require('ali-oss')
export default {
  data() {
    return {
      status: 'ready',
      files: [],
      point: {},
      uploading: false,
      percent: 0,
      ImgUrl: [],
      fileUpload: '',
      imgFile: ''
    }
  },
  methods: {
    add() {
      this.$refs.file.click()
    },
    remove(index) {
      this.files.splice(index, 1)
      // console.log(this.files)
      this.ImgUrl.splice(index, 1)
      // console.log(this.ImgUrl)
    },
    fileChange(e) {
      const list = this.$refs.file.files

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
        // console.log(that.files)
        that.imgFile = e.target.result
        that.getImgToBase64(that.imgFile, function(data) {
          var myFile = that.dataURLtoFile(data, '/ss')
          // console.log(myFile)
          that.submit(myFile)
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
    submit(imgfile) {
      //日期格式化
      let that = this
      Date.prototype.Format = function(fmt) {
        //author: meizz
        var o = {
          'M+': this.getMonth() + 1, //月份
          'd+': this.getDate(), //日
          'h+': this.getHours(), //小时
          'm+': this.getMinutes(), //分
          's+': this.getSeconds(), //秒
          'q+': Math.floor((this.getMonth() + 3) / 3), //季度
          S: this.getMilliseconds() //毫秒
        }
        if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + '').substr(4 - RegExp.$1.length))
        for (var k in o)
          if (new RegExp('(' + k + ')').test(fmt))
            fmt = fmt.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k] : ('00' + o[k]).substr(('' + o[k]).length))
        return fmt
      }
      let name = this.$parent.name
      var basePath = new Date().Format('yyyy/MM/dd/')
      // console.log(that.fileUpload)
      const client = new OSS({
        region: 'oss-cn-beijing',
        accessKeyId: name.a,
        accessKeySecret: name.b,
        bucket: name.c,
        secure: true //是否开启HTTPS
      })
      client.multipartUpload('/workorderUser/' + that.guid() + that.fileUpload.name, imgfile).then(function(result) {
        // console.log(result.res.requestUrls)
        let ll = result.res.requestUrls
        that.ImgUrl.push(ll)
        // console.log(that.ImgUrl)
      })
    }
  }
}
</script>
<style>
.vue-uploader .file-list {
  padding: 10px 0px;
}
.vue-uploader .file-list:after {
  content: '';
  display: block;
  clear: both;
  visibility: hidden;
  line-height: 0;
  height: 0;
  font-size: 0;
}
.vue-uploader .file-list .file-item {
  float: left;
  margin-right: 10px;

  position: relative;
  width: 80px;
  text-align: center;
}
.vue-uploader .file-list .file-item img {
  width: 80px;
  height: 80px;
  border: 1px solid #ececec;
}
.vue-uploader .file-list .file-item .file-remove {
  position: absolute;
  right: -6px;
  display: inline;
  top: -6px;
  width: 20px;
  height: 20px;
  font-size: 20px;
  text-align: center;
  cursor: pointer;
  background: url(../../assets/image/workorder-icon/close.png);
  background-size: 100%;
}
.vue-uploader .file-list .file-item .file-name {
  margin: 0;
  height: 40px;
  word-break: break-all;
  font-size: 14px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.vue-uploader .add {
  width: 80px;
  height: 80px;
  float: left;
  text-align: center;
  font-size: 9px;
  cursor: pointer;
  border: 1px dashed #ccc;
  color: #999;
}
.vue-uploader .add span {
  background: url(../../assets/image/workorder-icon/cam.png) no-repeat;
  display: block;
  background-size: 100%;
  line-height: 0;
  width: 21px;
  height: 19px;
  margin: 20px auto 6px;
}
.vue-uploader .upload-func {
  display: flex;
  padding: 0;
  margin: 0px;
  background: #f8f8f8;
  border-top: 1px solid #ececec;
}
.vue-uploader .upload-func .progress-bar {
  flex-grow: 1;
}
.vue-uploader .upload-func .progress-bar section {
  margin-top: 5px;
  background: #00b4aa;
  border-radius: 3px;
  text-align: center;
  color: #fff;
  font-size: 12px;
  transition: all 0.5s ease;
}
.vue-uploader .upload-func .operation-box {
  flex-grow: 0;
  padding-left: 10px;
}
.vue-uploader .upload-func .operation-box button {
  padding: 4px 12px;
  color: #fff;
  background: #007acc;
  border: none;
  border-radius: 2px;
  cursor: pointer;
}
.vue-uploader > input[type='file'] {
  display: none;
}
</style>
