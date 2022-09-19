<template>
    <div class="container">
        <div class="cover" v-if="isCover"></div>
        <div class="getoff-dialog" v-if="isShowBox">
            <div class="getoff-dialog_header">提示</div>
            <div class="getoff-dialog_content">
                <div class="getoff-dialog_box" v-html='content'>
                </div>
            </div>
            <div class="getoff-dialog_btn">
                <div class="getoff-dialog_btn-t" @click="TrueClick">
                    {{btnText[0]}}
                </div>
                <div class="getoff-dialog_btn-x" @click="NoClick">
                    {{btnText[1]}}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
  export default {
    name: 'buttonModule',
    props:{
      'content':{
        type: String,
        required: true
      },
      'btnText':{
        type: Array,
        default () {
          return ['确认','取消'];
        }
      }
    },
    data(){
      return {
        isShowBox:true,
        isCover:true
      }
    },
    methods:{
      TrueClick(){

      },
      NoClick(){

      }
    }
  }
</script>

<style lang="scss" scoped>
    .container{
        .cover {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }
        .getoff-dialog {
            position: fixed;
            top: 45%;
            left: 50%;
            width: 320px;
            overflow: hidden;
            font-size: 16px;
            background-color: rgba(255, 221, 198, 1);
            border-radius: 8px;
            -webkit-transform: translate3d(-50%, -50%, 0);
            transform: translate3d(-50%, -50%, 0);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-transition: 0.3s;
            transition: 0.3s;
            -webkit-transition-property: opacity, -webkit-transform;
            transition-property: opacity, -webkit-transform;
            transition-property: transform, opacity;
            transition-property: transform, opacity, -webkit-transform;
            .getoff-dialog_header {
                padding-top: 17px;
                font-size: 18px;
                font-family: PingFangSC-Medium, PingFang SC;
                font-weight: 500;
                color: rgba(6, 94, 111, 1);
                text-align: center;
            }
            .getoff-dialog_content {
                .message-title {
                    height: 21px;
                    font-size: 15px;
                    font-family: PingFangSC-Regular, PingFang SC;
                    font-weight: 400;
                    color: rgba(6, 94, 111, 1);
                    line-height: 21px;
                    text-align: center;
                    margin-top: 17px;
                }
                .getoff-dialog_box {
                    font-size: 14px;
                    font-family: PingFangSC-Regular, PingFang SC;
                    font-weight: 400;
                    color: rgba(6, 94, 111, 1);
                    padding: 0 20px;
                    h3 {
                        font-size: 14px;
                        padding: 20px 0;
                    }
                    span{
                        display: block;
                        text-align: center;
                        margin-top: 20px;
                    }
                    p{
                        margin: 10px 0 20px;
                        text-align: center;
                    }
                }
            }
            .getoff-dialog_btn {
                display: flex;
                text-align: center;
                background-color: rgba(255, 221, 198, 1);
                border-top: 1px solid rgba(255, 182, 182, 1);
                .getoff-dialog_btn-x {
                    flex: 1;
                    height: 50px;
                    line-height: 48px;
                }
                .getoff-dialog_btn-t {
                    flex: 1;
                    height: 50px;
                    line-height: 48px;
                    background-color: rgba(222, 63, 63, 1);
                    font-size: 16px;
                    color: rgba(255, 255, 255, 1);
                }
            }
        }
    }
</style>