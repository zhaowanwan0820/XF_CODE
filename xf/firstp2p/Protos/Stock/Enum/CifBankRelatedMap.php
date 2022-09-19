<?php
namespace NCFGroup\Protos\Stock\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
use NCFGroup\Protos\Stock\Enum\CifContractIDs;
use NCFGroup\Protos\Stock\Enum\CifBankNeedPassword;
class CifBankRelatedMap extends AbstractEnum
{
    const JTYH = 'JTYH'; //交通银行
    const JSYH = 'JSYH'; //建设银行
    const NYYH = 'NYYH'; //农业银行
    const XYYH = 'XYYH'; //兴业银行
    const ZSYH = 'ZSYH'; //招商银行
    const GSYH = 'GSYH'; //工商银行
    const ZGYH = 'ZGYH'; //中国银行
    const GDYH = 'GDYH'; //光大银行
    const PAYH = 'PAYH'; //平安银行
    const PFYH = 'PFYH'; //浦发银行
    const ZXYH = 'ZXYH'; //中信银行
    const GFYH = 'GFYH'; //广发银行
    // 转账激活提示：开户成功后，需通过网银向证券账户转入1元人民币完成绑定
    //券商不支持一卡多开提示：请确认您所使用的银行卡没有在其他券商绑定
    //预指定提示：在收到确认短信后，请携带身份证和银行卡到营业网点办理三方存管签约

