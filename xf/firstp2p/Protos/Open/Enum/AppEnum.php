<?php

/**
 * AppEnum.php
 *
 * Filename: AppEnum.php
 * Descrition: app相关常量定义
 * Author: yutao@ucfgroup.com
 * Date: 16-1-24 下午2:46
 */

namespace NCFGroup\Protos\Open\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class AppEnum extends AbstractEnum {

    //app status app状态
    const VERIFYING = 0;
    const REJECT = 2;
    const ONLINE = 1;
    const VERIFYING_DONE = 3;
    const NOSUBMIT = 4;

    public static $APP_STATUS = array(
        self::VERIFYING => '待审核',
        self::VERIFYING_DONE => '已审核',
        self::NOSUBMIT => '未提交',
        //self::ONLINE => '上线',
        //self::REJECT => '拒绝',
    );

    const PATTENR_HOSTED = 1;    //简单模式(云托管)
    const PATTERN_ADVANCED = 2;  //高级模式(API)

    const PLATFROM_APP = 1;     //app端
    const PLATFROM_WEB = 2;     //pc端
    const PLATFROM_WAP = 4;     //wap端

    const GUIDE_SUBMIT_AUDIT = 6; //提交审核了
}
