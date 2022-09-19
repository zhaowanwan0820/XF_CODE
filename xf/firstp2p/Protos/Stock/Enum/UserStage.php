<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserStage extends AbstractEnum
{
    const INIT = 'init';        // 初始化
    const IMAGE = 'image';    //影响采集
    const PROFILE = 'profile';  //基本信息
    const VIDEO = 'video';    //视频见证
    const CERT = 'certificate';  //证书申请成功
    const OPTION = 'accountOptions';   //开户协议与证券账户
    const PASSWD = 'passwords';    //密码设置
    const DEPOSIT = 'depository';   //存管指定
    const RISK = 'riskAssessment';   //风险评测
    const RISK_CONFIRM = 'riskConfirm';   //确认看过风险评测结果
    const QUESTION = 'questionnaire';    //回访问卷
    const COMPLETE = 'submissionComplete';   //申请完成
    const PROGRESS = 'reviewProgress';   //审核进度
    const TRAGE = 'startTrading';     //开始交易

    private static $_details = array(
        self::INIT => '初始化',
        self::IMAGE => '影像采集',
        self::PROFILE => '基本资料',
        self::VIDEO => '视频见证',
        self::CERT => '证书申请成功',
        self::OPTION => '开户协议与证券账户',
        self::PASSWD => '密码设置',
        self::DEPOSIT => '存管指定',
        self::RISK => '风险评测',
        self::RISK_CONFIRM => '确认看过风险测评结果',
        self::QUESTION => '回访问卷',
        self::COMPLETE => '申请完成',
        self::PROGRESS => '审核进度',
        self::TRAGE => '开始交易',
    );

    public static function getMap()
    {
        return self::$_details;
    }

}