    protected static $details = array(
       self::JTYH => array(
            'bankName' => '交通银行',
            'contractId' => CifContractIDs::BOCOMDEPOSITORY,
            'p2pBankCode' => 'BOCOM',
            'cardMore' => '',//'支持'，
            'needPwd' => CifBankNeedPassword::NONEEDPWD,
            'firstTransfer' => '',
            'inNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            'outNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            //'' => '',
        ),//交通银行

        self::JSYH => array(
            'bankName' => '建设银行',
            'contractId' => CifContractIDs::CCBDEPOSITORY,
            'p2pBankCode' => 'CCB',
            'cardMore' => '',//'支持',
            'needPwd' =>  CifBankNeedPassword::NONEEDPWD,
            'firstTransfer' => '',
            'inNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            'outNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            //'' => '',
        ),//建设银行
        self::NYYH => array(
            'bankName' => '农业银行',
            'contractId' => CifContractIDs::ABCDEPOSITORY,
            'p2pBankCode' => 'ABC',
            'cardMore' => '',//'支持',
            'needPwd' => CifBankNeedPassword::NONEEDPWD, //使用密码
            'firstTransfer' => '',     //首次从银行发起转账
            'inNeedPwd' => CifBankNeedPassword::WITHDRAWALPWD,//转入银行-证券
            'outNeedPwd'=> CifBankNeedPassword::NONEEDPWD,//转出 证券-银行
            //'' => '',
        ),//农业银行
        self::XYYH => array(
            'bankName' => '兴业银行',
            'contractId' => CifContractIDs::CIBDEPOSITORY,
            'p2pBankCode' => 'CIB',
            'cardMore' => '',//'支持(20)',
            'needPwd' => CifBankNeedPassword::WITHDRAWALPWD,//CifBankNeedPassword::WITHDRAWALPWD,
            'firstTransfer' => '',
            'inNeedPwd' => CifBankNeedPassword::WITHDRAWALPWD,
            'outNeedPwd'=> CifBankNeedPassword::NONEEDPWD,
            //'' => '',
        ),//兴业银行
        self::ZSYH => array(
            'bankName' => '招商银行',
            'contractId' => CifContractIDs::CMBDEPOSITORY,
            'p2pBankCode' => 'CMB',
            'cardMore' => '',
            'needPwd' => CifBankNeedPassword::NONEEDPWD,//CifBankNeedPassword::WITHDRAWALPWD,
            'firstTransfer' => '开户成功后，需通过网银向证券账户转入1元人民币完成绑定',//'使用招商银行三方存管, 请您在成功开户后通过网银向证券账户转入1元人民币来激活三方存管。',
            'inNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            'outNeedPwd'=> CifBankNeedPassword::NONEEDPWD,
            //'' => '',
        ), //招商银行
        self::GSYH => array(
            'bankName' => '工商银行',
            'contractId' => CifContractIDs::ICBCDEPOSITORY,
            'p2pBankCode' => 'ICBC',
            'cardMore' => '',
            'needPwd' => CifBankNeedPassword::NONEEDPWD,
            'firstTransfer' => '开户成功后，需通过网银向证券账户转入1元人民币完成绑定',//'使用工商银行三方存管, 请您在成功开户后通过网银向证券账户转入1元人民币来激活三方存管。',
            'inNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            'outNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            //'' => '',
        ),//工商银行//预指定
        self::ZGYH => array(
            'bankName' => '中国银行',
            'contractId' => CifContractIDs::BOCDEPOSITORY,
            'p2pBankCode' => 'BOC',
            'cardMore' => '',//支持(9)',
            'needPwd' => CifBankNeedPassword::PHONEBANKPWD,
            'firstTransfer' => '',
            'inNeedPwd' => CifBankNeedPassword::PHONEBANKPWD,
            'outNeedPwd'=> CifBankNeedPassword::NONEEDPWD,
            //'' => '',
        ),//中国银行
        self::GDYH =>  array(
            'bankName' => '光大银行',
            'contractId' => CifContractIDs::GDDEPOSITORY,
            'p2pBankCode' => 'CEB',
            'cardMore' => '',//'支持',
            'needPwd' => CifBankNeedPassword::NONEEDPWD,
            'firstTransfer' => '',
            'inNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            'outNeedPwd'=> CifBankNeedPassword::NONEEDPWD,
            //'' => '',
        ),//光大银行
        self::PAYH =>  array(
           'bankName' => '平安银行',
           'contractId' => CifContractIDs::PAYHDEPOSITORY,
           'p2pBankCode' => 'PAB',
           'cardMore' => '',//'支持(99)',
           'needPwd' => CifBankNeedPassword::WITHDRAWALPWD,
           'firstTransfer' => '开户成功后，需通过网银向证券账户转入1元人民币完成绑定',//'使用平安银行三方存管, 请您在成功开户后通过网银向证券账户转入1元人民币来激活三方存管。',
           'inNeedPwd' => CifBankNeedPassword::WITHDRAWALPWD,
           'outNeedPwd'=> CifBankNeedPassword::NONEEDPWD,
           //'' => '',
        ),//平安银行
        self::PFYH => array(
           'bankName' => '浦发银行',
           'contractId' => CifContractIDs::PDFZDEPOSITORY,
           'p2pBankCode' => 'SPDB',
           'cardMore' => '',//'支持',
           'needPwd' => CifBankNeedPassword::WITHDRAWALPWD,
           'firstTransfer' => '开户成功后，需通过网银向证券账户转入1元人民币完成绑定',//'使用浦发银行三方存管, 请您在成功开户后通过网银向证券账户转入1元人民币来激活三方存管。',
           'inNeedPwd' => CifBankNeedPassword::NONEEDPWD,
           'outNeedPwd'=> CifBankNeedPassword::NONEEDPWD,
           //'' => '',
        ),//浦发银行
        self::ZXYH => array(
            'bankName' => '中信银行',
            'contractId' => CifContractIDs::ZXDEPOSITORY,
            'p2pBankCode' => 'CNCB',
            'cardMore' => '请确认您所使用的银行卡没有在其他券商绑定',//'根据银行存管业务规定，请确认您所使用的银行卡没有在其他券商设置过三方存管',
            'needPwd' => CifBankNeedPassword::WITHDRAWALPWD,
            'firstTransfer' => '',
            'inNeedPwd' => CifBankNeedPassword::NONEEDPWD,
            'outNeedPwd' => CifBankNeedPassword::NONEEDPWD,
        ),//中信银行
       self::GFYH => array(
           'bankName' => '广发银行',
           'contractId' =>CifContractIDs::GFDEPOSITORY,
           'p2pBankCode' => 'GDB',
           'cardMore' => '请确认您所使用的银行卡没有在其他券商绑定',//'根据银行存管业务规定，请确认您所使用的银行卡没有在其他券商设置过三方存管',
           'needPwd' => CifBankNeedPassword::NONEEDPWD,
           'firstTransfer' => '',
           'inNeedPwd' => CifBankNeedPassword::NONEEDPWD,
           'outNeedPwd' => CifBankNeedPassword::NONEEDPWD,
       ),//广发银行
    );
    public static function getMapInfo()
    {
        return self::$details;
    }
}
