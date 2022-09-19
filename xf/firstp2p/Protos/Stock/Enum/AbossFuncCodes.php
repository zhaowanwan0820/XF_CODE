<?php
namespace NCFGroup\Protos\Stock\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class AbossFuncCodes extends AbstractEnum
{
    const BANKCODEPARAM = "103010";
    const STOCK_INFO = "104101";
    const STOCK_VERI_PWD = "190101";
    const ACCOUNT_INFO  = "303002";
    const STOCK_BUY_SALE = "204501";
    const USER_POSITION = "304101";
    const ENTRUST_TODAY = "304103";
    const ENTRUST_HISTORY = "404202";
    const STOCKHOLDER_GDH = "304001";
    const STOCKHOLDER_INFO = "304002";
    const BANK_TO_STOCK = "203111";
    const STOCK_TO_BANK = "203113";
    const CANCEL_ENTRUST = "204502";
    const TRANSACTION_LOG_TODAY = "304109";
    const TRANSACTION_LOG_TODAY_BATCH = "304110";
    const TRANSACTION_LOG_HISTORY = "404201";
    const MONEY_LOG_TODAY = "303010";
    const BANK_STOCK_MONEY_LOG_TODAY = "303111";
    const BANK_STOCK_MONEY_LOG_HISTORY = "403204";
    const MONEY_LOG_HISTORY = "403201";
    const USER_BANK_INFO = "303103";
    const ENTRUST_CNT = "204503";

    const CERT_QUERY = "603209";
    const CERT_APPLY = "603200";
    const CERT_DOWNLOAD = "603201";
    const CERT_UPDATE = "603203";
    const CERT_VERI = "603208";

    const CURRENT_MARKET_DAY = "104060";//查询当天所属交易日
    const NEXT_MARKET_DAY = "104061";//查询下一交易日
    const PREV_MARKET_DAY = "104062";//查询上一交易日

    const USER_BASE_INFO = "302001";//客户基本信息查询
    const DATA_DICTIONARY = "101004";//系统数据字典查询
    const USER_RATION_EQUITY_INFO = "304230";
    const ENTRUSTED_INFO = "304116";//查询指定委托号的委托详细信息

    protected static $names = array(/*{{{*/
        self::ENTRUST_CNT => "可委托数量计算",
        self::USER_BANK_INFO => "银证转帐对应关系查询",
        self::MONEY_LOG_HISTORY => "资金变动明细查询",
        self::MONEY_LOG_TODAY => "资金明细查询",
        self::BANK_STOCK_MONEY_LOG_TODAY => "证券发起银证业务查询",
        self::BANK_STOCK_MONEY_LOG_HISTORY => "三方存管历史申请流水查询",
        self::TRANSACTION_LOG_HISTORY => "证券历史成交查询",
        self::TRANSACTION_LOG_TODAY => "客户实时成交查询",
        self::TRANSACTION_LOG_TODAY_BATCH => '客户分笔成交查询',
        self::CANCEL_ENTRUST => "股票委托撤单",
        self::CERT_VERI => '验证签名',
        self::CERT_UPDATE => '个人证书更新',
        self::CERT_DOWNLOAD => '个人证书下载',
        self::BANKCODEPARAM => "银行代码参数查询",
        self::STOCK_INFO => "证券行情查询",
        self::STOCK_VERI_PWD => "交易密码检验",
        self::ACCOUNT_INFO => "资金账户查询",
        self::STOCK_BUY_SALE => "股票买卖委托",
        self::USER_POSITION => '客户持仓查询',
        self::ENTRUST_TODAY => '客户当日委托查询',
        self::ENTRUST_HISTORY => '证券历史委托查询',
        self::STOCKHOLDER_GDH => '客户股东号查询',
        self::STOCKHOLDER_INFO => '股东资料查询',
        self::BANK_TO_STOCK => "银行转证券",
        self::STOCK_TO_BANK => "证券转银行",

        self::CERT_QUERY => "根据客户信息查询个人证书",
        self::CERT_APPLY => '个人证书申请',

        self::CURRENT_MARKET_DAY => '查询当天所属交易日',
        self::NEXT_MARKET_DAY => '查询下一交易日',
        self::PREV_MARKET_DAY => '查询上一交易日',
        self::USER_BASE_INFO => '客户基本信息查询',
        self::DATA_DICTIONARY => '系统数据字典查询',
        self::USER_RATION_EQUITY_INFO => '用户配售权益信息查询',
        self::ENTRUSTED_INFO => '查询指定委托号的委托详细信息',
    );/*}}}*/

