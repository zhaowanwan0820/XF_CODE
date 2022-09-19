<?php
namespace NCFGroup\Protos\O2O\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class UserRelationEnum extends AbstractEnum {
    // 零售店用户类型
    const USER_TYPE_INVITER = 1;        // 邀请人
    const USER_TYPE_STORER = 2;         // 核销人
    // 人员在职状态
    const STATUS_ON = 1;                // 在职
    const STATUS_OFF = 0;               // 离职
    /**
     * 用户在职状态
     */
    public static $status = array(
        self::STATUS_ON => '在职',
        self::STATUS_OFF => '离职'
    );
}
