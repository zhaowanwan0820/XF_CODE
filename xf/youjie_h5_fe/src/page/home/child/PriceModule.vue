<template>
    <div class="priceWrap">
        <div class="credits">
            <span class="ji"></span>
            <span class="total">{{credits}}</span>
        </div>
        <span class="jia">+</span>
        <div class="money">
            <span class="moneyLable">￥</span>
            <span class="moneyNum">{{money}}</span>
        </div>
    </div>
</template>

<script>
    export default {
        props:["credits","money"]
    }
</script>

<style lang="scss" scoped>
$base-color: #B75800;
.priceWrap{
    display: flex;
    align-items: baseline;
    .credits{
        // display: inline-block;
        display: flex;
        align-items: baseline;
        .ji{
            display: inline-block;
            vertical-align: baseline;
            width: 10px;
            height: 10px;
            background: url("../../../assets/image/hh-icon/new-home/lable-ji.png") no-repeat;
            background-size: 100% 100%;
        }
        .total{
            font-size:12px;
            font-family:DINAlternate-Bold,DINAlternate;
            font-weight:bold;
            color:$base-color;
            line-height:12px;
        }
    }
    .jia{
        font-size:8px;
        font-family:PingFangSC-Semibold,PingFang SC;
        font-weight:600;
        color:$base-color;
        line-height:8px;
        display: inline-block;
    }
    .money{
        display: inline-block;
        vertical-align: baseline;
        .moneyLable{
            font-size:8px;
            font-family:PingFangSC-Semibold,PingFang SC;
            font-weight:600;
            color:$base-color;
            line-height:20px;
        }
        .moneyNum{
            font-size:12px;
            font-family:DINAlternate-Bold,DINAlternate;
            font-weight:bold;
            color:$base-color;
            line-height:12px;
        }
    }
}
</style>