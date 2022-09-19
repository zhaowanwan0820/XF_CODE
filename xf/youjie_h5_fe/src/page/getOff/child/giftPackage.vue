<template>
    <div class="goods">
        <div class="goods-title">
            <h4>领取礼包</h4>
            <div class="title-r">
                <img :src="isRadio?radio_2:radio_1" alt="" @click="Radio_Click">
                <p>阅读并同意<span @click="Protocol">《用户享受债权之互联网技术服务协议》</span>协议</p>
            </div>
        </div>
        <div class="box" v-for="(items, index) in goodslist" :key="index">
            <div class="box-left">
                <img :src="items.thumb" alt="" @error="imgError(items)">
            </div>
            <div class="box-right">
                <h4>{{items.name}}</h4>
                <p>￥{{items.current_price}} </p>
                <div class="box-btn">
                    <span>剩余{{items.good_stock}}份</span>
                    <div class="btn" :class="{tobtn:!isRadio||!items.good_stock}" @click="Receive(items.id,items.is_agree)">{{items.good_stock==0?'领光了':'立即领取'}}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
  import { Toast } from 'mint-ui';
  import {getGoodsInfo} from '../../../api/getOff'
  export default {
    name: 'giftPackage',
    props:['goodslist'],
    data(){
      return {
        radio_1:require("../../../assets/image/getoff/radio-1.png"),
        radio_2:require("../../../assets/image/getoff/radio-2.png"),
        isRadio:false
      }
    },
    components:{},
    created() {
      console.log(this.datalist)
    },
    computed:{},
    methods:{
      Protocol(){
        this.$router.push({name:'protocol'})
      },
      imgError(item){
        item.thumb = require("../../../assets/image/change-icon/default_image_02@2x.png")
      },
      //复选框
      Radio_Click(){
        this.isRadio = !this.isRadio
      },
      //领取
      Receive(n,t){
        // if(this.isRadio){
        //   getGoodsInfo({product:n}).then(res=>{
        //     this.$router.push({name:`expandProduct`,query:{id:n}})
        //   }).catch(err=>{
        //     setTimeout(()=>{this.$router.push({name:'home'})},1000)
        //   })
        // }else {
        //   Toast('请先阅读并同意《XXXX协议》')
        // }
        this.$emit('func',n)
      }
    }
  }
</script>

<style lang="scss" scoped>
    .goods{
        margin: 18px 0 15px;
        padding: 0 8px 5px;
        background-color: rgba(6, 94, 111, 1);
        .goods-title{
            height: 90px;
            h4{
                font-size:15px;
                font-family:PingFangSC-Medium,PingFang SC;
                font-weight:500;
                color:rgba(255,255,255,1);
                padding: 10px 0 0;
            }
            .title-r{
                margin-top: 10px;
                display: flex;
                /*align-items: center;*/
                img{
                    width: 18px;
                    height: 18px;
                }
                p{
                    font-size:14px;
                    font-family:PingFangSC-Regular,PingFang SC;
                    font-weight:400;
                    color:rgba(255,255,255,0.8);
                    margin-left: 4px;
                    span{
                        font-size:14px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:rgba(255,255,255,1);
                    }
                }
            }
        }
        .box{
            height:100px;
            display: flex;
            align-items: center;
            margin: 0 0 10px;
            padding: 15px 8px;
            background-color: rgba(222, 63, 63, 1);
            .box-left{
                margin: 0 10px 0 0;
                img{
                    width: 100px;
                    height: 100px;
                    display: block;
                    border-radius: 4px;
                }
            }
            .box-right{
                flex: auto;
                height: 100px;
                display: flex;
                flex-direction: column;
                justify-content: space-around;
                h4{
                    font-size:14px;
                    font-family:PingFangSC-Regular,PingFang SC;
                    font-weight:400;
                    color:rgba(255,255,255,1);
                    display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;
                }
                p{
                    height:20px;
                    font-size:20px;
                    font-family:PingFangSC-Medium,PingFang SC;
                    font-weight:500;
                    color:rgba(255,255,255,1);
                    line-height:24px;
                    text-decoration:line-through;
                }
                .box-btn{
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    span{
                        font-size:10px;
                        font-family:PingFangSC-Regular,PingFang SC;
                        font-weight:400;
                        color:rgba(255,255,255,1);
                    }
                    .btn{
                        width:89px;
                        height:26px;
                        line-height: 26px;
                        background:rgba(253,209,107,1);
                        border-radius:13px;
                        text-align: center;
                        font-size: 14px;
                        color: rgba(201, 59, 59, 1);
                    }
                    .tobtn{
                        pointer-events: none;
                        background-color: #b9b9b9;
                        color: #fff;
                    }
                }
            }
        }
    }
</style>