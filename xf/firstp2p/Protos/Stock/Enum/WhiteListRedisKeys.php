<?php
namespace NCFGroup\Protos\Stock\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class WhiteListRedisKeys extends AbstractEnum
{
    const WHITELISTBYCARD = 'WXLC_staff';
    const WHITELISTBYUSERID = 'WXLC_staff_userId';
    const WHITELISTBYUSERIDPREG = 'stock_allow_userid_preg';
    const WHITELISTBYMOBILE = 'WXLC_staff_mobile';
    const WHITELISTREPEATACCOUNT = 'WXLC_repeat_account';

    private static $_details = array(
        self::WHITELISTBYCARD => '根据身份证设白名单(WXLC_staff)',
        self::WHITELISTBYUSERID => '根据UserId设白名单(WXLC_staff_userId)',
        self::WHITELISTBYUSERIDPREG => '允许根据UserId正则表达式设白名单',
        self::WHITELISTBYMOBILE => '根据手机号设白名单(WXLC_staff_mobile)',
        self::WHITELISTREPEATACCOUNT => '可重复开户的用户Id白名单',
    );

    public static function getKeys()
    {
        return self::$_details;
    }

}
