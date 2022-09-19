<template>
  <div id="voucher-detail">
    <div class="header">
      <div class="wrap">
        <div class="arrow" @click="goBack">
          <img src="../../static/images/back-arrow.png" alt="" />
        </div>
        <div class="title">还款凭证上传</div>
      </div>
    </div>
    <div class="content">
      <div class="total-equity">
        <div class="userInfo">
          {{real_name}} {{sex}}，{{hello}}。 {{notice}}
        </div>
       
      </div>
      
      <van-form>
        <van-field
          v-if="voucherObj.status == 3"
          v-model="voucherObj.reason"
          rows="1"
          autosize
          label="失败原因"
          type="textarea"
          class="redText"
        />
        <!-- <van-field
          v-model="params.number"
          :disabled="canEdit"
          name="合同编号"
          label="合同编号"
          placeholder="请输入合同编号"
        /> -->
        <div class="voucherFileBox" v-if=" voucherObj.picture.length == 0 || isModify">
          <van-uploader
            :deletable="true"
            v-model="voucherFiles"
            :max-count="24"
            upload-icon="plus"
            :multiple="true"
          />
        </div>
        <div class="voucherFileBox_success" v-if=" !isModify && voucherObj.picture.length > 0 ">
          <div v-for="(item, index) in voucherFiles" :key="index">
            <img
              :src="item.url"
              alt=""
              srcset=""
              @click="showPreView(item.url)"
            />
          </div>
        </div>
        <div class="tips-box">
          <p style="font-weight: bold; color: #555; margin-bottom: 3px">
            温馨提示:
          </p>
          <p>1.请在光线较好的环境下拍摄，确保照片内容清晰可见。</p>
          <p>
            2.上传的图片信息需真实准确，若上传虚假信息引起的任何隐患，由您本人负责。
          </p>
          <p>
            3.还款凭证内容应尽可能包含还款时间、打款账号、收款账号等信息以便于我们后续核对。
          </p>
        </div>
      </van-form>
    </div>
    <div style="margin: 17px ;">
      <van-button style="background: rgba(59, 45, 233, 1);"
        round
        block
        type="info"
        v-if="voucherObj.picture.length==0 "
        @click="onSubmit(false)"
      >
        提交
      </van-button>
     
      <van-button style="background: rgba(59, 45, 233, 1);"
        round
        block
        type="info"
        v-if="isModify"
        @click="onSubmit(true)"
      >
        重新提交
      </van-button>
      <van-button style="background: rgba(59, 45, 233, 1);"
        round
        block
        type="info"
        v-if="voucherObj.picture.length>0 && !isModify"
        @click="onEdit"
      >
        修改
      </van-button>
      
    </div>
    <van-overlay :show="show" @click="show = false">
      <div class="wrapper">
        <img :src="previewImg" alt="" srcset="" />
      </div>
    </van-overlay>
    <van-overlay :show="loadingShow" @click="show = false">
      <div class="wrapper">
        <van-loading type="spinner" color="#1989fa">上传中,请勿退出</van-loading>
      </div>
    </van-overlay>
  </div>
</template>

<script>
import {
    mapState,
    mapMutations,
    mapGetters
  } from 'vuex'
