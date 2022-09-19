<template>
    <div 
        :class="[
            'module_title',
            this.titleContent.otherStyle?this.titleContent.otherStyle:'',
        ]"
    >
        <div class="text_left">
            <span class="bigText">{{this.titleContent.title1}}</span>
            <span class="smallText">{{this.titleContent.title2}}</span>
        </div>
        <div class="text_right" v-if="this.titleContent.rightMore" @click="jumpFun()">
            <span class="more">查看更多</span>
        </div>
    </div>
</template>

<script>
export default {
    name:"moduleTitle",
    data(){
        return {
            // title1:"特色推荐专区",
            //     title2:"选你所爱",
            //     rightMore:false,
            //     jumpUrl:""
        }
    },
    props:["titleContent"],
    methods:{
        jumpFun:function(){
            // this.$router.push({ name: this.titleContent.jumpUrl, query: this.titleContent.urlQuery })
            this.titleContent.jumpUrl();
        }
    }
}
</script>

<style lang="scss" scoped>
.module_title{
    width: 100%;
    overflow: hidden;
    .text_left{
        float: left;
        .bigText{
            font-size:16px;
            font-family:PingFangSC-Medium,PingFang SC;
            font-weight:500;
            color:rgba(64,64,64,1);
            line-height:16px;
            margin-right: 8px;
        }
        .smallText{
            font-size:12px;
            font-family:PingFangSC-Regular,PingFang SC;
            font-weight:400;
            color:rgba(153,153,153,1);
            line-height:12px;
        }
    }
    .text_right{
        float: right;
        // display: flex;
        // align-items: baseline;
        vertical-align: bottom;
        .more{
            font-size:12px;
            font-family:PingFangSC-Regular,PingFang SC;
            font-weight:400;
            color:rgba(153,153,153,1);
            margin-right: 12px;
            line-height: 12px;
            position: relative;
            &::after{
                content:"";
                position: absolute;
                width: 10px;
                height: 10px;
                top:50%;
                right: -11px;
                background: url("../../../assets/image/hh-icon/new-home/more.png") no-repeat;
                background-size: 100% auto;
                transform: translate3d(0,-50%,0);
            }
        }
    }
}
.homeNew{
    .text_left .bigText, 
    .text_left .smallText, 
    .text_right .more {
        color: #fff;
    }
    .text_right .more{
        margin-right: 0px;
        &::after{
            background: none;
        }

    }
}
</style>