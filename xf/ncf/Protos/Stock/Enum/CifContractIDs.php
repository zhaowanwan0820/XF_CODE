<?php
namespace NCFGroup\Protos\Stock\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CifContractIDs extends AbstractEnum
{
    const PDCAO = '39';//'14';  //个人数字证书申请责任书//
    const OPENFUND = '62';//证券投资基金投资人权益须知;    旧： 38';//'15';//开放式基金交易协议
    const ZQTZFUNDRISKINTRO = '63';//'47';//'16'; //证券投资基金投资人风险提示
    const USERACCOUNTCONTRACT = '40';//'17';//客户账户开户协议
    const SHZQJYSGRTZZXWZY = '41';//'33';  //上海证券交易所个人投资者行为指引
    const ZQJYWTFXJSS = '46';//'19';//证券交易委托风险揭示书
    const ZQJYWTDLXY = '45';//20';//证券交易委托代理协议
    const WSJYWTXY = '44';//'29';//网上交易委托协议书
    const MOBILEWTXYS = '43';//'30';//手机委托协议书
    const SHZQJYSZDJYXY = '42';//'31'; //上海证券交易所指定交易协议书
    const CHZQSESA = '44';//'29';   //成浩证券电子签名约定书 ---------暂无协议暂时使用网上交易委托协议书
    const CHZQESESA = '44';//'29'; //成浩证券经济业务电子系列协议书 ---------- 暂无协议暂时使用网上交易委托协议书
    const CHZQYXRYFWQRS = '32'; //诚浩证券营销人员服务确认书

   //银行相关协议
    const BOCOMDEPOSITORY = '64';//'52';//'34';//交通银行三方存管协议 --------已有协议
    const CCBDEPOSITORY = '51';//'28';//建设银行三方存管协议//
    const ABCDEPOSITORY = '53';//'26';//农业银行三方存管协议//
    const CIBDEPOSITORY = '56';//'27';//兴业银行三方存管协议//
    const CMBDEPOSITORY = '57';//'23';//招商银行三方存管协议//
    const ICBCDEPOSITORY = '48';//'35';//工商银行三方存管协议 ----------已有协议
    const BOCDEPOSITORY = '59';//'22';// 中国银行三方存管协议//
    const PDFZDEPOSITORY = '55';//'21';//浦东发展银行
    const PAYHDEPOSITORY = '54';//'24';//平安银行
    const GDDEPOSITORY = '49';//'25';//光大银行
    const GFDEPOSITORY = '50';//'37';//广发银行
    const ZXDEPOSITORY = '58';//'36';//中信银行
}
