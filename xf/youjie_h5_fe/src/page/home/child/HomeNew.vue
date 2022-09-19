<template>
    <div class="homeNewPage">
        <div class="homeNewWrap">
            <div class="homeNewTitle">
                <module-title :titleContent="titleContent"></module-title>
            </div>
            <div class="homeNewContent">
                <div class="item" v-for="(item,index) in items" :key="'homeNew'+index"  v-stat="{ id: `homeNew_${index}`}">
                    <div class="imgWrap">
                        <img
                            class="imgWrap-icon"
                            v-lazy="{
                                src: item.thumb,
                                error: require('../../../assets/image/change-icon/default_image_02@2x.png'),
                                loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-prod-thumb.png')
                            }"
                        />
                    </div>
                    <div class="textWrap">
                        <p class="tipText">{{item.name}}</p>
                        <price-item 
                            :cash="item.MONEY_SHOW" 
                            :surplus="item.HB_SHOW" 
                            class="product-price-wrapper"
                        ></price-item>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ModuleTitle from "./ModuleTitle"
import PriceItem from '../../../components/common/ListItemPrice'
export default {
    data(){
        return {
            titleContent:{
                title1:"新人专区",
                title2:"新人必抢清单",
                rightMore:true,
                jumpUrl:this.newUserClick,
                otherStyle:"homeNew"
            },
            // items:[1,2,3],
            lazy: {
                src: require('../../../assets/image/hh-icon/b0-home/new-user-bg.png'),
                error: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-new-uswe-bg.png'),
                loading: require('../../../assets/image/hh-icon/y0-lazy-load/lazy-new-uswe-bg.png')
            }
        }
    },
    props: ['items'],
    components:{
        ModuleTitle,
        PriceItem,
    },
    methods: {
        newUserClick() {
        this.Jump('https://m.huanhuanyiwu.com/operation/index.php?url=https%3A%2F%2Fwww.itouzi.com%2Fe%2Fyiuyangou_wap')
        }
    }
}
</script>

<style lang="scss" scoped>
.homeNewPage{
    margin-top: 16px;
    padding: 0 10px;
    box-sizing: border-box;
    .homeNewWrap{
        background:#FC3249;
        border-radius:2px;  
        padding: 16px 8px;
        .homeNewContent{
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            .item{
                height: 148px;
                width: 109px;
                background: url("../../../assets/image/hh-icon/new-home/new-bg.png") no-repeat;
                background-size: 100% 100%;
                .imgWrap{
                    width: 92px;
                    height: 92px;
                    margin: 0 auto;
                    .imgWrap-icon{
                        width: 100%;
                        height: 100%;
                    }
                }
                .textWrap{
                    padding: 0 6px;
                    box-sizing: border-box;
                    margin-top: 11px;
                    .tipText{
                        width: 100%;
                        overflow: hidden;
                        white-space: nowrap; 
                        text-overflow: ellipsis;
                        font-size:12px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:#404040;
                        line-height:12px;
                    }
                    .product-price-wrapper{
                        margin-top: 8px;
                    }
                }
            }
        }
    }
}
</style>