<template>
  <div id="help">
      <common-header title="帮助" style="border-bottom: 1px solid #F4F4F4;"></common-header>
      <div class="help_content">
          <h1 style="margin-bottom:0">若修改中遇到问题</h1>
          <h1 style="margin-top:0">您还可以通过发送邮件提交修改申请</h1>
          <p>发送“注册手机号码变更申请”至xxbg@ucfgroup.com，邮件格式如下：</p>
          <p>一、邮件标题：《姓名+更换网信注册手机号码》</p>
          <p>二、以正文形式提供：</p>
          <p>1.姓名</p>
          <p>2.身份证号码</p>
          <p>3.原网信平台注册手机号码</p>
          <p>4.需更换的新手机号码（必须为本人实名认证手机号码，如非本人实名认证手机号码，引起的任何账户信息安全隐患，由申请人本人负责）</p>
          <p>三、以附件形式提供：</p>
          <p>1.身份证正、反面照片或扫描件</p>
          <p>2.本人手持身份证正反面照片（身份证关键信息清晰可见；非镜像照片）</p>
      </div>
  </div>
</template>

<script>
import commonHeader from "@/components/CommonHeader.vue";
export default {

}
</script>

<style lang="less" scoped>
.help_content{
    margin-top: 36px;
    font-size: 12px;
    color: #777;
    border: 1px solid rgba(0,0,0,0);

    h1{
        text-align: center;
        margin-top: 30px;
        color: #555;
        font-size: 18px;
        margin-bottom: 40px;
    }
    p{
        padding: 0 30px;
        text-align: justify;
        line-height: 22px;
    }
}
</style>