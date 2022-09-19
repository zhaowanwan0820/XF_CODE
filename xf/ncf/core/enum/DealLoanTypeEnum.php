<?php

namespace core\enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class DealLoanTypeEnum extends AbstractEnum {

    const TYPE_DQZZ = "ZZ";     //短期周转
    const TYPE_GFDK = "GF";     //购房借款
    const TYPE_ZXDK = "ZX";     //装修借款
    const TYPE_GRXF = "GR";     //个人消费
    const TYPE_HLCB = "HL";     //婚礼筹备
    const TYPE_JYPX = "JY";     //教育培训
    const TYPE_QCXF = "QC";     //汽车消费
    const TYPE_TZCY = "CY";     //投资创业
    const TYPE_YLZC = "YL";     //医疗支出
    const TYPE_QTJK = "QT";     //其他借款
    const TYPE_CD = "CD";       //车贷
    const TYPE_FD = "FD";       //房贷
    const TYPE_JYD = "JD";      //经营贷
    const TYPE_GRD = "GD";      //个贷
    const TYPE_ZCZR = "ZC";     //资产转让
    const TYPE_YSD = "YSD";     //应收贷
    const TYPE_LGL = "LGL";     //利滚利
    const TYPE_DTB = "DTB";     //多投宝
    const TYPE_BXT = "BXT";     //变现通
    const TYPE_XFD = "XFD";     // 首山-消费贷
    const TYPE_XFFQ = "XFFQ";   //消费分期
    const TYPE_GLJH = "GLJH";   //资产管理计划
    const TYPE_ZHANGZHONG = "ZZJR";   //掌众-闪电消费
    const TYPE_XSJK = "XSJK";   //首山-昂励-信石借款-闪信贷
    const TYPE_XD = "XD";   //小贷放贷
    const TYPE_CR = "CR";   // 产融贷
    const TYPE_YTSH = "YTSH";   // 享花-云图生活
    const TYPE_ARTD = "ARTD";// 融艺贷
    const TYPE_WXYJB ="WXYJB";
    const TYPE_XJDGFD ="XJDGFD"; // 大树-现金贷-功夫贷
    const TYPE_XJDCDT ="XJDCDT"; // 首山-现金贷-车贷通
    const TYPE_XJDYYJ ="XJDYYJ"; // 众利-现金贷-优易借-放心花
    const TYPE_DSD = "DSD";  // 供应链店商贷
    const TYPE_ZZJRXS = "ZZJRXS"; // 掌众50天(线上)-闪电消费(线上)
    const TYPE_DFD = 'DFD'; // 东风贷
    const TYPE_HDD = 'HDD'; // 汇达贷
    const TYPE_NDD = 'NDZND'; //农担支农贷-农担贷-国担支农贷
    const TYPE_CRDJYD = 'CRDJYD'; //产融贷经易贷-经易贷
    const TYPE_GRZFFQ = 'GRZFFQ'; //个人租房分期


    // 自动进入上标队列
    const AUTO_STARTY_YES = 1;
    const AUTO_STARTY_NO = 0;

    // 自动放款
    const AUTO_LOAN_YES = 1;
    const AUTO_LOAN_NO = 0;
}