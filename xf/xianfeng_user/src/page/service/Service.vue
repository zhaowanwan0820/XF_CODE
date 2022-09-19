<template>
  <div>
    <common-header :title="title" style="border-bottom: 1px solid #F4F4F4;"></common-header>
    <div class="content">
      <div class="wrap">
        <div class="emial-box">
          <div class="icon"><img src="../../static/images/email-img.png" alt="邮箱"></div>
          <div class="middle">
            <div class="name">客服电子邮箱</div>
            <div class="info">{{emailUrl}}</div>
          </div>
          <div class="copy-btn" :data-clipboard-text='emailUrl' @click="copy">复制邮箱</div>
        </div>
        <div>
          <submit-feed-back></submit-feed-back>
        </div>
      </div>
    </div>
    <div class="common-question">
      <div class="wrap">
        <div class="top">
          <div class="icon"><img src="../../static/images/wenti-icon.png" alt=""></div>
          <div class="title">常见问题</div>
        </div>
        <van-collapse v-model="activeNames">
          <van-collapse-item title="如需更换注册手机号码" name="1">
            <p>发送“注册手机号码变更申请”至xxbg@ucfgroup.com，邮件格式如下：</p>
            <p>一、邮件标题：《姓名+更换网信注册手机号码》</p>
            <p>二、以正文形式提供：</p>
            <p>1）姓名</p>
            <p>2）身份证号码</p>
            <p>3）原网信平台注册手机号码</p>
            <p>4）需更换的新手机号码（必须为本人实名认证手机号码，如非本人实名认证手机号码，引起的任何账户信息安全隐患，由申请人本人负责。）</p>
            <p>三、以附件形式提供：</p>
            <p>1）身份证正、反面照片或扫描件</p>
            <p>2）本人手持身份证正反面照片（身份证关键信息清晰可见；非镜像照片）</p>
          </van-collapse-item>
          <van-collapse-item title="如需变更原账户绑定的银行卡" name="2">
            <p>发送“原账户绑定银行卡变更申请”至xxbg@ucfgroup.com，邮件格式如下：</p>
            <p>一、邮件标题：《姓名+更换原网信账户绑定的银行卡》</p>
            <p>二、以正文形式提供：</p>
            <p>1）姓名</p>
            <p>2）身份证号码</p>
            <p>3）注册手机号码</p>
            <p>4）原绑定银行卡号；</p>
            <p>5）需更换的新银行卡号（银行卡户主需是本人，如非本人银行账户引起的任何账户安全隐患，由申请人本人负责。）</p>
            <p>三、以附件形式提供：</p>
            <p>1）身份证正、反面照片或扫描件</p>
            <p>2）本人手持身份证正反面照片（身份证关键信息清晰可见；非镜像照片）</p>
            <p>3）本人手持新银行卡照片（银行卡关键信息清晰可见；非镜像照片）</p>
          </van-collapse-item>
        </van-collapse>
      </div>
    </div>
  </div>
</template>

<script>
  import commonHeader from '@/components/CommonHeader'
  import submitFeedBack from '@/components/SubmitFeedBack'
  import Clipboard from 'clipboard';
  import {
    Toast,
    Collapse,
    CollapseItem
  } from 'vant';
  export default {
    components: {
      commonHeader,
      submitFeedBack
    },
    data() {
      return {
        title: '联系客服',
        emailUrl: 'xxbg@ucfgroup.com',
        activeNames: ['1'],
      }
    },
    mounted() {
      document.querySelector('body').setAttribute('style', 'background-color:#F4F4F4')
    },
    beforeDestroy() {
      document.querySelector('body').removeAttribute('style')
    },
    methods: {
      copy() {
        var clipboard = new Clipboard('.copy-btn')
        clipboard.on('success', e => {
          Toast.success('复制成功')
          // 释放内存
          clipboard.destroy()
        })
        clipboard.on('error', e => {
          // 不支持复制
          Toast.success('复制失败')
          // 释放内存
          clipboard.destroy()
        })
      }
    }
  }
</script>

<style lang="less" scoped>
  .content {
    background: #FFF;

    .wrap {
      padding: 51px 15px 0 15px;

      .emial-box {
        display: flex;
        height: 85px;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        font-size: 14px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(255, 255, 255, 1);
        background: url(../../static/images/kefu-bg.png) center no-repeat;
        background-size: 100% 100%;

        .icon {
          margin-left: 22px;

          img {
            width: 46px;
            height: 47px;
          }
        }

        .middle {
          flex: 1;
          display: felx;
          flex-direction: column;
          align-items: center;
          margin: 0 13px;

          .info {
            margin: 5px 0 6px 0;
          }
        }

        .copy-btn {
          margin-right: 20px;
          text-align: center;
          width: 76px;
          height: 27px;
          line-height: 27px;
          background: rgba(57, 52, 223, 1);
          box-shadow: 0px 0px 9px 0px rgba(26, 23, 137, 1);
          border-radius: 13px;
        }
      }
    }
  }

  .common-question {
    margin-top: 10px;
    background: #fff;
    padding-bottom: 130px;

    .wrap {
      margin: 0 15px;

      .top {
        display: flex;
        flex-direction: row;
        font-size: 15px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
        padding: 20px 0;
        border-bottom: 1px dashed rgba(85, 46, 32, 0.2);

        img {
          width: 20px;
          height: 20px;
          margin-right: 10px;
        }
      }
    }
  }

  /deep/.van-collapse {
    .van-collapse-item {
      border-bottom: 1px dashed rgba(85, 46, 32, 0.2);
    }

    .van-collapse-item {
      .van-cell {
        padding: 15px 0;
        border: none;
        background: none;
        font-size: 14px;
        font-family: PingFangSC-Regular, PingFang SC;
        font-weight: 400;
        color: rgba(64, 64, 64, 1);
      }

      .van-cell:not(:last-child):after {
        display: none;
      }

      .van-collapse-item__wrapper {
        background: rgba(246, 246, 246, 1);
        .van-collapse-item__content {
          background: none;
          padding: 13px;
          font-size: 12px;
          font-family: PingFangSC-Regular, PingFang SC;
          font-weight: 400;
          color: rgba(102, 102, 102, 1);
        }
      }
      .van-collapse-item__content:before {
        content: "";
        width: 0px;
        height: 0px;
        border-left: 10px solid transparent;
        border-right: 10px solid transparent;
        border-bottom: 10px solid #f6f6f6;
        position: absolute;
        top: 46px;
        left:20px;
        z-index: 200;
      }
      .van-cell__right-icon {
        color: #cdcdcd;
      }

    }
  }
  /deep/ [class*=van-hairline]:after{
    border: none!important;
  }






</style>