    protected static $details = array(/*{{{*/
        self::TRANSACTION_LOG_TODAY_BATCH => array(
            'FID_KHH',
            //'FID_EN_WTH',
            'FID_ROWCOUNT',
            'FID_BROWINDEX',
            'FID_FLAG',
            'FID_SORTTYPE',
        ),
        self::STOCK_VERI_PWD => array(
            'FID_KHH',
            'FID_JYMM',
            'FID_JMLX',
        ),
        self::ENTRUST_CNT => array(
            'FID_KHH',
            'FID_GDH',
            'FID_JYS',
            'FID_ZQDM',
            'FID_WTLB',
            'FID_WTJG',
            'FID_DDLX',
        ),
        self::USER_BANK_INFO => array(
            'FID_KHH',
        ),
        self::MONEY_LOG_HISTORY => array(
            'FID_KHH',
            'FID_KSRQ',
            'FID_JSRQ',
            'FID_BROWINDEX',
            'FID_ROWCOUNT',
            'FID_SORTTYPE',
        ),
        self::MONEY_LOG_TODAY => array(
            'FID_KHH',
            'FID_SORTTYPE',
        ),
        self::BANK_STOCK_MONEY_LOG_TODAY => array(
            'FID_KHH',
        ),
        self::BANK_STOCK_MONEY_LOG_HISTORY => array(
            'FID_KHH',
            'FID_KSRQ',
            'FID_JSRQ',
            'FID_BROWINDEX',
            'FID_ROWCOUNT',
            'FID_SORTTYPE',
        ),
        self::TRANSACTION_LOG_HISTORY => array(
            'FID_KHH',
            'FID_KSRQ',
            'FID_JSRQ',
            'FID_BROWINDEX',
            'FID_ROWCOUNT',
            'FID_SORTTYPE',
            'FID_EN_JYLB',
        ),
        self::TRANSACTION_LOG_TODAY => array(
            'FID_KHH',
            'FID_ROWCOUNT',
            'FID_BROWINDEX',
            'FID_FLAG',
        ),
        self::CANCEL_ENTRUST => array(
            'FID_KHH',
            'FID_GDH',
            'FID_JYS',
            'FID_WTH',
        ),
        self::STOCK_TO_BANK => array(
            'FID_KHH',
            'FID_ZJZH',
            'FID_BZ',
            'FID_YHZH',
            'FID_YHDM',
            'FID_ZZJE',
            'FID_ZJMM',
            'FID_JMLX',
        ),
        self::CERT_VERI => array(
            'UI_FID_ZSBFJG',
            'UI_FID_SIGNTYPE',
            'UI_FID_SIGNEDDATA',
            'UI_FID_ORIGINDATA',
        ),
        self::CERT_UPDATE => array(
            'UI_FID_ZSBFJG',
            'UI_FID_ZSDN',
            'UI_FID_ZSLX',
        ),
        self::CERT_DOWNLOAD => array(
            'UI_FID_ZSBFJG',
            'UI_FID_CKH',
            'UI_FID_AUTHCODE',
            'UI_FID_P10',
            'UI_FID_ZSLX',
        ),
        self::CERT_APPLY => array(
            'UI_FID_ZSBFJG',
            'UI_FID_NAME',
            'UI_FID_GJBH',
            'UI_FID_ZJLX',
            'UI_FID_ZJH',
            'UI_FID_ZSLX',
            'UI_FID_ZSSQFS',
        ),
        self::BANK_TO_STOCK => array(
            'FID_KHH',
            'FID_ZJZH',
            'FID_BZ',
            'FID_YHZH',
            'FID_YHDM',
            'FID_ZZJE',
            'FID_WBZHMM',
            'FID_JMLX',
        ),
        self::STOCKHOLDER_INFO => array(
            'FID_GDH',
            'FID_JYS',
        ),
        self::STOCKHOLDER_GDH => array(
            'FID_KHH',
        ),
        self::ENTRUST_HISTORY => array(
            'FID_KHH',
            'FID_KSRQ',
            'FID_JSRQ',
            'FID_BROWINDEX',
            'FID_ROWCOUNT',
            'FID_SORTTYPE',
        ),
        self::ENTRUST_TODAY => array(
            'FID_KHH',
            'FID_FLAG',
            'FID_SORTTYPE',
            'FID_ROWCOUNT',
            'FID_BROWINDEX',
        ),
        self::USER_POSITION => array(
            'FID_KHH',
            'FID_EXFLG',
        ),
        self::CERT_QUERY => array(
            'UI_FID_ZSBFJG',
            'UI_FID_NAME',
            'UI_FID_GJBH',
            'UI_FID_ZJLX',
            'UI_FID_ZJH',
        ),

        //以下是交易
        self::BANKCODEPARAM => [
            "FID_JGLB",
        ],
        self::STOCK_INFO => array(
            'FID_ZQDM',//证券代码
            'FID_JYS',
        ),
        self::ACCOUNT_INFO => array(
            'FID_KHH',
            'FID_EXFLG',
        ),
        self::STOCK_BUY_SALE => array(
            'FID_KHH',
            'FID_JYMM',
            'FID_JMLX',
            'FID_GDH',
            'FID_JYS',
            'FID_ZQDM',
            'FID_JYLB',
            'FID_WTSL',
            'FID_WTJG',
            'FID_DDLX',
        ),
        self::CURRENT_MARKET_DAY => array(
            'FID_JYS',
        ),
        self::NEXT_MARKET_DAY => array(
            'FID_RQ',
            'FID_JYS',
        ),
        self::PREV_MARKET_DAY => array(
            'FID_JYS',
            'FID_RQ',
        ),
        self::USER_BASE_INFO => array(
            'FID_KHH',
            'FID_EXFLG',
        ),
        self::DATA_DICTIONARY => array(
            'FID_FLDM',
        ),
        self::USER_RATION_EQUITY_INFO => array(
            'FID_KHH',
            'FID_ZQDM',
            'FID_JYS',
        ),
        self::ENTRUSTED_INFO => array(
            'FID_JYS',
            'FID_WTH',
            //'FID_SBWTH',
            //'FID_CXBZ',
        ),
    );/*}}}*/

