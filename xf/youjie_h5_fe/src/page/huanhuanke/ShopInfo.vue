<template>
  <div class="shopinfo">
    <mt-header class="header" title="设置">
      <header-item slot="left" :isBack="true" @onclick="goBack"></header-item>
    </mt-header>
    <ul>
      <li class="shop-img" @click="uploadHead">
        <span>小店头像</span>
        <input type="file" @change="headChange" ref="headFile" accept="image/jpg,image/png,image/jpeg" />
        <div
          class="user_head"
          :style="{ backgroundImage: `url(${shop_base_infos.shop_icon || headIcon})` }"
          v-if="!headfile.src"
        ></div>
        <div class="user_head" :style="{ backgroundImage: `url(${headfile.src})` }" v-else></div>
        <!-- <img class="user_head" :src="(shop_base_infos.shop_icon || headIcon)" v-if="!headfile.src"></img> -->
        <!-- <img class="user_head" :src="headfile.src" v-else></img> -->
        <img class="go_enter" src="../../assets/image/hh-icon/icon-enter-列表箭头.svg" />
      </li>
      <li @click="setShopName">
        <span>小店昵称</span>
        <label>{{ shop_base_infos.shop_name }}</label>
        <img src="../../assets/image/hh-icon/icon-enter-列表箭头.svg" />
      </li>
      <li @click="setShopWelcome">
        <span>小店迎宾语</span>
        <label>{{ shop_base_infos.shop_desc }}</label>
        <img src="../../assets/image/hh-icon/icon-enter-列表箭头.svg" />
      </li>
      <li @click="selectShopBG">
        <span>小店招牌图片</span>
        <input type="file" @change="bgChange" ref="bgFile" accept="image/jpg,image/png,image/jpeg" />
        <img src="../../assets/image/hh-icon/icon-enter-列表箭头.svg" />
      </li>
      <li @click="manageShopCategory">
        <span>分类列表管理</span>
        <img src="../../assets/image/hh-icon/icon-enter-列表箭头.svg" />
      </li>
    </ul>
  </div>
</template>
<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import HeaderItem from '../../components/common/HeaderItem'
import { getMyShopInfo, setShopBaseInfo } from '../../api/huanhuanke'
import { Toast, Indicator } from 'mint-ui'

export default {
  name: 'shopInfo',
  data() {
    return {
      headIcon: require('../../assets/image/hh-icon/myStore/head@2x.png'),
      headfile: {}, // 存放上传文件的各种信息
      bgFile: {},
      headImgUrl: [],
      bgImgUrl: [],
      fileUpload: ''
    }
  },
  computed: {
    ...mapState({
      shop_base_infos: state => state.mystore.shop_base_infos
    })
  },
  created() {
    Indicator.open()
    getMyShopInfo().then(res => {
      this.saveShopInfo(res)
      Indicator.close()
    })
  },
  watch: {
    headfile: {
      handler() {
        this.saveShopInfo({ ...this.shop_base_infos, shop_icon: this.headfile.src })
      },
      deep: true
    },
    bgFile: {
      handler() {
        this.saveShopInfo({ ...this.shop_base_infos, shop_banner: this.bgFile.src })
      },
      deep: true
    }
  },
  methods: {
    ...mapMutations({
      saveShopInfo: 'setShopBaseInfos'
    }),
    goBack() {
      this.$_goBack()
    },
    uploadHead() {
      this.$refs.headFile.click()
    },
    setShopName() {
      this.$router.push({ name: 'exitShopName' })
    },
    setShopWelcome() {
      this.$router.push({ name: 'exitShopWelcome' })
    },
    selectShopBG() {
      this.$refs.bgFile.click()
    },
    manageShopCategory() {
      this.$router.push({ name: 'manageCategoryList' })
    },
    headChange(e) {
      const curFile = e.target.files[0] // <=> this.$refs.headFile.files[0], 因为e.target即为this.$refs.headFile
      const item = {
        name: curFile.name,
        size: curFile.size,
        file: curFile
      }
      let reader = new FileReader() //异步读取计算机文件信息
      let _this = this
      reader.readAsDataURL(curFile)
      reader.onload = function(e) {
        Indicator.open()
        _this.$set(item, 'src', e.target.result)
        _this.headfile = { ...item }
        // _this.headImgFile = e.target.result
        if (curFile.size / 1024 > 300) {
          _this.utils.getImgToBase64(e.target.result, function(data) {
            let myFile = _this.dataURLtoFile(data, '/ss')
            if (myFile.size / 1024 > 300) {
              Toast('图片太大，无法上传')
              Indicator.close()
            } else {
              setShopBaseInfo({ shop_icon: data }).then(res => {
                Indicator.close()
              })
            }
          })
        } else {
          setShopBaseInfo({ shop_icon: e.target.result }).then(res => {
            Indicator.close()
          })
        }
      }
      // this.$refs.headFile.value = ''
    },
    bgChange(e) {
      const curFile = e.target.files[0] // <=> this.$refs.headFile.files[0], 因为e.target即为this.$refs.headFile
      const item = {
        name: curFile.name,
        size: curFile.size,
        file: curFile
      }
      let reader = new FileReader() //异步读取计算机文件信息
      let _this = this
      reader.readAsDataURL(curFile)
      reader.onload = function(e) {
        Indicator.open()
        _this.$set(item, 'src', e.target.result)
        _this.bgFile = { ...item }
        if (curFile.size / 1024 > 1024) {
          _this.utils.getImgToBase64(e.target.result, function(data) {
            let myFile = _this.dataURLtoFile(data, '/ss')
            if (myFile.size / 1024 > 1024) {
              Toast('图片太大，无法上传')
              Indicator.close()
            } else {
              setShopBaseInfo({ shop_banner: data }).then(res => {
                Indicator.close()
              })
            }
          })
        } else {
          setShopBaseInfo({ shop_banner: e.target.result }).then(res => {
            Indicator.close()
          })
        }
      }
      // this.$refs.bgFile.value = ''
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
    }
  }
}
</script>
<style lang="scss" scoped>
.shopinfo {
  .header {
    @include header;
    border-bottom: 0.5px solid rgba(85, 46, 32, 0.2);
  }
  ul {
    background: #fff;
    padding-left: 15px;
    font-weight: 400;

    li {
      position: relative;
      padding: 17px 15px 18px 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 0.5px solid rgba(85, 46, 32, 0.2);

      span:first-child {
        display: inline-block;
        height: 22px;
        color: #707070;
        line-height: 22px;
      }

      label {
        position: absolute;
        right: 31px;
        width: 186px;
        color: #404040;
        text-align: right;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      img {
        height: 12px;
        // margin-top: 5px;
      }
    }

    li:last-child {
      border: none;
    }

    li.shop-img {
      padding: 21px 15px 21px 0;
      height: 40px;

      div.user_head {
        width: 40px;
        height: 40px;
        position: absolute;
        right: 31px;
        border-radius: 50%;
        background-size: cover;
        background-position: 50%;
        background-repeat: no-repeat;
        background-color: #fff;
        box-sizing: border-box;
      }

      img.go_enter {
        height: 12px;
      }
    }

    input[type='file'] {
      display: none;
    }
  }
}
</style>
