<?php
namespace NCFGroup\Protos\Stock\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;
class CifBankNeedPassword extends AbstractEnum
{
    const NONEEDPWD = 0;//无需密码
    const WITHDRAWALPWD = 1; //取款密码
    const PHONEBANKPWD = 2; //电话银行密码
    protected static $details = array(
        self::NONEEDPWD => '无需密码',
        self::WITHDRAWALPWD => '取款密码',
        self::PHONEBANKPWD => '电话银行密码',
    );

   public static function getName()
    {
        return self::$details;
    }
}
