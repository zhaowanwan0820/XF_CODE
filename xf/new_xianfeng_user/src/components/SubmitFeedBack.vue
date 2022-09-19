<template>
  <div>
    <div class="item" style="border: none;">
      <div class="left">
        <div class="icon-box">
          <img src="../static/images/yj-icon.png" alt />
        </div>
        <div class="item-name">意见反馈</div>
      </div>
    </div>
    <!-- 反馈提交框-->
    <div class="feedback-box">
      <div class="wrap">
        <div class="label">问题描述</div>
        <div class="textarea-box">
          <textarea v-model="params.content" placeholder="告诉我们您遇到的问题，我们会第一时间处理" />
        </div>
        <div class="feedback-btn">
          <span @click="feedback">提交反馈</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import {
    feedbackRequest,
  } from "../api/home.js";
  import {
    Toast,
  } from "vant";
  export default{
    data(){
      return{
        params: {
          content: "",
        },
      }
    },
    methods:{
      //提交反馈
      feedback() {
        if (this.params.content == "") {
          Toast("请输入意见反馈内容");
          return
        }
        feedbackRequest(this.params).then(
          res => {
            if (res.code === 0) {
              Toast("提交成功");
              this.params.content = "";
            } else {
              Toast(res.info);
            }
          },
          error => {
            Toast(error.info);
          }
        );
      },
    }
  }
</script>

<style lang="less" scoped>
  .item {
    display: flex;
    flex-direction: row;
    height: 55px;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px dashed rgba(198, 198, 198, 0.3);

    .left {
      display: flex;
      flex-direction: row;
      align-items: center;

      .icon-box {
        width: 20px;
        height: 20px;

        img {
          width: 100%;
          height: 100%;
        }
      }

      .item-name {
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        margin-left: 10px;
        margin-right: 5px;
      }

      .new-news {
        width: 33px;
        height: 14px;

        img {
          width: 100%;
          height: 100%;
        }
      }
    }

    .arrow-box {
      width: 30px;
      height: 30px;
      display: flex;
      justify-content: center;
      align-items: center;

      img {
        width: 6px;
        height: 11px;
      }
    }
  }
  .feedback-box {
    padding-bottom: 20px;
    .wrap {
      margin: 0 10px 17px 24px;
      background: rgba(249, 249, 249, 1);
      border-radius: 1px;
      min-height: 118px;
      .label {
        font-size: 13px;
        font-family: PingFangSC-Medium, PingFang SC;
        font-weight: 500;
        color: rgba(64, 64, 64, 1);
        padding: 10px 0 4px 0;
        margin: 0 12px;
        height: 17px;
        line-height: 17px;
        border-bottom: 1px dashed #dbdbdb;
      }
      .textarea-box {
        margin: 9px 12px 0 12px;
        textarea {
          border: none;
          width: 100%;
          min-height: 45px;
          background: rgba(249, 249, 249, 1);
          outline: none;
          font-size: 13px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: #000;
          resize: none;
        }
      }
      .feedback-btn {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        margin-right: 5px;
        margin-top: 4px;
        span {
          text-align: center;
          width: 57px;
          height: 24px;
          line-height: 24px;
          border-radius: 2px;
          border: 1px solid rgba(59, 45, 233, 1);
          font-size: 10px;
          font-family: PingFangSC-Medium, PingFang SC;
          font-weight: 500;
          color: rgba(77, 88, 249, 1);
          margin-bottom: 4px;
        }
      }
    }
  }
</style>
