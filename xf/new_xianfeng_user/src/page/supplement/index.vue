<template>
  <page-wrapper class="supplement">
    <common-header title="委托授权资料补充" style="border-bottom: 1px solid #F4F4F4;"></common-header>
    <main class="container">
      <section class="header">
        <header>尊敬的网信普惠用户，您好：</header>
        <p>您已签署的《授权委托书》，因法律诉讼要求，还需要您提供本人身份证正反面照片，作为提起法律诉讼的基础材料。</p>
        <p>请上传您本人“{{ user.realName }}”的“{{ user.idNo }}”的身份证正反面照片。</p>
      </section>
      <form ref="form">
        <div>
          <label for="idcard_face" :class="{watermark:!!idcard_face}">
            <img :src="idcard_face" v-if="idcard_face" alt="上传身份证正面">
            <img src="./../../assets/images/idcard.face.bg.png" v-else alt="上传身份证正面" />
          </label>
          <input accept="image/*" name="idcard_face" type="file" id="idcard_face" @change="faceChangeHandler">
        </div>
        <div>
          <label for="idcard_back" :class="{watermark:!!idcard_back}">
            <img :src="idcard_back" v-if="idcard_back" alt="上传身份证反面">
            <img src="./../../assets/images/idcard.back.bg.png" v-else alt="上传身份证反面" />
          </label>
          <input accept="image/*" name="idcard_back" type="file" id="idcard_back" @change="backChangeHandler">
        </div>
      </form>
      <section class="bottom">
        <header><small>温馨提示：</small></header>
        <p>1.请在光线较好的环境下进行拍摄，确保照片清晰可见。</p>
        <p>2.拍摄时请尽量保持画面整洁，没有其他物体入镜。</p>
        <p> 3.请务必上传本人身份证照片，确保诉讼资料有效。</p>
      </section>
    </main>
    <footer>
      <van-button type="primary" :loading="loading" loading-text="正在校验身份信息，请稍等片刻" @click="submitIdCard">提交</van-button>
    </footer>
  </page-wrapper>
</template>

<script>
import PageWrapper from "../../components/PageWrapper";
import CommonHeader from "@/components/CommonHeader.vue"
import {fetchUploadIdCard, getVerifiedInfoRequest} from "../../api/home";

export default {
  name: "Supplement",
  components: {PageWrapper, CommonHeader},
  data() {
    return {
      idcard_face: '',
      idcard_back: '',
      loading: false,
      user: {},
    }
  },
  created() {
    // console.log(this.formatName('小明'))
    // console.log(this.formatName('小明明'))
    // console.log(this.formatName('小灰灰'))
    // console.log(this.formatName('小灰灰好'))
    getVerifiedInfoRequest({})
      .then(({code, data, info}) => {
        if (!code) {
          this.user = {idNo: this.formatIdCard(data.idno), realName: this.formatName(data.real_name)}
        } else {
          this.$toast({type: 'fail', message: info})
        }
      })
  },
  methods: {
    formatIdCard(idCard) {
      return idCard.replace(/(\d{6})\d{8}([\d|X]{4}|[\d|X]{1})/, '$1***********$2');
    },
    formatName(name) {
      const middle = (name.length - 2) || 1
      // '小明'.replace(new RegExp(/([\s\S]{1})[\s\S]{1}([\s\S])?/),'$1*$2')
      // '小灰灰'.replace(new RegExp(/([\s\S]{1})[\s\S]{1}([\s\S])?/),'$1*$2')
      // '小灰灰好'.replace(new RegExp(/([\s\S]{1})[\s\S]{1}([\s\S])?/),'$1*$2')
      return name.replace(new RegExp('([\\s\\S]{1})[\\s\\S]{'+middle+'}([\\s\\S])?'),'$1*$2')
    },
    fileProcessing(file) {
      return new Promise((resolve, reject) => {
        if (!file) {
          reject()
        } else {
          const reader = new FileReader()
          reader.onload = function () {
            resolve(this.result)
          }
          reader.readAsDataURL(file)
        }
      })
    },
    faceChangeHandler(e) {
      const [file] = e.target.files
      this.fileProcessing(file)
        .then(data => {
          this.idcard_face = data
        })
    },
    backChangeHandler(e) {
      const [file] = e.target.files
      this.fileProcessing(file)
        .then(data => {
          this.idcard_back = data
        })
    },
    submitIdCard() {
      if (!this.idcard_face) {
        this.$toast('请上传身份证正面')
        return false
      } else if (!this.idcard_back) {
        this.$toast('请上传身份证反面')
        return false
      }
      this.loading = true
      fetchUploadIdCard({idcard_face: this.idcard_face, idcard_back: this.idcard_back})
        .then(({code, info}) => {
          if (code) {
            this.$toast({message: info, type: 'fail', duration: 10000})
            return false
          }
          this.$toast({type: 'success', message: '提交成功'})
          this.$router.push({name: 'home'})
        })
        .catch(err => {
          console.error(err)
          this.$toast({type: 'fail', message: '服务器错误'})
        })
        .finally(() => {
          this.loading = false
        })
    }
  }
}
</script>

<style scoped lang="less">
.supplement {
  background: #eeeeee;

  .container, footer {
    padding: 20px;
  }

  .container {
    background: #FFFFFF;

    header {
      color: #333333;
      font-size: 15px;
    }

    p {
      color: #666666;
      font-size: 12px;
      line-height: 22px;
    }

    .header {
      p {
        text-indent: 24px;
      }
    }

    form {
      display: flex;
      margin: 60px 0 35px 0;

      div {
        flex: 1;
        position: relative;
        box-sizing: border-box;


        label {
          text-align: center;
          display: block;
          height: 92px;
          width: 158px;
          margin: 0 auto;
          overflow: hidden;
          position: relative;
          background: #6d93ff;
          &:last-child {
            margin-left: 10px;
          }

          &.watermark::after {
            content: '法律诉讼专用';
            position: absolute;
            top: 50%;
            left: 50%;
            z-index: 100;
            transform: translate3d(-50%, -50%, 0) rotate(-30deg);
            color: rgba(#FFFFFF, .7);
            //background: rgba(0, 0, 0, .5);
            padding: 5px;
            width: 120px;
            letter-spacing: 3px;
            font-size: 14px;
            text-align: center;

          }

          img {
            max-width: 100%;
            height: 100%;
          }
        }

        input {
          position: absolute;
          top: -1000px;
        }
      }
    }
  }

  .bottom {
    header {
      color: #555;
    }

    p {
      color: #bbbbbb;
    }
  }


  footer {
    button {
      background: #ff4000;
      color: #FFFFFF;
      display: block;
      width: 100%;
      height: 40px;
      border-radius: 22px;
      border: none;
    }
  }
}
</style>
