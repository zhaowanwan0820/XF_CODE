<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class AccountStage extends AbstractEnum
{
    const LOGIN = 'login';        //用户登录
    const UPLOADIMG = 'uploadimg';    //上传证件照片
    const IDCONFIRM = 'idconfirm';  //上传基本资料
    const WITNESS = 'witness';    //视频
    const CERTINTALL = 'certintall';  //安装证书
    const CAPITALACCT = 'capitalacct';   //签协议
    const STKACCT = 'stkacct';    //选择市场
    const SETPWD = 'setpwd';   //设置密码
    const TPBANK = 'tpbank';   //三方存管
    const RISKSURVEY = 'risksurvey';   //风险测评
    const VISITSURVEY = 'visitsurvey';    //问卷回访
    const VERIFYING = 'verifying';   //审核中
    const REBUT = 'rebut';   //驳回
    const VERIFIED = 'verified';     //审核通过
    const SUCCESS = 'success';     //开通完成

    private static $_details = array(
        self::LOGIN => '用户登录',
        self::UPLOADIMG => '上传证件照片',
        self::IDCONFIRM => '上传基本资料',
        self::WITNESS => '视频',
        self::CERTINTALL => '安装证书',
        self::CAPITALACCT => '签协议',
        self::STKACCT => '选择市场',
        self::SETPWD => '设置密码',
        self::TPBANK => '三方存管',
        self::RISKSURVEY => '风险测评',
        self::VISITSURVEY => '问卷回访',
        self::VERIFYING => '审核中',
        self::REBUT => '驳回',
        self::VERIFIED => '审核通过',
        self::SUCCESS => '开通完成',
    );

    public static function getMap()
    {
        return self::$_details;
    }

}