import { Toast, Dialog } from "vant";
import { compressImage } from "@/util/compressImage"; // 图片压缩方法
import {
  getBorrowerVoucherInfoRequest,
  borrowerVoucherUploadRequest,
} from "../../api/propertyCompose.js";
export default {
  data() {
    return {
      real_name:'',
      notice:'请上传您的还款凭证。',
      sex:'',
      hello:'',
      isModify:false,
      loadingShow: false,
      show: false,
      previewImg: null,
      params: {
        id: "", //投资记录ID
        platform_id: "", //平台ID
        number: "",
        picture: [],
      },
      reason: "jasd拉都是咖啡连锁店减肥路上的六块腹肌拉卡市领导看风景",
      voucherObj: {
        picture: [],
        status: "",
        reason: "",
      },
      voucherFiles: [
        // {url:"https://img.yzcdn.cn/vant/leaf.jpg"},
        // {url:"http://oss.xfuser.com/deal_load_audit/160307868190137.jpg"},
        // {url:"http://oss.xfuser.com/deal_load_audit/160307868190137.jpg"},
        // {url:"http://oss.xfuser.com/deal_load_audit/160307868190137.jpg"},
        // {url:"http://oss.xfuser.com/deal_load_audit/160307868190137.jpg"},
        // {url:"http://oss.xfuser.com/deal_load_audit/160307868190137.jpg"}
      ],
      canEdit: false,
    };
  },
  created() {
    document.getElementsByTagName("html")[0].style.backgroundColor = "#ebebeb";
  },
  mounted() {
    this.getDealLoadAuditInfo();
  },
  methods: {
     ...mapMutations({
        clearToken: 'clearToken'
      }),

    showPreView(url) {
      this.previewImg = url;
      this.show = true;
    },
    goBack() {
      Dialog.confirm({
          title: "提示",
          message: "确定要放弃上传还款凭证吗？",
        })
          .then(() => {
            this.clearToken();
            localStorage.removeItem('m_assets_garden', {});
            localStorage.removeItem('is_set_pay_password', '');
            localStorage.removeItem('xianfeng', '');
            this.$router.push({
              path: "/borrowerlogin"
            });
          })
          .catch(() => {
            // on cancel
          });
    },
    onEdit() {
      this.isModify=true;
      this.voucherFiles = [];
      this.params.picture = [];
    },
    async onSubmit(covered) {
      let vm = this;
      

      // if(this.voucherObj.picture.length>0){
      //     this.voucherObj.picture.map((v,i)=>{
      //         this.params.picture.push(v)
      //     })
      // }

      console.log(covered,5555);
      if (covered) {
        if (this.voucherFiles.length > 0) {
          this.voucherFiles.map((v, i) => {
            let img = new Image();
            let url = window.URL || window.webkitURL;
            img.src = url.createObjectURL(v.file);
            img.onload = async function () {
              let config = {
                width: img.width, // 压缩后图片的宽
                height: img.height, // 压缩后图片的高
                quality: 0.1, // 压缩后图片的清晰度，取值0-1，值越小，所绘制出的图像越模糊
              };
              await compressImage(v.file, config).then(async (result) => {
                // result 为压缩后二进制文件
                await vm.params.picture.push(result);
                console.log(result, "-----");
              });
            };
          });
        } else {
          Toast("请完善信息");
          return;
        } //申请中，重新提交
        Dialog.confirm({
          title: "提示",
          message: "重新提交将覆盖旧的内容确定重新提交吗？",
        })
          .then(() => {
            this.submitData();
          })
          .catch(() => {
            // on cancel
          });
      } else {
        if (this.voucherFiles.length > 0) {
          this.updateVoucher();
        } else {
          Toast("请完善信息");
          return;
        }
      }
    },
    async updateVoucher() {
      let vm = this;
      // try{
      let imgTotal = this.voucherFiles.length;
      for (let i = 0; i < imgTotal; i++) {
        await addImageProcess(this.voucherFiles[i]).then(async (file) => {
          // console.log(file.width,'-------')
          let config = {
            width: file.width, // 压缩后图片的宽
            height: file.height, // 压缩后图片的高
            quality: 0.1, // 压缩后图片的清晰度，取值0-1，值越小，所绘制出的图像越模糊
          };
          await compressImage(this.voucherFiles[i].file, config).then(
            (result) => {
              // result 为压缩后二进制文件
              vm.params.picture.push(result);
              // console.log(result, "-----");
            }
          );
        });
      }
      vm.submitData();
      // console.log(vm.params.picture)
      function addImageProcess(v) {
        return new Promise((resolve, reject) => {
          let img = new Image();
          let url = window.URL || window.webkitURL;
          img.src = url.createObjectURL(v.file);
          img.onload = () => resolve(img);
          img.onerror = () => reject("加载失败");
        });
      }
      // }catch(err){
      //     console.log(err)
      // }
    },
    submitData() {
      let vm = this;
      vm.loadingShow = true;
      borrowerVoucherUploadRequest(vm.params).then((res) => {
        if (res.code === 0) {
          vm.loadingShow = false;
          Toast("提交成功");
          setTimeout(() => {
            vm.getDealLoadAuditInfo();
            vm.isModify = false;
          }, 2000);
        } else {
          vm.loadingShow = false;
          Toast(res.info);
        }
      });
    },
    
    getDealLoadAuditInfo() {
      getBorrowerVoucherInfoRequest().then((res) => {
        if (res.code === 0) {
          this.voucherObj = res.data;
          this.real_name = res.data.real_name;
          this.sex = res.data.sex;
          this.hello = res.data.hello;
          this.voucherFiles = [];
          if (res.data.picture.length > 0) {
            res.data.picture.map((v, i) => {
              this.voucherFiles.push({ url: v });
            });
            this.notice = '';
          }else{
            this.canEdit = true;
          }
        }
      });
      
    },
  },
};
</script>

<style lang="less" scoped>

.wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;

  img {
    width: 100%;
  }
}
.tips-box {
  margin: 17px 0;
  padding: 0 20px;
  font-size: 13px;
  line-height: 23px;

  p {
    margin: 0;
    color: #bbb;
  }
}
.content {
  margin-top: 36px;
  position: relative;
  background: #fff;
  border-bottom: 1px solid #fff;
  
  .total-equity {
    // display: flex;
    // flex-direction: column;
    // align-items: center;
    // //background: rgba(59, 45, 233, 1);
    // border-radius: 12px;
     margin-bottom: 20px;
     background: rgba(255, 255, 255, 1);
     box-shadow: 0px 1px 8px 0px rgba(53, 116, 250, 0.15);
     border-radius: 4px;
    .userInfo {
    
      font-size: 16px;
      font-family: PingFangSC-Medium, PingFang SC;
      // font-weight: 500;
      margin: 20px 0 10px 20px;
      
      color: rgb(58, 56, 56);;
      line-height: 34px;
    }

  }

  .voucherFileBox,
  .voucherFileBox_success {
    position: relative;
    padding: 17px 20px;
  }
  .voucherFileBox_success {
    // justify-content: space-between;

    div {
      display: inline-block;
      width: 100px;
      height: 90px;
      margin-left: 10px;
      margin-bottom: 17px;

      img {
        width: 100%;
        height: 100%;
      }
    }
    // div:first-child{
    //     margin-left: 0;
    // }
  }
}
.voucherFileBox::after,
.voucherFileBox_success::after {
  position: absolute;
  box-sizing: border-box;
  content: " ";
  pointer-events: none;
  right: 0;
  bottom: 0;
  left: 0;
  border-bottom: 1px solid #ebedf0;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
}
.header {
  z-index: 100;
  height: 36px;
  line-height: 36px;
  border-bottom: 1px solid #f4f4f4;
  background-color: #ffffff;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;

  .wrap {
    padding: 0 9px;
    display: flex;
    flex-direction: row;
    align-items: center;

    .arrow {
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;

      img {
        width: 100%;
        height: 100%;
      }
    }

    .title {
      text-align: center;
      font-size: 18px;
      font-family: PingFangSC-Regular, PingFang SC;
      font-weight: 400;
      color: rgba(51, 51, 51, 1);
      flex: 1;
      padding-right: 30px;
    }
  }
}
</style>