<template>
  <div id="voucher-detail">
    <div class="header">
      <div class="wrap">
        <div class="arrow" @click="goBack">
          <img src="../../static/images/back-arrow.png" alt="" />
        </div>
        <div class="title">合同及交易凭证</div>
      </div>
    </div>
    <div class="content">
      <van-form>
        <van-field
          v-if="voucherObj.status == 3  && !isModify"
          v-model="voucherObj.reason"
          rows="1"
          autosize
          label="失败原因"
          type="textarea"
          class="redText"
        />
        <van-field
          v-model="params.number"
          :disabled="canEdit"
          name="合同编号"
          label="合同编号"
          placeholder="请输入合同编号"
        />
        <div class="voucherFileBox" v-if="voucherObj.status==0 || (isModify && (voucherObj.status==1 || voucherObj.status==3)) ">
          <van-uploader
            :deletable="!canEdit"
            :disabled="canEdit"
            v-model="voucherFiles"
            :max-count="6"
            upload-icon="plus"
            :multiple="true"
          />
        </div>
        <div class="voucherFileBox_success" v-if="(voucherObj.status==2||voucherObj.status==1||voucherObj.status==3) && !isModify">
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
          <p>1.请在光线较好的环境下拍摄，确保照片清晰可见。</p>
          <p>
            2.图片内容需包涵：打款凭证，合同首页，认购协议，风险认知书，投资者资格确认表，风险承受能力评估问卷（个人/机构）。
          </p>
          <p>
            3.上传的图片信息需真实准确，若上传虚假信息引起的任何账户信息安全隐患，由您本人负责。
          </p>
          <p>4.一般审核周期为10个工作日。</p>
        </div>
      </van-form>
    </div>
    <div style="margin: 17px">
      <van-button
        round
        block
        type="info"
        v-if="voucherObj.status == 0"
        @click="onSubmit(false)"
      >
        提交
      </van-button>
      <van-button
        round
        block
        type="info"
        v-if="voucherObj.status == 1 && canEdit == true"
        @click="onEdit"
      >
        修改
      </van-button>
      <van-button
        round
        block
        type="info"
        v-if="voucherObj.status == 1 && canEdit == false"
        @click="onSubmit(true)"
      >
        重新提交
      </van-button>
      <van-button
        round
        block
        type="info"
        v-if="voucherObj.status == 3 && canEdit == true"
        @click="onEdit"
      >
        修改
      </van-button>
      <van-button
        round
        block
        type="info"
        v-if="voucherObj.status == 3 && canEdit == false"
        @click="onSubmit(false)"
      >
        重新提交
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
import { Toast, Dialog } from "vant";
import { compressImage } from "@/util/compressImage"; // 图片压缩方法
import {
  getDealLoadAuditInfoRequest,
  getDealLoadAuditUploadRequest,
} from "../../api/propertyCompose.js";
export default {
  data() {
    return {
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
    this.init();
    this.getDealLoadAuditInfo();
  },
  methods: {
    showPreView(url) {
      this.previewImg = url;
      this.show = true;
    },
    goBack() {
      if (this.voucherObj.status != 2 && !this.canEdit) {
        Dialog.confirm({
          title: "提示",
          message: "确定要放弃上传合同及交易凭证吗？",
        })
          .then(() => {
            // on confirm
            window.history.go(-1);
          })
          .catch(() => {
            // on cancel
          });
      } else {
        window.history.go(-1);
      }
    },
    onEdit() {
      this.isModify=true;
      this.canEdit = false;
      this.voucherFiles = [];
      this.params.picture = [];
    },
    async onSubmit(covered) {
      let vm = this;
      if (!this.params.number) {
        Toast("请完善信息");
        return;
      }

      // if(this.voucherObj.picture.length>0){
      //     this.voucherObj.picture.map((v,i)=>{
      //         this.params.picture.push(v)
      //     })
      // }

      console.log(covered);
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
      getDealLoadAuditUploadRequest(vm.params).then((res) => {
        if (res.code === 0) {
          vm.loadingShow = false;
          Toast("提交成功");
          setTimeout(() => {
            vm.$router.push({
              path: "/lendingDetails",
              query: {
                id: vm.params.id,
                platform_id: vm.params.platform_id,
              },
            });
          }, 2000);
        } else {
          vm.loadingShow = false;
          Toast(res.info);
        }
      });
    },
    init() {
      this.params.id = this.$route.query.id;
      this.params.platform_id = this.$route.query.platform_id;
    },
    getDealLoadAuditInfo() {
      getDealLoadAuditInfoRequest(this.params).then((res) => {
        if (res.code === 0) {
          this.voucherObj = res.data;

          this.params.number = res.data.number;
          this.voucherFiles = [];
          if (res.data.picture.length > 0) {
            res.data.picture.map((v, i) => {
              this.voucherFiles.push({ url: v });
            });
          }
          this.canEdit =
            this.voucherObj.status == 1 ||
            this.voucherObj.status == 2 ||
            this.voucherObj.status == 3;
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