    protected static $outFields = array(/*{{{*/
        self::TRANSACTION_LOG_TODAY_BATCH => array(
            'FID_BROWINDEX',
            'FID_JYS',
            'FID_GDH',
            'FID_WTH',
            'FID_SBWTH',
            'FID_CXBZ',
            'FID_ZQDM',
            'FID_ZQMC',
            'FID_WTLB',
            'FID_CJSL',
            'FID_CJJG',
            'FID_CJJE',
            'FID_QSJE',
            'FID_CJSJ',
            'FID_BZ',
            'FID_CJBH',
        ),
        self::STOCK_VERI_PWD => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_GDH',
            'FID_JYS',
            'FID_KHH',
            'FID_KHQZ',
            'FID_YYB',
            'FID_XTCSBZ',
            'FID_KHXM',
            'FID_KHZT',
            'FID_TZZFL',
        ),
        self::ENTRUST_CNT => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_WTSL',
        ),
        self::USER_BANK_INFO => array(
            'FID_YHZH',
            'FID_BZ',
            'FID_ZJZH',
            'FID_YHDM',
        ),
        self::MONEY_LOG_HISTORY => array(
            'FID_BROWINDEX',
            'FID_RQ',
            'FID_FSSJ',
            'FID_ZJZH',
            'FID_BZ',
            'FID_SRJE',
            'FID_FCJE',
            'FID_BCZJYE',
            'FID_ZY',
        ),
        self::MONEY_LOG_TODAY => array(
            'FID_BROWINDEX',
            'FID_LSH',
            'FID_RQ',
            'FID_FSSJ',
            'FID_ZJZH',
            'FID_YWKM',
            'FID_BZ',
            'FID_SRJE',
            'FID_FCJE',
            'FID_BCZJYE',
            'FID_ZY',
            'FID_FSJE',
        ),
        self::BANK_STOCK_MONEY_LOG_TODAY => array(
            'FID_SQH',
            'FID_ZJZH',
            'FID_JGDM',
            'FID_WBZH',
            'FID_BZ',
            'FID_YWLB',
            'FID_FSJE',
            'FID_BCZJYE',
            'FID_SQSJ',
            'FID_CLJG',
            'FID_JGSM',
            'FID_CXSQH',
        ),
        self::BANK_STOCK_MONEY_LOG_HISTORY => array(
            'FID_BROWINDEX',
            'FID_SQRQ',
            'FID_SQSJ',
            'FID_SQH',
            'FID_ZJZH',
            'FID_BZ',
            'FID_YHDM',
            'FID_YHZH',
            'FID_YWLB',
            'FID_FCJE',
            'FID_BCZJYE',
            'FID_CLJG',
            'FID_JGSM',
        ),
        self::TRANSACTION_LOG_HISTORY => array(
            'FID_BROWINDEX',
            'FID_CJRQ',
            'FID_JYS',
            'FID_GDH',
            'FID_BZ',
            'FID_WTH',
            'FID_WTLB',
            'FID_ZQDM',
            'FID_ZQMC',
            'FID_CJSJ',
            'FID_CJSL',
            'FID_CJJG',
            'FID_JSJ',
            'FID_CJJE',
            'FID_BZS1',
            'FID_S1',
            'FID_S2',
            'FID_S3',
            'FID_S4',
            'FID_S5',
            'FID_S6',
            'FID_S11',
            'FID_S12',
            'FID_S13',
            'FID_CJBH',
            'FID_CJBS',
            'FID_YSJE',
            'FID_BCZQSL',
            'FID_BCZJYE',
            'FID_JYSFY',
            'FID_JYFY',
            'FID_JSRQ',
            'FID_LXJE',
        ),
        self::TRANSACTION_LOG_TODAY => array(
            'FID_BROWINDEX',
            'FID_JYS',
            'FID_GDH',
            'FID_WTH',
            'FID_SBWTH',
            'FID_CXBZ',
            'FID_ZQDM',
            'FID_ZQMC',
            'FID_WTLB',
            'FID_CJSL',
            'FID_CJJG',
            'FID_CJJE',
            'FID_CJSJ',
        ),
        self::CANCEL_ENTRUST => array(
            'FID_CODE',
            'FID_MESSAGE',
        ),
        self::STOCK_TO_BANK => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_SQH',
        ),
        self::CERT_VERI => array(
            'UI_FID_CODE',
            'UI_FID_MESSAGE',
            'UI_FID_NOTE',
        ),
        self::CERT_UPDATE => array(
            'UI_FID_CODE',
            'UI_FID_MESSAGE',
            'UI_FID_NOTE',
            'UI_FID_CKH',
            'UI_FID_ZSSN',
            'UI_FID_AUTHCODE',
        ),
        self::CERT_DOWNLOAD => array(
            'UI_FID_CODE',
            'UI_FID_MESSAGE',
            'UI_FID_NOTE',
            'UI_FID_CERTDATA',
            'UI_FID_ZSDN',
            'UI_FID_STARTTIME',
            'UI_FID_ENDTIME',
        ),
        self::CERT_APPLY => array(
            'UI_FID_CODE',
            'UI_FID_MESSAGE',
            'UI_FID_NOTE',
            'UI_FID_CKH',
            'UI_FID_ZSSN',
            'UI_FID_AUTHCODE',
        ),
        self::BANK_TO_STOCK => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_SQH',
        ),
        self::STOCKHOLDER_INFO => array(
            'FID_KHH',
            'FID_KHXM',
            'FID_GDXM',
            'FID_BZ',
            'FID_ZZHBZ',
            'FID_JYQX',
        ),
        self::STOCKHOLDER_GDH => array(
            'FID_GDH',
            'FID_JYS',
            'FID_BZ',
            'FID_ZZHBZ',
            'FID_JYQX',
            'FID_JSJG',  //结算机构(实际返回的是存管银行的代码)
        ),
        self::ENTRUST_HISTORY => array(
            'FID_BROWINDEX',
            'FID_WTRQ',
            'FID_JYS',
            'FID_GDH',
            'FID_WTH',
            'FID_WTLB',
            'FID_CXBZ',
            'FID_ZQDM',
            'FID_ZQMC',
            'FID_DDLX',
            'FID_WTSL',
            'FID_WTJG',
            'FID_WTSJ',
            'FID_SBSJ',
            'FID_SBJG',
            'FID_JGSM',
            'FID_CDSL',
            'FID_CJSL',
            'FID_CJJE',
            'FID_CJJG',
            'FID_BZ',
            'FID_QSZJ',
            'FID_NODE',
            'FID_WTPCH',
        ),
        self::ENTRUST_TODAY => array(
            'FID_BROWINDEX',
            'FID_JYS',
            'FID_GDH',
            'FID_WTH',
            'FID_WTLB',
            'FID_CXBZ',
            'FID_ZQDM',
            'FID_ZQMC',
            'FID_DDLX',
            'FID_WTSL',
            'FID_WTJG',
            'FID_WTSJ',
            'FID_SBSJ',
            'FID_SBJG',
            'FID_JGSM',
            'FID_CDSL',
            'FID_CJSL',
            'FID_CJJE',
            'FID_CJJG',
            'FID_BZ',
            'FID_QSZJ',
            'FID_NODE',
            'FID_WTPCH',
        ),
        self::USER_POSITION => array(
            'FID_GDH',
            'FID_JYS',
            'FID_ZQDM',
            'FID_ZQMC',
            'FID_BZ',
            'FID_ZQSL',
            'FID_DRMCWTSL',
            'FID_DRMRCJSL',
            'FID_DRMCCJSL',
            'FID_KSHSL',
            'FID_KSGSL',
            'FID_KMCSL',//可卖出数量
            'FID_DJSL',
            'FID_FLTSL',
            'FID_JCCL',//当前持仓数量，包括了当天买入成交及卖出成交部分
            'FID_WJSSL',
            'FID_JGSL',
            'FID_KCRQ',
            'FID_ZXSZ',
            'FID_JYDW',
            'FID_ZXJ',//最新价
            'FID_LXBJ',
            'FID_MRJJ',
            'FID_CCJJ',//成本价(卖出不影响成本价的算法)
            'FID_BBJ',
            'FID_FDYK',//浮动盈亏（市价与保本价的计算结果）
            'FID_LJYK',
            'FID_TBCBJ',
            'FID_TBBBJ',
            'FID_TBFDYK',
        ),
        self::CERT_QUERY => array(
            'UI_FID_CODE',
            'UI_FID_MESSAGE',
            'UI_FID_NOTE',
            'UI_FID_NAME',
            'UI_FID_GJBH',
            'UI_FID_ZJLX',
            'UI_FID_ZJH',
            'UI_FID_ZSLX',
            'UI_FID_ZSSQFS',
            'UI_FID_EMAIL',
            'UI_FID_ZSDN',
            'UI_FID_STARTTIME',
            'UI_FID_ENDTIME',
            'UI_FID_ZSSN',
            'UI_FID_ZSZT',
        ),
        //以下是交易
        self::BANKCODEPARAM => [
            "FID_JGDM",
            "FID_JGLB",
            "FID_JGJC",
        ],
        self::STOCK_BUY_SALE => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_WTH',
        ),

        self::STOCK_INFO => array(/*{{{*/
            'FID_JYS',
            'FID_ZQMC',//证券名称
            'FID_BZ',
            'FID_JYDW',
            'FID_TPBZ',
            'FID_ZXJ',
            'FID_ZSP',
            'FID_JKP',
            'FID_JYJW',
            'FID_JJJYBZ',
            'FID_LXBJ',
            'FID_ZDBJ',
            'FID_ZGBJ',
            'FID_CJSL',
            'FID_CJJE',
            'FID_ZXZS',
            'FID_MRJG1',
            'FID_MRJG2',
            'FID_MRJG3',
            'FID_MRJG4',
            'FID_MRJG5',
            'FID_MCJG1',
            'FID_MCJG2',
            'FID_MCJG3',
            'FID_MCJG4',
            'FID_MCJG5',
            'FID_MRSL1',
            'FID_MRSL2',
            'FID_MRSL3',
            'FID_MRSL4',
            'FID_MRSL5',
            'FID_MCSL1',
            'FID_MCSL2',
            'FID_MCSL3',
            'FID_MCSL4',
            'FID_MCSL5',
            'FID_FXED',
            'FID_KYXYED',
            'FID_EDKZBZ',
            'FID_ZQLB',//证券类别
            'FID_JYJS',
        ),/*}}}*/

        self::ACCOUNT_INFO => array(/*{{{*/
            'FID_ZJZH',
            'FID_BZ',
            'FID_ZZHBZ',
            'FID_ZHLB',
            'FID_ZHZT',
            'FID_ZHYE',
            'FID_DJJE',
            'FID_KYZJ',//可用资金
            'FID_KQZJ',//可取资金 app显示为账户余额
            'FID_OFSS_JZ',
            'FID_ZXSZ',
            'FID_QTZC',
            'FID_ZZC',
            'FID_KYZJ2',
        ),/*}}}*/

        self::CURRENT_MARKET_DAY => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_JYRBS',
        ),
        self::NEXT_MARKET_DAY => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_JYRBS',
        ),
        self::PREV_MARKET_DAY => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_JYRBS',
        ),
        self::USER_BASE_INFO => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_FWXM',
            'FID_WTFS',
            'FID_ZJBH',
            'FID_YYB',
            'FID_KHQZ',
            'FID_KHKH',
            'FID_KHXM',
            'FID_KHZT',
            'FID_TZZFL',
            'FID_KHJF',
            'FID_DZ',
            'FID_DH',
            'FID_FAX',
            'FID_EMAIL',
            'FID_MOBILE',
        ),
        self::DATA_DICTIONARY => array(
            'FID_FLDM',
            'FID_BM',
            'FID_BMSM',
        ),
        self::USER_RATION_EQUITY_INFO => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_KHH',
            'FID_YYB',
            'FID_ZQDM',
            'FID_GDH',
            'FID_JYS',
            'FID_BZ',
            'FID_ZQMC',
            'FID_ZQSL',
            'FID_DZRQ',
        ),
        self::ENTRUSTED_INFO => array(
            'FID_CODE',
            'FID_MESSAGE',
            'FID_KHH',
            'FID_GDH',
            'FID_JYS',
            'FID_WTH',
            'FID_SBWTH',
            'FID_CXBZ',
            'FID_WTLB',
            'FID_ZQDM',
            'FID_ZQMC',
            'FID_DDLX',
            'FID_WTSL',
            'FID_WTJG',
            'FID_WTSJ',
            'FID_SBSJ',
            'FID_SBJG',
            'FID_JGSM',
            'FID_CDSL',
            'FID_CJJE',
            'FID_CJJG',
            'FID_BZ',
            'FID_QSZJ',
            'FID_NODE',
            'FID_WTPCH',
        ),
    );/*}}}*/

    public function getArgs()
    {
        return self::$details[$this->getValue()];
    }

    public function getName()
    {
        return self::getFuncName($this->getValue());
    }

    public static function getFuncName($funcCode)
    {
        return self::$names[$funcCode];
    }

    public function getOutFields()
    {
        return self::$outFields[$this->getValue()];
    }
}
